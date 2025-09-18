<?php

namespace Drupal\saho_featured_articles\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Service for managing statistics and most-read content.
 */
class StatisticsService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a StatisticsService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    Connection $database,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->database = $database;
    $this->logger = $logger_factory->get('saho_featured_articles');
  }

  /**
   * Get most read featured content based on statistics.
   *
   * @param array $featured_nids
   *   Array of node IDs to filter by.
   * @param int $limit
   *   Maximum number of items to return.
   *
   * @return array
   *   Array of node IDs sorted by view count.
   */
  public function getMostReadFeatured(array $featured_nids, $limit = 10) {
    $this->logger->info('Getting most read from @count featured nodes (limit: @limit)', [
      '@count' => count($featured_nids),
      '@limit' => $limit,
    ]);

    if (empty($featured_nids)) {
      $this->logger->warning('No featured node IDs provided to getMostReadFeatured');
      return [];
    }

    try {
      // Check if statistics module is enabled and table exists
      if (!$this->database->schema()->tableExists('node_counter')) {
        $this->logger->warning('Statistics table node_counter does not exist');
        return array_slice($featured_nids, 0, $limit);
      }

      $query = $this->database->select('node_counter', 'nc');
      $query->fields('nc', ['nid', 'totalcount']);
      $query->condition('nc.nid', $featured_nids, 'IN');
      $query->orderBy('nc.totalcount', 'DESC');
      $query->range(0, $limit);

      $results = $query->execute();
      $most_read_nids = [];
      $stats = [];

      foreach ($results as $record) {
        $most_read_nids[] = $record->nid;
        $stats[$record->nid] = $record->totalcount;
      }

      $this->logger->info('Found @count most read nodes with statistics', [
        '@count' => count($most_read_nids),
      ]);

      if (!empty($stats)) {
        $this->logger->debug('Top view counts: @stats', [
          '@stats' => json_encode(array_slice($stats, 0, 3)),
        ]);
      }

      return $most_read_nids;
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting most read featured content: @error', [
        '@error' => $e->getMessage(),
      ]);
      // Fallback to returning first N featured items
      return array_slice($featured_nids, 0, $limit);
    }
  }

  /**
   * Get view count for a specific node.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return int
   *   The view count, or 0 if not found.
   */
  public function getNodeViewCount($nid) {
    try {
      if (!$this->database->schema()->tableExists('node_counter')) {
        return 0;
      }

      $count = $this->database->select('node_counter', 'nc')
        ->fields('nc', ['totalcount'])
        ->condition('nc.nid', $nid)
        ->execute()
        ->fetchField();

      return $count ?: 0;
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting view count for node @nid: @error', [
        '@nid' => $nid,
        '@error' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Check if statistics module is properly configured.
   *
   * @return bool
   *   TRUE if statistics are available, FALSE otherwise.
   */
  public function isStatisticsAvailable() {
    return $this->database->schema()->tableExists('node_counter');
  }

}