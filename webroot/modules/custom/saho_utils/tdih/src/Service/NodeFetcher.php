<?php

namespace Drupal\tdih\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

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
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new NodeFetcher object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('tdih');
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
        ->sort('field_this_day_in_history_3', 'DESC');

      // If a specific month-day is provided, use LIKE to get potential matches.
      if ($month_day) {
        $query->condition('field_this_day_in_history_3', "%-$month_day", 'LIKE');
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
      $this->logger->error('Error loading potential event nodes: @message', [
        '@message' => $e->getMessage(),
      ]);
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
        ->sort('field_this_day_in_history_3', 'DESC');

      // If a specific month-day is provided, use LIKE to get potential matches.
      if ($month_day) {
        $query->condition('field_this_day_in_history_3', "%-$month_day", 'LIKE');
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
      $this->logger->error('Error loading birthday event nodes: @message', [
        '@message' => $e->getMessage(),
      ]);
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
      // Use database connection directly for better performance.
      $database = \Drupal::database();

      // Query to extract month and day from the date field.
      $query = $database->select('node_field_data', 'n');
      $query->join('node__field_this_day_in_history_3', 'f', 'n.nid = f.entity_id');
      $query->join('node__field_home_page_feature', 'h', 'n.nid = h.entity_id');
      $query->fields('f', ['field_this_day_in_history_3_value']);
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
      $this->logger->error('Error getting available dates: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $dates;
  }

}