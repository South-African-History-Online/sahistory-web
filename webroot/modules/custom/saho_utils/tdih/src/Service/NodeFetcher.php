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
   * Load nodes matching today's month/day.
   *
   * Adjust your field and content type as needed.
   *
   * @param string $month
   *   The month (01-12).
   * @param string $day
   *   The day (01-31).
   *
   * @return array
   *   Array of Node objects.
   */
  public function loadTodayNodes($month, $day) {
    try {
      // Ensure month and day are properly formatted with leading zeros.
      $month = str_pad($month, 2, '0', STR_PAD_LEFT);
      $day = str_pad($day, 2, '0', STR_PAD_LEFT);

      // First try to get featured events for today.
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $nids = $query->condition('type', 'event')
        ->condition('status', 1)
        ->condition('field_this_day_in_history_3', "%-$month-$day", 'LIKE')
        ->condition('field_home_page_feature', 1)
        ->accessCheck(TRUE)
        ->execute();

      // If no featured events found, get at least one event for today.
      if (empty($nids)) {
        $query = $this->entityTypeManager->getStorage('node')->getQuery();
        $nids = $query->condition('type', 'event')
          ->condition('status', 1)
          ->condition('field_this_day_in_history_3', "%-$month-$day", 'LIKE')
          ->accessCheck(TRUE)
          ->sort('field_this_day_in_history_3', 'DESC')
          ->range(0, 1)
          ->execute();
      }

      if ($nids) {
        return $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading TDIH nodes for @date: @message', [
        '@date' => "$month-$day",
        '@message' => $e->getMessage(),
      ]);
    }
    return [];
  }

  /**
   * Load nodes matching a specific month/day.
   *
   * This method is similar to loadTodayNodes but doesn't require the
   * field_home_page_feature flag, allowing it to return all events for a date.
   *
   * @param string $month
   *   The month (01-12).
   * @param string $day
   *   The day (01-31).
   * @param int $limit
   *   Maximum number of nodes to return (0 for no limit).
   *
   * @return array
   *   Array of Node objects.
   */
  public function loadDateNodes($month, $day, $limit = 0) {
    try {
      // Ensure month and day are properly formatted with leading zeros.
      $month = str_pad($month, 2, '0', STR_PAD_LEFT);
      $day = str_pad($day, 2, '0', STR_PAD_LEFT);

      // Query for published "event" nodes with date field matching mm-dd.
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition('type', 'event')
        ->condition('status', 1)
        ->condition('field_this_day_in_history_3', "%-$month-$day", 'LIKE')
        ->accessCheck(TRUE)
        ->sort('field_this_day_in_history_3', 'DESC');

      // Apply limit if specified.
      if ($limit > 0) {
        $query->range(0, $limit);
      }

      $nids = $query->execute();

      if ($nids) {
        return $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading date nodes for @date: @message', [
        '@date' => "$month-$day",
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
      $query->fields('f', ['field_this_day_in_history_3_value']);
      $query->condition('n.type', 'event')
        ->condition('n.status', 1)
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

      // Log the number of unique dates found.
      $this->logger->info('Found @count unique dates for TDIH date picker.', [
        '@count' => count($dates),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting available dates: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $dates;
  }

}
