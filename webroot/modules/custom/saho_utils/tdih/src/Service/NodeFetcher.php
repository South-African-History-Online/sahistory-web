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
    // Example query: load published "event" nodes with date field
    // matching mm-dd.
    $query = \Drupal::entityQuery('node');
    $nids = $query->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_this_day_in_history_3', "%-$month-$day", 'LIKE')
      ->condition('field_home_page_feature', 1)
      ->accessCheck(TRUE)
      ->execute();

    if ($nids) {
      return Node::loadMultiple($nids);
    }
    return [];
  }

}
