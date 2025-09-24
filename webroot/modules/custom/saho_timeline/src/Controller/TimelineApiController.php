<?php

namespace Drupal\saho_timeline\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\saho_timeline\Service\TimelineEventService;
use Drupal\saho_timeline\Service\TimelineFilterService;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;
use Drupal\file\FileInterface;

/**
 * Controller for timeline API endpoints.
 */
class TimelineApiController extends ControllerBase {

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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a TimelineApiController object.
   *
   * @param \Drupal\saho_timeline\Service\TimelineEventService $timeline_event_service
   *   The timeline event service.
   * @param \Drupal\saho_timeline\Service\TimelineFilterService $timeline_filter_service
   *   The timeline filter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(TimelineEventService $timeline_event_service, TimelineFilterService $timeline_filter_service, RendererInterface $renderer) {
    $this->timelineEventService = $timeline_event_service;
    $this->timelineFilterService = $timeline_filter_service;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('saho_timeline.event_service'),
      $container->get('saho_timeline.filter_service'),
      $container->get('renderer')
    );
  }

  /**
   * Get timeline events via API with caching.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with events.
   */
  public function getEvents(Request $request) {
    // Create cache ID based on all query parameters.
    $query_params = $request->query->all();
    ksort($query_params);
    $cache_id = 'timeline_api:' . md5(serialize($query_params));

    // TEMPORARILY DISABLE API CACHE for debugging
    // Try to get from cache first (1 hour cache)
    $cache = \Drupal::cache();
    // $cache->get($cache_id);
    $cached = FALSE;
    if ($cached && $cached->valid) {
      // Add cache headers.
      $response = new JsonResponse($cached->data);
      $response->setMaxAge(3600);
      $response->headers->set('X-Cache', 'HIT');
      return $response;
    }

    // Validate and sanitize input parameters.
    $filters = $this->validateAndSanitizeFilters([
      'content_type' => $request->query->get('content_type'),
      'time_period' => $request->query->get('time_period'),
      'geographical_location' => $request->query->get('geographical_location'),
      'themes' => $request->query->get('themes'),
      'categories' => $request->query->get('categories'),
      'keywords' => $request->query->get('keywords'),
      'start_date' => $request->query->get('start_date'),
      'end_date' => $request->query->get('end_date'),
    ]);

    // Validate pagination parameters.
    $limit = $this->validateLimit($request->query->get('limit', 50));
    $offset = $this->validateOffset($request->query->get('offset', 0));

    // Validate sort parameter.
    $sort = $this->validateSort($request->query->get('sort', 'date_asc'));

    // Search for events.
    if (!empty($filters['keywords'])) {
      $events = $this->timelineEventService->searchEvents($filters['keywords'], $filters);
      // No dateless events for search results.
      $dateless_events = [];
    }
    elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
      $events = $this->timelineEventService->getEventsByDateRange(
        $filters['start_date'],
        $filters['end_date'],
        $filters
      );
      // No dateless events for date range queries.
      $dateless_events = [];
    }
    else {
      // Get all events segregated by date availability.
      $segregated = $this->timelineEventService->getAllTimelineEventsSegregated();
      // Events with proper historical dates.
      $events = $segregated['events'];
      // Events needing date review.
      $dateless_events = $segregated['dateless_events'];
    }

    // Apply sorting.
    $events = $this->sortEvents($events, $sort);

    // Calculate facets.
    $facets = $this->timelineFilterService->buildFacetCounts($events);

    // Apply intelligent sampling if we have too many events.
    $total = count($events);

    // For debugging: temporarily disable sampling to see if that's the issue
    // If we have more events than requested limit, do time-based sampling
    // to ensure good distribution across all time periods.
    if ($limit < $total && $total > 1000 && $limit < 3000) {
      // Only sample if requesting less than 3000 events.
      $events = $this->sampleEventsByTimePeriod($events, $limit);
    }
    else {
      // For requests of 3000+ events, return them all (up to the limit)
      // without sampling.
      $events = array_slice($events, $offset, $limit);
    }

    // Build response data.
    $response_data = [
      'events' => [],
      'dateless_events' => [],
      'facets' => $facets,
      'total' => $total,
      'limit' => $limit,
      'offset' => $offset,
    ];

    // Format events for JSON response with error handling.
    foreach ($events as $event) {
      try {
        $formatted_event = $this->formatEventForApi($event);
        // Test if the event can be JSON encoded.
        $test_json = json_encode($formatted_event);
        if ($test_json !== FALSE) {
          $response_data['events'][] = $formatted_event;
        }
        else {
        }
      }
      catch (\Exception $e) {
      }
    }

    // Add dateless events for admin review (if any exist)
    if (!empty($dateless_events)) {
      foreach ($dateless_events as $dateless_event) {
        try {
          $response_data['dateless_events'][] = [
            'id' => $dateless_event['id'],
            'title' => $dateless_event['title'],
            'created' => date('Y-m-d H:i:s', $dateless_event['created']),
            'status' => $dateless_event['status'],
            'reason' => $dateless_event['reason'],
            'edit_url' => '/node/' . $dateless_event['id'] . '/edit',
          ];
        }
        catch (\Exception $e) {
        }
      }
    }

    // If requested, return HTML instead of JSON.
    if ($request->query->get('format') === 'html') {
      $html = $this->renderEventsAsHtml($events);
      $response_data['html'] = $html;
    }

    // TEMPORARILY DISABLE API CACHE for debugging
    // Cache the response for 1 hour.
    // $cache->set($cache_id, $response_data, time() + 3600,
    // ['node_list:event']);
    // Create response with cache headers.
    $response = new JsonResponse($response_data);
    $response->setMaxAge(3600);
    $response->headers->set('X-Cache', 'MISS');
    return $response;
  }

  /**
   * Sort events array.
   *
   * @param array $events
   *   Array of events to sort.
   * @param string $sort
   *   Sort parameter.
   *
   * @return array
   *   Sorted events array.
   */
  protected function sortEvents(array $events, $sort) {
    switch ($sort) {
      case 'date_desc':
        usort($events, function ($a, $b) {
          $date_a = $this->getEventDateForSort($a);
          $date_b = $this->getEventDateForSort($b);
          return strcmp($date_b, $date_a);
        });
        break;

      case 'title':
        usort($events, function ($a, $b) {
          $title_a = $this->getEventTitle($a);
          $title_b = $this->getEventTitle($b);
          return strcmp($title_a, $title_b);
        });
        break;

      case 'relevance':
        // Already sorted by relevance if searching.
        break;

      case 'date_asc':
      default:
        usort($events, function ($a, $b) {
          $date_a = $this->getEventDateForSort($a);
          $date_b = $this->getEventDateForSort($b);
          return strcmp($date_a, $date_b);
        });
        break;
    }

    return $events;
  }

  /**
   * Get event date for sorting.
   *
   * @param mixed $event
   *   The event entity or object.
   *
   * @return string
   *   The event date.
   */
  protected function getEventDateForSort($event) {
    if ($event instanceof ContentEntityInterface) {
      // Check multiple date fields in order of preference.
      $date_fields = [
      // Primary TDIH field.
        'field_this_day_in_history_3',
      // Secondary TDIH field (likely newer events)
        'field_this_day_in_history_date_2',
      // Event start date.
        'field_start_date',
      // Event end date.
        'field_end_date',
      // Birth dates.
        'field_drupal_birth_date',
      // Death dates.
        'field_drupal_death_date',
      // Generic event date.
        'field_event_date',
      ];

      foreach ($date_fields as $field_name) {
        if ($event->hasField($field_name) && !$event->get($field_name)->isEmpty()) {
          return $event->get($field_name)->value;
        }
      }
    }
    elseif (is_object($event) && isset($event->date)) {
      return $event->date;
    }

    return '';
  }

  /**
   * Get event title.
   *
   * @param mixed $event
   *   The event entity or object.
   *
   * @return string
   *   The event title.
   */
  protected function getEventTitle($event) {
    if (method_exists($event, 'label')) {
      return $event->label();
    }
    elseif (is_object($event) && isset($event->title)) {
      return $event->title;
    }

    return '';
  }

  /**
   * Format event for API response.
   *
   * @param mixed $event
   *   The event entity or object.
   *
   * @return array
   *   Formatted event data.
   */
  protected function formatEventForApi($event) {
    $data = [
      'id' => NULL,
      'title' => '',
      'date' => '',
      'body' => '',
      'url' => '',
      'type' => 'event',
      'image' => NULL,
      'location' => NULL,
      'themes' => [],
      'categories' => [],
      'field_ref_str' => NULL,
    ];

    if ($event instanceof ContentEntityInterface) {
      $data['id'] = $event->id();
      $title = $event->label();
      // More robust UTF-8 cleaning.
      $title = @iconv('UTF-8', 'UTF-8//IGNORE', $title);
      if ($title === FALSE) {
        $title = mb_convert_encoding($event->label(), 'UTF-8', 'UTF-8');
      }
      // Remove any remaining invalid characters.
      $title = htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8', FALSE);
      $title = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $title);
      $data['title'] = trim(strip_tags($title));
      $data['url'] = $event->toUrl('canonical', ['absolute' => TRUE])->toString();
      $data['type'] = $event->bundle();

      // Get date from multiple possible fields.
      $date_fields = [
        'field_this_day_in_history_3',
        'field_this_day_in_history_date_2',
        'field_event_date',
      ];

      foreach ($date_fields as $field_name) {
        if ($event->hasField($field_name) && !$event->get($field_name)->isEmpty()) {
          $date_value = $event->get($field_name)->value;
          if (!empty($date_value)) {
            $data['date'] = $date_value;
            break;
          }
        }
      }

      // Fallback to creation date if no specific date field.
      if (empty($data['date']) && $event instanceof NodeInterface) {
        $data['date'] = date('Y-m-d', $event->getCreatedTime());
      }

      // Get body with robust UTF-8 cleaning.
      if ($event->hasField('body') && !$event->get('body')->isEmpty()) {
        $body = $event->get('body')->value;
        // More robust UTF-8 cleaning for body text.
        $body = @iconv('UTF-8', 'UTF-8//IGNORE', $body);
        if ($body === FALSE) {
          $body = mb_convert_encoding($event->get('body')->value, 'UTF-8', 'UTF-8');
        }
        // Strip HTML tags first to avoid breaking UTF-8 sequences.
        $body = strip_tags($body);
        // Remove invalid characters using modern approach.
        $body = htmlspecialchars($body, ENT_QUOTES | ENT_HTML5, 'UTF-8', FALSE);
        $body = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $body);
        $body = trim(strip_tags($body));
        $data['body'] = !empty($body) ? substr($body, 0, 300) . '...' : '';
      }

      // Get image from multiple possible fields.
      $image_fields = ['field_tdih_image', 'field_event_image', 'field_image'];
      foreach ($image_fields as $field_name) {
        if ($event->hasField($field_name) && !$event->get($field_name)->isEmpty()) {
          $image = $event->get($field_name)->entity;
          if ($image && $image instanceof FileInterface) {
            $file_url_generator = \Drupal::service('file_url_generator');
            $data['image'] = $file_url_generator->generateAbsoluteString($image->getFileUri());
            // Use the first image found.
            break;
          }
        }
      }

      // Get location.
      if ($event->hasField('field_location') && !$event->get('field_location')->isEmpty()) {
        $locations = [];
        foreach ($event->get('field_location') as $location) {
          if (isset($location->entity) && $location->entity) {
            $locations[] = $location->entity->label();
          }
        }
        $data['location'] = implode(', ', $locations);
      }

      // Get themes.
      if ($event->hasField('field_themes') && !$event->get('field_themes')->isEmpty()) {
        foreach ($event->get('field_themes') as $theme) {
          if (isset($theme->entity) && $theme->entity) {
            $data['themes'][] = $theme->entity->label();
          }
        }
      }

      // Get categories.
      if ($event->hasField('field_tags') && !$event->get('field_tags')->isEmpty()) {
        foreach ($event->get('field_tags') as $tag) {
          if (isset($tag->entity) && $tag->entity) {
            $data['categories'][] = $tag->entity->label();
          }
        }
      }

      // Get field_ref_str (reference/citation string).
      if ($event->hasField('field_ref_str') && !$event->get('field_ref_str')->isEmpty()) {
        $ref_str = $event->get('field_ref_str')->value;
        if (!empty($ref_str)) {
          // Clean the reference string similar to other text fields.
          $ref_str = @iconv('UTF-8', 'UTF-8//IGNORE', $ref_str);
          if ($ref_str === FALSE) {
            $ref_str = mb_convert_encoding($event->get('field_ref_str')->value, 'UTF-8', 'UTF-8');
          }
          $ref_str = trim(strip_tags($ref_str));
          $data['field_ref_str'] = $ref_str;
        }
      }
    }
    elseif (is_object($event)) {
      // Handle parsed HTML timeline events.
      $data['title'] = $event->title ?? '';
      $data['date'] = $event->date ?? '';
      $data['body'] = $event->body ?? '';
      $data['type'] = 'timeline_html';
    }

    return $data;
  }

  /**
   * Sample events by time period to ensure good distribution.
   *
   * @param array $events
   *   Array of events to sample.
   * @param int $target_count
   *   Target number of events to return.
   *
   * @return array
   *   Sampled events with good time distribution.
   */
  protected function sampleEventsByTimePeriod(array $events, int $target_count) {
    if (count($events) <= $target_count) {
      return $events;
    }

    // Group events by decade for balanced sampling.
    $decades = [];
    foreach ($events as $event) {
      $date = $this->getEventDateForSort($event);
      if (empty($date)) {
        continue;
      }

      $year = (int) substr($date, 0, 4);
      $decade = floor($year / 10) * 10;

      if (!isset($decades[$decade])) {
        $decades[$decade] = [];
      }
      $decades[$decade][] = $event;
    }

    // Sort decades chronologically.
    ksort($decades);

    // Calculate events per decade based on target.
    $decade_count = count($decades);
    $base_per_decade = max(1, floor($target_count / $decade_count));
    $remainder = $target_count - ($base_per_decade * $decade_count);

    $sampled = [];
    $decades_keys = array_keys($decades);

    foreach ($decades_keys as $i => $decade) {
      $decade_events = $decades[$decade];

      // Recent decades get extra events from remainder.
      $events_for_decade = $base_per_decade;
      if ($remainder > 0 && $i >= ($decade_count - $remainder)) {
        $events_for_decade++;
      }

      // Sample events from this decade.
      if (count($decade_events) <= $events_for_decade) {
        $sampled = array_merge($sampled, $decade_events);
      }
      else {
        // Take evenly spaced events from this decade.
        $step = count($decade_events) / $events_for_decade;
        for ($j = 0; $j < $events_for_decade; $j++) {
          $index = floor($j * $step);
          if (isset($decade_events[$index])) {
            $sampled[] = $decade_events[$index];
          }
        }
      }
    }

    return $sampled;
  }

  /**
   * Render events as HTML.
   *
   * @param array $events
   *   Array of events.
   *
   * @return string
   *   Rendered HTML.
   */
  protected function renderEventsAsHtml(array $events) {
    $build = [
      '#theme' => 'saho_timeline',
      '#events' => $events,
      '#timeline_type' => 'default',
    ];

    return $this->renderer->renderRoot($build);
  }

  /**
   * Validate and sanitize filter parameters.
   *
   * @param array $filters
   *   Raw filter parameters.
   *
   * @return array
   *   Validated and sanitized filters.
   */
  protected function validateAndSanitizeFilters(array $filters) {
    $sanitized = [];

    // Allowed content types.
    $allowed_content_types = ['all', 'event', 'article', 'biography', 'topic', 'place', 'archive'];
    if (!empty($filters['content_type']) && in_array($filters['content_type'], $allowed_content_types)) {
      $sanitized['content_type'] = $filters['content_type'];
    }

    // Allowed time periods.
    $allowed_periods = [
      'all',
      'pre-1500',
      '1500-1600',
      '1600-1700',
      '1700-1800',
      '1800-1900',
      '1900-1950',
      '1950-1990',
      '1990-2000',
      '2000-present',
    ];
    if (!empty($filters['time_period']) && in_array($filters['time_period'], $allowed_periods)) {
      $sanitized['time_period'] = $filters['time_period'];
    }

    // Validate dates.
    if (!empty($filters['start_date']) && $this->isValidDate($filters['start_date'])) {
      $sanitized['start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date']) && $this->isValidDate($filters['end_date'])) {
      $sanitized['end_date'] = $filters['end_date'];
    }

    // Sanitize keywords.
    if (!empty($filters['keywords'])) {
      $keywords = trim(strip_tags($filters['keywords']));
      if (strlen($keywords) >= 2 && strlen($keywords) <= 200) {
        $sanitized['keywords'] = $keywords;
      }
    }

    // Validate arrays (geographical_location, themes, categories).
    foreach (['geographical_location', 'themes', 'categories'] as $array_field) {
      if (!empty($filters[$array_field])) {
        if (is_array($filters[$array_field])) {
          $sanitized[$array_field] = array_map('strip_tags', $filters[$array_field]);
        }
        elseif (is_string($filters[$array_field])) {
          $sanitized[$array_field] = [strip_tags($filters[$array_field])];
        }
      }
    }

    return $sanitized;
  }

  /**
   * Validate limit parameter.
   *
   * @param mixed $limit
   *   The limit parameter.
   *
   * @return int
   *   Validated limit.
   */
  protected function validateLimit($limit) {
    $limit = (int) $limit;
    // Allow up to 5000 events for rich timeline.
    if ($limit < 1 || $limit > 5000) {
      // Default to 2000 events instead of 50.
      return 2000;
    }
    return $limit;
  }

  /**
   * Validate offset parameter.
   *
   * @param mixed $offset
   *   The offset parameter.
   *
   * @return int
   *   Validated offset.
   */
  protected function validateOffset($offset) {
    $offset = (int) $offset;
    if ($offset < 0 || $offset > 10000) {
      // Default offset.
      return 0;
    }
    return $offset;
  }

  /**
   * Validate sort parameter.
   *
   * @param mixed $sort
   *   The sort parameter.
   *
   * @return string
   *   Validated sort option.
   */
  protected function validateSort($sort) {
    $allowed_sorts = ['date_asc', 'date_desc', 'title', 'relevance'];
    if (!in_array($sort, $allowed_sorts)) {
      // Default sort.
      return 'date_asc';
    }
    return $sort;
  }

  /**
   * Check if a date string is valid.
   *
   * @param string $date
   *   The date string to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  protected function isValidDate($date) {
    if (!is_string($date)) {
      return FALSE;
    }

    // Check for YYYY-MM-DD format.
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      $parts = explode('-', $date);
      return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    return FALSE;
  }

}
