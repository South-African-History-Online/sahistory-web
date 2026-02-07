<?php

namespace Drupal\saho_utils\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Service for centralizing sorting logic across SAHO blocks.
 *
 * This service eliminates duplicate sorting code across blocks by providing
 * consistent, reusable sorting methods for entity queries and loaded entities.
 */
class SortingService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SortingService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Applies sorting to an entity query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query to apply sorting to.
   * @param string $sort_by
   *   The sort method. Supported values:
   *   - 'latest': Sort by created date, newest first (created DESC)
   *   - 'oldest': Sort by created date, oldest first (created ASC)
   *   - 'recently_updated': Sort by changed date, newest first (changed DESC)
   *   - 'title_asc': Sort by title alphabetically (title ASC)
   *   - 'random': Random selection (applies range + shuffle later)
   *   - 'none': No sorting applied.
   * @param string $entity_type
   *   The entity type being queried. Defaults to 'node'.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The modified query with sorting applied.
   */
  public function applySorting(QueryInterface $query, string $sort_by, string $entity_type = 'node'): QueryInterface {
    // Handle empty or 'none' sort option.
    if (empty($sort_by) || $sort_by === 'none') {
      return $query;
    }

    // Apply sorting based on the sort option.
    switch ($sort_by) {
      case 'latest':
      case 'created':
        $query->sort('created', 'DESC');
        break;

      case 'oldest':
        $query->sort('created', 'ASC');
        break;

      case 'recently_updated':
      case 'changed':
        $query->sort('changed', 'DESC');
        break;

      case 'title_asc':
      case 'title':
        $query->sort('title', 'ASC');
        break;

      case 'random':
        // For random sorting, we'll fetch more results and shuffle later.
        // The calling code should handle the shuffle after loading entities.
        // We don't apply any sorting here to allow natural ordering.
        break;

      default:
        // Invalid sort option - log a warning but continue without sorting.
        \Drupal::logger('saho_utils')->warning(
          'Invalid sort option "@sort_by" provided to SortingService::applySorting(). No sorting applied.',
          ['@sort_by' => $sort_by]
        );
        break;
    }

    return $query;
  }

  /**
   * Applies random selection with image field requirement.
   *
   * This method is commonly used for featured content blocks that need to
   * display items with images in random order.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query to apply random selection to.
   * @param string $image_field
   *   The machine name of the image field to check.
   *   (e.g., 'field_article_image').
   * @param int $limit
   *   The number of items to fetch before shuffling. Defaults to 50.
   *   A higher limit provides more variety in random selection.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The modified query with image field condition and range applied.
   *   The calling code should shuffle the results after loading.
   */
  public function applyRandomWithImages(
    QueryInterface $query,
    string $image_field,
    int $limit = 50,
  ): QueryInterface {
    // Ensure the image field is not empty.
    $query->condition($image_field, NULL, 'IS NOT NULL');

    // Fetch a larger set to shuffle later.
    // This provides better randomization than database-level random sorting.
    $query->range(0, $limit);

    return $query;
  }

  /**
   * Sorts an array of loaded entities.
   *
   * This method is useful when you need to sort entities after they've been
   * loaded, or when the sort order can't be achieved through query sorting.
   *
   * @param array $entities
   *   An array of loaded entities to sort.
   * @param string $sort_by
   *   The sort method. Supported values:
   *   - 'latest': Sort by created date, newest first
   *   - 'oldest': Sort by created date, oldest first
   *   - 'recently_updated': Sort by changed date, newest first
   *   - 'title_asc': Sort by title alphabetically
   *   - 'random': Random shuffle
   *   - 'none': No sorting (returns as-is).
   *
   * @return array
   *   The sorted array of entities. Original array keys are preserved for
   *   non-random sorting, discarded for random sorting.
   */
  public function sortLoadedEntities(array $entities, string $sort_by): array {
    // Handle empty array or 'none' sort option.
    if (empty($entities) || empty($sort_by) || $sort_by === 'none') {
      return $entities;
    }

    // Apply sorting based on the sort option.
    switch ($sort_by) {
      case 'random':
        // Shuffle the array for random order.
        // Get values to discard keys, then shuffle.
        $entities = array_values($entities);
        shuffle($entities);
        break;

      case 'latest':
      case 'created':
        // Sort by created date, newest first.
        uasort($entities, function ($a, $b) {
          if (!method_exists($a, 'getCreatedTime') || !method_exists($b, 'getCreatedTime')) {
            return 0;
          }
          return $b->getCreatedTime() <=> $a->getCreatedTime();
        });
        break;

      case 'oldest':
        // Sort by created date, oldest first.
        uasort($entities, function ($a, $b) {
          if (!method_exists($a, 'getCreatedTime') || !method_exists($b, 'getCreatedTime')) {
            return 0;
          }
          return $a->getCreatedTime() <=> $b->getCreatedTime();
        });
        break;

      case 'recently_updated':
      case 'changed':
        // Sort by changed date, newest first.
        uasort($entities, function ($a, $b) {
          if (!method_exists($a, 'getChangedTime') || !method_exists($b, 'getChangedTime')) {
            return 0;
          }
          return $b->getChangedTime() <=> $a->getChangedTime();
        });
        break;

      case 'title_asc':
      case 'title':
        // Sort by title alphabetically.
        uasort($entities, function ($a, $b) {
          if (!method_exists($a, 'label') || !method_exists($b, 'label')) {
            return 0;
          }
          return strcasecmp($a->label(), $b->label());
        });
        break;

      default:
        // Invalid sort option - log a warning but return unsorted.
        \Drupal::logger('saho_utils')->warning(
          'Invalid sort option "@sort_by" provided to SortingService::sortLoadedEntities(). No sorting applied.',
          ['@sort_by' => $sort_by]
        );
        break;
    }

    return $entities;
  }

  /**
   * Gets the standard sorting options for block configuration forms.
   *
   * This provides a consistent set of sorting options across all SAHO blocks.
   *
   * @param bool $include_random
   *   Whether to include the random option. Defaults to TRUE.
   * @param bool $include_none
   *   Whether to include the 'none' option. Defaults to TRUE.
   *
   * @return array
   *   An associative array of sorting options keyed by machine name.
   */
  public function getSortingOptions(bool $include_random = TRUE, bool $include_none = TRUE): array {
    $options = [];

    if ($include_none) {
      $options['none'] = t('No sorting (use natural order)');
    }

    if ($include_random) {
      $options['random'] = t('Random (shuffle results)');
    }

    $options['latest'] = t('Latest (most recently created)');
    $options['oldest'] = t('Oldest (least recently created)');
    $options['recently_updated'] = t('Recently Updated (most recently modified)');
    $options['title_asc'] = t('Title (alphabetical A-Z)');

    return $options;
  }

}
