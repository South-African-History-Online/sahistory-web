<?php

namespace Drupal\Tests\saho_timeline\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_timeline\Service\TimelineEventService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests period bucketing and date extraction (#454).
 *
 * @coversDefaultClass \Drupal\saho_timeline\Service\TimelineEventService
 * @group saho_timeline
 */
class TimelineEventServicePeriodTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected TimelineEventService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->service = new TimelineEventService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(Connection::class),
      $this->createMock(CacheBackendInterface::class),
      $this->createMock(ConfigFactoryInterface::class),
    );
  }

  /**
   * Invokes a protected method on the service.
   */
  protected function invoke(string $method, array $args) {
    $ref = new \ReflectionMethod(TimelineEventService::class, $method);
    $ref->setAccessible(TRUE);
    return $ref->invokeArgs($this->service, $args);
  }

  /**
   * Builds a parsed pseudo-event carrying only a date string.
   */
  protected function pseudoEvent(string $date): object {
    return (object) ['title' => 'x', 'date' => $date, 'body' => ''];
  }

  /**
   * @covers ::calculatePeriod
   * @dataProvider periodProvider
   */
  public function testCalculatePeriod(string $date, string $type, string $expected): void {
    $this->assertSame($expected, $this->invoke('calculatePeriod', [$this->pseudoEvent($date), $type]));
  }

  /**
   * Period bucketing cases, including the century boundary off-by-one.
   */
  public static function periodProvider(): array {
    return [
      'decade' => ['1925-09-19', 'decade', '1920s'],
      'year' => ['1925-09-19', 'year', '1925'],
      'century plain' => ['1925-09-19', 'century', '20th Century'],
      // 1900 closes the 19th century; 1901 opens the 20th.
      'century boundary low' => ['1900-06-01', 'century', '19th Century'],
      'century boundary high' => ['1901-01-01', 'century', '20th Century'],
      'century 2000' => ['2000-12-31', 'century', '20th Century'],
      'century 2001' => ['2001-01-01', 'century', '21st Century'],
      'ordinal 1st' => ['0050-01-01', 'century', '1st Century'],
      'ordinal 2nd' => ['0150-01-01', 'century', '2nd Century'],
      'ordinal 3rd' => ['0250-01-01', 'century', '3rd Century'],
      'ordinal 11th not 11st' => ['1050-01-01', 'century', '11th Century'],
      'ordinal 12th not 12nd' => ['1150-01-01', 'century', '12th Century'],
    ];
  }

  /**
   * @covers ::calculatePeriod
   */
  public function testEmptyDateFallsBackToUnknown(): void {
    $event = (object) ['title' => 'x', 'body' => ''];
    $this->assertSame('Unknown Period', $this->invoke('calculatePeriod', [$event, 'decade']));
  }

  /**
   * Creation time buckets by formatted date, never by raw timestamp.
   *
   * A raw-timestamp substr(0, 4) once filed 2024 nodes under the 1710s.
   *
   * @covers ::getEventDate
   * @covers ::calculatePeriod
   */
  public function testCreatedTimestampIsNeverReadAsYear(): void {
    // 2024-03-06; the raw timestamp 1709683200 starts with "1709".
    $created = 1709683200;
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->willReturn(FALSE);
    $node->method('getCreatedTime')->willReturn($created);

    $this->assertSame(date('Y-m-d', $created), $this->invoke('getEventDate', [$node]));
    $this->assertSame('2020s', $this->invoke('calculatePeriod', [$node, 'decade']));
  }

}
