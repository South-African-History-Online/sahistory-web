<?php

declare(strict_types=1);

namespace Drupal\saho_relations\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Applies relationship edges to nodes additively and reversibly.
 *
 * Safety invariants enforced by this service:
 * - Append-only: existing references are never removed or replaced. New
 *   targets are union-merged with the current value set.
 * - Whitelisted: only the entity-reference fields in self::ALLOWED_FIELDS may
 *   be written; any other field is rejected.
 * - Validated: a target is only added when it exists, is published, and its
 *   bundle is permitted by the field's own configuration.
 * - Idempotent: a target already present on the field is skipped.
 * - Reversible: every applied edge is recorded so RollbackManager can remove
 *   exactly what this service added.
 *
 * There is intentionally no code path that clears, deletes, or overwrites a
 * field value. Deletion is impossible by construction.
 */
final class RelationWriter {

  /**
   * Entity-reference fields this writer is permitted to modify.
   *
   * Any edge targeting a field outside this list is rejected. This is the
   * primary guard against touching curated or unrelated fields.
   */
  public const ALLOWED_FIELDS = [
    'field_people_related_tab',
    'field_topics_related_tab',
    'field_organizations_related_tab',
    'field_timelines_related_tab',
    'field_media_library_related_tab',
    'field_default_article_relate_tab',
    'field_tags',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly EntityFieldManagerInterface $entityFieldManager,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Apply a batch of edges additively.
   *
   * @param array $edges
   *   A list of edge arrays, each with keys: source_nid, field, target_id and
   *   optionally confidence and target_bundle.
   * @param array $options
   *   Keys:
   *   - dry_run (bool): when TRUE nothing is saved; the result still reports
   *     exactly what would have been added. Default TRUE.
   *   - min_confidence (float): edges below this confidence are skipped.
   *   - fields (string[]|null): restrict to these fields (must be a subset of
   *     ALLOWED_FIELDS). NULL means all allowed fields.
   *
   * @return array
   *   Result with keys:
   *   - applied: list of ['nid', 'field', 'added' => int[]] rollback records.
   *   - stats: counters (nodes_touched, edges_added, skipped_existing,
   *     skipped_confidence, rejected_field, rejected_target, rejected_bundle).
   *   - rejected: list of ['edge' => array, 'reason' => string] for audit.
   */
  public function apply(array $edges, array $options = []): array {
    $dry_run = $options['dry_run'] ?? TRUE;
    $min_confidence = (float) ($options['min_confidence'] ?? 0.0);
    $only_fields = $options['fields'] ?? NULL;
    if ($only_fields !== NULL) {
      $only_fields = array_values(array_intersect($only_fields, self::ALLOWED_FIELDS));
    }

    $logger = $this->loggerFactory->get('saho_relations');
    $node_storage = $this->entityTypeManager->getStorage('node');

    $stats = [
      'nodes_touched' => 0,
      'edges_added' => 0,
      'skipped_existing' => 0,
      'skipped_confidence' => 0,
      'rejected_field' => 0,
      'rejected_target' => 0,
      'rejected_bundle' => 0,
    ];
    $applied = [];
    $rejected = [];

    // Group edges by source node so each node is loaded and saved once.
    $by_source = [];
    foreach ($edges as $edge) {
      $nid = (int) ($edge['source_nid'] ?? 0);
      if ($nid > 0) {
        $by_source[$nid][] = $edge;
      }
    }

    foreach ($by_source as $nid => $node_edges) {
      $node = $node_storage->load($nid);
      if (!$node instanceof NodeInterface) {
        foreach ($node_edges as $edge) {
          $rejected[] = ['edge' => $edge, 'reason' => 'source_missing'];
          $stats['rejected_target']++;
        }
        continue;
      }

      // Collect new target ids per field, deduplicated and validated.
      $additions = [];
      foreach ($node_edges as $edge) {
        $field = (string) ($edge['field'] ?? '');
        $target_id = (int) ($edge['target_id'] ?? 0);
        $confidence = (float) ($edge['confidence'] ?? 1.0);

        if ($confidence < $min_confidence) {
          $stats['skipped_confidence']++;
          continue;
        }
        if (!in_array($field, self::ALLOWED_FIELDS, TRUE)
          || ($only_fields !== NULL && !in_array($field, $only_fields, TRUE))) {
          $rejected[] = ['edge' => $edge, 'reason' => 'field_not_allowed'];
          $stats['rejected_field']++;
          continue;
        }
        if (!$node->hasField($field)) {
          $rejected[] = ['edge' => $edge, 'reason' => 'field_absent_on_bundle'];
          $stats['rejected_field']++;
          continue;
        }
        if ($target_id <= 0) {
          $rejected[] = ['edge' => $edge, 'reason' => 'no_target'];
          $stats['rejected_target']++;
          continue;
        }
        if (!$this->targetIsValid($node, $field, $target_id, $reason)) {
          $rejected[] = ['edge' => $edge, 'reason' => $reason];
          $stats[$reason === 'target_bundle_not_allowed' ? 'rejected_bundle' : 'rejected_target']++;
          continue;
        }
        $additions[$field][$target_id] = $target_id;
      }

      if (!$additions) {
        continue;
      }

      // Compute the union-merge per field without mutating the entity yet, so
      // a dry run has zero side effects on the static entity cache.
      $plan = [];
      foreach ($additions as $field => $candidate_ids) {
        $existing = $this->currentTargetIds($node, $field);
        $new = array_values(array_diff($candidate_ids, $existing));
        $stats['skipped_existing'] += count($candidate_ids) - count($new);
        if (!$new) {
          continue;
        }
        $plan[$field] = ['existing' => $existing, 'new' => $new];
        $stats['edges_added'] += count($new);
      }

      if (!$plan) {
        continue;
      }

      foreach ($plan as $field => $sets) {
        $applied[] = ['nid' => (int) $nid, 'field' => $field, 'added' => $sets['new']];
      }
      $stats['nodes_touched']++;

      if (!$dry_run) {
        // Append-only: keep every existing reference, add the new ones.
        foreach ($plan as $field => $sets) {
          $merged = array_merge($sets['existing'], $sets['new']);
          $node->set($field, array_map(static fn($id) => ['target_id' => $id], $merged));
        }
        $node->save();
        $logger->info('Added @count reference(s) to node @nid across @fields', [
          '@count' => array_sum(array_map(static fn($s) => count($s['new']), $plan)),
          '@nid' => $nid,
          '@fields' => implode(', ', array_keys($plan)),
        ]);
      }
    }

    return ['applied' => $applied, 'stats' => $stats, 'rejected' => $rejected];
  }

  /**
   * Read the current target ids on a field, preserving order.
   */
  protected function currentTargetIds(NodeInterface $node, string $field): array {
    $ids = [];
    foreach ($node->get($field) as $item) {
      /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
      $id = (int) $item->target_id;
      if ($id > 0) {
        $ids[] = $id;
      }
    }
    return $ids;
  }

  /**
   * Validate a target against the field's configuration.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The source node (provides bundle-specific field definitions).
   * @param string $field
   *   The field name.
   * @param int $target_id
   *   The candidate target id.
   * @param string|null $reason
   *   Set to a machine reason when validation fails.
   */
  protected function targetIsValid(NodeInterface $node, string $field, int $target_id, ?string &$reason = NULL): bool {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $node->bundle());
    $definition = $definitions[$field] ?? NULL;
    if ($definition === NULL) {
      $reason = 'field_absent_on_bundle';
      return FALSE;
    }

    $target_type = $definition->getSetting('target_type') ?: 'node';
    $handler = $definition->getSetting('handler_settings') ?: [];
    $allowed_bundles = $handler['target_bundles'] ?? NULL;

    $target = $this->entityTypeManager->getStorage($target_type)->load($target_id);
    if ($target === NULL) {
      $reason = 'target_missing';
      return FALSE;
    }
    // Only link published targets when the target supports publishing.
    if ($target instanceof NodeInterface && !$target->isPublished()) {
      $reason = 'target_unpublished';
      return FALSE;
    }
    if (is_array($allowed_bundles) && $allowed_bundles && !in_array($target->bundle(), $allowed_bundles, TRUE)) {
      $reason = 'target_bundle_not_allowed';
      return FALSE;
    }

    $reason = NULL;
    return TRUE;
  }

}
