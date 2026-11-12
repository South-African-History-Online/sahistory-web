<?php

namespace Drupal\saho_upcoming_events\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
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
      // Collapse the whole block (including its Layout Builder label) rather
      // than shipping a visible empty band on the home page. Publishing an
      // event restores it, so carry the cache metadata even when empty.
      return [
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

    // Soonest first. Sorting DESC and walking from the far end handed the
    // block the furthest-away events whenever more than limit * 3 were
    // published. Pre-filter in SQL to events that start, or end, on
    // or after today so the ASC window is spent on candidates only; the
    // exact effective-end check still happens in PHP below. Dates are
    // stored as UTC strings, so compare against today's SAST midnight in
    // UTC with a day of slack - the PHP filter trims the overshoot.
    $cutoff = (clone $today)
      ->modify('-1 day')
      ->setTimezone(new \DateTimeZone('UTC'))
      ->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'upcomingevent')
      ->condition('status', 1)
      ->exists('field_start_date');
    $not_over = $query->orConditionGroup()
      ->condition('field_start_date', $cutoff, '>=')
      ->condition('field_end_date', $cutoff, '>=');
    $query->condition($not_over)
      ->sort('field_start_date', 'ASC')
      ->range(0, $limit * 3);

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    $nodes = $storage->loadMultiple($nids);

    // Same status rule as the listing and the event page.
    $upcoming_events = [];
    foreach ($nodes as $node) {
      $status = saho_upcoming_events_status($node, $today);
      if (!$status['start'] || $status['status'] === 'past') {
        continue;
      }
      $upcoming_events[] = [
        'node' => $node,
        'start_timestamp' => $status['start']->getTimestamp(),
        'is_happening_now' => $status['status'] === 'in-progress',
      ];
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

}
