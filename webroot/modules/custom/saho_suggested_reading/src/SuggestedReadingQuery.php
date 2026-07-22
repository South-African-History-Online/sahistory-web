<?php

declare(strict_types=1);

namespace Drupal\saho_suggested_reading;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Cached lookup of nodes that have images, for the suggested-reading band.
 *
 * The underlying query joins ten image field tables (five bundles x two
 * fields each) behind an OR of IS NOT NULL conditions - a shape no index
 * can serve, so every execution walks thousands of rows. It fires up to
 * four times per cold full-node render, which made it the site's single
 * biggest database consumer. The nid lists are therefore cached in
 * cache.default, keyed by the normalized condition set.
 *
 * Invalidation is deliberately TTL-only: the five bundles involved are
 * written continuously (editors plus enrichment tooling), so node_list
 * tags would flush this cache as often as the render cache above it and
 * it would never be warm. A suggested-reading band tolerates hours of
 * staleness invisibly; unpublished or deleted nodes are filtered out on
 * every hit. The custom tag is an editorial kill switch:
 *
 * @code
 * drush ev '\Drupal::service("cache_tags.invalidator")
 *   ->invalidateTags(["saho_suggested_reading"]);'
 * @endcode
 */
class SuggestedReadingQuery {

  /**
   * Cache tag used as a manual kill switch for all cached lists.
   */
  public const CACHE_TAG = 'saho_suggested_reading';

  /**
   * Seconds a cached nid list stays valid.
   */
  protected const CACHE_MAX_AGE = 21600;

  /**
   * Nids fetched and cached per condition set.
   *
   * Always the band maximum (12) regardless of the caller's limit, so one
   * cache entry serves every call site and still has headroom after the
   * excluded nid and any stale unpublished nodes are dropped in PHP.
   */
  protected const FETCH_LIMIT = 12;

  /**
   * Constructs the service.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache bin.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    protected readonly Connection $database,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly CacheBackendInterface $cache,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * Returns published nodes with images matching the given conditions.
   *
   * @param array $conditions
   *   Supported keys: field_feature_parent (parent nid), field_tags
   *   (array of term ids), exclude_nid (nid to omit from the result).
   * @param int $limit
   *   Maximum number of nodes to return.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Published nodes keyed by nid, newest first, at most $limit items.
   */
  public function getEntitiesWithImages(array $conditions, int $limit): array {
    // exclude_nid is stripped from both the query and the cache key and
    // applied in PHP below - otherwise every node on the site would get
    // its own copy of an otherwise identical list. Most importantly the
    // unfiltered "popular" fallback collapses to one sitewide entry.
    $exclude_nid = (int) ($conditions['exclude_nid'] ?? 0);
    $normalized = $this->normalizeConditions($conditions);
    $cid = $this->buildCid($normalized);

    $cached = $this->cache->get($cid);
    if ($cached !== FALSE) {
      $nodes = $this->filterNodes($cached->data, $exclude_nid);
      if ($nodes !== [] || $cached->data === []) {
        return array_slice($nodes, 0, $limit, TRUE);
      }
      // A non-empty cached list that filters down to nothing means the
      // listed nodes were unpublished or deleted mid-TTL - recompute once
      // rather than serving an empty band for hours.
    }

    $nids = $this->queryNids($normalized);
    $this->cache->set(
      $cid,
      $nids,
      $this->time->getRequestTime() + static::CACHE_MAX_AGE,
      [static::CACHE_TAG]
    );
    return array_slice($this->filterNodes($nids, $exclude_nid), 0, $limit, TRUE);
  }

