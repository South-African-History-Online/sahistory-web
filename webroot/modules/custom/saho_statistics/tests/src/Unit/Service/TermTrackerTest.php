<?php

namespace Drupal\Tests\saho_statistics\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\entity_usage\EntityUsageInterface;
use Drupal\saho_statistics\TermTracker;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the TermTracker service.
 *
 * @group saho_statistics
 * @coversDefaultClass \Drupal\saho_statistics\TermTracker
 */
class TermTrackerTest extends UnitTestCase {

  /**
   * The term tracker service.
   *
   * @var \Drupal\saho_statistics\TermTracker
   */
  protected $termTracker;

  /**
   * The database connection mock.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity usage mock.
   *
   * @var \Drupal\entity_usage\EntityUsageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityUsage;

  /**
   * The cache backend mock.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheBackend;

  /**
   * The module handler mock.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityUsage = $this->createMock(EntityUsageInterface::class);
    $this->cacheBackend = $this->createMock(CacheBackendInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->termTracker = new TermTracker(
      $this->database,
      $this->entityTypeManager,
      $this->entityUsage,
      $this->cacheBackend,
      $this->moduleHandler
    );
  }

  /**
   * @covers ::getMostReadContent
   */
  public function testGetMostReadContentWithoutStatisticsModule() {
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('statistics')
      ->willReturn(FALSE);

    $result = $this->termTracker->getMostReadContent(10, 'all_time', []);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::getMostReadContent
   */
  public function testGetMostReadContentWithCachedResults() {
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('statistics')
      ->willReturn(TRUE);

    $cached_data = [
      (object) [
        'nid' => 1,
        'title' => 'Test Article',
        'totalcount' => 100,
      ],
    ];

    $cache = (object) ['data' => $cached_data];
    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->with('saho_statistics:most_read:all_time::10')
      ->willReturn($cache);

    $result = $this->termTracker->getMostReadContent(10, 'all_time', []);

    $this->assertSame($cached_data, $result);
  }

  /**
   * @covers ::getMostReadContent
   */
  public function testGetMostReadContentAllTime() {
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('statistics')
      ->willReturn(TRUE);

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->willReturn(FALSE);

    $query = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $results = [
      (object) [
        'nid' => 1,
        'title' => 'Article 1',
        'type' => 'article',
        'created' => 1609459200,
        'totalcount' => 500,
        'daycount' => 10,
        'timestamp' => time(),
      ],
      (object) [
        'nid' => 2,
        'title' => 'Article 2',
        'type' => 'article',
        'created' => 1609545600,
        'totalcount' => 300,
        'daycount' => 5,
        'timestamp' => time(),
      ],
    ];

    $statement->expects($this->once())
      ->method('fetchAll')
      ->willReturn($results);

    $query->expects($this->exactly(2))
      ->method('fields')
      ->willReturnSelf();

    $query->expects($this->once())
      ->method('join')
      ->willReturnSelf();

    $query->expects($this->once())
      ->method('condition')
      ->with('nfd.status', 1)
      ->willReturnSelf();

    $query->expects($this->once())
      ->method('orderBy')
      ->with('nc.totalcount', 'DESC')
      ->willReturnSelf();

    $query->expects($this->once())
      ->method('range')
      ->with(0, 10)
      ->willReturnSelf();

    $query->expects($this->once())
      ->method('execute')
      ->willReturn($statement);

    $this->database->expects($this->once())
      ->method('select')
      ->with('node_counter', 'nc')
      ->willReturn($query);

    $this->cacheBackend->expects($this->once())
      ->method('set')
      ->with(
        'saho_statistics:most_read:all_time::10',
        $results,
        $this->anything(),
        ['node_list', 'node_counter']
      );

    $result = $this->termTracker->getMostReadContent(10, 'all_time', []);

    $this->assertSame($results, $result);
  }

  /**
   * @covers ::getMostReadContent
   */
  public function testGetMostReadContentToday() {
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('statistics')
      ->willReturn(TRUE);

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->willReturn(FALSE);

    $query = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $results = [
      (object) [
        'nid' => 1,
        'title' => 'Today Article',
        'type' => 'article',
        'created' => time(),
        'totalcount' => 100,
        'daycount' => 50,
        'timestamp' => time(),
      ],
    ];

    $statement->expects($this->once())
      ->method('fetchAll')
      ->willReturn($results);

    $query->expects($this->any())
      ->method('fields')
      ->willReturnSelf();

    $query->expects($this->any())
      ->method('join')
      ->willReturnSelf();

    $query->expects($this->any())
      ->method('condition')
      ->willReturnSelf();

    // Verify 'today' sorts by daycount DESC.
    $query->expects($this->once())
      ->method('orderBy')
      ->with('nc.daycount', 'DESC')
      ->willReturnSelf();

    $query->expects($this->any())
      ->method('range')
      ->willReturnSelf();

    $query->expects($this->once())
      ->method('execute')
      ->willReturn($statement);

    $this->database->expects($this->once())
      ->method('select')
      ->willReturn($query);

    $result = $this->termTracker->getMostReadContent(10, 'today', []);

    $this->assertSame($results, $result);
  }

  /**
   * @covers ::getMostReadContent
   */
  public function testGetMostReadContentWithContentTypeFilter() {
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('statistics')
      ->willReturn(TRUE);

    $this->cacheBackend->expects($this->once())
      ->method('get')
      ->willReturn(FALSE);

    $query = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $statement->expects($this->once())
      ->method('fetchAll')
      ->willReturn([]);

    $query->expects($this->any())
      ->method('fields')
      ->willReturnSelf();

    $query->expects($this->any())
      ->method('join')
      ->willReturnSelf();

    // Should call condition twice: once for status, once for type.
    $query->expects($this->exactly(2))
      ->method('condition')
      ->willReturnSelf();

    $query->expects($this->any())
      ->method('orderBy')
      ->willReturnSelf();

    $query->expects($this->any())
      ->method('range')
      ->willReturnSelf();

    $query->expects($this->once())
      ->method('execute')
      ->willReturn($statement);

    $this->database->expects($this->once())
      ->method('select')
      ->willReturn($query);

    $result = $this->termTracker->getMostReadContent(5, 'all_time', ['article', 'biography']);

    $this->assertIsArray($result);
  }

  /**
   * @covers ::clearCache
   */
  public function testClearCache() {
    $this->cacheBackend->expects($this->once())
      ->method('deleteAll');

    $this->termTracker->clearCache();
  }

  /**
   * @covers ::getTotalPageViews
   */
  public function testGetTotalPageViewsWithoutStatistics() {
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('statistics')
      ->willReturn(FALSE);

    $result = $this->termTracker->getTotalPageViews();

    $this->assertSame(0, $result);
  }

  /**
   * @covers ::getTotalPageViews
   */
  public function testGetTotalPageViewsWithStatistics() {
    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('statistics')
      ->willReturn(TRUE);

    $query = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $statement->expects($this->once())
      ->method('fetchField')
      ->willReturn(5000);

    $query->expects($this->once())
      ->method('execute')
      ->willReturn($statement);

    $this->database->expects($this->once())
      ->method('select')
      ->with('node_counter', 'nc')
      ->willReturn($query);

    $result = $this->termTracker->getTotalPageViews();

    $this->assertSame(5000, $result);
  }

}
