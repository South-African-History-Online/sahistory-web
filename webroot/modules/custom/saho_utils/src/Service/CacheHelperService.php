<?php

declare(strict_types=1);

namespace Drupal\saho_utils\Service;

/**
 * Service for building standardized cache arrays.
 *
 * Provides consistent cache configuration across SAHO custom blocks,
 * ensuring proper cache invalidation and performance.
 */
class CacheHelperService {

  /**
   * Build standard cache array for blocks.
   *
   * @param string $block_id
   *   The block plugin ID.
   * @param array $config
   *   The block configuration array.
   * @param int $max_age
   *   Cache max age in seconds. Default is 3600 (1 hour).
   *
   * @return array
   *   Cache array with contexts, tags, and max-age.
   */
  public function buildStandardCache(string $block_id, array $config, int $max_age = 3600): array {
    return [
      'contexts' => [
        'languages:language_interface',
        'theme',
        'user.permissions',
      ],
      'tags' => [
        'block_view',
        'config:block.block.' . $block_id,
      ],
      'max-age' => $max_age,
    ];
  }

  /**
   * Build cache array for node list blocks.
   *
   * Includes proper cache tags for node lists of a specific content type.
   *
   * @param string $content_type
   *   The node content type (e.g., 'article', 'biography').
   * @param array $additional_tags
   *   Additional cache tags to include.
   * @param int $max_age
   *   Cache max age in seconds. Default is 3600 (1 hour).
   *
   * @return array
   *   Cache array optimized for node lists.
   */
  public function buildNodeListCache(
    string $content_type,
    array $additional_tags = [],
    int $max_age = 3600,
  ): array {
    $tags = [
      'node_list',
      'node_list:' . $content_type,
    ];

    // Merge additional tags.
    if (!empty($additional_tags)) {
      $tags = array_merge($tags, $additional_tags);
    }

    return [
      'contexts' => [
        'languages:language_interface',
        'theme',
      ],
      'tags' => $tags,
      'max-age' => $max_age,
    ];
  }

  /**
   * Build cache array for random selection blocks.
   *
   * Uses shorter cache time since random selections should vary.
   *
   * @param int $max_age
   *   Cache max age in seconds. Default is 300 (5 minutes).
   *
   * @return array
   *   Cache array for random content.
   */
  public function buildRandomCache(int $max_age = 300): array {
    return [
      'contexts' => [
        'languages:language_interface',
        'theme',
      ],
      'tags' => [
        'node_list',
      ],
      'max-age' => $max_age,
    ];
  }

  /**
   * Build cache array for manual override blocks.
   *
   * Includes cache tags for the specific manually-selected entity.
   *
   * @param string $entity_type
   *   The entity type (e.g., 'node', 'taxonomy_term').
   * @param int $entity_id
   *   The entity ID.
   * @param int $max_age
   *   Cache max age in seconds. Default is 3600 (1 hour).
   *
   * @return array
   *   Cache array for manual override.
   */
  public function buildManualOverrideCache(
    string $entity_type,
    int $entity_id,
    int $max_age = 3600,
  ): array {
    return [
      'contexts' => [
        'languages:language_interface',
        'theme',
      ],
      'tags' => [
        $entity_type . ':' . $entity_id,
      ],
      'max-age' => $max_age,
    ];
  }

  /**
   * Add a cache tag to an existing cache array.
   *
   * @param array $cache
   *   The existing cache array.
   * @param string $tag
   *   The cache tag to add.
   *
   * @return array
   *   Updated cache array.
   */
  public function addCacheTag(array $cache, string $tag): array {
    if (!isset($cache['tags'])) {
      $cache['tags'] = [];
    }

    if (!in_array($tag, $cache['tags'], TRUE)) {
      $cache['tags'][] = $tag;
    }

    return $cache;
  }

  /**
   * Add multiple cache tags to an existing cache array.
   *
   * @param array $cache
   *   The existing cache array.
   * @param array $tags
   *   Array of cache tags to add.
   *
   * @return array
   *   Updated cache array.
   */
  public function addCacheTags(array $cache, array $tags): array {
    if (!isset($cache['tags'])) {
      $cache['tags'] = [];
    }

    foreach ($tags as $tag) {
      if (!in_array($tag, $cache['tags'], TRUE)) {
        $cache['tags'][] = $tag;
      }
    }

    return $cache;
  }

  /**
   * Set cache max-age for an existing cache array.
   *
   * @param array $cache
   *   The existing cache array.
   * @param int $max_age
   *   Cache max age in seconds.
   *
   * @return array
   *   Updated cache array.
   */
  public function setCacheMaxAge(array $cache, int $max_age): array {
    $cache['max-age'] = $max_age;
    return $cache;
  }

  /**
   * Add a cache context to an existing cache array.
   *
   * @param array $cache
   *   The existing cache array.
   * @param string $context
   *   The cache context to add (e.g., 'user', 'url.path').
   *
   * @return array
   *   Updated cache array.
   */
  public function addCacheContext(array $cache, string $context): array {
    if (!isset($cache['contexts'])) {
      $cache['contexts'] = [];
    }

    if (!in_array($context, $cache['contexts'], TRUE)) {
      $cache['contexts'][] = $context;
    }

    return $cache;
  }

  /**
   * Merge two cache arrays.
   *
   * Combines contexts, tags, and uses the shorter max-age.
   *
   * @param array $cache1
   *   First cache array.
   * @param array $cache2
   *   Second cache array.
   *
   * @return array
   *   Merged cache array.
   */
  public function mergeCacheArrays(array $cache1, array $cache2): array {
    $merged = [
      'contexts' => array_unique(array_merge(
        $cache1['contexts'] ?? [],
        $cache2['contexts'] ?? []
      )),
      'tags' => array_unique(array_merge(
        $cache1['tags'] ?? [],
        $cache2['tags'] ?? []
      )),
      'max-age' => min(
        $cache1['max-age'] ?? 3600,
        $cache2['max-age'] ?? 3600
      ),
    ];

    return $merged;
  }

  /**
   * Build cache for time-based content (e.g., This Day in History).
   *
   * Varies cache by current date and uses midnight as max-age.
   *
   * @param string $content_type
   *   The node content type.
   *
   * @return array
   *   Cache array for time-based content.
   */
  public function buildTimeBasedCache(string $content_type): array {
    // Calculate seconds until midnight for cache expiration.
    $now = new \DateTime();
    $midnight = new \DateTime('tomorrow');
    $seconds_until_midnight = $midnight->getTimestamp() - $now->getTimestamp();

    return [
      'contexts' => [
        'languages:language_interface',
        'theme',
        'timezone',
      ],
      'tags' => [
        'node_list',
        'node_list:' . $content_type,
      ],
      'max-age' => $seconds_until_midnight,
    ];
  }

}
