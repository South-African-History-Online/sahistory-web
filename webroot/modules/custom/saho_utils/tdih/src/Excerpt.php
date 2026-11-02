<?php

declare(strict_types=1);

namespace Drupal\tdih;

/**
 * Sentence-aware plain-text excerpts for the TDIH asides.
 *
 * The home-page aside shows a short synopsis under the day's lead event.
 * Events carry a curated field_synopsis on roughly half the records; the
 * rest fall back to the opening of the body. A hard character cut lands
 * mid-word on a date or a name, so the fallback cuts at the last sentence
 * boundary that fits, and only drops to a word boundary with an ellipsis
 * when the first sentence alone is longer than the budget.
 */
final class Excerpt {

  /**
   * Collapses HTML to plain text: tags stripped, entities decoded, one space.
   *
   * @param string $html
   *   Rendered HTML (or already-plain text).
   *
   * @return string
   *   The trimmed plain text.
   */
  public static function plain(string $html): string {
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Non-breaking spaces survive entity decoding as U+00A0; fold them too.
    $text = preg_replace('/[\s\x{00A0}]+/u', ' ', $text) ?? $text;
    return trim($text);
  }

  /**
   * Returns the leading sentences of a text that fit in a budget.
   *
   * @param string $text
   *   Plain text or HTML; HTML is collapsed first.
   * @param int $max
   *   Maximum length in characters (multibyte-safe).
   *
   * @return string
   *   The excerpt, or an empty string when there is no text.
   */
  public static function sentences(string $text, int $max = 220): string {
    $text = self::plain($text);
    if ($text === '' || mb_strlen($text) <= $max) {
      return $text;
    }

    $head = mb_substr($text, 0, $max + 1);
    // Last sentence end inside the budget: terminal punctuation followed by
    // whitespace or a closing quote. Abbreviations such as "Dr." or "St."
    // are accepted as cuts - a short clean sentence beats a mid-word cut.
    if (preg_match_all('/[.!?]["\'\x{201D}\x{2019}]?(?=\s)/u', $head, $m, PREG_OFFSET_CAPTURE)) {
      $last = end($m[0]);
      $cut = self::charOffset($head, $last[1] + strlen($last[0]));
      $candidate = trim(mb_substr($head, 0, $cut));
      // A cut that keeps under a third of the budget is a stub (an initial,
      // a lone date); fall through to the word cut instead.
      if (mb_strlen($candidate) >= (int) ($max / 3)) {
        return $candidate;
      }
    }

    // No usable sentence boundary: cut at the last word that fits.
    $words = mb_substr($text, 0, $max);
    $space = mb_strrpos($words, ' ');
    if ($space !== FALSE && $space > 0) {
      $words = mb_substr($words, 0, $space);
    }
    return rtrim($words, " ,;:-") . '...';
  }

  /**
   * Converts a byte offset from preg into a character offset.
   */
  private static function charOffset(string $text, int $byte_offset): int {
    return mb_strlen(substr($text, 0, $byte_offset));
  }

}
