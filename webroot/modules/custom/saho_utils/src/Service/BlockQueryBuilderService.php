<?php

namespace Drupal\saho_utils\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Service to standardize entity query construction across SAHO blocks.
 *
 * This service provides a consistent approach to building entity queries
 * for featured content blocks throughout the SAHO project. All methods
 * support method chaining for flexible query construction.
 */
class BlockQueryBuilderService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new BlockQueryBuilderService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Build a base entity query with bundle condition.
   *
   * Creates a new entity query for the specified entity type and bundle,
   * with access checking enabled by default.
   *
   * @param string $entity_type
   *   The entity type to query (e.g., 'node', 'taxonomy_term').
   * @param string $bundle
   *   The bundle/content type to filter by (e.g., 'article', 'event').
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query object with bundle condition applied.
   */
  public function buildBaseQuery(string $entity_type, string $bundle): QueryInterface {
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
    $query->accessCheck(TRUE);

    // Apply bundle condition based on entity type.
    $bundle_key = $this->getBundleKey($entity_type);
    if ($bundle_key) {
      $query->condition($bundle_key, $bundle);
    }

    return $query;
  }

  /**
   * Add published status filter to query.
   *
   * Filters the query to only include published entities (status = 1).
   * Works for both nodes and taxonomy terms.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to modify.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The modified query object for method chaining.
   */
  public function addPublishedFilter(QueryInterface $query): QueryInterface {
    $query->condition('status', 1);
    return $query;
  }

  /**
   * Add image field filter to query.
   *
   * Filters the query to only include entities that have the specified
   * image field populated (not NULL and not empty).
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to modify.
   * @param string $field_name
   *   The machine name of the image field to check (e.g., 'field_image').
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The modified query object for method chaining.
   */
  public function addImageFilter(QueryInterface $query, string $field_name): QueryInterface {
    if (!empty($field_name)) {
      $query->condition($field_name, NULL, 'IS NOT NULL');
      $query->exists($field_name);
    }
    return $query;
  }

  /**
   * Add featured content filter to query.
   *
   * Filters for entities marked as featured. Supports multiple possible
   * field names to handle different content types that may use different
   * field names for featured status (e.g., field_featured,
   * field_home_page_feature).
   *
   * Uses OR condition group when multiple fields are provided, returning
   * entities where ANY of the specified fields is set to 1.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to modify.
   * @param array $feature_fields
   *   Array of field machine names that indicate featured status.
   *   Example: ['field_featured', 'field_home_page_feature'].
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The modified query object for method chaining.
   */
  public function addFeaturedFilter(QueryInterface $query, array $feature_fields): QueryInterface {
    if (empty($feature_fields)) {
      return $query;
    }

    // If only one field, add simple condition.
    if (count($feature_fields) === 1) {
      $query->condition(reset($feature_fields), 1);
      return $query;
    }

    // Multiple fields: create OR condition group.
    $or_group = $query->orConditionGroup();
    foreach ($feature_fields as $field_name) {
      if (!empty($field_name)) {
        $or_group->condition($field_name, 1);
      }
    }

    $query->condition($or_group);
    return $query;
  }

  /**
   * Add category/taxonomy term filter to query.
   *
   * Filters the query to only include entities tagged with the specified
   * taxonomy term. Handles NULL/empty term IDs gracefully by skipping
   * the filter.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to modify.
   * @param string $field_name
   *   The machine name of the taxonomy reference field.
   * @param mixed $term_id
   *   The taxonomy term ID to filter by. Can be NULL or empty.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The modified query object for method chaining.
   */
  public function addCategoryFilter(QueryInterface $query, string $field_name, $term_id): QueryInterface {
    if (!empty($field_name) && !empty($term_id)) {
      $query->condition($field_name, $term_id);
    }
    return $query;
  }

  /**
   * Apply result limit to query.
   *
   * Sets the maximum number of results to return. Uses the range() method
   * to limit results starting from offset 0.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to modify.
   * @param int $limit
   *   The maximum number of results to return. Must be positive.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The modified query object for method chaining.
   */
  public function applyLimit(QueryInterface $query, int $limit): QueryInterface {
    if ($limit > 0) {
      $query->range(0, $limit);
    }
    return $query;
  }

  /**
   * Get the bundle key for an entity type.
   *
   * Returns the appropriate bundle field name for different entity types.
   * This is used internally to apply bundle conditions correctly.
   *
   * @param string $entity_type
   *   The entity type ID.
   *
   * @return string|null
   *   The bundle key name, or NULL if the entity type doesn't use bundles.
   */
  protected function getBundleKey(string $entity_type): ?string {
    $bundle_keys = [
      'node' => 'type',
      'taxonomy_term' => 'vid',
      'media' => 'bundle',
      'paragraph' => 'type',
      'block_content' => 'type',
    ];

    return $bundle_keys[$entity_type] ?? NULL;
  }

}
