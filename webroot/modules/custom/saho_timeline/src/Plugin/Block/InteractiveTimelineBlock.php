<?php

namespace Drupal\saho_timeline\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\saho_timeline\Service\TimelineEventService;

/**
 * Provides an 'Interactive Timeline' block.
 *
 * @Block(
 *   id = "saho_interactive_timeline_block",
 *   admin_label = @Translation("SAHO Interactive Timeline"),
 *   category = @Translation("All custom")
 * )
 */
class InteractiveTimelineBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The timeline event service.
   *
   * @var \Drupal\saho_timeline\Service\TimelineEventService
   */
  protected $timelineEventService;

  /**
   * Constructs an InteractiveTimelineBlock object.
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
      'timeline_style' => 'modern',
      'show_search' => TRUE,
      'show_filters' => TRUE,
      'enable_zoom' => TRUE,
      'auto_scroll' => FALSE,
      'event_limit' => 50,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['timeline_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Timeline Style'),
      '#options' => [
        'modern' => $this->t('Modern Interactive'),
        'classic' => $this->t('Classic View'),
        'minimal' => $this->t('Minimal Style'),
      ],
      '#default_value' => $this->configuration['timeline_style'],
      '#description' => $this->t('Visual style for the interactive timeline.'),
    ];

    $form['show_search'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Search'),
      '#default_value' => $this->configuration['show_search'],
      '#description' => $this->t('Display search functionality.'),
    ];

    $form['show_filters'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Filters'),
      '#default_value' => $this->configuration['show_filters'],
      '#description' => $this->t('Display filter options.'),
    ];

    $form['enable_zoom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Zoom'),
      '#default_value' => $this->configuration['enable_zoom'],
      '#description' => $this->t('Allow users to zoom in/out of the timeline.'),
    ];

    $form['auto_scroll'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Scroll'),
      '#default_value' => $this->configuration['auto_scroll'],
      '#description' => $this->t('Automatically scroll through timeline events.'),
    ];

    $form['event_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Event Limit'),
      '#default_value' => $this->configuration['event_limit'],
      '#min' => 10,
      '#max' => 500,
      '#description' => $this->t('Maximum number of events to load initially.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['timeline_style'] = $form_state->getValue('timeline_style');
    $this->configuration['show_search'] = $form_state->getValue('show_search');
    $this->configuration['show_filters'] = $form_state->getValue('show_filters');
    $this->configuration['enable_zoom'] = $form_state->getValue('enable_zoom');
    $this->configuration['auto_scroll'] = $form_state->getValue('auto_scroll');
    $this->configuration['event_limit'] = $form_state->getValue('event_limit');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // Get events for interactive display.
    $events = $this->timelineEventService->getAllTimelineEvents();

    // Apply event limit.
    if ($config['event_limit'] && count($events) > $config['event_limit']) {
      $events = array_slice($events, 0, $config['event_limit'], TRUE);
    }

    $build = [
      '#theme' => 'saho_timeline_interactive',
      '#events' => $events,
      '#timeline_style' => $config['timeline_style'],
      '#show_search' => $config['show_search'],
      '#show_filters' => $config['show_filters'],
      '#enable_zoom' => $config['enable_zoom'],
      '#auto_scroll' => $config['auto_scroll'],
      '#attached' => [
        'library' => [
          'saho_timeline/timeline-interactive',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:event'],
        'contexts' => ['url.query_args'],
      ],
    ];

    return $build;
  }

}
