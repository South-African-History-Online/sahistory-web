<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_suggested_reading\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_suggested_reading\SuggestedReadingQuery;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the cached nid-list layer of the suggested-reading query.
 *
 * The SQL itself is not under test - the test subclass substitutes
 * fixture nids for queryNids() - the caching contract is: stable cache
 * ids for equivalent conditions, exclude_nid handled outside the cache,
 * unpublished nodes filtered on hits, empty results cached, and a
 * recompute when a stale list filters down to nothing.
 *
 * @coversDefaultClass \Drupal\saho_suggested_reading\SuggestedReadingQuery
 * @group saho_suggested_reading
 */
class SuggestedReadingQueryTest extends UnitTestCase {

  /**
   * Frozen request time used across the tests.
   */
  private const REQUEST_TIME = 1_700_000_000;

  /**
   * Builds the test service with fixture nids and a node map.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache mock.
   * @param array $fixture_nids
   *   Nids queryNids() returns on every invocation.
   * @param array $node_map
   *   Available nodes keyed by nid; loadMultiple() intersects with them.
   *
   * @return \Drupal\Tests\saho_suggested_reading\Unit\TestSuggestedReadingQuery
   *   The service, with a public $queryCalls invocation log.
   */
  private function buildService(CacheBackendInterface $cache, array $fixture_nids = [], array $node_map = []): TestSuggestedReadingQuery {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturnCallback(
      static fn(array $nids) => array_intersect_key($node_map, array_flip($nids))
    );

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);

    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(self::REQUEST_TIME);

    return new TestSuggestedReadingQuery(
      $this->createMock(Connection::class),
      $entity_type_manager,
      $cache,
      $time,
      $fixture_nids,
    );
  }

  /**
   * Builds a node mock with a fixed published state.
   */
  private function mockNode(bool $published): NodeInterface {
    $node = $this->createMock(NodeInterface::class);
    $node->method('isPublished')->willReturn($published);
    return $node;
  }

  /**
   * @covers ::normalizeConditions
   * @covers ::buildCid
   */
  public function testEquivalentConditionsShareOneCacheId(): void {
    $seen_cids = [];
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturnCallback(function (string $cid) use (&$seen_cids) {
      $seen_cids[] = $cid;
      return FALSE;
    });

    $service = $this->buildService($cache);
    // Different tag order, string vs int ids, different excluded nid,
    // different key order - all the same effective query.
    $service->getEntitiesWithImages(['field_tags' => [3, 1, 2], 'exclude_nid' => 7], 4);
    $service->getEntitiesWithImages(['exclude_nid' => 9, 'field_tags' => ['2', '3', '1']], 4);

    $this->assertCount(2, $seen_cids);
    $this->assertSame($seen_cids[0], $seen_cids[1]);
    $this->assertSame(
      [['field_tags' => [1, 2, 3]], ['field_tags' => [1, 2, 3]]],
      $service->queryCalls,
      'queryNids receives normalized conditions without exclude_nid.'
    );
  }

  /**
   * @covers ::getEntitiesWithImages
   */
  public function testColdPathPrimesCacheWithFullList(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);
    $cache->expects($this->once())->method('set')->with(
      $this->stringStartsWith('saho_suggested_reading:img_nids:'),
      [11, 12],
      self::REQUEST_TIME + 21600,
      [SuggestedReadingQuery::CACHE_TAG],
    );

    $node_map = [11 => $this->mockNode(TRUE), 12 => $this->mockNode(TRUE)];
    $service = $this->buildService($cache, [11, 12], $node_map);

    $nodes = $service->getEntitiesWithImages(['field_feature_parent' => '42'], 6);
    $this->assertSame([11, 12], array_keys($nodes));
    $this->assertSame([['field_feature_parent' => 42]], $service->queryCalls);
  }

  /**
   * @covers ::getEntitiesWithImages
   * @covers ::filterNodes
   */
  public function testWarmPathFiltersExcludedAndUnpublished(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn((object) ['data' => [1, 2, 3, 4]]);
    $cache->expects($this->never())->method('set');

    $node_map = [
      1 => $this->mockNode(TRUE),
      // Nid 2 was unpublished after the list was cached.
      2 => $this->mockNode(FALSE),
      3 => $this->mockNode(TRUE),
      4 => $this->mockNode(TRUE),
    ];
    $service = $this->buildService($cache, [], $node_map);

    // Nid 3 is the page being viewed - excluded in PHP, not in the key.
    $nodes = $service->getEntitiesWithImages(['exclude_nid' => 3], 6);
    $this->assertSame([1, 4], array_keys($nodes));
    $this->assertSame([], $service->queryCalls, 'A warm hit never runs the query.');

    // A tighter caller limit slices the same cached list.
    $this->assertSame([1], array_keys($service->getEntitiesWithImages(['exclude_nid' => 3], 1)));
  }

  /**
   * @covers ::getEntitiesWithImages
   */
  public function testEmptyResultsAreCachedAndServed(): void {
    // Cold: an empty result is still written to cache...
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);
    $cache->expects($this->once())->method('set')->with(
      $this->anything(),
      [],
      self::REQUEST_TIME + 21600,
      [SuggestedReadingQuery::CACHE_TAG],
    );
    $service = $this->buildService($cache, []);
    $this->assertSame([], $service->getEntitiesWithImages([], 6));
    $this->assertCount(1, $service->queryCalls);

    // ...and a warm empty hit is a valid answer, not a recompute trigger.
    $warm_cache = $this->createMock(CacheBackendInterface::class);
    $warm_cache->method('get')->willReturn((object) ['data' => []]);
    $warm_cache->expects($this->never())->method('set');
    $warm_service = $this->buildService($warm_cache, [99]);
    $this->assertSame([], $warm_service->getEntitiesWithImages([], 6));
    $this->assertSame([], $warm_service->queryCalls);
  }

  /**
   * @covers ::getEntitiesWithImages
   */
  public function testStaleListFilteringToNothingRecomputes(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    // The cached list only holds a node that has since been unpublished.
    $cache->method('get')->willReturn((object) ['data' => [5]]);
    $cache->expects($this->once())->method('set')->with(
      $this->anything(),
      [6],
      self::REQUEST_TIME + 21600,
      [SuggestedReadingQuery::CACHE_TAG],
    );

    $node_map = [5 => $this->mockNode(FALSE), 6 => $this->mockNode(TRUE)];
    $service = $this->buildService($cache, [6], $node_map);

    $nodes = $service->getEntitiesWithImages([], 6);
    $this->assertSame([6], array_keys($nodes), 'The recompute serves fresh nodes.');
    $this->assertCount(1, $service->queryCalls, 'Exactly one recompute, not a loop.');
  }

}
