<?php

declare(strict_types=1);

namespace Drupal\saho_linkfix\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Rewrites legacy links inside body HTML, guardedly and reversibly.
 *
 * Used for relative legacy links (../../bios/x.htm) that a redirect cannot
 * catch, because a relative href resolves against the current page URL rather
 * than the legacy path. Only exact href occurrences with a confident mapping
 * are touched; every other character of the body is preserved byte-for-byte.
 *
 * Guarantees, asserted by kernel tests:
 *  - It only ever replaces the specific from->to hrefs handed to it.
 *  - It snapshots the original body value before saving, for precise rollback.
 *  - It is idempotent: re-running finds nothing to replace and saves nothing.
 *  - Dry run computes the plan and writes nothing.
 */
final class BodyLinkRewriter {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Apply per-node link replacements to the body field.
   *
   * @param array $jobs
   *   List of jobs:
   *   ['nid' => int, 'field' => 'body',
   *    'replacements' => [['from' => raw href, 'to' => new href], ...]].
   * @param array $options
   *   Keys: dry_run (bool, default TRUE).
   *
   * @return array
   *   ['applied' => [snapshot,...], 'stats' => [...]].
   */
  public function apply(array $jobs, array $options = []): array {
    $dry_run = $options['dry_run'] ?? TRUE;
    $storage = $this->entityTypeManager->getStorage('node');

    $applied = [];
    $stats = [
      'nodes_considered' => 0,
      'nodes_changed' => 0,
      'links_replaced' => 0,
      'nodes_skipped' => 0,
    ];

    foreach ($jobs as $job) {
      $stats['nodes_considered']++;
      $nid = (int) ($job['nid'] ?? 0);
      $field = (string) ($job['field'] ?? 'body');
      $replacements = $job['replacements'] ?? [];
      $node = $nid ? $storage->load($nid) : NULL;
      if (!$node || !$node->hasField($field)) {
        $stats['nodes_skipped']++;
        continue;
      }

      $items = $node->get($field);
      $node_changed = FALSE;
      $node_replacements = 0;
      $snapshot_items = [];

      foreach ($items as $delta => $item) {
        /** @var \Drupal\text\Plugin\Field\FieldType\TextItemBase $item */
        $original = (string) $item->value;
        [$new_value, $count] = $this->rewrite($original, $replacements);
        if ($count > 0 && $new_value !== $original) {
          $snapshot_items[] = [
            'delta' => $delta,
            'original' => $original,
            'rewritten' => $new_value,
          ];
          if (!$dry_run) {
            $item->value = $new_value;
          }
          $node_changed = TRUE;
          $node_replacements += $count;
        }
      }

      if (!$node_changed) {
        $stats['nodes_skipped']++;
        continue;
      }

      $stats['nodes_changed']++;
      $stats['links_replaced'] += $node_replacements;
      $applied[] = [
        'nid' => $nid,
        'field' => $field,
        'items' => $snapshot_items,
      ];

      if (!$dry_run) {
        $node->setNewRevision(TRUE);
        $node->setRevisionLogMessage('saho_linkfix: rewrote ' . $node_replacements . ' legacy link(s)');
        $node->save();
      }
    }

    if (!$dry_run && $stats['nodes_changed']) {
      $this->loggerFactory->get('saho_linkfix')->info(
        'Rewrote @l legacy links across @n nodes.',
        ['@l' => $stats['links_replaced'], '@n' => $stats['nodes_changed']],
      );
    }

    return ['applied' => $applied, 'stats' => $stats];
  }

  /**
   * Restore body values changed by a previous run, if still untouched since.
   *
   * Only reverts an item whose current value still equals our rewritten value,
   * so manual edits made after the run are never clobbered.
   */
  public function revert(array $applied, array $options = []): array {
    $dry_run = $options['dry_run'] ?? TRUE;
    $storage = $this->entityTypeManager->getStorage('node');
    $stats = ['nodes' => 0, 'reverted_items' => 0, 'skipped_items' => 0];

    foreach ($applied as $record) {
      $node = $storage->load((int) ($record['nid'] ?? 0));
      $field = (string) ($record['field'] ?? 'body');
      if (!$node || !$node->hasField($field)) {
        continue;
      }
      $items = $node->get($field);
      $node_touched = FALSE;
      foreach ($record['items'] ?? [] as $snap) {
        $delta = (int) $snap['delta'];
        $item = $items->get($delta);
        if (!$item) {
          $stats['skipped_items']++;
          continue;
        }
        /** @var \Drupal\text\Plugin\Field\FieldType\TextItemBase $item */
        if ((string) $item->value === (string) $snap['rewritten']) {
          if (!$dry_run) {
            $item->value = $snap['original'];
          }
          $stats['reverted_items']++;
          $node_touched = TRUE;
        }
        else {
          $stats['skipped_items']++;
        }
      }
      if ($node_touched) {
        $stats['nodes']++;
        if (!$dry_run) {
          $node->setNewRevision(TRUE);
          $node->setRevisionLogMessage('saho_linkfix: reverted legacy link rewrite');
          $node->save();
        }
      }
    }
    return $stats;
  }

  /**
   * Replace exact legacy hrefs within an HTML string.
   *
   * Matches the href only inside double- or single-quoted attribute values, so
   * arbitrary body prose containing the same substring is never altered.
   *
   * @return array
   *   [string $new_html, int $replacements].
   */
  protected function rewrite(string $html, array $replacements): array {
    $total = 0;
    foreach ($replacements as $rep) {
      $from = (string) ($rep['from'] ?? '');
      $to = (string) ($rep['to'] ?? '');
      if ($from === '' || $to === '' || $from === $to) {
        continue;
      }
      foreach (['"', "'"] as $q) {
        $needle = 'href=' . $q . $from . $q;
        $replace = 'href=' . $q . $to . $q;
        $count = 0;
        $html = str_replace($needle, $replace, $html, $count);
        $total += $count;
      }
    }
    return [$html, $total];
  }

}
