<?php

namespace Drupal\tdih\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Service to fetch nodes for "Today in History" logic.
 *
 * The month-day lookups use a leading-wildcard LIKE on field_event_date,
 * which no index can serve - every execution scans the full event table.
 * The nid lists are therefore cached in cache.default per month-day, tagged
 * with node_list:event so event saves refresh them immediately, and capped
 * at 24 hours as a garbage-collection backstop.
 */
class NodeFetcher {

  /**
   * Seconds a cached nid list may live without invalidation.
   */
  protected const CACHE_MAX_AGE = 86400;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The default cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new NodeFetcher object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache bin.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    CacheBackendInterface $cache,
    TimeInterface $time,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->cache = $cache;
    $this->time = $time;
  }

  /**
   * Load events that potentially match a date pattern.
   *
   * Only loads events that are featured on the front page.
   *
   * @param string $month_day
   *   The month-day pattern to search for (e.g., "08-02").
   *
   * @return array
   *   Array of Node objects.
   */
  public function loadPotentialEvents($month_day = NULL) {
    try {
      $filtered = $month_day && $this->isValidMonthDay($month_day);
      $cid = 'tdih:potential_events:' . ($filtered ? $month_day : 'all');
      $nids = $this->getCachedNids($cid, function () use ($filtered, $month_day) {
        $query = $this->entityTypeManager->getStorage('node')->getQuery();
        $query->condition('type', 'event')
          ->condition('status', 1)
          ->condition('field_home_page_feature', 1)
          // Published-only content feeding a shared, permission-unaware
          // cache: no per-user access check. The site has no
          // hook_node_grants() implementations and saho_api_guard never
          // restricts viewing published nodes - revisit if a node-grants
          // module is ever installed.
          ->accessCheck(FALSE)
          ->sort('field_event_date', 'DESC');

        // If a specific month-day is provided, use LIKE to get potential
        // matches. The format was validated (MM-DD) to prevent LIKE
        // injection attacks; wildcards are escaped as belt-and-braces.
        if ($filtered) {
          $safe_month_day = $this->escapeLikeWildcards($month_day);
          $query->condition('field_event_date', "%-$safe_month_day", 'LIKE');
        }

        // Limit to reasonable number for performance.
        $query->range(0, 500);

        return $query->execute();
      });

      return $this->loadPublishedNodes($nids);
    }
    catch (\Exception $e) {
    }
    return [];
  }

  /**
   * Load ALL events that match a date pattern for birthday feature.
   *
   * Unlike loadPotentialEvents(), this method returns ALL events that match
   * the specified date pattern, not just those featured on the front page.
   *
   * @param string $month_day
   *   The month-day pattern to search for (e.g., "08-02").
   *
   * @return array
   *   Array of Node objects.
   */
  public function loadAllBirthdayEvents($month_day) {
    try {
      $filtered = $month_day && $this->isValidMonthDay($month_day);
      $cid = 'tdih:birthday_events:' . ($filtered ? $month_day : 'all');
      $nids = $this->getCachedNids($cid, function () use ($filtered, $month_day) {
        $query = $this->entityTypeManager->getStorage('node')->getQuery();
        $query->condition('type', 'event')
          ->condition('status', 1)
          // See loadPotentialEvents(): shared cache, published-only query,
          // no grants modules installed.
          ->accessCheck(FALSE)
          ->sort('field_event_date', 'DESC');

        if ($filtered) {
          $safe_month_day = $this->escapeLikeWildcards($month_day);
          $query->condition('field_event_date', "%-$safe_month_day", 'LIKE');
        }

        // Limit to reasonable number for performance.
        $query->range(0, 500);

        return $query->execute();
      });

      return $this->loadPublishedNodes($nids);
    }
    catch (\Exception $e) {
    }
    return [];
  }

  /**
   * Get all available months and days that have events.
   *
   * This method is used to populate the date picker with valid dates.
   * Uses a direct database query for better performance with large datasets.
   * Only includes dates for events that are featured on the front page.
   *
   * @return array
   *   Array of month-day combinations that have events.
   */
  public function getAvailableDates() {
    $cached = $this->cache->get('tdih:available_dates');
    if ($cached !== FALSE) {
      return $cached->data;
    }

    $dates = [];

    try {
      // Query to extract month and day from the date field.
      $query = $this->database->select('node_field_data', 'n');
      $query->join('node__field_event_date', 'f', 'n.nid = f.entity_id');
      $query->join('node__field_home_page_feature', 'h', 'n.nid = h.entity_id');
      $query->fields('f', ['field_event_date_value']);
      $query->condition('n.type', 'event')
        ->condition('n.status', 1)
        ->condition('h.field_home_page_feature_value', 1)
        ->distinct();

      $results = $query->execute()->fetchCol();

      // Process the results to extract month-day combinations.
      foreach ($results as $date_value) {
        // Extract month and day from the date value (format: YYYY-MM-DD).
        if (preg_match('/\d{4}-(\d{2})-(\d{2})/', $date_value, $matches)) {
          $month = $matches[1];
          $day = $matches[2];
          $month_day = "$month-$day";
          if (!in_array($month_day, $dates)) {
            $dates[] = $month_day;
          }
        }
      }

      // Sort the dates for better user experience.
      sort($dates);

      $this->cache->set(
        'tdih:available_dates',
        $dates,
        $this->time->getRequestTime() + static::CACHE_MAX_AGE,
        ['node_list:event']
      );
    }
    catch (\Exception $e) {
    }

    return $dates;
  }

  /**
   * Returns a cached nid list, running the query callback on a miss.
   *
   * Empty lists are cached too - a FALSE from the backend is the only
   * miss signal - so days with no events do not re-scan the event table
   * on every request.
   *
   * @param string $cid
   *   The cache id.
   * @param callable $query
   *   Callback executing the entity query and returning nids.
   *
   * @return array
   *   The nid list.
   */
  protected function getCachedNids($cid, callable $query) {
    $cached = $this->cache->get($cid);
    if ($cached !== FALSE) {
      return $cached->data;
    }
    $nids = array_values($query() ?: []);
    $this->cache->set(
      $cid,
      $nids,
      $this->time->getRequestTime() + static::CACHE_MAX_AGE,
      ['node_list:event']
    );
    return $nids;
  }

  /**
   * Loads nodes for a nid list, dropping anything no longer published.
   *
   * Cached lists can go stale within the TTL window: deleted nodes simply
   * fall out of loadMultiple(), unpublished ones are filtered here. This
   * filter is also the access guard that replaced the per-user entity
   * query access check (published nodes are world-visible on this site).
   *
   * @param array $nids
   *   The nid list.
   *
   * @return array
   *   Array of published Node objects keyed by nid.
   */
  protected function loadPublishedNodes(array $nids) {
    if (!$nids) {
      return [];
    }
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    return array_filter($nodes, function ($node) {
      return $node instanceof NodeInterface && $node->isPublished();
    });
  }

  /**
   * Validate that a month-day string matches the expected format.
   *
   * @param string $month_day
   *   The string to validate.
   *
   * @return bool
   *   TRUE if valid MM-DD format, FALSE otherwise.
   */
  protected function isValidMonthDay($month_day) {
    // Must match exactly MM-DD format (two digits, hyphen, two digits).
    if (!preg_match('/^\d{2}-\d{2}$/', $month_day)) {
      return FALSE;
    }

    // Extract and validate month/day values.
    [$month, $day] = explode('-', $month_day);
    $month_int = (int) $month;
    $day_int = (int) $day;

    // Use checkdate() for proper month-specific day validation.
    // Use year 2000 (a leap year) to allow Feb 29.
    return checkdate($month_int, $day_int, 2000);
  }

  /**
   * Escape LIKE wildcards in a string.
   *
   * @param string $value
   *   The value to escape.
   *
   * @return string
   *   The escaped value safe for use in LIKE queries.
   */
  protected function escapeLikeWildcards($value) {
    // Use Drupal's database abstraction for cross-database compatibility.
    return $this->database->escapeLike($value);
  }

}
