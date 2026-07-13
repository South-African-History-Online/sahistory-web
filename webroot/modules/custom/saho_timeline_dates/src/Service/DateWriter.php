<?php

declare(strict_types=1);

namespace Drupal\saho_timeline_dates\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Applies extracted dates to the dedicated timeline fields, reversibly.
 *
 * Safety invariants (mirroring saho_relations' RelationWriter):
 * - NEVER writes field_event_date. The curated field - and everything
 *   that consumes it, TDIH's day/month matching above all - is untouched
 *   by construction: no code path in this class references it except
 *   the guard that SKIPS nodes where it is populated.
 * - Additive-only: nodes with an existing field_timeline_date value are
 *   skipped, never overwritten.
 * - Dry-run by default.
 * - Reversible: every write is recorded in saho_timeline_dates_log with
 *   a batch id; rollback() clears exactly the values a batch wrote,
 *   verifying current field content matches the log before clearing.
 */
final class DateWriter {

  /**
   * The only fields this writer may touch.
   */
  public const ALLOWED_FIELDS = [
    'field_timeline_date',
    'field_timeline_date_end',
    'field_timeline_date_precision',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
    protected readonly TimeInterface $time,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Applies pending log rows at or above a confidence floor.
   *
   * @param float $min_confidence
   *   Confidence floor.
   * @param bool $dry_run
   *   TRUE reports without writing (default).
   * @param string|null $batch_id
   *   Marker recorded on applied rows; generated when NULL.
   * @param int $limit
   *   Max rows to process this run (0 = all).
   *
   * @return array
   *   ['batch_id', 'stats' => [...], 'applied_nids' => int[]].
   */
  public function applyPending(float $min_confidence = 0.85, bool $dry_run = TRUE, ?string $batch_id = NULL, int $limit = 0): array {
    $batch_id = $batch_id ?: 'batch-' . date('Ymd-His');
    $logger = $this->loggerFactory->get('saho_timeline_dates');
    $storage = $this->entityTypeManager->getStorage('node');

    $query = $this->database->select('saho_timeline_dates_log', 'l')
      ->fields('l')
      ->condition('status', 'pending')
      ->condition('confidence', $min_confidence, '>=')
      ->orderBy('confidence', 'DESC')
      ->orderBy('id', 'ASC');
    if ($limit > 0) {
      $query->range(0, $limit);
    }
    $rows = $query->execute()->fetchAll();

    $stats = [
      'considered' => count($rows),
      'applied' => 0,
      'skipped_curated' => 0,
      'skipped_existing' => 0,
      'skipped_missing' => 0,
      'skipped_bundle' => 0,
    ];
    $applied_nids = [];

    foreach ($rows as $row) {
      $node = $storage->load((int) $row->nid);
      if (!$node instanceof NodeInterface || !$node->isPublished()) {
        $stats['skipped_missing']++;
        continue;
      }
      if ($node->bundle() !== 'event' || !$node->hasField('field_timeline_date')) {
        $stats['skipped_bundle']++;
        continue;
      }
      // The curated date wins forever: nothing to rescue here, and the
      // COALESCE in the API would ignore us anyway.
      if ($node->hasField('field_event_date') && !$node->get('field_event_date')->isEmpty()) {
        $stats['skipped_curated']++;
        $this->markRow((int) $row->id, 'rejected', $batch_id, $dry_run);
        continue;
      }
      // Additive-only: an existing extracted date is never overwritten.
      if (!$node->get('field_timeline_date')->isEmpty()) {
        $stats['skipped_existing']++;
        $this->markRow((int) $row->id, 'rejected', $batch_id, $dry_run);
        continue;
      }

      $stats['applied']++;
      $applied_nids[] = (int) $row->nid;

      if (!$dry_run) {
        $node->set('field_timeline_date', $row->extracted_date);
        $node->set('field_timeline_date_precision', $row->precision);
        if ($row->date_end && $node->hasField('field_timeline_date_end')) {
          $node->set('field_timeline_date_end', $row->date_end);
        }
        $node->save();
        $this->markRow((int) $row->id, 'applied', $batch_id, FALSE);
        $logger->info('Applied @precision date @date to node @nid (@method, @confidence)', [
          '@precision' => $row->precision,
          '@date' => $row->extracted_date,
          '@nid' => $row->nid,
          '@method' => $row->method,
          '@confidence' => $row->confidence,
        ]);
      }
    }

    return ['batch_id' => $batch_id, 'stats' => $stats, 'applied_nids' => $applied_nids];
  }

  /**
   * Rolls back one apply-batch: clears exactly what the batch wrote.
   *
   * A node is only cleared when its current field_timeline_date still
   * equals the logged value - a hand-edited node is left alone and
   * reported.
   *
   * @return array
   *   ['cleared' => int, 'skipped_changed' => int, 'skipped_missing' => int].
   */
  public function rollback(string $batch_id, bool $dry_run = TRUE): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $rows = $this->database->select('saho_timeline_dates_log', 'l')
      ->fields('l')
      ->condition('status', 'applied')
      ->condition('batch_id', $batch_id)
      ->execute()->fetchAll();

    $stats = ['cleared' => 0, 'skipped_changed' => 0, 'skipped_missing' => 0];

    foreach ($rows as $row) {
      $node = $storage->load((int) $row->nid);
      if (!$node instanceof NodeInterface || !$node->hasField('field_timeline_date')) {
        $stats['skipped_missing']++;
        continue;
      }
      $current = $node->get('field_timeline_date')->value;
      if ($current !== $row->extracted_date) {
        $stats['skipped_changed']++;
        continue;
      }
      $stats['cleared']++;
      if (!$dry_run) {
        $node->set('field_timeline_date', NULL);
        $node->set('field_timeline_date_precision', NULL);
        if ($node->hasField('field_timeline_date_end')) {
          $node->set('field_timeline_date_end', NULL);
        }
        $node->save();
        $this->markRow((int) $row->id, 'rolled_back', $batch_id, FALSE);
      }
    }

    return $stats;
  }

  /**
   * Updates a log row's status (no-op under dry run).
   */
  protected function markRow(int $id, string $status, string $batch_id, bool $dry_run): void {
    if ($dry_run) {
      return;
    }
    $this->database->update('saho_timeline_dates_log')
      ->fields([
        'status' => $status,
        'batch_id' => $batch_id,
        'changed' => $this->time->getRequestTime(),
      ])
      ->condition('id', $id)
      ->execute();
  }

}
