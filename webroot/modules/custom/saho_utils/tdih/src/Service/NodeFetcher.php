<?php

namespace Drupal\tdih\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Sample reusable service for date-specific queries.
 */
class NodeFetcher {

  /**
   * The entity type manager.
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Fetch nodes matching a specific day/month.
   */
  public function getNodesByDate($day, $month) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_this_day_in_history_3', "%-$month-$day", 'LIKE')
      ->accessCheck(true);

    $nids = $query->execute();
    return $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
  }

}
