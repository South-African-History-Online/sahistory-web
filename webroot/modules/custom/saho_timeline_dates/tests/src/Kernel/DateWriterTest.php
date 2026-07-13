<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_timeline_dates\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\saho_timeline_dates\Service\DateWriter;

/**
 * Guard, idempotency and rollback tests for the date writer.
 *
 * @group saho_timeline_dates
 */
final class DateWriterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'datetime',
    'options',
    'saho_timeline',
    'saho_timeline_dates',
  ];

  /**
   * The writer under test.
   */
  protected DateWriter $writer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('saho_timeline_dates', ['saho_timeline_dates_log']);

    NodeType::create(['type' => 'event', 'name' => 'Event'])->save();

    $fields = [
      ['field_event_date', 'datetime', ['datetime_type' => 'date']],
      ['field_timeline_date', 'datetime', ['datetime_type' => 'date']],
      ['field_timeline_date_end', 'datetime', ['datetime_type' => 'date']],
      [
        'field_timeline_date_precision',
        'list_string',
        [
          'allowed_values' => [
            'day' => 'Day',
            'month' => 'Month',
            'year' => 'Year',
            'range' => 'Range',
            'circa' => 'Circa',
          ],
        ],
      ],
    ];
    foreach ($fields as [$name, $type, $settings]) {
      FieldStorageConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'type' => $type,
        'settings' => $settings,
        'cardinality' => 1,
      ])->save();
      FieldConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'bundle' => 'event',
      ])->save();
    }

    $this->writer = $this->container->get('saho_timeline_dates.writer');
  }

  /**
   * Inserts a pending log row for a node.
   */
  protected function logRow(int $nid, string $date, string $precision = 'day', float $confidence = 0.95, ?string $end = NULL): void {
    \Drupal::database()->insert('saho_timeline_dates_log')->fields([
      'nid' => $nid,
      'extracted_date' => $date,
      'date_end' => $end,
      'precision' => $precision,
      'confidence' => $confidence,
      'method' => 'test',
      'snippet' => 'evidence',
      'status' => 'pending',
      'created' => 1,
      'changed' => 1,
    ])->execute();
  }

  /**
   * A dry run reports but writes nothing, and rows stay pending.
   */
  public function testDryRunWritesNothing(): void {
    $node = Node::create(['type' => 'event', 'title' => 'Dateless', 'status' => 1]);
    $node->save();
    $this->logRow((int) $node->id(), '1900-05-01');

    $result = $this->writer->applyPending(0.8, TRUE);
    $this->assertSame(1, $result['stats']['applied']);

    $reloaded = Node::load($node->id());
    $this->assertTrue($reloaded->get('field_timeline_date')->isEmpty());
    // The log row is still pending after a dry run.
    $status = \Drupal::database()->query('SELECT status FROM {saho_timeline_dates_log} WHERE nid = :nid', [':nid' => $node->id()])->fetchField();
    $this->assertSame('pending', $status);
  }

  /**
   * Applying writes date, end and precision - never the curated field.
   */
  public function testApplyAndPrecision(): void {
    $node = Node::create(['type' => 'event', 'title' => 'Dateless', 'status' => 1]);
    $node->save();
    $this->logRow((int) $node->id(), '1899-01-01', 'range', 0.9, '1902-12-31');

    $result = $this->writer->applyPending(0.8, FALSE);
    $this->assertSame(1, $result['stats']['applied']);

    $reloaded = Node::load($node->id());
    $this->assertSame('1899-01-01', $reloaded->get('field_timeline_date')->value);
    $this->assertSame('1902-12-31', $reloaded->get('field_timeline_date_end')->value);
    $this->assertSame('range', $reloaded->get('field_timeline_date_precision')->value);
    // And crucially: the curated field stays empty.
    $this->assertTrue($reloaded->get('field_event_date')->isEmpty());
  }

  /**
   * Nodes with a curated date are skipped entirely.
   */
  public function testCuratedDateIsNeverTouched(): void {
    $node = Node::create([
      'type' => 'event',
      'title' => 'Curated',
      'status' => 1,
      'field_event_date' => '1960-03-21',
    ]);
    $node->save();
    $this->logRow((int) $node->id(), '1961-01-01');

    $result = $this->writer->applyPending(0.8, FALSE);
    $this->assertSame(0, $result['stats']['applied']);
    $this->assertSame(1, $result['stats']['skipped_curated']);

    $reloaded = Node::load($node->id());
    $this->assertSame('1960-03-21', $reloaded->get('field_event_date')->value);
    $this->assertTrue($reloaded->get('field_timeline_date')->isEmpty());
  }

  /**
   * An existing extracted date is never overwritten.
   */
  public function testExistingExtractedDateIsNotOverwritten(): void {
    $node = Node::create([
      'type' => 'event',
      'title' => 'Already extracted',
      'status' => 1,
      'field_timeline_date' => '1850-01-01',
    ]);
    $node->save();
    $this->logRow((int) $node->id(), '1851-01-01');

    $result = $this->writer->applyPending(0.8, FALSE);
    $this->assertSame(0, $result['stats']['applied']);
    $this->assertSame(1, $result['stats']['skipped_existing']);
    $this->assertSame('1850-01-01', Node::load($node->id())->get('field_timeline_date')->value);
  }

  /**
   * Rows below the confidence floor are not even considered.
   */
  public function testConfidenceFloor(): void {
    $node = Node::create(['type' => 'event', 'title' => 'Weak candidate', 'status' => 1]);
    $node->save();
    $this->logRow((int) $node->id(), '1900-01-01', 'year', 0.6);

    $result = $this->writer->applyPending(0.85, FALSE);
    $this->assertSame(0, $result['stats']['considered']);
    $this->assertTrue(Node::load($node->id())->get('field_timeline_date')->isEmpty());
  }

  /**
   * Rollback clears exactly what a batch wrote.
   */
  public function testRollbackClearsExactlyWhatWasWritten(): void {
    $applied = Node::create(['type' => 'event', 'title' => 'To roll back', 'status' => 1]);
    $applied->save();
    $this->logRow((int) $applied->id(), '1900-05-01');

    $result = $this->writer->applyPending(0.8, FALSE, 'batch-test');
    $this->assertSame(1, $result['stats']['applied']);
    $this->assertSame('1900-05-01', Node::load($applied->id())->get('field_timeline_date')->value);

    // A hand-edited node must survive the rollback.
    $edited = Node::load($applied->id());
    $stats = $this->writer->rollback('batch-test', FALSE);
    $this->assertSame(1, $stats['cleared']);
    $this->assertTrue(Node::load($applied->id())->get('field_timeline_date')->isEmpty());
    $this->assertTrue(Node::load($applied->id())->get('field_timeline_date_precision')->isEmpty());
  }

  /**
   * Rollback leaves hand-edited values alone.
   */
  public function testRollbackSkipsHandEditedNodes(): void {
    $node = Node::create(['type' => 'event', 'title' => 'Hand edited later', 'status' => 1]);
    $node->save();
    $this->logRow((int) $node->id(), '1900-05-01');
    $this->writer->applyPending(0.8, FALSE, 'batch-edit');

    // An editor corrects the extracted date by hand.
    $edited = Node::load($node->id());
    $edited->set('field_timeline_date', '1901-06-02');
    $edited->save();

    $stats = $this->writer->rollback('batch-edit', FALSE);
    $this->assertSame(0, $stats['cleared']);
    $this->assertSame(1, $stats['skipped_changed']);
    $this->assertSame('1901-06-02', Node::load($node->id())->get('field_timeline_date')->value);
  }

  /**
   * The TDIH invariant survives a full apply + rollback cycle.
   *
   * Day/month matching on field_event_date must return identical results
   * before and after the backfill runs.
   */
  public function testTdihQueryUnchangedByBackfill(): void {
    // TDIH-visible fixture: curated dates whose day/month matter.
    $curated = [];
    foreach (['1960-03-21', '1976-06-16', '1994-03-21'] as $date) {
      $node = Node::create([
        'type' => 'event',
        'title' => "Curated $date",
        'status' => 1,
        'field_event_date' => $date,
      ]);
      $node->save();
      $curated[] = $node->id();
    }
    // Dateless nodes the backfill will touch - including a year-only
    // extraction that would fabricate a January 1st.
    $dateless = Node::create(['type' => 'event', 'title' => 'Dateless', 'status' => 1]);
    $dateless->save();
    $this->logRow((int) $dateless->id(), '1961-01-01', 'year', 0.9);

    // Replicates tdih NodeFetcher's matching shape: LIKE '%-MM-DD'.
    $tdih_query = fn(string $monthday) => \Drupal::database()
      ->query("SELECT entity_id FROM {node__field_event_date} WHERE field_event_date_value LIKE :md ORDER BY entity_id", [':md' => '%-' . $monthday])
      ->fetchCol();

    $march21_before = $tdih_query('03-21');
    $jan1_before = $tdih_query('01-01');

    $result = $this->writer->applyPending(0.8, FALSE, 'batch-tdih');
    $this->assertSame(1, $result['stats']['applied']);

    $this->assertSame($march21_before, $tdih_query('03-21'));
    // The fabricated January 1st does NOT appear in TDIH's field.
    $this->assertSame($jan1_before, $tdih_query('01-01'));

    $this->writer->rollback('batch-tdih', FALSE);
    $this->assertSame($march21_before, $tdih_query('03-21'));
    $this->assertSame($jan1_before, $tdih_query('01-01'));
  }

}
