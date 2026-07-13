<?php

declare(strict_types=1);

namespace Drupal\saho_relations\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Reverses a RelationWriter run using its recorded rollback file.
 *
 * Removes only the target ids that the writer added, and only when they are
 * still present. References added by anyone else (or already present before
 * the run) are never touched.
 */
final class RollbackManager {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Revert applied edges.
   *
   * @param array $applied
   *   The 'applied' list produced by RelationWriter::apply(), each entry being
   *   ['nid' => int, 'field' => string, 'added' => int[]].
   * @param array $options
   *   Keys: dry_run (bool, default TRUE).
   *
   * @return array
   *   Stats with keys: nodes_touched, refs_removed, skipped_absent.
   */
  public function revert(array $applied, array $options = []): array {
    $dry_run = $options['dry_run'] ?? TRUE;
    $logger = $this->loggerFactory->get('saho_relations');
    $node_storage = $this->entityTypeManager->getStorage('node');

    $stats = ['nodes_touched' => 0, 'refs_removed' => 0, 'skipped_absent' => 0];

    // Group rollback records by node so each node saves at most once.
    $by_node = [];
    foreach ($applied as $record) {
      $nid = (int) ($record['nid'] ?? 0);
      $field = (string) ($record['field'] ?? '');
      $added = array_map('intval', $record['added'] ?? []);
      if ($nid > 0 && $field !== '' && $added) {
        foreach ($added as $id) {
          $by_node[$nid][$field][$id] = $id;
        }
      }
    }

    foreach ($by_node as $nid => $fields) {
      $node = $node_storage->load($nid);
      if (!$node instanceof NodeInterface) {
        continue;
      }
      $changed = FALSE;
      foreach ($fields as $field => $remove_ids) {
        if (!$node->hasField($field)) {
          continue;
        }
        $kept = [];
        foreach ($node->get($field) as $item) {
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
          $id = (int) $item->target_id;
          if ($id > 0 && isset($remove_ids[$id])) {
            $stats['refs_removed']++;
            $changed = TRUE;
            unset($remove_ids[$id]);
            continue;
          }
          $kept[] = $id;
        }
        $stats['skipped_absent'] += count($remove_ids);
        if ($changed) {
          $node->set($field, array_map(static fn($id) => ['target_id' => $id], $kept));
        }
      }
      if ($changed) {
        $stats['nodes_touched']++;
        if (!$dry_run) {
          $node->save();
          $logger->info('Reverted additions on node @nid', ['@nid' => $nid]);
        }
      }
    }

    return $stats;
  }

}
