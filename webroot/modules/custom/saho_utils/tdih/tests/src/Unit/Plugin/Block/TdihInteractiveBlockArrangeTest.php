<?php

declare(strict_types=1);

namespace Drupal\Tests\tdih\Unit\Plugin\Block;

use Drupal\tdih\Plugin\Block\TdihInteractiveBlock;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the lead / "Also on this day" arrangement of a day's events.
 *
 * @coversDefaultClass \Drupal\tdih\Plugin\Block\TdihInteractiveBlock
 * @group tdih
 */
class TdihInteractiveBlockArrangeTest extends UnitTestCase {

  /**
   * Builds a minimal item.
   */
  private function item(int $id, int $year, bool $image = FALSE): array {
    return [
      'id' => $id,
      'title' => "Event $id",
      'event_date' => mktime(0, 0, 0, 11, 2, $year),
      'image' => $image ? "/img/$id.webp" : '',
    ];
  }

  /**
   * @covers ::arrangeDay
   */
  public function testEmptyDay(): void {
    $this->assertSame([NULL, []], TdihInteractiveBlock::arrangeDay([], []));
  }

  /**
   * @covers ::arrangeDay
   */
  public function testCuratedEventWithImageLeadsAndRestIsChronological(): void {
    $all = [
      1 => $this->item(1, 1930, TRUE),
      2 => $this->item(2, 1908, TRUE),
      3 => $this->item(3, 1962),
      4 => $this->item(4, 1985),
    ];
    $featured = [3 => $all[3], 1 => $all[1]];

    [$lead, $more] = TdihInteractiveBlock::arrangeDay($featured, $all, 4);

    $this->assertSame(1, $lead['id'], 'The curated event with an image leads, even when listed second.');
    $this->assertTrue($lead['featured']);
    $this->assertSame([2, 3, 4], array_column($more, 'id'), 'Ledger is every other event, oldest first.');
  }

  /**
   * @covers ::arrangeDay
   */
  public function testNoCuratedEventPrefersAnyImageThenFirst(): void {
    $all = [
      3 => $this->item(3, 1962),
      2 => $this->item(2, 1908, TRUE),
    ];
    [$lead, $more] = TdihInteractiveBlock::arrangeDay([], $all);
    $this->assertSame(2, $lead['id']);
    $this->assertFalse($lead['featured']);
    $this->assertSame([3], array_column($more, 'id'));

    [$lead] = TdihInteractiveBlock::arrangeDay([], [3 => $this->item(3, 1962)]);
    $this->assertSame(3, $lead['id'], 'A day with no images still has a lead.');
  }

  /**
   * @covers ::arrangeDay
   */
  public function testLedgerIsCappedAndStaysChronological(): void {
    $all = [];
    foreach (range(1, 8) as $i) {
      $all[$i] = $this->item($i, 1900 + $i);
    }
    [$lead, $more] = TdihInteractiveBlock::arrangeDay([], $all, 4);
    $this->assertSame(1, $lead['id']);
    $this->assertCount(4, $more);
    $years = array_map(static fn (array $i): int => (int) date('Y', $i['event_date']), $more);
    $sorted = $years;
    sort($sorted);
    $this->assertSame($sorted, $years);
    $this->assertNotContains(1, array_column($more, 'id'), 'The lead is never repeated in the ledger.');
  }

}
