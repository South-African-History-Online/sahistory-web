<?php

namespace Drupal\tdih\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

class NodeFetcher {

  protected $entityTypeManager;
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * Fetch nodes for TDIH.
   */
  public function getNodes() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('status', 1)
      ->condition('type', 'event')
      ->range(0, 5)
      ->accessCheck(TRUE); // Ensure access check is explicitly defined.

    $nids = $query->execute();

    // Log the fetched node IDs for debugging purposes.
    $this->logger->debug('Fetched node IDs: @nids', ['@nids' => json_encode($nids)]);

    return $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
  }
}
