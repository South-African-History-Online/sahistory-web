<?php

namespace Drupal\tdih\Service;

use Drupal\node\Entity\Node;

/**
 * Service to fetch nodes for "Today in History" logic.
 */
class NodeFetcher {

  /**
   * Load nodes matching today's month/day.
   *
   * Adjust your field and content type as needed.
   */
  public function loadTodayNodes($month, $day) {
    // Ensure month and day are properly formatted with leading zeros.
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $day = str_pad($day, 2, '0', STR_PAD_LEFT);

    // First try to get featured events for today.
    $query = \Drupal::entityQuery('node');
    $nids = $query->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_this_day_in_history_3', "%-$month-$day", 'LIKE')
      ->condition('field_home_page_feature', 1)
      ->accessCheck(TRUE)
      ->execute();

    // If no featured events found, get at least one event for today.
    if (empty($nids)) {
      $query = \Drupal::entityQuery('node');
      $nids = $query->condition('type', 'event')
        ->condition('status', 1)
        ->condition('field_this_day_in_history_3', "%-$month-$day", 'LIKE')
        ->accessCheck(TRUE)
        ->sort('field_this_day_in_history_3', 'DESC')
        ->range(0, 1)
        ->execute();
    }

    if ($nids) {
      return Node::loadMultiple($nids);
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
    // Ensure month and day are properly formatted with leading zeros.
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    $day = str_pad($day, 2, '0', STR_PAD_LEFT);

    // Query for published "event" nodes with date field matching mm-dd.
    $query = \Drupal::entityQuery('node');
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
      return Node::loadMultiple($nids);
    }
    return [];
  }

  /**
   * Get all available months and days that have events.
   *
   * This method is used to populate the date picker with valid dates.
   *
   * @return array
   *   Array of month-day combinations that have events.
   */
  public function getAvailableDates() {
    $dates = [];

    // Query for all published "event" nodes with a date field.
    $query = \Drupal::entityQuery('node');
    $nids = $query->condition('type', 'event')
      ->condition('status', 1)
      ->exists('field_this_day_in_history_3')
      ->accessCheck(TRUE)
      ->execute();

    if ($nids) {
      $nodes = Node::loadMultiple($nids);
      foreach ($nodes as $node) {
        if ($node->hasField('field_this_day_in_history_3') && !$node->get('field_this_day_in_history_3')->isEmpty()) {
          $date_value = $node->get('field_this_day_in_history_3')->value;
          // Extract month and day from the date value (format: YYYY-MM-DD)
          if (preg_match('/\d{4}-(\d{2})-(\d{2})/', $date_value, $matches)) {
            $month = $matches[1];
            $day = $matches[2];
            $month_day = "$month-$day";
            if (!in_array($month_day, $dates)) {
              $dates[] = $month_day;
            }
          }
        }
      }
    }

    return $dates;
  }

}
