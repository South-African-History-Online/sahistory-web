<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_frontpage\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\saho_frontpage\ArchiveCountsService;
use Drupal\saho_refs\DisplayRefService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the archive counts aggregation for the front page.
 *
 * @coversDefaultClass \Drupal\saho_frontpage\ArchiveCountsService
 * @group saho_frontpage
 */
class ArchiveCountsServiceTest extends UnitTestCase {

  /**
   * Published counts per bundle used across the tests.
   */
  private const BUNDLE_COUNTS = [
    'biography' => 10766,
    'event' => 17654,
    'place' => 1865,
    'archive' => 30277,
    'article' => 2810,
  ];

  /**
   * Records-with-sources count used across the tests.
   */
  private const SOURCES_COUNT = 19206;

  /**
   * Builds the service with a cold cache and count queries wired up.
   */
  private function buildService(): ArchiveCountsService {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturnOnConsecutiveCalls(
      ...array_values(self::BUNDLE_COUNTS)
    );

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->with(ArchiveCountsService::COUNTS_CID)->willReturn(FALSE);
    $cache->expects($this->once())->method('set')->with(
      ArchiveCountsService::COUNTS_CID,
      $this->anything(),
      $this->anything(),
      ['node_list'],
    );

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn((string) self::SOURCES_COUNT);
    $database = $this->createMock(Connection::class);
    $database->method('query')->willReturn($statement);

    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(1_700_000_000);

    $service = new ArchiveCountsService(
      $entity_type_manager,
      $cache,
      $database,
      new DisplayRefService($time),
      $this->createMock(DateFormatterInterface::class),
      $time,
    );
    // The service wraps labels in $this->t(); a unit test has no container.
    $service->setStringTranslation($this->getStringTranslationStub());
    return $service;
  }

  /**
   * @covers ::getRawCounts
   * @covers ::getCounts
   */
  public function testGetCountsAggregatesAndFormats(): void {
    $rows = $this->buildService()->getCounts();
    // Labels are t()-wrapped TranslatableMarkup; compare their string values.
    $rows = array_map(static fn(array $r): array => ['label' => (string) $r['label'], 'value' => $r['value']], $rows);
    $expected = [
      ['label' => 'Records', 'value' => '63,372'],
      ['label' => 'Biographies', 'value' => '10,766'],
      ['label' => 'Events', 'value' => '17,654'],
      ['label' => 'Places', 'value' => '1,865'],
      ['label' => 'Documents', 'value' => '30,277'],
      ['label' => 'Records with sources', 'value' => '19,206'],
    ];
    $this->assertSame($expected, $rows);
  }

  /**
   * @covers ::getBrowseTypes
   */
  public function testBrowseTypesMap(): void {
    $items = $this->buildService()->getBrowseTypes();
    $this->assertCount(6, $items);
    $this->assertSame(
      ['Biographies', 'Topics', 'Places', 'Events', 'Archive', 'Classroom'],
      array_map('strval', array_column($items, 'label')),
    );
    $this->assertSame(
      ['biography', 'topic', 'place', 'event', 'archive', 'article'],
      array_column($items, 'type'),
    );
    $this->assertSame(
      ['/biographies', '/politics-society', '/places', '/timelines', '/archives', '/classroom'],
      array_column($items, 'href'),
    );
    $this->assertSame('10,766', $items[0]['count']);
    $this->assertNull($items[5]['count'], 'Classroom has no clean bundle count.');
  }

  /**
   * @covers ::getRawCounts
   */
  public function testCountsComeFromCacheWhenWarm(): void {
    $raw = self::BUNDLE_COUNTS + ['sources' => self::SOURCES_COUNT, 'records' => 63372];

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->with(ArchiveCountsService::COUNTS_CID)
      ->willReturn((object) ['data' => $raw]);
    $cache->expects($this->never())->method('set');

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->never())->method('getStorage');

    $time = $this->createMock(TimeInterface::class);
    $service = new ArchiveCountsService(
      $entity_type_manager,
      $cache,
      $this->createMock(Connection::class),
      new DisplayRefService($time),
      $this->createMock(DateFormatterInterface::class),
      $time,
    );
    $service->setStringTranslation($this->getStringTranslationStub());
    $this->assertSame('63,372', $service->getCounts()[0]['value']);
  }

}
