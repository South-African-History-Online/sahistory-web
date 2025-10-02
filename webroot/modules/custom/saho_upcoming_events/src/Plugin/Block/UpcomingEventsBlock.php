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
      'block_title' => 'Upcoming Events',
      'excerpt_length' => 150,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['block_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block Title'),
      '#default_value' => $config['block_title'],
      '#description' => $this->t('The title to display for this block.'),
    ];

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
    $this->configuration['block_title'] = $form_state->getValue('block_title');
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
    // Use start of today to include events happening today.
    $today = new \DateTime('today', new \DateTimeZone('UTC'));
    $current_date = $today->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'upcomingevent')
      ->condition('status', 1)
      ->condition('field_start_date', $current_date, '>=')
      ->sort('field_start_date', 'ASC')
      ->range(0, $limit);

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    return $storage->loadMultiple($nids);
  }

}
