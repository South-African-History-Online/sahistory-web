<?php

declare(strict_types=1);

namespace Drupal\Tests\tdih\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\node\NodeInterface;
use Drupal\tdih\Service\NodeFetcher;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the cached nid-list layer in the TDIH node fetcher.
 *
 * @coversDefaultClass \Drupal\tdih\Service\NodeFetcher
 * @group tdih
 */
class NodeFetcherTest extends UnitTestCase {

  /**
   * Frozen request time used across the tests.
   */
  private const REQUEST_TIME = 1_700_000_000;

  /**
   * Builds a node mock with a fixed published state.
   */
  private function mockNode(bool $published): NodeInterface {
    $node = $this->createMock(NodeInterface::class);
    $node->method('isPublished')->willReturn($published);
    return $node;
  }

  /**
   * Builds a time mock returning the frozen request time.
   */
  private function mockTime(): TimeInterface {
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(self::REQUEST_TIME);
    return $time;
  }

  /**
   * Builds a connection mock whose escapeLike() is a pass-through.
   */
  private function mockDatabase(): Connection {
    $database = $this->createMock(Connection::class);
    $database->method('escapeLike')->willReturnArgument(0);
    return $database;
  }

  /**
   * @covers ::loadPotentialEvents
   * @covers ::getCachedNids
   * @covers ::loadPublishedNodes
   */
  public function testColdPathQueriesAndPrimesCache(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([5 => 5, 9 => 9]);

    $published = $this->mockNode(TRUE);
    $also_published = $this->mockNode(TRUE);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->with([5, 9])
      ->willReturn([5 => $published, 9 => $also_published]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);
    $cache->expects($this->once())->method('set')->with(
      'tdih:potential_events:03-21',
      [5, 9],
      self::REQUEST_TIME + 86400,
      ['node_list:event'],
    );

    $fetcher = new NodeFetcher($entity_type_manager, $this->mockDatabase(), $cache, $this->mockTime());
    $nodes = $fetcher->loadPotentialEvents('03-21');
    $this->assertSame([5 => $published, 9 => $also_published], $nodes);
  }

  /**
   * @covers ::loadPotentialEvents
   * @covers ::getCachedNids
   * @covers ::loadPublishedNodes
   */
  public function testWarmPathSkipsQueryAndFiltersUnpublished(): void {
    $published = $this->mockNode(TRUE);
    $unpublished = $this->mockNode(FALSE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->never())->method('getQuery');
    $storage->method('loadMultiple')->with([5, 9])
      ->willReturn([5 => $published, 9 => $unpublished]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->with('tdih:potential_events:03-21')
      ->willReturn((object) ['data' => [5, 9]]);
    $cache->expects($this->never())->method('set');

    $fetcher = new NodeFetcher($entity_type_manager, $this->mockDatabase(), $cache, $this->mockTime());
    $nodes = $fetcher->loadPotentialEvents('03-21');
    $this->assertSame([5 => $published], $nodes, 'The stale unpublished nid is filtered out on hit.');
  }

  /**
   * @covers ::loadPotentialEvents
   * @covers ::loadAllBirthdayEvents
   * @covers ::getCachedNids
   */
  public function testCidsMirrorTheEffectiveQuery(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);

    $seen_cids = [];
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturnCallback(function (string $cid) use (&$seen_cids) {
      $seen_cids[] = $cid;
      return FALSE;
    });

    $fetcher = new NodeFetcher($entity_type_manager, $this->mockDatabase(), $cache, $this->mockTime());
    // NULL and an impossible date both drop the LIKE condition, so both
    // must read the unfiltered "all" entry - never a poisoned day key.
    $fetcher->loadPotentialEvents(NULL);
    $fetcher->loadPotentialEvents('99-99');
    // Feb 29 is a valid month-day (leap-year aware) and the birthday list
    // uses its own prefix so featured and unfeatured lists never mix.
    $fetcher->loadAllBirthdayEvents('02-29');

    $this->assertSame([
      'tdih:potential_events:all',
      'tdih:potential_events:all',
      'tdih:birthday_events:02-29',
    ], $seen_cids);
  }

  /**
   * @covers ::loadAllBirthdayEvents
   * @covers ::getCachedNids
   */
  public function testEmptyResultsAreCached(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->never())->method('getQuery');
    $storage->expects($this->never())->method('loadMultiple');

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);

    // A cached empty list is a valid hit - only FALSE means miss - so a
    // day without events must not re-run the full event-table scan.
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->with('tdih:birthday_events:01-01')
      ->willReturn((object) ['data' => []]);
    $cache->expects($this->never())->method('set');

    $fetcher = new NodeFetcher($entity_type_manager, $this->mockDatabase(), $cache, $this->mockTime());
    $this->assertSame([], $fetcher->loadAllBirthdayEvents('01-01'));
  }

}
