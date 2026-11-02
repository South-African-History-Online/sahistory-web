<?php

declare(strict_types=1);

namespace Drupal\Tests\tdih\Unit\Plugin\Block;

use Drupal\tdih\Plugin\Block\BornTodayBlock;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the portrait-first pick for "Born on this day".
 *
 * @coversDefaultClass \Drupal\tdih\Plugin\Block\BornTodayBlock
 * @group tdih
 */
class BornTodayBlockPickTest extends UnitTestCase {

  /**
   * Builds a minimal item.
   */
  private function person(int $id, string $birth, bool $image): array {
    return ['id' => $id, 'birth_date' => $birth, 'image' => $image ? "/p/$id.webp" : ''];
  }

  /**
   * @covers ::pick
   */
  public function testPortraitsFirstThenOldestFirst(): void {
    $items = [
      $this->person(1, '1950-11-02', FALSE),
      $this->person(2, '1908-11-02', TRUE),
      $this->person(3, '1871-11-02', FALSE),
      $this->person(4, '1990-11-02', TRUE),
    ];
    $picked = BornTodayBlock::pick($items, 3);
    $this->assertCount(3, $picked);
    $ids = array_column($picked, 'id');
    $this->assertContains(2, $ids);
    $this->assertContains(4, $ids, 'Both portraits make the row.');
    $births = array_column($picked, 'birth_date');
    $sorted = $births;
    sort($sorted);
    $this->assertSame($sorted, $births, 'Row reads oldest birth first.');
  }

  /**
   * @covers ::pick
   */
  public function testWithoutPortraitPreferenceAndFewerThanLimit(): void {
    $items = [
      $this->person(1, '1950-11-02', FALSE),
      $this->person(2, '1908-11-02', TRUE),
    ];
    $picked = BornTodayBlock::pick($items, 3, FALSE);
    $this->assertSame([2, 1], array_column($picked, 'id'));
    $this->assertSame([], BornTodayBlock::pick([], 3));
  }

  /**
   * @covers ::parseBirth
   * @dataProvider birthProvider
   */
  public function testParseBirth(string $iso, string $text, ?array $expected): void {
    $this->assertSame($expected, BornTodayBlock::parseBirth($iso, $text));
  }

  /**
   * Birth-date spellings seen in the register.
   */
  public static function birthProvider(): array {
    $nov2 = ['date' => '1908-11-02', 'year' => '1908', 'month_day' => '11-02'];
    return [
      'datetime field wins' => ['1908-11-02', '1 January 1900', $nov2],
      'spaced text' => ['', '2 November 1908', $nov2],
      'zero-padded dashed text' => ['', '02-November-1908', $nov2],
      'abbreviated month' => ['', '2 Nov 1908', $nov2],
      'year only' => ['', '1932', NULL],
      'circa' => ['', 'c. 1852', NULL],
      'impossible day' => ['', '31 November 1908', NULL],
      'empty' => ['', '', NULL],
    ];
  }

}
