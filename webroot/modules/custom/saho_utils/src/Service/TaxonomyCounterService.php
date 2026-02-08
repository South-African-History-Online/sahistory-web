<?php

declare(strict_types=1);

namespace Drupal\saho_utils\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Service for counting entities by taxonomy term reference.
 *
 * Provides reusable methods to count content (nodes, media, etc.) that
 * reference specific taxonomy terms. Used across multiple SAHO blocks
 * for displaying accurate content counts.
 */
class TaxonomyCounterService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a TaxonomyCounterService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Count entities that reference a specific taxonomy term.
   *
   * @param int $term_id
   *   The taxonomy term ID to count.
   * @param string $entity_type
   *   The entity type to count (e.g., 'node', 'media').
   * @param array $bundles
   *   Array of bundles to filter by (e.g., ['article', 'page']).
   *   If empty, all bundles are counted.
   * @param array $fields
   *   Array of field names that might reference the term.
   *   The count will include entities where ANY of these fields reference the term.
   * @param bool $published_only
   *   Whether to count only published entities. Default TRUE.
   *
   * @return int
   *   The number of entities referencing the term.
   */
  public function countByTerm(
    int $term_id,
    string $entity_type = 'node',
    array $bundles = [],
    array $fields = [],
    bool $published_only = TRUE,
  ): int {
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery()
      ->accessCheck(TRUE);

    // Filter by bundles if specified.
    if (!empty($bundles)) {
      $bundle_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('bundle');
      $query->condition($bundle_key, $bundles, 'IN');
    }

    // Filter by published status if applicable.
    if ($published_only) {
      $published_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('published');
      if ($published_key) {
        $query->condition($published_key, 1);
      }
    }

    // Add term reference conditions.
    if (!empty($fields)) {
      if (count($fields) === 1) {
        // Single field - simple condition.
        $query->condition($fields[0], $term_id);
      }
      else {
        // Multiple fields - use OR condition group.
        $or_group = $query->orConditionGroup();
        foreach ($fields as $field) {
          $or_group->condition($field, $term_id);
        }
        $query->condition($or_group);
      }
    }

    return (int) $query->count()->execute();
  }

  /**
   * Count nodes by taxonomy term across multiple content types.
   *
   * Convenience method for counting nodes specifically.
   *
   * @param int $term_id
   *   The taxonomy term ID.
   * @param array $bundles
   *   Node types to include (e.g., ['article', 'page']).
   * @param array $fields
   *   Field names that reference the term.
   *
   * @return int
   *   The count of matching nodes.
   */
  public function countNodesByTerm(int $term_id, array $bundles = ['article'], array $fields = []): int {
    return $this->countByTerm($term_id, 'node', $bundles, $fields, TRUE);
  }

  /**
   * Get counts for multiple terms at once.
   *
   * More efficient than calling countByTerm() in a loop.
   *
   * @param array $term_ids
   *   Array of taxonomy term IDs.
   * @param string $entity_type
   *   The entity type to count.
   * @param array $bundles
   *   Bundles to filter by.
   * @param array $fields
   *   Field names that reference terms.
   * @param bool $published_only
   *   Whether to count only published entities.
   *
   * @return array
   *   Associative array of term_id => count.
   */
  public function countMultipleTerms(
    array $term_ids,
    string $entity_type = 'node',
    array $bundles = [],
    array $fields = [],
    bool $published_only = TRUE,
  ): array {
    $counts = array_fill_keys($term_ids, 0);

    if (empty($fields)) {
      return $counts;
    }

    $storage = $this->entityTypeManager->getStorage($entity_type);
    $bundle_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('bundle');
    $published_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('published');

    // Build base query conditions.
    $base_conditions = [];
    if (!empty($bundles)) {
      $base_conditions[$bundle_key] = $bundles;
    }
    if ($published_only && $published_key) {
      $base_conditions[$published_key] = 1;
    }

    // Count for each field separately.
    foreach ($fields as $field) {
      $query = $storage->getQuery()->accessCheck(TRUE);

      foreach ($base_conditions as $key => $value) {
        $query->condition($key, $value, is_array($value) ? 'IN' : '=');
      }

      $query->condition($field, $term_ids, 'IN');

      // Group by term ID to get counts.
      $results = $query->execute();

      if (!empty($results)) {
        // Load entities to check which terms they reference.
        $entities = $storage->loadMultiple($results);
        foreach ($entities as $entity) {
          if ($entity instanceof FieldableEntityInterface && $entity->hasField($field)) {
            $referenced_terms = $entity->get($field)->getValue();
            foreach ($referenced_terms as $reference) {
              $tid = (int) $reference['target_id'];
              if (in_array($tid, $term_ids, TRUE)) {
                $counts[$tid]++;
              }
            }
          }
        }
      }
    }

    return $counts;
  }

  /**
   * Get the most recent entity for a taxonomy term.
   *
   * Useful for "featured item" displays in blocks.
   *
   * @param int $term_id
   *   The taxonomy term ID.
   * @param string $entity_type
   *   The entity type.
   * @param array $bundles
   *   Bundles to filter by.
   * @param array $fields
   *   Field names that reference the term.
   * @param string $sort_field
   *   Field to sort by (default: 'created').
   * @param string $sort_direction
   *   Sort direction ('ASC' or 'DESC', default: 'DESC').
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The most recent entity or NULL.
   */
  public function getRecentEntity(
    int $term_id,
    string $entity_type = 'node',
    array $bundles = [],
    array $fields = [],
    string $sort_field = 'created',
    string $sort_direction = 'DESC',
  ) {
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery()
      ->accessCheck(TRUE)
      ->sort($sort_field, $sort_direction)
      ->range(0, 1);

    // Filter by bundles if specified.
    if (!empty($bundles)) {
      $bundle_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('bundle');
      $query->condition($bundle_key, $bundles, 'IN');
    }

    // Published only.
    $published_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('published');
    if ($published_key) {
      $query->condition($published_key, 1);
    }

    // Add term reference conditions.
    if (!empty($fields)) {
      if (count($fields) === 1) {
        $query->condition($fields[0], $term_id);
      }
      else {
        $or_group = $query->orConditionGroup();
        foreach ($fields as $field) {
          $or_group->condition($field, $term_id);
        }
        $query->condition($or_group);
      }
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return NULL;
    }

    return $this->entityTypeManager->getStorage($entity_type)->load(reset($ids));
  }

}
