<?php

namespace Drupal\saho_statistics;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\entity_usage\EntityUsageInterface;

/**
 * Service for tracking popular taxonomy terms based on entity usage.
 */
class TermTracker {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity usage service.
   *
   * @var \Drupal\entity_usage\EntityUsageInterface
   */
  protected $entityUsage;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a TermTracker object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_usage\EntityUsageInterface $entity_usage
   *   The entity usage service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    EntityUsageInterface $entity_usage,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityUsage = $entity_usage;
    $this->cacheBackend = $cache_backend;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Gets the most popular taxonomy terms across all vocabularies.
   *
   * @param int $limit
   *   The number of terms to return.
   * @param string|null $vocabulary_id
   *   (Optional) The vocabulary ID to limit results to.
   *
   * @return array
   *   An array of term entities, sorted by popularity (usage count).
   */
  public function getPopularTerms($limit = 10, $vocabulary_id = NULL) {
    $cid = 'saho_statistics:popular_terms:' . ($vocabulary_id ?: 'all') . ':' . $limit;

    // Check if we have a cached result.
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    // Query the entity_usage table to get the most referenced terms.
    $query = $this->database->select('entity_usage', 'eu');
    $query->addField('eu', 'target_id');
    $query->condition('eu.target_type', 'taxonomy_term');
    $query->addExpression('SUM(eu.count)', 'usage_count');
    $query->groupBy('eu.target_id');
    $query->orderBy('usage_count', 'DESC');
    // Get more than we need to filter by vocabulary.
    $query->range(0, $limit * 3);
    $results = $query->execute()->fetchAllKeyed();

    if (empty($results)) {
      return [];
    }

    // Load the term storage.
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    // Load all the terms with their usage counts.
    $term_ids = array_keys($results);
    $terms = $term_storage->loadMultiple($term_ids);

    // Prepare the result array with usage counts attached.
    $popular_terms = [];
    foreach ($terms as $id => $term) {
      // If vocabulary_id is specified, only include terms from that vocabulary.
      if ($vocabulary_id && $term->bundle() !== $vocabulary_id) {
        continue;
      }

      // Add usage count as a property on the term.
      $term->usage_count = $results[$id];
      $popular_terms[] = $term;

      // Break once we have enough terms.
      if (count($popular_terms) >= $limit) {
        break;
      }
    }

    // Cache the result for 1 hour.
    $this->cacheBackend->set($cid, $popular_terms, time() + 3600, ['saho_statistics', 'taxonomy']);

    return $popular_terms;
  }

  /**
   * Clears the popular terms cache.
   */
  public function clearCache() {
    $this->cacheBackend->deleteAll();
  }

  /**
   * Gets the total page views for the site.
   *
   * @return int
   *   The total number of page views.
   */
  public function getTotalPageViews() {
    // This would likely use the statistics module if installed.
    if ($this->moduleHandler->moduleExists('statistics')) {
      $query = $this->database->select('node_counter', 'nc');
      $query->addExpression('SUM(totalcount)', 'total_views');
      return $query->execute()->fetchField() ?: 0;
    }

    return 0;
  }

  /**
   * Gets the most viewed content.
   *
   * @param int $limit
   *   The number of items to return.
   *
   * @return array
   *   An array of node IDs with their view counts.
   */
  public function getMostViewedContent($limit = 10) {
    // This would use the statistics module if installed.
    if ($this->moduleHandler->moduleExists('statistics')) {
      $query = $this->database->select('node_counter', 'nc');
      $query->fields('nc', ['nid', 'totalcount']);
      $query->orderBy('totalcount', 'DESC');
      $query->range(0, $limit);
      return $query->execute()->fetchAllKeyed();
    }

    return [];
  }

  /**
   * Gets the most read content with full details.
   *
   * Returns content nodes sorted by view count with optional filtering by
   * content type and time period.
   *
   * @param int $limit
   *   The number of items to return. Default is 10.
   * @param string $time_period
   *   The time period to filter by. Options: 'all_time', 'today'.
   *   Default is 'all_time'.
   * @param array $content_types
   *   Array of content type machine names to filter by. Empty array means
   *   all types. Default is empty array.
   *
   * @return array
   *   Array of objects with node details including nid, title, type, created,
   *   view count (totalcount or daycount), and timestamp.
   */
  public function getMostReadContent($limit = 10, $time_period = 'all_time', array $content_types = []) {
    // Check if statistics module is installed.
    if (!$this->moduleHandler->moduleExists('statistics')) {
      return [];
    }

    // Build cache ID based on parameters.
    $cid = 'saho_statistics:most_read:' . $time_period . ':' . implode('_', $content_types) . ':' . $limit;

    // Check if we have a cached result.
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    // Build the query.
    $query = $this->database->select('node_counter', 'nc');
    $query->fields('nc', ['nid', 'totalcount', 'daycount', 'timestamp']);

    // Join with node_field_data to get node details.
    $query->join('node_field_data', 'nfd', 'nc.nid = nfd.nid');
    $query->fields('nfd', ['title', 'type', 'created']);

    // Only published nodes.
    $query->condition('nfd.status', 1);

    // Filter by content types if specified.
    if (!empty($content_types)) {
      $query->condition('nfd.type', $content_types, 'IN');
    }

    // Sort and filter based on time period.
    switch ($time_period) {
      case 'today':
        $query->orderBy('nc.daycount', 'DESC');
        break;

      case 'this_week':
        // Filter to nodes accessed in the last 7 days.
        $week_ago = time() - (7 * 86400);
        $query->condition('nc.timestamp', $week_ago, '>=');
        $query->orderBy('nc.totalcount', 'DESC');
        break;

      case 'all_time':
      default:
        $query->orderBy('nc.totalcount', 'DESC');
        break;
    }

    $query->range(0, $limit);
    $results = $query->execute()->fetchAll();

    // Cache the result for 1 hour.
    $this->cacheBackend->set($cid, $results, time() + 3600, ['node_list', 'node_counter']);

    return $results;
  }

}
