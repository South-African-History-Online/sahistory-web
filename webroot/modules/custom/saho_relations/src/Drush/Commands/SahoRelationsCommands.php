<?php

declare(strict_types=1);

namespace Drupal\saho_relations\Drush\Commands;

use Drupal\Core\Database\Connection;
use Drupal\saho_relations\Service\CandidateGenerator;
use Drupal\saho_relations\Service\CandidateScorer;
use Drupal\saho_relations\Service\EntityDictionaryBuilder;
use Drupal\saho_relations\Service\RelationWriter;
use Drupal\saho_relations\Service\RollbackManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands driving the SAHO relations enrichment pipeline.
 *
 * All commands read and write JSON/CSV artifacts on disk. Nothing is written
 * to the database except by relations:apply, which defaults to a dry run.
 */
final class SahoRelationsCommands extends DrushCommands {

  public function __construct(
    protected readonly EntityDictionaryBuilder $dictionaryBuilder,
    protected readonly CandidateGenerator $candidateGenerator,
    protected readonly CandidateScorer $candidateScorer,
    protected readonly RelationWriter $relationWriter,
    protected readonly RollbackManager $rollbackManager,
    protected readonly Connection $database,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('saho_relations.dictionary_builder'),
      $container->get('saho_relations.candidate_generator'),
      $container->get('saho_relations.candidate_scorer'),
      $container->get('saho_relations.relation_writer'),
      $container->get('saho_relations.rollback_manager'),
      $container->get('database'),
    );
  }

  /**
   * Enrich edges with node titles and split them into chunk files.
   *
   * The chunk files live in a host-visible directory so that workflow
   * subagents (which read the host filesystem) can adjudicate them. Each chunk
   * is a self-contained JSON array carrying the evidence needed to judge a link
   * without any database access.
   */
  #[CLI\Command(name: 'saho:relations-adj-export', aliases: ['srae'])]
  #[CLI\Option(name: 'in', description: 'Edges JSON path.')]
  #[CLI\Option(name: 'tiers', description: 'Comma-separated tiers to export.')]
  #[CLI\Option(name: 'chunk', description: 'Edges per chunk file.')]
  #[CLI\Option(name: 'out-dir', description: 'Output directory for chunk files.')]
  public function adjExport(
    array $options = [
      'in' => 'edges.json',
      'tiers' => 'review',
      'chunk' => '40',
      'out-dir' => '/var/www/html/webroot/sites/default/files/saho_relations_work/chunks',
    ],
  ): void {
    $edges = $this->readJson($options['in']);
    $tiers = explode(',', $options['tiers']);
    $edges = array_values(array_filter($edges, static fn($e) => in_array($e['tier'] ?? '', $tiers, TRUE)));

    // Batch-load every title we need in one query.
    $nids = [];
    foreach ($edges as $e) {
      $nids[(int) $e['source_nid']] = TRUE;
      $nids[(int) $e['target_id']] = TRUE;
    }
    $titles = $this->loadTitles(array_keys($nids));

    foreach ($edges as &$e) {
      $e['source_title'] = $titles[(int) $e['source_nid']] ?? '';
      $e['target_title'] = $titles[(int) $e['target_id']] ?? '';
    }
    unset($e);

    $dir = $options['out-dir'];
    if (!is_dir($dir)) {
      mkdir($dir, 0775, TRUE);
    }
    array_map('unlink', glob($dir . '/chunk_*.json') ?: []);

    $chunks = array_chunk($edges, max(1, (int) $options['chunk']));
    foreach ($chunks as $i => $chunk) {
      $this->writeJson(sprintf('%s/chunk_%04d.json', $dir, $i), $chunk);
    }
    $this->logger()->success(sprintf(
      'Exported %d edges into %d chunk files under %s',
      count($edges),
      count($chunks),
      $dir,
    ));
  }

  /**
   * Merge adjudication verdicts back into the edges, updating confidence/tier.
   *
   * A verdict file is a JSON array of objects keyed by source_nid+field+
   * target_id with keep (bool), confidence (float) and reason (string). Edges
   * the adjudicator rejected are dropped; kept ones take the adjudicated
   * confidence and are re-tiered.
   */
  #[CLI\Command(name: 'saho:relations-adj-merge', aliases: ['sram'])]
  #[CLI\Option(name: 'in', description: 'Original edges JSON path.')]
  #[CLI\Option(name: 'verdicts-dir', description: 'Directory of verdict JSON files.')]
  #[CLI\Option(name: 'out', description: 'Output adjudicated edges JSON path.')]
  public function adjMerge(
    array $options = [
      'in' => 'edges.json',
      'verdicts-dir' => '/var/www/html/webroot/sites/default/files/saho_relations_work/verdicts',
      'out' => 'edges_adjudicated.json',
    ],
  ): void {
    $verdicts = [];
    foreach (glob($options['verdicts-dir'] . '/*.json') ?: [] as $file) {
      foreach ($this->readJson($file) as $v) {
        $key = ($v['source_nid'] ?? '') . '|' . ($v['field'] ?? '') . '|' . ($v['target_id'] ?? '');
        $verdicts[$key] = $v;
      }
    }

    $out = [];
    $kept = 0;
    $rejected = 0;
    foreach ($this->readJson($options['in']) as $edge) {
      $key = ($edge['source_nid'] ?? '') . '|' . ($edge['field'] ?? '') . '|' . ($edge['target_id'] ?? '');
      if (!isset($verdicts[$key])) {
        // Tiers not sent for adjudication (e.g. high/low) pass through.
        $out[] = $edge;
        continue;
      }
      $v = $verdicts[$key];
      if (empty($v['keep'])) {
        $rejected++;
        continue;
      }
      $confidence = max(0.0, min(1.0, (float) ($v['confidence'] ?? $edge['confidence'] ?? 0.5)));
      $edge['confidence'] = round($confidence, 2);
      $edge['tier'] = $confidence >= 0.80 ? 'high' : ($confidence >= 0.50 ? 'review' : 'low');
      $edge['adjudicated'] = TRUE;
      $edge['adj_reason'] = $v['reason'] ?? '';
      $out[] = $edge;
      $kept++;
    }

    $this->writeJson($options['out'], $out);
    $this->logger()->success(sprintf(
      'Adjudication merged: %d kept, %d rejected, %d total edges written to %s',
      $kept,
      $rejected,
      count($out),
      $options['out'],
    ));
  }

  /**
   * Load node titles for a set of ids in one query.
   */
  protected function loadTitles(array $nids): array {
    if (!$nids) {
      return [];
    }
    $rows = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid', 'title'])
      ->condition('n.nid', $nids, 'IN')
      ->execute();
    $titles = [];
    foreach ($rows as $row) {
      $titles[(int) $row->nid] = $row->title;
    }
    return $titles;
  }

  /**
   * Score candidate edges into tiered edges with confidence.
   */
  #[CLI\Command(name: 'saho:relations-score', aliases: ['srs'])]
  #[CLI\Option(name: 'in', description: 'Candidates JSON path.')]
  #[CLI\Option(name: 'dict', description: 'Dictionary JSON path (for homonym detection).')]
  #[CLI\Option(name: 'out', description: 'Output edges JSON path.')]
  public function score(array $options = ['in' => 'candidates.json', 'dict' => 'dictionary.json', 'out' => 'edges.json']): void {
    $candidates = $this->readJson($options['in']);
    $dictionary = $this->readJson($options['dict']);
    $edges = $this->candidateScorer->score($candidates, $dictionary);
    $this->writeJson($options['out'], $edges);
    $tiers = array_count_values(array_map(static fn($e) => $e['tier'] ?? 'none', $edges));
    $this->logger()->success(sprintf(
      'Scored %d edges to %s (high: %d, review: %d, low: %d)',
      count($edges),
      $options['out'],
      $tiers['high'] ?? 0,
      $tiers['review'] ?? 0,
      $tiers['low'] ?? 0,
    ));
  }

  /**
   * Build the entity dictionary and write it to JSON.
   */
  #[CLI\Command(name: 'saho:relations-extract', aliases: ['srx'])]
  #[CLI\Option(name: 'bundles', description: 'Comma-separated target bundles.')]
  #[CLI\Option(name: 'out', description: 'Output JSON path.')]
  public function extract(array $options = ['bundles' => NULL, 'out' => 'dictionary.json']): void {
    $bundles = $options['bundles'] ? explode(',', $options['bundles']) : NULL;
    $dictionary = $this->dictionaryBuilder->build(array_filter(['bundles' => $bundles]));
    $this->writeJson($options['out'], array_values($dictionary));
    $this->logger()->success(sprintf('Wrote %d entities to %s', count($dictionary), $options['out']));
  }

  /**
   * Generate candidate edges (name-match and/or Solr MLT).
   */
  #[CLI\Command(name: 'saho:relations-candidates', aliases: ['src'])]
  #[CLI\Option(name: 'field', description: 'Target relation field.')]
  #[CLI\Option(name: 'dict', description: 'Dictionary JSON produced by extract.')]
  #[CLI\Option(name: 'signal', description: 'name_match, title_match, mlt, or both (= name_match + mlt).')]
  #[CLI\Option(name: 'source-bundles', description: 'Comma-separated source bundles to scan.')]
  #[CLI\Option(name: 'target-bundles', description: 'Comma-separated target bundles to keep.')]
  #[CLI\Option(name: 'mlt-top-k', description: 'MLT neighbours per node.')]
  #[CLI\Option(name: 'include-source-field', description: 'title_match: also scan field_source credits.')]
  #[CLI\Option(name: 'max-per-source', description: 'title_match: cap matches per source node.')]
  #[CLI\Option(name: 'out', description: 'Output JSON path.')]
  public function candidates(
    array $options = [
      'field' => 'field_people_related_tab',
      'dict' => 'dictionary.json',
      'signal' => 'name_match',
      'source-bundles' => 'article,biography,event,place',
      'target-bundles' => NULL,
      'mlt-top-k' => '8',
      'include-source-field' => TRUE,
      'max-per-source' => '5',
      'out' => 'candidates.json',
    ],
  ): void {
    $field = $options['field'];
    $source_bundles = explode(',', $options['source-bundles']);
    $target_bundles = $options['target-bundles'] ? explode(',', $options['target-bundles']) : NULL;
    $signal = $options['signal'];

    $dictionary = [];
    foreach ($this->readJson($options['dict']) as $entry) {
      if ($target_bundles === NULL || in_array($entry['bundle'], $target_bundles, TRUE)) {
        $dictionary[$entry['nid']] = $entry;
      }
    }

    $candidates = [];
    if ($signal === 'name_match' || $signal === 'both') {
      $candidates = array_merge($candidates, $this->candidateGenerator->nameMatchCandidates(
        $dictionary,
        $field,
        ['source_bundles' => $source_bundles],
      ));
    }
    if ($signal === 'title_match') {
      // Deliberately explicit, never folded into 'both': title scanning is
      // built for title-identified bundles (image) and its precision profile
      // differs from the body scan.
      $candidates = array_merge($candidates, $this->candidateGenerator->titleMatchCandidates(
        $dictionary,
        $field,
        [
          'source_bundles' => $source_bundles,
          'include_source_field' => filter_var($options['include-source-field'], FILTER_VALIDATE_BOOLEAN),
          'max_per_source' => (int) $options['max-per-source'],
        ],
      ));
    }
    if ($signal === 'mlt' || $signal === 'both') {
      $source_nids = array_keys($dictionary);
      $candidates = array_merge($candidates, $this->candidateGenerator->mltCandidates(
        $source_nids,
        $field,
        ['top_k' => (int) $options['mlt-top-k'], 'target_bundles' => $target_bundles],
      ));
    }

    $this->writeJson($options['out'], $candidates);
    $this->logger()->success(sprintf('Wrote %d candidate edges to %s', count($candidates), $options['out']));
  }

  /**
   * Write a CSV review artifact from an edges JSON file.
   */
  #[CLI\Command(name: 'saho:relations-report', aliases: ['srr'])]
  #[CLI\Option(name: 'in', description: 'Edges JSON path.')]
  #[CLI\Option(name: 'out', description: 'Output CSV path.')]
  public function report(array $options = ['in' => 'edges.json', 'out' => 'review.csv']): void {
    $edges = $this->readJson($options['in']);
    $fh = fopen($options['out'], 'w');
    fputcsv($fh, [
      'approve', 'source_nid', 'field', 'target_id', 'target_bundle',
      'confidence', 'tier', 'signal', 'evidence',
    ]);
    foreach ($edges as $e) {
      fputcsv($fh, [
        // Pre-tick high-confidence rows; reviewers clear or keep.
        (($e['tier'] ?? '') === 'high') ? 1 : 0,
        $e['source_nid'] ?? '',
        $e['field'] ?? '',
        $e['target_id'] ?? '',
        $e['target_bundle'] ?? '',
        $e['confidence'] ?? '',
        $e['tier'] ?? '',
        is_array($e['signal'] ?? NULL) ? implode('+', $e['signal']) : ($e['signal'] ?? ''),
        mb_substr((string) ($e['evidence'] ?? ''), 0, 200),
      ]);
    }
    fclose($fh);
    $this->logger()->success(sprintf('Wrote %d rows to %s', count($edges), $options['out']));
  }

  /**
   * Apply edges additively. Dry run by default.
   */
  #[CLI\Command(name: 'saho:relations-apply', aliases: ['sra'])]
  #[CLI\Option(name: 'in', description: 'Edges JSON path.')]
  #[CLI\Option(name: 'approved', description: 'Optional approved CSV to filter edges (from report).')]
  #[CLI\Option(name: 'min-confidence', description: 'Minimum confidence to apply.')]
  #[CLI\Option(name: 'fields', description: 'Comma-separated subset of allowed fields.')]
  #[CLI\Option(name: 'apply', description: 'Actually write to the database (otherwise dry run).')]
  #[CLI\Option(name: 'rollback-out', description: 'Where to write the rollback record.')]
  public function applyEdges(
    array $options = [
      'in' => 'edges.json',
      'approved' => NULL,
      'min-confidence' => '0.0',
      'fields' => NULL,
      'apply' => FALSE,
      'rollback-out' => 'rollback.json',
    ],
  ): void {
    $edges = $this->readJson($options['in']);
    if ($options['approved']) {
      $edges = $this->filterByApproved($edges, $options['approved']);
    }
    $result = $this->relationWriter->apply($edges, [
      'dry_run' => !$options['apply'],
      'min_confidence' => (float) $options['min-confidence'],
      'fields' => $options['fields'] ? explode(',', $options['fields']) : NULL,
    ]);

    $this->io()->title($options['apply'] ? 'APPLY' : 'DRY RUN');
    foreach ($result['stats'] as $k => $v) {
      $this->io()->writeln(sprintf('  %-20s %d', $k, $v));
    }

    if ($options['apply'] && $result['applied']) {
      $this->writeJson($options['rollback-out'], $result['applied']);
      $this->logger()->success(sprintf('Rollback record written to %s', $options['rollback-out']));
    }
  }

  /**
   * Revert a previous apply using its rollback record. Dry run by default.
   */
  #[CLI\Command(name: 'saho:relations-rollback', aliases: ['srb'])]
  #[CLI\Option(name: 'in', description: 'Rollback JSON path.')]
  #[CLI\Option(name: 'apply', description: 'Actually remove the added references.')]
  public function rollback(array $options = ['in' => 'rollback.json', 'apply' => FALSE]): void {
    $applied = $this->readJson($options['in']);
    $stats = $this->rollbackManager->revert($applied, ['dry_run' => !$options['apply']]);
    $this->io()->title($options['apply'] ? 'ROLLBACK' : 'ROLLBACK (DRY RUN)');
    foreach ($stats as $k => $v) {
      $this->io()->writeln(sprintf('  %-16s %d', $k, $v));
    }
  }

  /**
   * Keep only edges whose (source_nid, field, target_id) is ticked in the CSV.
   */
  protected function filterByApproved(array $edges, string $csv_path): array {
    $approved = [];
    $fh = fopen($csv_path, 'r');
    $header = fgetcsv($fh);
    $idx = array_flip($header ?: []);
    while (($row = fgetcsv($fh)) !== FALSE) {
      if ((int) ($row[$idx['approve']] ?? 0) === 1) {
        $approved[$row[$idx['source_nid']] . '|' . $row[$idx['field']] . '|' . $row[$idx['target_id']]] = TRUE;
      }
    }
    fclose($fh);
    return array_values(array_filter($edges, static function (array $e) use ($approved) {
      return isset($approved[($e['source_nid'] ?? '') . '|' . ($e['field'] ?? '') . '|' . ($e['target_id'] ?? '')]);
    }));
  }

  /**
   * Read a JSON array from disk.
   */
  protected function readJson(string $path): array {
    if (!is_file($path)) {
      throw new \RuntimeException("File not found: $path");
    }
    $data = json_decode((string) file_get_contents($path), TRUE, 512, JSON_THROW_ON_ERROR);
    return is_array($data) ? $data : [];
  }

  /**
   * Write data as pretty JSON to disk.
   */
  protected function writeJson(string $path, array $data): void {
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
  }

  /**
   * Cross-link collection siblings into field_people_related_tab.
   *
   * The richest existing graph is field_feature_parent (~46% of nodes belong to
   * a collection). Nodes sharing a parent are, by curation, related, so this
   * links each people-relation-capable record (article/biography/event/place/
   * image - the bundles carrying field_people_related_tab) to its sibling
   * biographies and articles. Reciprocal by construction. Flows through the
   * guarded, append-only, validated, reversible RelationWriter.
   *
   * SAFE-BY-DESIGN (learned the hard way):
   * - Candidates are deduplicated and capped PER NODE across all of a node's
   *   collections, so hub records never accumulate hundreds of links.
   * - Writes are batched with a per-batch entity-cache reset, and the memory +
   *   execution-time limits are lifted, so thousands of node saves complete.
   * - Applies dry-run by default and writes a rollback file on --apply.
   * - The archive bundle has no *_related_tab field, so its ~30k nodes are
   *   skipped (they surface related content via the render-time reading list).
   *
   * RUN IN A MAINTENANCE WINDOW (or via drush deploy, which enables maintenance
   * mode): it is a multi-minute bulk write and must not race live traffic.
   */
  #[CLI\Command(name: 'saho:relations-siblings', aliases: ['srsib'])]
  #[CLI\Option(name: 'apply', description: 'Write the relations (default: dry run).')]
  #[CLI\Option(name: 'cap', description: 'Max sibling links per record (TOTAL, deduped across collections).')]
  #[CLI\Option(name: 'max-collection', description: 'Skip collections larger than this (mega-buckets).')]
  #[CLI\Option(name: 'limit', description: 'Process at most N source records (0 = all). For scoped/incremental runs.')]
  #[CLI\Option(name: 'rollback-out', description: 'Where to write the rollback record.')]
  #[CLI\Usage(name: 'drush saho:relations-siblings', description: 'Dry-run the sibling enrichment.')]
  #[CLI\Usage(name: 'drush saho:relations-siblings --apply', description: 'Write the relations (maintenance window).')]
  public function siblings(
    array $options = [
      'apply' => FALSE,
      'cap' => 12,
      'max-collection' => 400,
      'limit' => 0,
      'rollback-out' => 'relations_siblings_rollback.json',
    ],
  ): void {
    ini_set('memory_limit', '-1');
    set_time_limit(0);
    $cap = max(1, (int) $options['cap']);
    $max_collection = max(2, (int) $options['max-collection']);
    $limit = max(0, (int) $options['limit']);
    $source_bundles = ['article', 'biography', 'event', 'place', 'image'];
    $target_bundles = ['biography', 'article'];

    // One pass: published parent -> [child => bundle].
    $q = $this->database->select('node__field_feature_parent', 'p');
    $q->join('node_field_data', 'n', 'n.nid = p.entity_id AND n.status = 1');
    $q->addField('p', 'field_feature_parent_target_id', 'pid');
    $q->addField('p', 'entity_id', 'nid');
    $q->addField('n', 'type', 'bundle');
    $collections = [];
    foreach ($q->execute() as $row) {
      $collections[(int) $row->pid][(int) $row->nid] = $row->bundle;
    }

    // Aggregate candidates PER SOURCE NODE, deduped across every collection the
    // node belongs to (this is the fix: the old cap was per-collection, so
    // multi-collection hubs like Mandela piled up 100+ links).
    $by_source = [];
    $skipped_big = 0;
    foreach ($collections as $children) {
      if (count($children) > $max_collection) {
        $skipped_big++;
        continue;
      }
      $targets = array_filter($children, static fn($b) => in_array($b, $target_bundles, TRUE));
      if (count($targets) < 2) {
        continue;
      }
      foreach ($children as $nid => $bundle) {
        if (!in_array($bundle, $source_bundles, TRUE)) {
          continue;
        }
        foreach ($targets as $tid => $tbundle) {
          if ($tid !== $nid) {
            // Keyed by target -> dedup across collections automatically.
            $by_source[$nid][$tid] = $tbundle;
          }
        }
      }
    }

    // Build capped edges (cap is now a real per-node total).
    $edges = [];
    $processed = 0;
    foreach ($by_source as $nid => $targets) {
      if ($limit && $processed >= $limit) {
        break;
      }
      $processed++;
      $i = 0;
      foreach ($targets as $tid => $tbundle) {
        $edges[] = [
          'source_nid' => $nid,
          'field' => 'field_people_related_tab',
          'target_id' => $tid,
          'target_bundle' => $tbundle,
        ];
        if (++$i >= $cap) {
          break;
        }
      }
    }

    $this->logger()->notice('Built {n} edges for {s} source records from {c} collections ({b} mega-collections skipped, cap {cap}/node).', [
      'n' => count($edges),
      's' => $processed,
      'c' => count($collections),
      'b' => $skipped_big,
      'cap' => $cap,
    ]);

    // Apply in batches: the writer loads/validates every target, so one call
    // over tens of thousands of edges exhausts memory. Reset the entity cache
    // between batches and accumulate rollback records.
    $by_source_edges = [];
    foreach ($edges as $edge) {
      $by_source_edges[$edge['source_nid']][] = $edge;
    }
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $totals = [
      'edges_added' => 0,
      'skipped_existing' => 0,
      'rejected_field' => 0,
      'rejected_bundle' => 0,
      'rejected_target' => 0,
      'nodes_touched' => 0,
    ];
    $applied = [];
    $batch = [];
    $batch_sources = 0;
    $flush = function () use (&$batch, &$batch_sources, &$totals, &$applied, $options, $node_storage): void {
      if ($batch === []) {
        return;
      }
      $result = $this->relationWriter->apply($batch, ['dry_run' => !$options['apply']]);
      foreach ($totals as $k => $_) {
        $totals[$k] += $result['stats'][$k] ?? 0;
      }
      foreach ($result['applied'] ?? [] as $record) {
        $applied[] = $record;
      }
      $node_storage->resetCache();
      drupal_static_reset();
      gc_collect_cycles();
      $batch = [];
      $batch_sources = 0;
    };
    foreach ($by_source_edges as $source_edges) {
      foreach ($source_edges as $e) {
        $batch[] = $e;
      }
      if (++$batch_sources >= 200) {
        $flush();
      }
    }
    $flush();

    $mode = $options['apply'] ? 'APPLIED' : 'DRY RUN';
    $this->logger()->notice('{mode}: {added} added, {existing} present, {rf}/{rb}/{rt} rejected(field/bundle/target); {touched} nodes touched.', [
      'mode' => $mode,
      'added' => $totals['edges_added'],
      'existing' => $totals['skipped_existing'],
      'rf' => $totals['rejected_field'],
      'rb' => $totals['rejected_bundle'],
      'rt' => $totals['rejected_target'],
      'touched' => $totals['nodes_touched'],
    ]);
    if ($options['apply'] && $applied !== []) {
      $this->writeJson($options['rollback-out'], $applied);
      $this->logger()->success(sprintf('Rollback record (%d node-field entries) written to %s', count($applied), $options['rollback-out']));
    }
  }

}
