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
   * @param int|null $limit
   *   Optional cap on how many events to load before grouping.
   *
   * @return array
   *   Events grouped by period.
   */
  public function getEventsGroupedByPeriod($period_type = 'decade', $limit = NULL) {
    $events = $this->getAllTimelineEvents(TRUE, TRUE, $limit);
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
   * Get timeline events including TDIH events, bounded by a result cap.
   *
   * @param bool $include_tdih
   *   Whether to include TDIH events.
   * @param bool $use_cache
   *   Whether to use caching.
   * @param int|null $limit
   *   Optional caller cap; the effective cap is the smaller of this and the
   *   max_api_results setting. Loading the full ~17k event corpus needs
   *   hundreds of MB - callers wanting complete coverage should use
   *   getAllTimelineEventsSegregated() instead.
   *
   * @return array
   *   Array of published event nodes, oldest first.
   */
  public function getAllTimelineEvents($include_tdih = TRUE, $use_cache = TRUE, $limit = NULL) {
    $config = $this->configFactory->get('saho_timeline.settings');
    $max_results = (int) ($config->get('max_api_results') ?: 1000);
    if ($limit !== NULL && (int) $limit > 0) {
      $max_results = min((int) $limit, $max_results);
    }

    $cache_id = 'saho_timeline:all_events:' . ($include_tdih ? 'with_tdih' : 'no_tdih') . ':' . $max_results;

    // Try to get from cache first.
    if ($use_cache && $cached = $this->cache->get($cache_id)) {
      return $cached->data;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('node');
      $query = $storage->getQuery()
        ->condition('type', 'event')
        ->condition('status', NodeInterface::PUBLISHED)
        ->accessCheck(TRUE)
        // Bounded and deterministic: newest changes first, capped in the
        // database. The unbounded variant loaded every event node into
        // memory (~600MB render).
        ->sort('changed', 'DESC')
        ->range(0, $max_results);

      $nids = $query->execute();

      if (empty($nids)) {
        return [];
      }

      $events = $storage->loadMultiple(array_values($nids));

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

      // Cache the results.
      if ($use_cache) {
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

    // The CONTAINS conditions can match broadly; cap the parse workload.
    $query->sort('changed', 'DESC')->range(0, 100);

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
      ];
      // NOTE: 'created' must NOT be in the list above - its raw value is a
      // Unix timestamp, and substr($timestamp, 0, 4) reads e.g. "1710" as a
      // year, bucketing present-day nodes into the 1710s. The formatted
      // fallback below handles creation time correctly.
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
          // 1900 belongs to the 19th century, 1901 opens the 20th.
          $century = intdiv($year - 1, 100) + 1;
          $suffixes = [1 => 'st', 2 => 'nd', 3 => 'rd'];
          $suffix = ($century % 100 >= 11 && $century % 100 <= 13)
            ? 'th'
            : ($suffixes[$century % 10] ?? 'th');
          return $century . $suffix . ' Century';

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
    if (preg_match('/^(\d{4})\s*[-–-]\s*(\d{4})/', $date_string, $matches)) {
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
      '/(\d{4})\s*[-–-]\s*(\d{4})/',
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
   * Builds the cached light index of dated events (#454/#430).
   *
   * One SQL join returning stdClass rows {id, title, date} in date order -
   * a few hundred KB instead of 3.5k fully hydrated nodes. The rows flow
   * through the API controller's sort/sampling helpers unchanged (they
   * already support ->date / ->title pseudo-events); full nodes are only
   * hydrated for the page actually returned.
   *
   * @return object[]
   *   Light event rows, field_event_date ascending.
   */
  public function getDatedEventIndex(): array {
    $cache_id = 'saho_timeline:dated_event_index';
    if ($cached = $this->cache->get($cache_id)) {
      return $cached->data;
    }

    $query = $this->database->select('node_field_data', 'n');
    $query->join('node__field_event_date', 'fed', 'fed.entity_id = n.nid AND fed.deleted = 0');
    $query->fields('n', ['nid', 'title']);
    $query->addField('fed', 'field_event_date_value', 'date');
    $query->condition('n.type', 'event');
    $query->condition('n.status', 1);
    $query->isNotNull('fed.field_event_date_value');
    $query->orderBy('fed.field_event_date_value', 'ASC');

    $rows = [];
    foreach ($query->execute() as $row) {
      $rows[] = (object) [
        'id' => (int) $row->nid,
        'title' => (string) $row->title,
        'date' => (string) $row->date,
      ];
    }

    $this->cache->set($cache_id, $rows, $this->time->getRequestTime() + 3600, ['node_list:event']);
    return $rows;
  }

  /**
   * Aggregated location/theme facet counts over all dated events.
   *
   * GROUP BY queries against the term reference tables - no node loads.
   *
   * @return array
   *   ['geographical_location' => [name => count], 'themes' => [name => count]].
   */
  public function getDatedEventTermFacets(): array {
    $cache_id = 'saho_timeline:dated_event_term_facets';
    if ($cached = $this->cache->get($cache_id)) {
      return $cached->data;
    }

    $facets = ['geographical_location' => [], 'themes' => []];
    $map = [
      'geographical_location' => 'node__field_location',
      'themes' => 'node__field_themes',
    ];
    foreach ($map as $facet => $table) {
      if (!$this->database->schema()->tableExists($table)) {
        continue;
      }
      $column = str_replace('node__', '', $table) . '_target_id';
      $query = $this->database->select('node_field_data', 'n');
      $query->join('node__field_event_date', 'fed', 'fed.entity_id = n.nid AND fed.deleted = 0');
      $query->join($table, 'ref', 'ref.entity_id = n.nid AND ref.deleted = 0');
      $query->join('taxonomy_term_field_data', 't', 't.tid = ref.' . $column);
      $query->addField('t', 'name');
      $query->addExpression('COUNT(DISTINCT n.nid)', 'total');
      $query->condition('n.type', 'event');
      $query->condition('n.status', 1);
      $query->isNotNull('fed.field_event_date_value');
      $query->groupBy('t.name');
      $query->orderBy('total', 'DESC');
      foreach ($query->execute() as $row) {
        $facets[$facet][$row->name] = (int) $row->total;
      }
    }

    $this->cache->set($cache_id, $facets, $this->time->getRequestTime() + 3600, ['node_list:event']);
    return $facets;
  }

  /**
   * The v2 skeleton index: every plottable event as one light row.
   *
   * COALESCEs the curated field_event_date with the machine-extracted
   * field_timeline_date (curated always wins), so date-rescue batches
   * surface here automatically. Rows carry a precision code: curated
   * dates are 'day' by definition of the editorial field; extracted
   * dates carry their recorded precision.
   *
   * @return object[]
   *   stdClass rows {id, title, date, precision}, date ascending.
   */
  public function getTimelineIndexV2(): array {
    $cache_id = 'saho_timeline:index_v2';
    if ($cached = $this->cache->get($cache_id)) {
      return $cached->data;
    }

    $rows = [];
    foreach ($this->buildIndexV2Query()->execute() as $row) {
      $rows[] = (object) [
        'id' => (int) $row->nid,
        'title' => (string) $row->title,
        'date' => (string) $row->event_date,
        'precision' => (string) $row->date_precision,
      ];
    }

    $this->cache->set($cache_id, $rows, $this->time->getRequestTime() + 3600, ['node_list:event']);
    return $rows;
  }

  /**
   * Light rows for one decade bucket, date ascending.
   *
   * @param string $bucket
   *   Either 'pre1500' or a decade token like '1900'.
   *
   * @return object[]
   *   stdClass rows {id, title, date, precision}.
   */
  public function getBucketRows(string $bucket): array {
    $query = $this->buildIndexV2Query();
    $date_expr = $this->coalescedDateExpression();
    if ($bucket === 'pre1500') {
      $query->where("$date_expr < '1500-01-01'");
    }
    else {
      $decade = (int) $bucket;
      $query->where("$date_expr >= :bucket_start AND $date_expr < :bucket_end", [
        ':bucket_start' => sprintf('%04d-01-01', $decade),
        ':bucket_end' => sprintf('%04d-01-01', $decade + 10),
      ]);
    }

    $rows = [];
    foreach ($query->execute() as $row) {
      $rows[] = (object) [
        'id' => (int) $row->nid,
        'title' => (string) $row->title,
        'date' => (string) $row->event_date,
        'precision' => (string) $row->date_precision,
      ];
    }
    return $rows;
  }

  /**
   * Per-bucket event counts for the density histogram.
   *
   * Derived from the cached v2 index - no extra query on the warm path.
   *
   * @return array
   *   ['pre1500' => n, '1500' => n, ...] in chronological order.
   */
  public function getDecadeCounts(): array {
    $counts = [];
    foreach ($this->getTimelineIndexV2() as $row) {
      $counts[static::bucketForDate($row->date)] = ($counts[static::bucketForDate($row->date)] ?? 0) + 1;
    }
    return $counts;
  }

  /**
   * Maps a stored YYYY-MM-DD date to its bucket token.
   */
  public static function bucketForDate(string $date): string {
    $year = (int) substr($date, 0, 4);
    if ($year < 1500) {
      return 'pre1500';
    }
    return (string) (intdiv($year, 10) * 10);
  }

  /**
   * Shared SELECT for the v2 index and bucket queries.
   *
   * LEFT JOINs both date sources; the timeline_date joins degrade to
   * plain NULL columns when the field tables do not exist yet (fresh
   * installs before config import, kernel tests without the fields).
   */
  protected function buildIndexV2Query() {
    $has_extracted = $this->database->schema()->tableExists('node__field_timeline_date');

    $query = $this->database->select('node_field_data', 'n');
    $query->leftJoin('node__field_event_date', 'fed', 'fed.entity_id = n.nid AND fed.deleted = 0');
    if ($has_extracted) {
      $query->leftJoin('node__field_timeline_date', 'ftd', 'ftd.entity_id = n.nid AND ftd.deleted = 0');
      $query->leftJoin('node__field_timeline_date_precision', 'ftp', 'ftp.entity_id = n.nid AND ftp.deleted = 0');
    }
    $query->fields('n', ['nid', 'title']);
    $date_expr = $this->coalescedDateExpression();
    $query->addExpression($date_expr, 'event_date');
    if ($has_extracted) {
      $query->addExpression("CASE WHEN fed.field_event_date_value IS NOT NULL THEN 'day' ELSE COALESCE(ftp.field_timeline_date_precision_value, 'day') END", 'date_precision');
    }
    else {
      $query->addExpression("'day'", 'date_precision');
    }
    $query->condition('n.type', 'event');
    $query->condition('n.status', 1);
    $query->where("$date_expr IS NOT NULL");
    $query->orderBy('event_date', 'ASC');
    $query->orderBy('n.nid', 'ASC');
    return $query;
  }

  /**
   * The COALESCE expression both v2 queries filter and sort on.
   */
  protected function coalescedDateExpression(): string {
    if ($this->database->schema()->tableExists('node__field_timeline_date')) {
      return 'COALESCE(fed.field_event_date_value, ftd.field_timeline_date_value)';
    }
    return 'fed.field_event_date_value';
  }

  /**
   * Dateless-event summary (count, optionally the first 500 rows).
   *
   * @param bool $full
   *   TRUE to include row data for admin review.
   *
   * @return array
   *   ['events' => array, 'count' => int].
   */
  public function getDatelessEvents(bool $full = FALSE): array {
    $dateless_events = [];

    $dateless_query = $this->database->select('node_field_data', 'n');
    $dateless_query->condition('n.type', 'event');
    $dateless_query->condition('n.status', 1);
    $dateless_query->leftJoin('node__field_event_date', 'fed', 'n.nid = fed.entity_id');
    $dateless_query->isNull('fed.field_event_date_value');

    if ($full) {
      $rows_query = clone $dateless_query;
      $rows_query->fields('n', ['nid', 'title', 'created', 'status']);
      $rows_query->range(0, 500);
      $rows_query->orderBy('n.created', 'DESC');
      foreach ($rows_query->execute() as $row) {
        $dateless_events[] = [
          'id' => $row->nid,
          'title' => $row->title,
          'created' => $row->created,
          'status' => $row->status ? 'Published' : 'Unpublished',
          'reason' => 'Missing field_event_date value',
        ];
      }
    }

    $count = (int) $dateless_query->countQuery()->execute()->fetchField();
    return ['events' => $dateless_events, 'count' => $count];
  }

}
