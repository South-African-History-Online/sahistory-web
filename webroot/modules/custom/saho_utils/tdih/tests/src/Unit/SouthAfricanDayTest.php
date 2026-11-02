<?php

declare(strict_types=1);

namespace Drupal\Tests\tdih\Unit;

use Drupal\tdih\SouthAfricanDay;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the South African day clock.
 *
 * @coversDefaultClass \Drupal\tdih\SouthAfricanDay
 * @group tdih
 */
class SouthAfricanDayTest extends UnitTestCase {

  /**
   * @covers ::monthDay
   * @covers ::now
   */
  public function testMonthDayFollowsJohannesburgNotUtc(): void {
    // 2026-11-01 22:30 UTC is already 2 November in Johannesburg (UTC+2).
    $ts = (new \DateTimeImmutable('2026-11-01 22:30:00', new \DateTimeZone('UTC')))->getTimestamp();
    $this->assertSame('11-02', SouthAfricanDay::monthDay($ts));
  }

  /**
   * @covers ::secondsUntilMidnight
   */
  public function testSecondsUntilMidnight(): void {
    $ts = (new \DateTimeImmutable('2026-11-02 23:00:00', new \DateTimeZone('Africa/Johannesburg')))->getTimestamp();
    $this->assertSame(3600, SouthAfricanDay::secondsUntilMidnight($ts));

    $late = (new \DateTimeImmutable('2026-11-02 23:59:50', new \DateTimeZone('Africa/Johannesburg')))->getTimestamp();
    $this->assertSame(60, SouthAfricanDay::secondsUntilMidnight($late), 'Never below the 60 s floor.');
  }

}
