<?php

namespace Drupal\saho_tools\Schema;

/**
 * Normalises SAHO's legacy date shapes into schema.org-safe ISO-8601 dates.
 *
 * Machine counterpart of the theme's _saho_record_date() humaniser: the same
 * input taxonomy (ISO datetimes, zero-padded partials, "19-September-1925"
 * legacy strings, bare years) but emitting Y-m-d / Y-m / Y - or NULL, because
 * prose ("circa 1918", "early 1960s") must be OMITTED from Date properties,
 * never emitted verbatim.
 *
 * Pure static and dependency-free for unit testing.
 */
final class SchemaDates {

  /**
   * Month names to numbers for the legacy d-MonthName-Y shape.
   */
  private const MONTHS = [
    'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
    'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
    'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
  ];

  /**
   * Normalises a raw date string to an ISO-8601 (partial) date.
   *
   * @param string $value
   *   The raw stored value.
   *
   * @return string|null
   *   'Y-m-d', 'Y-m' or 'Y' - or NULL when the value is junk or prose.
   */
  public static function normalize(string $value): ?string {
    $value = trim($value);
    if ($value === '') {
      return NULL;
    }

    // ISO date, optionally with a time suffix ("2001-05-04T00:00:00" or
    // "2001-05-04 00:00:00"). Zeroed month/day segments encode partial dates.
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})([T ].*)?$/', $value, $m)) {
      $y = (int) $m[1];
      $mo = (int) $m[2];
      $d = (int) $m[3];
      if ($y < 1000 || $y > 2099) {
        return NULL;
      }
      if ($mo === 0) {
        return (string) $y;
      }
      if ($mo < 1 || $mo > 12) {
        return NULL;
      }
      if ($d === 0) {
        return sprintf('%04d-%02d', $y, $mo);
      }
      return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : NULL;
    }

    // Year-month partial ("1993-12").
    if (preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
      $y = (int) $m[1];
      $mo = (int) $m[2];
      if ($y >= 1000 && $y <= 2099 && $mo >= 1 && $mo <= 12) {
        return sprintf('%04d-%02d', $y, $mo);
      }
      return NULL;
    }

    // Legacy "19-September-1925" (also accepts "9 September 1925").
    if (preg_match('/^(\d{1,2})[- ]([a-zA-Z]+)[- ](\d{4})$/', $value, $m)) {
      $d = (int) $m[1];
      $mo = self::MONTHS[strtolower($m[2])] ?? NULL;
      $y = (int) $m[3];
      if ($mo !== NULL && $y >= 1000 && $y <= 2099 && checkdate($mo, $d, $y)) {
        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
      }
      return NULL;
    }

    // Bare year.
    if (preg_match('/^(\d{4})$/', $value, $m)) {
      $y = (int) $m[1];
      return ($y >= 1000 && $y <= 2099) ? (string) $y : NULL;
    }

    // Anything else is prose - omit rather than pollute a Date property.
    return NULL;
  }

}
