<?php

declare(strict_types=1);

namespace Drupal\saho_classroom\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Renders a Classroom slide-schema JSON value as an interactive deck.
 *
 * The formatter reads a text/long-string field whose value is the structured
 * slide schema from docs/classroom/20-html-format.md, decodes it, and hands the
 * lesson metadata plus slides to the saho_classroom:presentation_deck
 * single-directory component. The component owns all markup and auto-attaches
 * the deck engine (CSS/JS), so this class only bridges stored data to props.
 *
 * When the stored value is not valid deck JSON the raw value is rendered as an
 * escaped fallback, so a mis-typed field never fatals a node view.
 *
 * SECURITY: the decoded schema is authored/agent content and is treated as a
 * trust boundary. Text props are auto-escaped by the component's Twig template;
 * inline SVG (media.data with type "svg") is emitted raw by the template and
 * therefore MUST be sanitised in the content pipeline before storage.
 *
 * @see \Drupal\saho_classroom\TopicSpineInterface
 */
#[FieldFormatter(
  id: 'saho_classroom_presentation_deck',
  label: new TranslatableMarkup('Presentation deck (SAHO Classroom)'),
  field_types: [
    'text_long',
    'text_with_summary',
    'string_long',
  ],
)]
final class PresentationDeckFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $elements = [];
    foreach ($items as $delta => $item) {
      $raw = (string) ($item->value ?? '');
      $deck = json_decode($raw, TRUE);

      if (!is_array($deck) || empty($deck['slides']) || !is_array($deck['slides'])) {
        // Not a deck payload: fail soft with the escaped raw value.
        $elements[$delta] = [
          '#markup' => $raw,
          '#allowed_tags' => [],
        ];
        continue;
      }

      $elements[$delta] = [
        '#type' => 'component',
        '#component' => 'saho_classroom:presentation_deck',
        '#props' => [
          'title' => (string) ($deck['title'] ?? ''),
          'aria_label' => $this->deckLabel($deck),
          'meta' => [
            'subject' => (string) ($deck['subject'] ?? ''),
            'grade' => (string) ($deck['grade'] ?? ''),
            'phase' => (string) ($deck['phase'] ?? ''),
            'caps_topic' => (string) ($deck['caps_topic'] ?? ''),
            'duration' => $deck['duration_minutes'] ?? NULL,
            'attribution' => (string) ($deck['attribution'] ?? ''),
          ],
          'slides' => $this->processInline(array_values($deck['slides'])),
        ],
      ];
    }

    return $elements;
  }

  /**
   * Recursively converts the inline-markdown subset in deck text to Markup.
   *
   * Mirrors the standalone render.php: **bold**, *italic* and `code`. Skips the
   * media "data" key (raw inline SVG must stay untouched). Strings with no
   * markdown markers are left as plain strings so Twig escapes them normally.
   *
   * @param mixed $value
   *   A slide-schema value (array, string or scalar).
   * @param int|string $key
   *   The key of $value within its parent (used to skip "data").
   *
   * @return mixed
   *   The value with text fields converted to Markup where applicable.
   */
  private function processInline(mixed $value, int|string $key = ''): mixed {
    if (is_array($value)) {
      $out = [];
      foreach ($value as $k => $v) {
        $out[$k] = $this->processInline($v, $k);
      }
      return $out;
    }
    if (is_string($value) && $key !== 'data' && (str_contains($value, '*') || str_contains($value, '`'))) {
      return $this->inlineMarkup($value);
    }
    return $value;
  }

  /**
   * Escapes a string, then applies the inline-markdown subset, as safe Markup.
   */
  private function inlineMarkup(string $text): MarkupInterface {
    $escaped = Html::escape($text);
    $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped);
    $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped);
    $escaped = preg_replace('/(?<![\*\w])\*(?!\s)(.+?)(?<!\s)\*(?![\*\w])/s', '<em>$1</em>', $escaped);
    return Markup::create($escaped);
  }

  /**
   * Builds an accessible label for the deck region.
   *
   * @param array $deck
   *   The decoded deck schema.
   *
   * @return string
   *   A human-readable label combining title, grade and subject.
   */
  private function deckLabel(array $deck): string {
    $title = trim((string) ($deck['title'] ?? ''));
    $grade = trim((string) ($deck['grade'] ?? ''));
    $subject = trim((string) ($deck['subject'] ?? ''));

    $context = array_filter([
      $grade !== '' ? 'Grade ' . $grade : '',
      $subject,
    ]);

    $label = $title !== '' ? $title : 'SAHO Classroom presentation';
    if ($context !== []) {
      $label .= ' - ' . implode(' ', $context);
    }
    return $label;
  }

}
