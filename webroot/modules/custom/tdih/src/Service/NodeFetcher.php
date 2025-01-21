<?php

namespace Drupal\tdih\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class NodeFetcher {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Fetch nodes matching a specific day and month.
   */
  public function getNodesByDate($day, $month) {
    $query = \Drupal::entityQuery('node')
      ->condition('status', 1) // Published nodes only.
      ->condition('type', 'event') // Use your content type.
      ->condition('field_this_day_in_history_3', "%-$month-$day", 'LIKE') // Match the date.
      ->condition('field_home_page_feature', 1) // Boolean field for homepage feature.
      ->accessCheck(TRUE);

    $nids = $query->execute();

    return $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
  }
}
