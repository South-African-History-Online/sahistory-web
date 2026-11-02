<?php

declare(strict_types=1);

namespace Drupal\tdih;

/**
 * Today, for This Day in History, is a South African calendar day.
 *
 * The site serves readers worldwide but the register turns over at
 * midnight in Johannesburg, so every TDIH block derives its month-day and
 * its cache lifetime from this one clock.
 */
final class SouthAfricanDay {

  /**
   * The IANA zone.
   */
  public const TIMEZONE = 'Africa/Johannesburg';

  /**
   * Returns today's MM-DD in South Africa.
   *
   * @param int|null $now
   *   A Unix timestamp, or NULL for the current time.
   */
  public static function monthDay(?int $now = NULL): string {
    return self::now($now)->format('m-d');
  }

  /**
   * Returns today's date in South Africa.
   *
   * @param int|null $now
   *   A Unix timestamp, or NULL for the current time.
   */
  public static function now(?int $now = NULL): \DateTimeImmutable {
    $date = new \DateTimeImmutable('now', new \DateTimeZone(self::TIMEZONE));
    return $now === NULL ? $date : $date->setTimestamp($now);
  }

  /**
   * Seconds until the next South African midnight, at least 60.
   *
   * Used as the render-cache max-age of the day-bound blocks so a page
   * cached at 23:59 does not show yesterday's events all of the next day.
   *
   * @param int|null $now
   *   A Unix timestamp, or NULL for the current time.
   */
  public static function secondsUntilMidnight(?int $now = NULL): int {
    $current = self::now($now);
    $midnight = $current->setTime(0, 0, 0)->modify('+1 day');
    return max($midnight->getTimestamp() - $current->getTimestamp(), 60);
  }

}
