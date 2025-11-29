<?php

namespace Drupal\saho_timeline\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\node\NodeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service for managing timeline events.
 */
class TimelineEventService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;


  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a TimelineEventService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TimeInterface $time, Connection $database, CacheBackendInterface $cache, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
    $this->database = $database;
    $this->cache = $cache;
    $this->configFactory = $config_factory;
  }

  /**
   * Get timeline events within a date range.
   *
   * @param string $start_date
   *   Start date in YYYY-MM-DD format.
   * @param string $end_date
   *   End date in YYYY-MM-DD format.
   * @param array $filters
   *   Additional filters (categories, tags, etc.).
   *
   * @return array
   *   Array of event nodes.
   */
  public function getEventsByDateRange($start_date, $end_date, array $filters = []) {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'event')
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(TRUE);

    // Add date range conditions if we have date fields.
    if ($start_date && $end_date) {
      $query->condition('field_event_date', $start_date, '>=');
      $query->condition('field_event_date', $end_date, '<=');
    }

    // Apply additional filters.
    foreach ($filters as $field => $value) {
      if (!empty($value)) {
        $query->condition($field, $value);
      }
    }

    // Sort by date.
    $query->sort('field_event_date', 'ASC');

    $nids = $query->execute();
    return $storage->loadMultiple($nids);
  }

  /**
   * Get events grouped by period.
   *
   * @param string $period_type
   *   Type of period (decade, century, year).
   *
   * @return array
   *   Events grouped by period.
   */
  public function getEventsGroupedByPeriod($period_type = 'decade') {
    $events = $this->getAllTimelineEvents();
    $grouped = [];

    foreach ($events as $event) {
      $period = $this->calculatePeriod($event, $period_type);
      if (!isset($grouped[$period])) {
        $grouped[$period] = [];
      }
      $grouped[$period][] = $event;
    }

    ksort($grouped);
    return $grouped;
  }

  /**
   * Get all timeline events including TDIH events.
   *
   * @param bool $include_tdih
   *   Whether to include TDIH events.
   * @param bool $use_cache
   *   Whether to use caching.
   *
   * @return array
   *   Array of all published event nodes.
   */
  public function getAllTimelineEvents($include_tdih = TRUE, $use_cache = TRUE) {
    $cache_id = 'saho_timeline:all_events:' . ($include_tdih ? 'with_tdih' : 'no_tdih');

    // TEMPORARILY DISABLE SERVICE CACHE for debugging
    // Try to get from cache first.
    if (FALSE && $use_cache && $cached = $this->cache->get($cache_id)) {
      return $cached->data;
    }

    try {
      // Temporarily use a simpler approach to debug the issue.
      $storage = $this->entityTypeManager->getStorage('node');
      $query = $storage->getQuery()
        ->condition('type', 'event')
        ->condition('status', NodeInterface::PUBLISHED)
        ->accessCheck(TRUE);

      // Get ALL event nodes - we'll separate them into dated vs dateless later
      // This ensures we include all events but handle them appropriately.
      // For now, let's get ALL events and separate them by date availability
      // No date filtering in query - we'll handle this in PHP.
      // Get a larger sample with better time distribution.
      $config = $this->configFactory->get('saho_timeline.settings');
      $max_results = $config->get('max_api_results') ?: 10000;

      // Don't limit here - we'll sample later
      // $query->range(0, $max_results);.
      // Don't sort in database query - let API handle date sorting
      // This ensures we get events from ALL date fields.
      $nids = $query->execute();

      if (empty($nids)) {
        return [];
      }

      // Convert to array if needed and sample events for better performance.
      $nids_array = is_array($nids) ? array_values($nids) : [$nids];
      $total_events = count($nids_array);

      // Load ALL events and let API do comprehensive date-based sampling
      // This ensures we don't miss recent events due to random sampling.
      $events = $storage->loadMultiple($nids_array);

      // Sort by date for timeline display.
      usort($events, function ($a, $b) {
        $date_a = $this->getEventDate($a);
        $date_b = $this->getEventDate($b);
        return strcmp($date_a, $date_b);
      });

      // Also include timeline HTML entities if configured.
      if ($include_tdih && $config->get('parse_html_timelines')) {
        $html_events = $this->getTimelineHtmlEntities();
        if (!empty($html_events)) {
          $events = array_merge($events, $html_events);
        }
      }

      // Sort all events by date.
      usort($events, function ($a, $b) {
        $date_a = $this->getEventDate($a);
        $date_b = $this->getEventDate($b);
        return strcmp($date_a, $date_b);
      });

      // TEMPORARILY DISABLE SERVICE CACHE SAVING for debugging
      // Cache the results.
      if (FALSE && $use_cache) {
        $cache_lifetime = $config->get('cache_lifetime') ?: 3600;
        $this->cache->set($cache_id, $events, $this->time->getRequestTime() + $cache_lifetime, ['node_list:event']);
      }

      return $events;

    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Get timeline HTML entities that contain timeline data.
   *
   * @return array
   *   Array of parsed timeline entities.
   */
  protected function getTimelineHtmlEntities() {
    $entities = [];

    // Query for nodes that might contain timeline HTML.
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(TRUE);

    // Look for nodes with timeline-related content.
    $or_group = $query->orConditionGroup();
    $or_group->condition('body', 'class="timeline"', 'CONTAINS');
    $or_group->condition('body', 'data-timeline', 'CONTAINS');
    $or_group->condition('title', 'timeline', 'CONTAINS');
    $query->condition($or_group);

    $nids = $query->execute();
    $nodes = $storage->loadMultiple($nids);

    foreach ($nodes as $node) {
      if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
        $parsed_events = $this->parseTimelineHtml($node->get('body')->value);
        $entities = array_merge($entities, $parsed_events);
      }
    }

    return $entities;
  }

  /**
   * Parse timeline HTML to extract event data.
   *
   * @param string $html
   *   HTML content to parse.
   *
   * @return array
   *   Array of parsed timeline events.
   */
  protected function parseTimelineHtml($html) {
    $events = [];

    // Use DOMDocument to parse HTML.
    $dom = new \DOMDocument();
    @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    // Look for timeline elements.
    $xpath = new \DOMXPath($dom);

    // Common timeline HTML patterns.
    $timeline_items = $xpath->query('//div[@class="timeline-item"] | //li[@class="timeline-event"] | //div[@data-timeline-date]');

    foreach ($timeline_items as $item) {
      $event_data = [
        'type' => 'timeline_html',
        'title' => '',
        'date' => '',
        'body' => '',
      ];

      // Extract title.
      $titles = $xpath->query('.//h2 | .//h3 | .//h4 | .//*[@class="timeline-title"]', $item);
      if ($titles->length > 0) {
        $event_data['title'] = trim($titles->item(0)->textContent);
      }

      // Extract date.
      $dates = $xpath->query('.//*[@class="timeline-date"] | .//*[@data-date]', $item);
      if ($dates->length > 0) {
        $raw_date = trim($dates->item(0)->textContent);
        $event_data['date'] = $this->normalizeFlexibleDate($raw_date);
      }
      elseif ($item instanceof \DOMElement && $item->hasAttribute('data-timeline-date')) {
        $raw_date = $item->getAttribute('data-timeline-date');
        $event_data['date'] = $this->normalizeFlexibleDate($raw_date);
      }

      // Also look for dates in text content.
      if (empty($event_data['date'])) {
        $text_content = $item->textContent;
        $extracted_date = $this->extractDateFromText($text_content);
        if ($extracted_date) {
          $event_data['date'] = $this->normalizeFlexibleDate($extracted_date);
        }
      }

      // Extract content.
      $contents = $xpath->query('.//*[@class="timeline-content"] | .//p', $item);
      if ($contents->length > 0) {
        $event_data['body'] = trim($contents->item(0)->textContent);
      }

      if (!empty($event_data['title']) || !empty($event_data['date'])) {
        $events[] = (object) $event_data;
      }
    }

    return $events;
  }

  /**
   * Get the date from an event entity.
   *
   * @param mixed $event
   *   The event entity or object.
   *
   * @return string
   *   The event date.
   */
  protected function getEventDate($event) {
    if ($event instanceof NodeInterface) {
      // Check multiple date fields in order of preference.
      $date_fields = [
      // Primary event date field.
        'field_event_date',
      // Event start date.
        'field_start_date',
      // Event end date.
        'field_end_date',
      // Birth dates.
        'field_drupal_birth_date',
      // Death dates.
        'field_drupal_death_date',
      // Publication dates.
        'field_publication_date_archive',
      // Archive dates.
        'field_archive_publication_date',
      // Fallback to creation date.
        'created',
      ];

      foreach ($date_fields as $field_name) {
        if ($event->hasField($field_name) && !$event->get($field_name)->isEmpty()) {
          $date_value = $event->get($field_name)->value;
          if (!empty($date_value)) {
            return $date_value;
          }
        }
      }

      // Ultimate fallback to created timestamp.
      return date('Y-m-d', $event->getCreatedTime());
    }
    elseif (is_object($event) && isset($event->date)) {
      return $event->date;
    }

    return '';
  }

  /**
   * Calculate the period for an event.
   *
   * @param mixed $event
   *   The event node or object.
   * @param string $period_type
   *   Type of period.
   *
   * @return string
   *   The period label.
   */
  protected function calculatePeriod($event, $period_type) {
    $date = $this->getEventDate($event);

    if (!empty($date)) {
      $year = (int) substr($date, 0, 4);

      switch ($period_type) {
        case 'century':
          $century = ceil($year / 100);
          return $century . 'th Century';

        case 'decade':
          $decade = floor($year / 10) * 10;
          return $decade . 's';

        case 'year':
        default:
          return (string) $year;
      }
    }

    return 'Unknown Period';
  }

  /**
   * Search timeline events.
   *
   * @param string $keywords
   *   Search keywords.
   * @param array $filters
   *   Additional filters.
   *
   * @return array
   *   Array of matching event nodes.
   */
  public function searchEvents($keywords, array $filters = []) {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'event')
      ->condition('status', NodeInterface::PUBLISHED)
      ->accessCheck(TRUE);

    if (!empty($keywords)) {
      $group = $query->orConditionGroup()
        ->condition('title', $keywords, 'CONTAINS')
        ->condition('body', $keywords, 'CONTAINS');
      $query->condition($group);
    }

    foreach ($filters as $field => $value) {
      if (!empty($value)) {
        $query->condition($field, $value);
      }
    }

    $query->sort('field_event_date', 'ASC');
    $nids = $query->execute();

    return $storage->loadMultiple($nids);
  }

  /**
   * Get events with better time period distribution.
   *
   * @param int $total_limit
   *   Total number of events to return.
   *
   * @return array
   *   Array of events distributed across time periods.
   */
  public function getEventsWithTimeDistribution($total_limit = 5000) {
    $storage = $this->entityTypeManager->getStorage('node');
    $all_events = [];

    // Define time periods with target counts.
    $time_periods = [
      ['start' => '1000-01-01', 'end' => '1500-12-31', 'target' => 100],
      ['start' => '1500-01-01', 'end' => '1650-12-31', 'target' => 150],
      ['start' => '1650-01-01', 'end' => '1800-12-31', 'target' => 250],
      ['start' => '1800-01-01', 'end' => '1900-12-31', 'target' => 400],
      ['start' => '1900-01-01', 'end' => '1950-12-31', 'target' => 450],
      ['start' => '1950-01-01', 'end' => '1990-12-31', 'target' => 400],
      ['start' => '1990-01-01', 'end' => '2025-12-31', 'target' => 250],
    ];

    foreach ($time_periods as $period) {
      $query = $storage->getQuery()
        ->condition('type', 'event')
        ->condition('status', NodeInterface::PUBLISHED)
        ->accessCheck(TRUE);

      // Add date conditions for this period.
      if ($period['start'] && $period['end']) {
        $query->condition('field_event_date', $period['start'], '>=');
        $query->condition('field_event_date', $period['end'], '<=');
      }
      elseif ($period['start']) {
        $query->condition('field_event_date', $period['start'], '>=');
      }
      elseif ($period['end']) {
        $query->condition('field_event_date', $period['end'], '<=');
      }

      $query->range(0, $period['target']);
      $query->sort('field_event_date', 'ASC');

      $nids = $query->execute();
      if (!empty($nids)) {
        $period_events = $storage->loadMultiple($nids);
        $all_events = array_merge($all_events, $period_events);

      }
    }

    // Sort all events by date.
    usort($all_events, function ($a, $b) {
      $date_a = $this->getEventDate($a);
      $date_b = $this->getEventDate($b);
      return strcmp($date_a, $date_b);
    });

    return array_slice($all_events, 0, $total_limit);
  }

  /**
   * Normalize flexible date formats to ISO format.
   *
   * @param string $date_string
   *   Raw date string.
   *
   * @return string
   *   Normalized ISO date (YYYY-MM-DD).
   */
  protected function normalizeFlexibleDate($date_string) {
    if (empty($date_string)) {
      return '';
    }

    $date_string = trim($date_string);

    // Already in ISO format.
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_string)) {
      return $date_string;
    }

    // Year only (1994)
    if (preg_match('/^\d{4}$/', $date_string)) {
      return $date_string . '-01-01';
    }

    // Month/Day (12/25, 12-25)
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})$/', $date_string, $matches)) {
      $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
      $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
      // Use 1900 as placeholder year.
      return '1900-' . $month . '-' . $day;
    }

    // Month/Day/Year (12/25/1994, 12-25-1994)
    if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $date_string, $matches)) {
      $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
      $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
      $year = $matches[3];
      return $year . '-' . $month . '-' . $day;
    }

    // Year/Month/Day (1994/12/25, 1994-12-25)
    if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/', $date_string, $matches)) {
      $year = $matches[1];
      $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
      $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
      return $year . '-' . $month . '-' . $day;
    }

    // Date ranges (1994-1996, extract start year)
    if (preg_match('/^(\d{4})\s*[-–—]\s*(\d{4})/', $date_string, $matches)) {
      return $matches[1] . '-01-01';
    }

    // Try to extract any 4-digit year.
    if (preg_match('/(\d{4})/', $date_string, $matches)) {
      return $matches[1] . '-01-01';
    }

    // Default to empty if no date found.
    return '';
  }

  /**
   * Extract date from general text content.
   *
   * @param string $text
   *   Text content to search.
   *
   * @return string|null
   *   Extracted date or NULL.
   */
  protected function extractDateFromText($text) {
    // Look for various date patterns in text.
    $patterns = [
    // 25th of December 1994
      '/(\d{1,2})(?:st|nd|rd|th)?\s+(?:of\s+)?([A-Za-z]+)\s+(\d{4})/',
    // December 25th, 1994.
      '/([A-Za-z]+)\s+(\d{1,2})(?:st|nd|rd|th)?,?\s+(\d{4})/',
    // 1994-1996
      '/(\d{4})\s*[-–—]\s*(\d{4})/',
    // Just a year.
      '/(\d{4})/',
    ];

    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $text, $matches)) {
        if (count($matches) >= 4) {
          // Full date match.
          $month_names = [
            'january' => '01',
            'february' => '02',
            'march' => '03',
            'april' => '04',
            'may' => '05',
            'june' => '06',
            'july' => '07',
            'august' => '08',
            'september' => '09',
            'october' => '10',
            'november' => '11',
            'december' => '12',
          ];

          $month_name = strtolower($matches[2]);
          if (isset($month_names[$month_name])) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            return $matches[3] . '-' . $month_names[$month_name] . '-' . $day;
          }
        }
        else {
          // Year only or range.
          return $matches[1];
        }
      }
    }

    return NULL;
  }

  /**
   * Check if an event has a proper historical date (not just creation date).
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return bool
   *   TRUE if the event has a historical date field populated.
   */
  protected function hasHistoricalDate($event) {
    if (!$event instanceof NodeInterface) {
      return FALSE;
    }

    // Check historical date fields (excluding creation date).
    $historical_date_fields = [
      'field_event_date',
      'field_start_date',
      'field_end_date',
      'field_drupal_birth_date',
      'field_drupal_death_date',
      'field_publication_date_archive',
      'field_archive_publication_date',
    ];

    foreach ($historical_date_fields as $field_name) {
      if ($event->hasField($field_name) && !$event->get($field_name)->isEmpty()) {
        $date_value = $event->get($field_name)->value;
        if (!empty($date_value)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Get timeline events separated into dated and dateless categories.
   *
   * Uses optimized database queries to avoid loading all nodes into memory.
   *
   * @param bool $include_tdih
   *   Whether to include TDIH events.
   * @param bool $use_cache
   *   Whether to use caching.
   * @param bool $include_dateless_full
   *   Whether to include full dateless event data for admin review.
   *
   * @return array
   *   Array with 'events' (with historical dates) and 'dateless_events' keys.
   */
  public function getAllTimelineEventsSegregated($include_tdih = TRUE, $use_cache = TRUE, $include_dateless_full = FALSE) {
    $cache_id = 'saho_timeline:segregated_events:' . ($include_dateless_full ? 'full' : 'count');

    // Try cache first.
    if ($use_cache && $cached = $this->cache->get($cache_id)) {
      return $cached->data;
    }

    // Use optimized database query to get only events WITH dates.
    // This avoids loading 17k+ nodes into memory.
    $storage = $this->entityTypeManager->getStorage('node');

    // Query for events that have field_event_date populated.
    $query = $storage->getQuery()
      ->condition('type', 'event')
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('field_event_date', NULL, 'IS NOT NULL')
      ->accessCheck(TRUE)
      ->sort('field_event_date', 'ASC');

    $dated_nids = $query->execute();

    // Load only the events with dates.
    $events_with_dates = [];
    if (!empty($dated_nids)) {
      $events_with_dates = $storage->loadMultiple($dated_nids);
    }

    // Get dateless events - either count only or full data for admin review.
    $dateless_events = [];
    $dateless_count = 0;

    $dateless_query = $this->database->select('node_field_data', 'n');
    $dateless_query->condition('n.type', 'event');
    $dateless_query->condition('n.status', 1);
    $dateless_query->leftJoin('node__field_event_date', 'fed', 'n.nid = fed.entity_id');
    $dateless_query->isNull('fed.field_event_date_value');

    if ($include_dateless_full) {
      // Get full dateless event info for admin review.
      // Limited to 500 for performance.
      $dateless_query->fields('n', ['nid', 'title', 'created', 'status']);
      $dateless_query->range(0, 500);
      $dateless_query->orderBy('n.created', 'DESC');
      $dateless_results = $dateless_query->execute()->fetchAll();

      foreach ($dateless_results as $row) {
        $dateless_events[] = [
          'id' => $row->nid,
          'title' => $row->title,
          'created' => $row->created,
          'status' => $row->status ? 'Published' : 'Unpublished',
          'reason' => 'Missing field_event_date value',
        ];
      }
      $dateless_count = count($dateless_results);

      // Get total count if more than limit.
      $total_dateless_query = $this->database->select('node_field_data', 'n');
      $total_dateless_query->condition('n.type', 'event');
      $total_dateless_query->condition('n.status', 1);
      $total_dateless_query->leftJoin('node__field_event_date', 'fed', 'n.nid = fed.entity_id');
      $total_dateless_query->isNull('fed.field_event_date_value');
      $dateless_count = (int) $total_dateless_query->countQuery()->execute()->fetchField();
    }
    else {
      // Just get the count for performance.
      $dateless_count = (int) $dateless_query->countQuery()->execute()->fetchField();
    }

    $result = [
      'events' => array_values($events_with_dates),
      'dateless_events' => $dateless_events,
      'stats' => [
        'total_events' => count($events_with_dates) + $dateless_count,
        'events_with_dates' => count($events_with_dates),
        'dateless_events' => $dateless_count,
      ],
    ];

    // Cache for 1 hour.
    if ($use_cache) {
      $this->cache->set($cache_id, $result, $this->time->getRequestTime() + 3600, ['node_list:event']);
    }

    return $result;
  }

}
