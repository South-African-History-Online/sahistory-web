<?php

namespace Drupal\saho_timeline\Controller;

use Drupal\node\NodeInterface;
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

    // Get events based on filters.
    $events = $this->timelineEventService->getEventsGroupedByPeriod('decade');

    // Build the render array for premium timeline only.
    $build = [
      '#theme' => 'saho_timeline_premium',
      '#events' => $events,
      '#filters' => $this->timelineFilterService->getAvailableFilters(),
      '#attached' => [
        'library' => [
          'saho_timeline/timeline-premium',
        ],
        'drupalSettings' => [
          'sahoTimeline' => [
            'apiEndpoint' => '/api/timeline/events',
            'activeFilters' => $filters,
            'version' => 'premium',
            'fallbackData' => $this->getFallbackTimelineData(),
          ],
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
   * Get fallback timeline data for when API fails.
   *
   * @return array
   *   Array of formatted timeline events.
   */
  protected function getFallbackTimelineData() {
    try {
      // Get a sample of events for fallback.
      $events = $this->timelineEventService->getAllTimelineEvents();

      // Limit to 100 events for fallback to avoid overwhelming the page.
      $sample_events = array_slice($events, 0, 100);

      $fallback_data = [];
      foreach ($sample_events as $event) {
        if ($event instanceof NodeInterface) {
          $fallback_data[] = [
            'id' => $event->id(),
            'title' => $event->label(),
            'date' => $this->getEventDate($event),
            'body' => $this->getEventBody($event),
            'url' => $event->toUrl()->toString(),
            'type' => $event->bundle(),
          ];
        }
      }

      return $fallback_data;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Get event date from various date fields.
   */
  protected function getEventDate($event) {
    $date_fields = [
      'field_event_date',
      'field_start_date',
      'field_end_date',
    ];

    foreach ($date_fields as $field_name) {
      if ($event->hasField($field_name) && !$event->get($field_name)->isEmpty()) {
        return $event->get($field_name)->value;
      }
    }

    return date('Y-m-d', $event->getCreatedTime());
  }

  /**
   * Get event body text.
   */
  protected function getEventBody($event) {
    if ($event->hasField('body') && !$event->get('body')->isEmpty()) {
      $body = strip_tags($event->get('body')->value);
      return substr($body, 0, 200) . '...';
    }
    return '';
  }

}
