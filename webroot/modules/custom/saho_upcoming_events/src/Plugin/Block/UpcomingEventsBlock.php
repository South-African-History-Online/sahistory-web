<?php

namespace Drupal\saho_upcoming_events\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Upcoming Events' block.
 *
 * @Block(
 *   id = "saho_upcoming_events_block",
 *   admin_label = @Translation("SAHO Upcoming Events"),
 *   category = @Translation("All custom"),
 *   context_definitions = {
 *   }
 * )
 */
class UpcomingEventsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UpcomingEventsBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'number_of_events' => 4,
      'show_images' => TRUE,
      'show_venue' => TRUE,
      'show_excerpt' => TRUE,
      'show_view_all_link' => TRUE,
      'excerpt_length' => 150,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Return default label - can be overridden when placing block.
    $config = $this->getConfiguration();
    // For backward compatibility with existing blocks.
    if (!empty($config['block_title'])) {
      return $config['block_title'];
    }
    return $this->t('Upcoming Events');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    // Note: Block title is now configured via the standard Drupal block title
    // field when placing the block. Use "Display title" checkbox to show/hide.
    $form['number_of_events'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of events to display'),
      '#default_value' => $config['number_of_events'],
      '#min' => 1,
      '#max' => 20,
      '#description' => $this->t('How many upcoming events to display.'),
    ];

    $form['display_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display Options'),
    ];

    $form['display_options']['show_images'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show event images'),
      '#default_value' => $config['show_images'],
    ];

    $form['display_options']['show_venue'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show event venue'),
      '#default_value' => $config['show_venue'],
    ];

    $form['display_options']['show_excerpt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show event excerpt'),
      '#default_value' => $config['show_excerpt'],
    ];

    $form['display_options']['excerpt_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Excerpt length (characters)'),
      '#default_value' => $config['excerpt_length'],
      '#min' => 50,
      '#max' => 500,
      '#states' => [
        'visible' => [
          ':input[name="settings[display_options][show_excerpt]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['display_options']['show_view_all_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show "View All Events" link'),
      '#default_value' => $config['show_view_all_link'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['number_of_events'] = $form_state->getValue('number_of_events');
    $this->configuration['show_images'] = $form_state->getValue(['display_options', 'show_images']);
    $this->configuration['show_venue'] = $form_state->getValue(['display_options', 'show_venue']);
    $this->configuration['show_excerpt'] = $form_state->getValue(['display_options', 'show_excerpt']);
    $this->configuration['excerpt_length'] = $form_state->getValue(['display_options', 'excerpt_length']);
    $this->configuration['show_view_all_link'] = $form_state->getValue(['display_options', 'show_view_all_link']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $events = $this->getUpcomingEvents($config['number_of_events']);

    if (empty($events)) {
      return [
        '#type' => 'markup',
        '#markup' => '<div class="upcoming-events-empty">' . $this->t('No upcoming events at this time.') . '</div>',
        '#cache' => [
          'tags' => ['node_list:upcomingevent'],
          'max-age' => 3600,
        ],
      ];
    }

    $build = [
      '#theme' => 'saho_upcoming_events_block',
      '#events' => $events,
      '#config' => $config,
      '#attached' => [
        'library' => ['saho_upcoming_events/upcoming_events'],
      ],
      '#cache' => [
        'tags' => ['node_list:upcomingevent'],
        'max-age' => 3600,
      ],
    ];

    return $build;
  }

  /**
   * Get upcoming events.
   *
   * @param int $limit
   *   The number of events to retrieve.
   *
   * @return array
   *   Array of upcoming event nodes.
   */
  private function getUpcomingEvents($limit = 4) {
    $storage = $this->entityTypeManager->getStorage('node');
    // Use SAST timezone for consistency.
    $today = new \DateTime('today', new \DateTimeZone('Africa/Johannesburg'));

    // Query more events than needed to account for filtering.
    // Sort DESC to get newest events first, then filter in PHP.
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'upcomingevent')
      ->condition('status', 1)
      ->exists('field_start_date')
      ->sort('field_start_date', 'DESC')
      ->range(0, $limit * 3);

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    $nodes = $storage->loadMultiple($nids);

    // Filter in PHP to include "happening now" events.
    $upcoming_events = [];
    foreach ($nodes as $node) {
      if (!$node->hasField('field_start_date') || $node->get('field_start_date')->isEmpty()) {
        continue;
      }

      // Calculate effective end date.
      $effective_end_date = $this->getEffectiveEndDate($node);
      // Skip events with invalid/missing dates.
      if ($effective_end_date === NULL) {
        continue;
      }

      $effective_end_date_only = new \DateTime($effective_end_date->format('Y-m-d'), new \DateTimeZone('Africa/Johannesburg'));

      // Include if event hasn't ended yet.
      if ($effective_end_date_only >= $today) {
        // @phpstan-ignore-next-line
        $start_date = $node->get('field_start_date')->date;
        $upcoming_events[] = [
          'node' => $node,
          'start_timestamp' => $start_date->getTimestamp(),
          'is_happening_now' => $this->isHappeningNow($node, $today),
        ];
      }

      if (count($upcoming_events) >= $limit) {
        break;
      }
    }

    // Sort: "happening now" first, then by start date.
    usort($upcoming_events, function ($a, $b) {
      if ($a['is_happening_now'] && !$b['is_happening_now']) {
        return -1;
      }
      if (!$a['is_happening_now'] && $b['is_happening_now']) {
        return 1;
      }
      return $a['start_timestamp'] <=> $b['start_timestamp'];
    });

    return array_column($upcoming_events, 'node');
  }

  /**
   * Get the effective end date for an event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The effective end date (end_date if set, otherwise start_date),
   *   or NULL if no valid date exists.
   */
  private function getEffectiveEndDate($node) {
    $start_date = NULL;
    if ($node->hasField('field_start_date') && !$node->get('field_start_date')->isEmpty()) {
      // @phpstan-ignore-next-line
      $start_date = $node->get('field_start_date')->date;
    }

    $end_date = NULL;
    if ($node->hasField('field_end_date') && !$node->get('field_end_date')->isEmpty()) {
      // @phpstan-ignore-next-line
      $end_date = $node->get('field_end_date')->date;
    }

    $effective_end_date = $end_date ?? $start_date;

    // Return NULL if no valid date exists (corrupted/incomplete event data).
    if ($effective_end_date === NULL) {
      return NULL;
    }

    $effective_end_date->setTimezone(new \DateTimeZone('Africa/Johannesburg'));
    return $effective_end_date;
  }

  /**
   * Check if an event is happening now.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The event node.
   * @param \DateTime $current_date
   *   The current date to compare against.
   *
   * @return bool
   *   TRUE if the event is happening now.
   */
  private function isHappeningNow($node, $current_date) {
    // @phpstan-ignore-next-line
    $start_date = $node->get('field_start_date')->date;
    if ($start_date === NULL) {
      return FALSE;
    }

    $start_date->setTimezone(new \DateTimeZone('Africa/Johannesburg'));
    $start_date_only = new \DateTime($start_date->format('Y-m-d'), new \DateTimeZone('Africa/Johannesburg'));

    $effective_end_date = $this->getEffectiveEndDate($node);
    // Events without valid dates cannot be "happening now".
    if ($effective_end_date === NULL) {
      return FALSE;
    }

    $effective_end_date_only = new \DateTime($effective_end_date->format('Y-m-d'), new \DateTimeZone('Africa/Johannesburg'));

    return ($start_date_only <= $current_date && $effective_end_date_only >= $current_date);
  }

}
