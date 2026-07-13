<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_timeline\Kernel;

use Drupal\Core\Field\FieldPurger;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\saho_timeline\Controller\TimelineApiV2Controller;
use Drupal\saho_timeline\Service\TimelineEventService;

/**
 * Tests the v2 skeleton index, bucket queries and COALESCE precedence.
 *
 * @group saho_timeline
 */
final class TimelineApiV2Test extends KernelTestBase {

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
  ];

  /**
   * The timeline event service under test.
   */
  protected TimelineEventService $eventService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['saho_timeline']);

    NodeType::create(['type' => 'event', 'name' => 'Event'])->save();

    $fields = [
      ['field_event_date', 'datetime', ['datetime_type' => 'date']],
      ['field_timeline_date', 'datetime', ['datetime_type' => 'date']],
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

    $this->eventService = $this->container->get('saho_timeline.event_service');
  }

  /**
   * Creates an event node with the given date field values.
   */
  protected function createEvent(string $title, array $values = [], bool $published = TRUE): Node {
    $node = Node::create([
      'type' => 'event',
      'title' => $title,
      'status' => $published,
    ] + $values);
    $node->save();
    return $node;
  }

  /**
   * The standard fixture set shared by most tests.
   *
   * @return \Drupal\node\Entity\Node[]
   *   Nodes keyed by a short handle.
   */
  protected function createFixtures(): array {
    return [
      'curated' => $this->createEvent('Sharpeville', ['field_event_date' => '1960-03-21']),
      'extracted' => $this->createEvent('Diamonds found', [
        'field_timeline_date' => '1867-01-01',
        'field_timeline_date_precision' => 'year',
      ]),
      'both' => $this->createEvent('Soweto uprising', [
        'field_event_date' => '1976-06-16',
        'field_timeline_date' => '1900-01-01',
        'field_timeline_date_precision' => 'year',
      ]),
      'dateless' => $this->createEvent('No date at all'),
      'unpublished' => $this->createEvent('Hidden', ['field_event_date' => '1961-05-31'], FALSE),
      'early' => $this->createEvent('Dias at the Cape', ['field_event_date' => '1488-02-03']),
    ];
  }

  /**
   * Index contains only plottable events, date ascending, curated wins.
   */
  public function testIndexCoalesceAndOrdering(): void {
    $nodes = $this->createFixtures();
    $rows = $this->eventService->getTimelineIndexV2();

    $this->assertCount(4, $rows);
    $this->assertSame(
      ['1488-02-03', '1867-01-01', '1960-03-21', '1976-06-16'],
      array_map(static fn($row) => $row->date, $rows)
    );
    $this->assertSame(
      ['day', 'year', 'day', 'day'],
      array_map(static fn($row) => $row->precision, $rows)
    );

    // The node with both dates surfaces under the CURATED date, and its
    // precision is day regardless of the extracted precision value.
    $both = $rows[3];
    $this->assertSame((int) $nodes['both']->id(), $both->id);
    $this->assertSame('1976-06-16', $both->date);
    $this->assertSame('day', $both->precision);
  }

  /**
   * Bucket queries slice the same corpus by decade.
   */
  public function testBucketRows(): void {
    $nodes = $this->createFixtures();

    $pre = $this->eventService->getBucketRows('pre1500');
    $this->assertCount(1, $pre);
    $this->assertSame((int) $nodes['early']->id(), $pre[0]->id);

    $sixties = $this->eventService->getBucketRows('1960');
    $this->assertCount(1, $sixties);
    $this->assertSame('1960-03-21', $sixties[0]->date);

    $this->assertCount(1, $this->eventService->getBucketRows('1860'));
    $this->assertCount(0, $this->eventService->getBucketRows('1650'));
  }

  /**
   * Decade counts and bucket token mapping.
   */
  public function testDecadeCountsAndBucketTokens(): void {
    $this->createFixtures();

    $this->assertSame(
      ['pre1500' => 1, '1860' => 1, '1960' => 1, '1970' => 1],
      $this->eventService->getDecadeCounts()
    );

    $this->assertSame('pre1500', TimelineEventService::bucketForDate('1499-12-31'));
    $this->assertSame('1500', TimelineEventService::bucketForDate('1500-01-01'));
    $this->assertSame('2020', TimelineEventService::bucketForDate('2025-11-21'));
  }

  /**
   * The index endpoint returns columnar data with the list cache tag.
   */
  public function testIndexEndpointShapeAndCacheability(): void {
    $this->createFixtures();

    $controller = TimelineApiV2Controller::create($this->container);
    $response = $controller->index();

    $this->assertContains('node_list:event', $response->getCacheableMetadata()->getCacheTags());

    $data = json_decode((string) $response->getContent(), TRUE);
    $this->assertSame(4, $data['count']);
    $this->assertSame([1488, 1976], $data['range']);
    $this->assertSame(['d', 'y', 'd', 'd'], $data['precision']);
    $this->assertCount(4, $data['nids']);
    $this->assertCount(4, $data['titles']);
    $this->assertSame('Dias at the Cape', $data['titles'][0]);
  }

  /**
   * Queries degrade gracefully when the extracted-date tables are absent.
   */
  public function testIndexWithoutExtractedDateFields(): void {
    // Drop the extracted-date fields entirely - the service must fall back
    // to curated dates only (fresh installs before config import).
    // Deleting the last field of a storage cascades to the storage itself.
    FieldConfig::loadByName('node', 'event', 'field_timeline_date')->delete();
    FieldConfig::loadByName('node', 'event', 'field_timeline_date_precision')->delete();
    $this->container->get(FieldPurger::class)->purgeBatch(50);

    $this->createEvent('Curated only', ['field_event_date' => '1912-01-08']);

    $rows = $this->eventService->getTimelineIndexV2();
    $this->assertCount(1, $rows);
    $this->assertSame('1912-01-08', $rows[0]->date);
    $this->assertSame('day', $rows[0]->precision);
  }

}
