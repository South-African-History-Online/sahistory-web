<?php

declare(strict_types=1);

namespace Drupal\saho_timeline_dates\Service;

use Drupal\Component\Utility\Html;

/**
 * Deterministic date extraction from event title and body prose.
 *
 * A pattern ladder from most to least specific, each rung carrying a
 * fixed confidence. The house style "On 24 December 1651, ..." opening
 * the body is the strongest signal in the corpus. Every candidate
 * carries the matched snippet as reviewable evidence.
 *
 * Years are bounded to 1000..(current year + 1) everywhere - the legacy
 * extractor's unbounded (\d{4}) fallback would happily date an event
 * from "5000 people died".
 */
final class DateExtractor {

  /**
   * Month name -> number, including common abbreviations.
   */
  private const MONTHS = [
    'january' => 1, 'jan' => 1,
    'february' => 2, 'feb' => 2,
    'march' => 3, 'mar' => 3,
    'april' => 4, 'apr' => 4,
    'may' => 5,
    'june' => 6, 'jun' => 6,
    'july' => 7, 'jul' => 7,
    'august' => 8, 'aug' => 8,
    'september' => 9, 'sep' => 9, 'sept' => 9,
    'october' => 10, 'oct' => 10,
    'november' => 11, 'nov' => 11,
    'december' => 12, 'dec' => 12,
  ];

  private const MONTH_RX = '(?:january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sept|sep|oct|nov|dec)';
  private const YEAR_RX = '(1[0-9]{3}|20[0-9]{2})';

  /**
   * How much body text is considered. Dates almost always lead.
   */
  private const BODY_WINDOW = 400;

  /**
   * Extract a date from the legacy filename (the migration golden key).
   *
   * Migrated This-Day-In-History events carry their original URL in
   * field_old_filename - 'chronology/thisday/1838-02-06.htm' IS the
   * event date at day precision, deterministically.
   *
   * @param string|null $old_filename
   *   The field_old_filename value.
   *
   * @return array|null
   *   Candidate row or NULL.
   */
  public function extractFromFilename(?string $old_filename): ?array {
    if (!$old_filename) {
      return NULL;
    }
    if (preg_match('#thisday/(\d{4})-(\d{2})-(\d{2})\.#', $old_filename, $m)) {
      $year = $this->boundedYear($m[1]);
      $month = (int) $m[2];
      $day = (int) $m[3];
      if ($year && checkdate($month, $day, $year)) {
        return [
          'date' => sprintf('%04d-%02d-%02d', $year, $month, $day),
          'date_end' => NULL,
          'precision' => 'day',
          'confidence' => 0.98,
          'method' => 'old_filename_thisday',
          'snippet' => $old_filename,
        ];
      }
    }
    return NULL;
  }

