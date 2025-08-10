<?php

namespace Drupal\saho_timeline\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\saho_timeline\Service\TimelineEventService;

/**
 * Provides a 'Timeline' block.
 *
 * @Block(
 *   id = "saho_timeline_block",
 *   admin_label = @Translation("SAHO Timeline"),
 *   category = @Translation("SAHO")
 * )
 */
class TimelineBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The timeline event service.
   *
   * @var \Drupal\saho_timeline\Service\TimelineEventService
   */
  protected $timelineEventService;

  /**
   * Constructs a TimelineBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\saho_timeline\Service\TimelineEventService $timeline_event_service
   *   The timeline event service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TimelineEventService $timeline_event_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->timelineEventService = $timeline_event_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('saho_timeline.event_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'display_mode' => 'default',
      'event_limit' => 10,
      'show_filters' => TRUE,
      'group_by' => 'decade',
      'date_range' => 'all',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display Mode'),
      '#options' => [
        'default' => $this->t('Default Timeline'),
        'compact' => $this->t('Compact View'),
        'expanded' => $this->t('Expanded View'),
        'horizontal' => $this->t('Horizontal Timeline'),
      ],
      '#default_value' => $this->configuration['display_mode'],
      '#description' => $this->t('Choose how the timeline should be displayed.'),
    ];

    $form['event_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Event Limit'),
      '#default_value' => $this->configuration['event_limit'],
      '#min' => 1,
      '#max' => 100,
      '#description' => $this->t('Maximum number of events to display.'),
    ];

    $form['show_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Filters'),
      '#default_value' => $this->configuration['show_filters'],
      '#description' => $this->t('Display filter options for users.'),
    ];

    $form['group_by'] = [
      '#type' => 'select',
      '#title' => $this->t('Group Events By'),
      '#options' => [
        'none' => $this->t('No Grouping'),
        'year' => $this->t('Year'),
        'decade' => $this->t('Decade'),
        'century' => $this->t('Century'),
      ],
      '#default_value' => $this->configuration['group_by'],
      '#description' => $this->t('How to group timeline events.'),
    ];

    $form['date_range'] = [
      '#type' => 'select',
      '#title' => $this->t('Date Range'),
      '#options' => [
        'all' => $this->t('All Time'),
        'century' => $this->t('Current Century'),
        'decade' => $this->t('Current Decade'),
        'year' => $this->t('Current Year'),
        'custom' => $this->t('Custom Range'),
      ],
      '#default_value' => $this->configuration['date_range'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['display_mode'] = $form_state->getValue('display_mode');
    $this->configuration['event_limit'] = $form_state->getValue('event_limit');
    $this->configuration['show_filters'] = $form_state->getValue('show_filters');
    $this->configuration['group_by'] = $form_state->getValue('group_by');
    $this->configuration['date_range'] = $form_state->getValue('date_range');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // Get events based on configuration.
    if ($config['group_by'] !== 'none') {
      $events = $this->timelineEventService->getEventsGroupedByPeriod($config['group_by']);
    }
    else {
      $events = $this->timelineEventService->getAllTimelineEvents();
    }

    // Apply event limit.
    if ($config['event_limit'] && !is_array(reset($events))) {
      $events = array_slice($events, 0, $config['event_limit'], TRUE);
    }

    $build = [
      '#theme' => 'saho_timeline',
      '#events' => $events,
      '#timeline_type' => $config['display_mode'],
      '#filters' => $config['show_filters'] ? $this->getFilterOptions() : [],
      '#attached' => [
        'library' => [
          'saho_timeline/timeline',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:event'],
        'contexts' => ['url.query_args'],
      ],
    ];

    return $build;
  }

  /**
   * Get filter options for the timeline.
   *
   * @return array
   *   Array of filter options.
   */
  protected function getFilterOptions() {
    return [
      'periods' => [
        'all' => $this->t('All Periods'),
        '1900-1950' => $this->t('1900-1950'),
        '1950-2000' => $this->t('1950-2000'),
        '2000-present' => $this->t('2000-Present'),
      ],
      'categories' => [
        'all' => $this->t('All Categories'),
        'political' => $this->t('Political'),
        'social' => $this->t('Social'),
        'cultural' => $this->t('Cultural'),
        'economic' => $this->t('Economic'),
      ],
    ];
  }

}
