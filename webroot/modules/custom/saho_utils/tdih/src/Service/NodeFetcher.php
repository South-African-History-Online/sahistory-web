<?php

namespace Drupal\tdih\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service to fetch nodes for "Today in History" logic.
 */
class NodeFetcher {

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
   * Constructs a new NodeFetcher object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
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
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition('type', 'event')
        ->condition('status', 1)
        ->condition('field_home_page_feature', 1)
        ->accessCheck(TRUE)
        ->sort('field_event_date', 'DESC');

      // If a specific month-day is provided, use LIKE to get potential matches.
      // Validate format (MM-DD) to prevent LIKE injection attacks.
      if ($month_day && $this->isValidMonthDay($month_day)) {
        // Escape LIKE wildcards to prevent injection.
        $safe_month_day = $this->escapeLikeWildcards($month_day);
        $query->condition('field_event_date', "%-$safe_month_day", 'LIKE');
      }

      // Limit to reasonable number for performance.
      $query->range(0, 500);

      $nids = $query->execute();

      if ($nids) {
        $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
        return $nodes;
      }
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
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition('type', 'event')
        ->condition('status', 1)
        ->accessCheck(TRUE)
        ->sort('field_event_date', 'DESC');

      // If a specific month-day is provided, use LIKE to get potential matches.
      // Validate format (MM-DD) to prevent LIKE injection attacks.
      if ($month_day && $this->isValidMonthDay($month_day)) {
        // Escape LIKE wildcards to prevent injection.
        $safe_month_day = $this->escapeLikeWildcards($month_day);
        $query->condition('field_event_date', "%-$safe_month_day", 'LIKE');
      }

      // Limit to reasonable number for performance.
      $query->range(0, 500);

      $nids = $query->execute();

      if ($nids) {
        $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
        return $nodes;
      }
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

    }
    catch (\Exception $e) {
    }

    return $dates;
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

    // Month must be 01-12, day must be 01-31.
    return $month_int >= 1 && $month_int <= 12 && $day_int >= 1 && $day_int <= 31;
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
