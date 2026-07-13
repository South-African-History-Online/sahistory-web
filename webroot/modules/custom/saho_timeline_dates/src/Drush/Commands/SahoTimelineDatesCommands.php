<?php

declare(strict_types=1);

namespace Drupal\saho_timeline_dates\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\saho_timeline_dates\Service\DateExtractor;
use Drupal\saho_timeline_dates\Service\DateWriter;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for the timeline date-rescue pipeline.
 *
 * Order of operations: extract (fills the log, never touches nodes) ->
 * review the coverage histogram -> apply --no-dry-run at a confidence
 * floor -> rollback a batch if it was wrong.
 */
final class SahoTimelineDatesCommands extends DrushCommands {

  public function __construct(
    protected readonly DateExtractor $extractor,
    protected readonly DateWriter $writer,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('saho_timeline_dates.extractor'),
      $container->get('saho_timeline_dates.writer'),
      $container->get('entity_type.manager'),
      $container->get('database'),
    );
  }

  /**
   * Extract date candidates from all dateless events into the log.
   *
   * Reads title + body of every published event with neither a curated
   * field_event_date nor an extracted field_timeline_date and no
   * existing log row, and records the best candidate per node as
   * status=pending. Writes NOTHING to nodes.
   */
  #[CLI\Command(name: 'saho-timeline-dates:extract', aliases: ['stde'])]
  #[CLI\Option(name: 'limit', description: 'Max nodes to scan this run (0 = all).')]
  #[CLI\Option(name: 'reset', description: 'Delete pending log rows first and re-extract.')]
  public function extract(array $options = ['limit' => 0, 'reset' => FALSE]): void {
    if ($options['reset']) {
      $deleted = $this->database->delete('saho_timeline_dates_log')
        ->condition('status', 'pending')
        ->execute();
      $this->io()->text("Cleared $deleted pending rows.");
    }

    $nids = $this->datelessNids((int) $options['limit']);
    $this->io()->text(sprintf('Scanning %d dateless events...', count($nids)));

    $storage = $this->entityTypeManager->getStorage('node');
    $now = time();
    $found = 0;
    $none = 0;
    $histogram = [];

    foreach (array_chunk($nids, 100) as $chunk) {
      foreach ($storage->loadMultiple($chunk) as $node) {
        // The legacy filename is deterministic when present - it wins
        // over any prose pattern.
        $old_filename = $node->hasField('field_old_filename') ? ($node->get('field_old_filename')->value ?? NULL) : NULL;
        $candidate = $this->extractor->extractFromFilename($old_filename);
        if ($candidate === NULL) {
          $body = $node->hasField('body') ? ($node->get('body')->value ?? '') : '';
          $candidate = $this->extractor->extract($node->label() ?? '', $body);
        }
        if ($candidate === NULL) {
          $none++;
          continue;
        }
        $found++;
        $band = $candidate['confidence'] >= 0.85 ? 'high (>=0.85)'
          : ($candidate['confidence'] >= 0.6 ? 'mid (0.6-0.85)' : 'low (<0.6)');
        $histogram[$candidate['precision']][$band] = ($histogram[$candidate['precision']][$band] ?? 0) + 1;

        $this->database->insert('saho_timeline_dates_log')->fields([
          'nid' => $node->id(),
          'extracted_date' => $candidate['date'],
          'date_end' => $candidate['date_end'],
          'precision' => $candidate['precision'],
          'confidence' => $candidate['confidence'],
          'method' => $candidate['method'],
          'snippet' => mb_substr($candidate['snippet'], 0, 500),
          'status' => 'pending',
          'created' => $now,
          'changed' => $now,
        ])->execute();
      }
      $storage->resetCache($chunk);
      $this->io()->write('.');
    }
    $this->io()->newLine();

    $this->io()->success(sprintf('%d candidates recorded, %d events yielded nothing.', $found, $none));
    $rows = [];
    foreach ($histogram as $precision => $bands) {
      foreach ($bands as $band => $total) {
        $rows[] = [$precision, $band, $total];
      }
    }
    $this->io()->table(['Precision', 'Confidence', 'Count'], $rows);
    $this->io()->text('Nothing has been written to any node. Review, then: drush saho-timeline-dates:apply --min-confidence=0.85 --no-dry-run');
  }

  /**
   * Apply pending candidates to the timeline date fields.
   */
  #[CLI\Command(name: 'saho-timeline-dates:apply', aliases: ['stda'])]
  #[CLI\Option(name: 'min-confidence', description: 'Confidence floor (default 0.85).')]
  #[CLI\Option(name: 'dry-run', description: 'Report without writing (default). Use --no-dry-run to write.')]
  #[CLI\Option(name: 'limit', description: 'Max rows this run (0 = all).')]
  public function apply(array $options = ['min-confidence' => 0.85, 'dry-run' => TRUE, 'limit' => 0]): void {
    $result = $this->writer->applyPending(
      (float) $options['min-confidence'],
      (bool) $options['dry-run'],
      NULL,
      (int) $options['limit'],
    );
    $mode = $options['dry-run'] ? 'DRY RUN - nothing written' : 'APPLIED';
    $this->io()->section("$mode (batch {$result['batch_id']})");
    $this->io()->table(['Stat', 'Count'], array_map(NULL, array_keys($result['stats']), array_values($result['stats'])));
    if (!$options['dry-run'] && $result['stats']['applied'] > 0) {
      $this->io()->text("Rollback with: drush saho-timeline-dates:rollback {$result['batch_id']} --no-dry-run");
    }
  }

  /**
   * Roll back one apply-batch.
   */
  #[CLI\Command(name: 'saho-timeline-dates:rollback', aliases: ['stdr'])]
  #[CLI\Argument(name: 'batch_id', description: 'The batch marker printed by :apply.')]
  #[CLI\Option(name: 'dry-run', description: 'Report without writing (default). Use --no-dry-run to write.')]
  public function rollback(string $batch_id, array $options = ['dry-run' => TRUE]): void {
    $stats = $this->writer->rollback($batch_id, (bool) $options['dry-run']);
    $this->io()->table(['Stat', 'Count'], array_map(NULL, array_keys($stats), array_values($stats)));
  }

  /**
   * Pipeline status: log rows by status and precision.
   */
  #[CLI\Command(name: 'saho-timeline-dates:status', aliases: ['stds'])]
  public function status(): void {
    $rows = $this->database->query(
      'SELECT status, precision, COUNT(*) AS total FROM {saho_timeline_dates_log} GROUP BY status, precision ORDER BY status, precision'
    )->fetchAll();
    $this->io()->table(
      ['Status', 'Precision', 'Count'],
      array_map(static fn($row) => [$row->status, $row->precision, $row->total], $rows)
    );
    $remaining = count($this->datelessNids(0));
    $this->io()->text("Dateless events not yet in the log: $remaining");
  }

  /**
   * Published events with no date anywhere and no log row yet.
   *
   * @return int[]
   *   Node ids.
   */
  protected function datelessNids(int $limit): array {
    $query = $this->database->select('node_field_data', 'n');
    $query->leftJoin('node__field_event_date', 'fed', 'fed.entity_id = n.nid AND fed.deleted = 0');
    $query->leftJoin('node__field_timeline_date', 'ftd', 'ftd.entity_id = n.nid AND ftd.deleted = 0');
    $query->leftJoin('saho_timeline_dates_log', 'l', 'l.nid = n.nid');
    $query->fields('n', ['nid']);
    $query->condition('n.type', 'event');
    $query->condition('n.status', 1);
    $query->isNull('fed.field_event_date_value');
    $query->isNull('ftd.field_timeline_date_value');
    $query->isNull('l.id');
    $query->orderBy('n.nid');
    if ($limit > 0) {
      $query->range(0, $limit);
    }
    return array_map('intval', $query->execute()->fetchCol());
  }

}