  /**
   * Extract the best candidate from an event's title and body.
   *
   * @param string $title
   *   The node title.
   * @param string|null $body
   *   Raw body value (may contain HTML).
   *
   * @return array|null
   *   ['date', 'date_end', 'precision', 'confidence', 'method',
   *   'snippet'] or NULL when nothing credible was found.
   */
  public function extract(string $title, ?string $body): ?array {
    $title_text = $this->plain($title);
    $body_text = mb_substr($this->plain($body ?? ''), 0, self::BODY_WINDOW);

    $candidates = [];

    // 1. House style: the body OPENS with "On [the] 24[th] [of] December
    // 1651" - the strongest signal there is.
    if (preg_match('/^on\s+(?:the\s+)?(\d{1,2})(?:st|nd|rd|th)?\s+(?:of\s+)?(' . self::MONTH_RX . ')\s+' . self::YEAR_RX . '/i', $body_text, $m, PREG_OFFSET_CAPTURE)) {
      $this->addDayCandidate($candidates, $m, 0.95, 'body_opening_on', $body_text);
    }

    // 2. "24 December 1651" in the title (0.9) or body window (0.85).
    foreach ([[$title_text, 0.9, 'title_dmy'], [$body_text, 0.85, 'body_dmy']] as [$haystack, $confidence, $method]) {
      if (preg_match('/\b(\d{1,2})(?:st|nd|rd|th)?\s+(' . self::MONTH_RX . ')\s+' . self::YEAR_RX . '\b/i', $haystack, $m, PREG_OFFSET_CAPTURE)) {
        $this->addDayCandidate($candidates, $m, $confidence, $method, $haystack);
      }
    }

    // 3. US order "December 24, 1651".
    foreach ([[$title_text, 0.88, 'title_mdy'], [$body_text, 0.82, 'body_mdy']] as [$haystack, $confidence, $method]) {
      if (preg_match('/\b(' . self::MONTH_RX . ')\s+(\d{1,2})(?:st|nd|rd|th)?,?\s+' . self::YEAR_RX . '\b/i', $haystack, $m, PREG_OFFSET_CAPTURE)) {
        $day = $this->boundedInt($m[2][0], 1, 31);
        $month = self::MONTHS[strtolower($m[1][0])] ?? NULL;
        $year = $this->boundedYear($m[3][0]);
        if ($day && $month && $year && checkdate($month, $day, $year)) {
          $candidates[] = $this->candidate(sprintf('%04d-%02d-%02d', $year, $month, $day), NULL, 'day', $confidence, $method, $haystack, $m[0][1], strlen($m[0][0]));
        }
      }
    }

    // 4. "December 1651" - month precision.
    foreach ([[$title_text, 0.78, 'title_my'], [$body_text, 0.72, 'body_my']] as [$haystack, $confidence, $method]) {
      if (preg_match('/\b(' . self::MONTH_RX . ')\s+' . self::YEAR_RX . '\b/i', $haystack, $m, PREG_OFFSET_CAPTURE)) {
        $month = self::MONTHS[strtolower($m[1][0])] ?? NULL;
        $year = $this->boundedYear($m[2][0]);
        if ($month && $year) {
          $candidates[] = $this->candidate(sprintf('%04d-%02d-01', $year, $month), NULL, 'month', $confidence, $method, $haystack, $m[0][1], strlen($m[0][0]));
        }
      }
    }

    // 5. "circa 1651" / "c. 1651".
    foreach ([[$title_text, 0.68, 'title_circa'], [$body_text, 0.62, 'body_circa']] as [$haystack, $confidence, $method]) {
      if (preg_match('/\bc(?:irca|a?\.)\s*' . self::YEAR_RX . '\b/i', $haystack, $m, PREG_OFFSET_CAPTURE)) {
        $year = $this->boundedYear($m[1][0]);
        if ($year) {
          $candidates[] = $this->candidate(sprintf('%04d-01-01', $year), NULL, 'circa', $confidence, $method, $haystack, $m[0][1], strlen($m[0][0]));
        }
      }
    }

    // 6. Year range "1651-1662" (also en dash).
    foreach ([[$title_text, 0.72, 'title_range'], [$body_text, 0.66, 'body_range']] as [$haystack, $confidence, $method]) {
      if (preg_match('/\b' . self::YEAR_RX . '\s*[-\x{2013}]\s*' . self::YEAR_RX . '\b/u', $haystack, $m, PREG_OFFSET_CAPTURE)) {
        $start = $this->boundedYear($m[1][0]);
        $end = $this->boundedYear($m[2][0]);
        if ($start && $end && $end >= $start) {
          $candidates[] = $this->candidate(sprintf('%04d-01-01', $start), sprintf('%04d-12-31', $end), 'range', $confidence, $method, $haystack, $m[0][1], strlen($m[0][0]));
        }
      }
    }

    // 7. "in 1651" - year precision.
    $in_year_passes = [
      [$title_text, 0.7, 'title_in_year'],
      [$body_text, 0.65, 'body_in_year'],
    ];
    foreach ($in_year_passes as [$haystack, $confidence, $method]) {
      if (preg_match('/\bin\s+' . self::YEAR_RX . '\b/i', $haystack, $m, PREG_OFFSET_CAPTURE)) {
        $year = $this->boundedYear($m[1][0]);
        if ($year) {
          $candidates[] = $this->candidate(sprintf('%04d-01-01', $year), NULL, 'year', $confidence, $method, $haystack, $m[0][1], strlen($m[0][0]));
        }
      }
    }

    // 8. Lone bounded year anywhere in the title - weak.
    if (preg_match('/(?<!\d)' . self::YEAR_RX . '(?!\d)/', $title_text, $m, PREG_OFFSET_CAPTURE)) {
      $year = $this->boundedYear($m[1][0]);
      if ($year) {
        $candidates[] = $this->candidate(sprintf('%04d-01-01', $year), NULL, 'year', 0.6, 'title_lone_year', $title_text, $m[0][1], strlen($m[0][0]));
      }
    }

    if ($candidates === []) {
      return NULL;
    }

    usort($candidates, static fn(array $a, array $b) => $b['confidence'] <=> $a['confidence']);
    $best = $candidates[0];

    // Conflicting years across strong candidates: flag for adjudication
    // by demoting confidence - the evidence disagrees with itself.
    $years = array_unique(array_map(static fn(array $c) => substr($c['date'], 0, 4), array_filter($candidates, static fn(array $c) => $c['confidence'] >= 0.6)));
    if (count($years) > 1 && $best['precision'] !== 'range') {
      $best['confidence'] = min($best['confidence'], 0.5);
      $best['method'] .= '+conflict';
    }

    return $best;
  }

  /**
   * Adds a validated day-precision candidate from a d-month-y match.
   */
  private function addDayCandidate(array &$candidates, array $m, float $confidence, string $method, string $haystack): void {
    $day = $this->boundedInt($m[1][0], 1, 31);
    $month = self::MONTHS[strtolower($m[2][0])] ?? NULL;
    $year = $this->boundedYear($m[3][0]);
    if ($day && $month && $year && checkdate($month, $day, $year)) {
      $candidates[] = $this->candidate(sprintf('%04d-%02d-%02d', $year, $month, $day), NULL, 'day', $confidence, $method, $haystack, $m[0][1], strlen($m[0][0]));
    }
  }

  /**
   * Builds a candidate row with an evidence snippet around the match.
   */
  private function candidate(string $date, ?string $date_end, string $precision, float $confidence, string $method, string $haystack, int $offset, int $length): array {
    $start = max(0, $offset - 60);
    $snippet = ($start > 0 ? '...' : '') . substr($haystack, $start, min(strlen($haystack) - $start, $length + 120));
    return [
      'date' => $date,
      'date_end' => $date_end,
      'precision' => $precision,
      'confidence' => $confidence,
      'method' => $method,
      'snippet' => trim($snippet),
    ];
  }

  /**
   * A year within 1000..(current year + 1), or NULL.
   */
  private function boundedYear(string $value): ?int {
    $year = (int) $value;
    return ($year >= 1000 && $year <= (int) date('Y') + 1) ? $year : NULL;
  }

  /**
   * An integer within [min, max], or NULL.
   */
  private function boundedInt(string $value, int $min, int $max): ?int {
    $int = (int) $value;
    return ($int >= $min && $int <= $max) ? $int : NULL;
  }

  /**
   * Stored (possibly HTML) text to searchable plain text.
   */
  private function plain(string $text): string {
    $clean = strip_tags(Html::decodeEntities($text));
    return trim(preg_replace('/\s+/u', ' ', $clean) ?? '');
  }

}
