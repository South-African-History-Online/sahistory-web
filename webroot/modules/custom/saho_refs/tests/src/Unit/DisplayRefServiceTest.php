<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_refs\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_refs\DisplayRefService;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\saho_refs\DisplayRefService
 * @group saho_refs
 */
final class DisplayRefServiceTest extends UnitTestCase {

  /**
   * The request time used across tests.
   */
  private const NOW = 1_800_000_000;

  /**
   * Builds the service with a fixed request time.
   */
  private function service(): DisplayRefService {
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn(self::NOW);
    return new DisplayRefService($time);
  }

  /**
   * Builds a node mock with no real ref field.
   */
  private function node(string $bundle, int $nid, int $created, int $changed): NodeInterface {
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->willReturn(FALSE);
    $node->method('bundle')->willReturn($bundle);
    $node->method('id')->willReturn($nid);
    $node->method('getCreatedTime')->willReturn($created);
    $node->method('getChangedTime')->willReturn($changed);
    return $node;
  }

  /**
   * @covers ::getRef
   * @covers ::prefix
   */
  public function testDerivedRef(): void {
    $s = $this->service();
    $this->assertSame('B-0085550', $s->getRef($this->node('biography', 85550, 0, 0)));
    $this->assertSame('R-0085106', $s->getRef($this->node('archive', 85106, 0, 0)));
    $this->assertSame('A-0070715', $s->getRef($this->node('article', 70715, 0, 0)));
    // Unknown bundle falls back to first letter uppercased.
    $this->assertSame('U-0000042', $s->getRef($this->node('upcomingevent', 42, 0, 0)));
  }

  /**
   * @covers ::getStatus
   * @covers ::getStatusKey
   */
  public function testStatusHeuristic(): void {
    $s = $this->service();
    $day = 86400;
    // Created 10 days ago -> New.
    $this->assertSame('New', $s->getStatus($this->node('article', 1, self::NOW - 10 * $day, self::NOW - 10 * $day)));
    // Old, changed long after creation -> Revised.
    $this->assertSame('Revised', $s->getStatus($this->node('article', 1, self::NOW - 900 * $day, self::NOW - 100 * $day)));
    // Old, barely changed -> Verified.
    $this->assertSame('Verified', $s->getStatus($this->node('article', 1, self::NOW - 900 * $day, self::NOW - 899 * $day)));
    $this->assertSame('verified', $s->getStatusKey($this->node('article', 1, self::NOW - 900 * $day, self::NOW - 899 * $day)));
  }

  /**
   * @covers ::nidFromRef
   */
  public function testNidFromRef(): void {
    $s = $this->service();
    $this->assertSame(85550, $s->nidFromRef('B-0085550'));
    $this->assertSame(42, $s->nidFromRef('U-0000042'));
    $this->assertNull($s->nidFromRef('nonsense'));
  }

}
