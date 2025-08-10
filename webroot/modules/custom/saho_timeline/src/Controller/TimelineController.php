<?php

namespace Drupal\saho_timeline\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\saho_timeline\Service\TimelineEventService;
use Drupal\saho_timeline\Service\TimelineFilterService;

/**
 * Controller for timeline display pages.
 */
class TimelineController extends ControllerBase {

  /**
   * The timeline event service.
   *
   * @var \Drupal\saho_timeline\Service\TimelineEventService
   */
  protected $timelineEventService;

  /**
   * The timeline filter service.
   *
   * @var \Drupal\saho_timeline\Service\TimelineFilterService
   */
  protected $timelineFilterService;

  /**
   * Constructs a TimelineController object.
   *
   * @param \Drupal\saho_timeline\Service\TimelineEventService $timeline_event_service
   *   The timeline event service.
   * @param \Drupal\saho_timeline\Service\TimelineFilterService $timeline_filter_service
   *   The timeline filter service.
   */
  public function __construct(TimelineEventService $timeline_event_service, TimelineFilterService $timeline_filter_service) {
    $this->timelineEventService = $timeline_event_service;
    $this->timelineFilterService = $timeline_filter_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_timeline.event_service'),
      $container->get('saho_timeline.filter_service')
    );
  }

  /**
   * Display the main timeline page.
   *
   * @return array
   *   Render array for the timeline page.
   */
  public function display() {
    // Get active filters from request.
    $filters = $this->timelineFilterService->getActiveFilters();
    
    // Get timeline version from query parameter (default to premium).
    $request = \Drupal::request();
    $version = $request->query->get('version', 'premium');
    
    // Validate version parameter.
    $allowed_versions = ['premium', 'interactive', 'simple'];
    if (!in_array($version, $allowed_versions)) {
      $version = 'premium';
    }
    
    // Get events based on filters.
    $events = $this->timelineEventService->getEventsGroupedByPeriod('decade');
    
    // Build the render array based on selected version.
    $build = [
      '#theme' => 'saho_timeline_' . $version,
      '#events' => $events,
      '#filters' => $this->timelineFilterService->getAvailableFilters(),
      '#attached' => [
        'library' => [
          'saho_timeline/timeline',
          'saho_timeline/timeline-' . $version,
        ],
        'drupalSettings' => [
          'sahoTimeline' => [
            'apiEndpoint' => '/api/timeline/events',
            'activeFilters' => $filters,
            'minYear' => 1000,
            'maxYear' => 2025,
            'version' => $version,
          ],
        ],
      ],
      '#cache' => [
        'tags' => ['node_list:event'],
        'contexts' => ['url.query_args'],
      ],
    ];
    
    // Add all timeline libraries for version switching.
    $build['#attached']['library'][] = 'saho_timeline/timeline-simple';
    $build['#attached']['library'][] = 'saho_timeline/timeline-interactive';
    $build['#attached']['library'][] = 'saho_timeline/timeline-premium-lib';
    $build['#attached']['library'][] = 'saho_timeline/timeline-premium';
    
    return $build;
  }
}