  /**
   * Runs the image-availability query and returns matching nids.
   *
   * Protected so tests can substitute fixture nids instead of mocking the
   * dynamic select builder.
   *
   * @param array $conditions
   *   Normalized conditions (no exclude_nid, sorted tag ids).
   *
   * @return array
   *   Node ids, newest first, at most FETCH_LIMIT items.
   */
  protected function queryNids(array $conditions): array {
    $query = $this->database->select('node_field_data', 'n');
    $query->fields('n', ['nid']);
    $query->condition('n.status', 1);
    $query->condition('n.type', ['article', 'biography', 'archive', 'place', 'event'], 'IN');

    // Join with image field tables to ensure images exist.
    $image_joins = [
      // Articles: field_article_image OR field_feature_banner.
      'article' => ['field_article_image', 'field_feature_banner'],
      // Biographies: field_bio_pic OR field_feature_banner.
      'biography' => ['field_bio_pic', 'field_feature_banner'],
      // Archives: field_archive_image OR field_image.
      'archive' => ['field_archive_image', 'field_image'],
      // Places: field_place_image OR field_feature_banner.
      'place' => ['field_place_image', 'field_feature_banner'],
      // Events: field_event_image OR field_tdih_image.
      'event' => ['field_event_image', 'field_tdih_image'],
    ];

    // Create a complex OR condition for images across all content types.
    $image_condition = $query->orConditionGroup();

    foreach ($image_joins as $bundle => $fields) {
      $bundle_condition = $query->andConditionGroup();
      $bundle_condition->condition('n.type', $bundle);

      $field_condition = $query->orConditionGroup();
      foreach ($fields as $field) {
        $alias = 'img_' . $bundle . '_' . str_replace('field_', '', $field);
        $query->leftJoin('node__' . $field, $alias, $alias . '.entity_id = n.nid AND ' . $alias . '.bundle = :bundle_' . $alias, [':bundle_' . $alias => $bundle]);
        $field_condition->isNotNull($alias . '.' . $field . '_target_id');
      }

      $bundle_condition->condition($field_condition);
      $image_condition->condition($bundle_condition);
    }

    $query->condition($image_condition);

    // Add specific field conditions.
    if (!empty($conditions['field_feature_parent'])) {
      $query->join('node__field_feature_parent', 'fp', 'fp.entity_id = n.nid');
      $query->condition('fp.field_feature_parent_target_id', $conditions['field_feature_parent']);
    }

    if (!empty($conditions['field_tags'])) {
      $query->join('node__field_tags', 'ft', 'ft.entity_id = n.nid');
      $query->condition('ft.field_tags_target_id', $conditions['field_tags'], 'IN');
    }

    // Order and limit.
    $query->orderBy('n.created', 'DESC');
    $query->range(0, static::FETCH_LIMIT);

    return array_map('intval', $query->execute()->fetchCol());
  }

  /**
   * Normalizes a conditions array into a canonical, cache-safe form.
   *
   * Equivalent inputs must produce identical cache keys: exclude_nid is
   * dropped (handled in PHP), tag ids are cast and sorted, keys are
   * ordered, and empty values are removed to match the query's own
   * empty() guards.
   *
   * @param array $conditions
   *   Raw caller conditions.
   *
   * @return array
   *   The canonical conditions.
   */
  protected function normalizeConditions(array $conditions): array {
    unset($conditions['exclude_nid']);
    if (!empty($conditions['field_tags'])) {
      $tags = array_map('intval', (array) $conditions['field_tags']);
      sort($tags, SORT_NUMERIC);
      $conditions['field_tags'] = array_values($tags);
    }
    if (!empty($conditions['field_feature_parent'])) {
      $conditions['field_feature_parent'] = (int) $conditions['field_feature_parent'];
    }
    $conditions = array_filter($conditions, static fn($value) => !empty($value));
    ksort($conditions);
    return $conditions;
  }

  /**
   * Builds the cache id for a normalized condition set.
   *
   * @param array $normalized
   *   The canonical conditions.
   *
   * @return string
   *   The cache id.
   */
  protected function buildCid(array $normalized): string {
    return 'saho_suggested_reading:img_nids:' . Crypt::hashBase64(serialize($normalized));
  }

  /**
   * Loads a nid list and drops excluded, unpublished and deleted nodes.
   *
   * Cached lists can go stale within the TTL window: deleted nodes fall
   * out of loadMultiple() by themselves, unpublished ones are filtered
   * here. Published-only is also the effective access policy - the site
   * has no node-grants modules, so no per-user check is needed on a
   * shared cache.
   *
   * @param array $nids
   *   The nid list.
   * @param int $exclude_nid
   *   A nid to omit (0 for none).
   *
   * @return \Drupal\node\NodeInterface[]
   *   Published nodes keyed by nid.
   */
  protected function filterNodes(array $nids, int $exclude_nid): array {
    $nids = array_filter($nids, static fn($nid) => (int) $nid !== $exclude_nid);
    if ($nids === []) {
      return [];
    }
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    return array_filter(
      $nodes,
      static fn($node) => $node instanceof NodeInterface && $node->isPublished()
    );
  }

}
