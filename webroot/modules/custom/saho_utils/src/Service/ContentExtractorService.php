<?php

declare(strict_types=1);

namespace Drupal\saho_utils\Service;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Service for extracting text content from entities.
 *
 * Provides standardized methods for extracting teasers, summaries, and body
 * text from various content entities with smart truncation and field fallbacks.
 */
class ContentExtractorService {

  /**
   * Extract a teaser from an entity's body field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to extract the teaser from.
   * @param int $length
   *   Maximum length of the teaser. Default is 150 characters.
   * @param string|null $field_name
   *   Specific field name to use. If NULL, will try common body field names.
   *
   * @return string
   *   The extracted teaser text, or empty string if no suitable field found.
   */
  public function extractTeaser(ContentEntityInterface $entity, int $length = 150, ?string $field_name = NULL): string {
    $teaser_text = '';

    // If specific field provided, use it. Otherwise try common candidates.
    $field_candidates = $field_name
      ? [$field_name]
      : ['body', 'field_body', 'field_description', 'field_summary'];

    foreach ($field_candidates as $candidate) {
      if ($entity->hasField($candidate) && !$entity->get($candidate)->isEmpty()) {
        $field = $entity->get($candidate)->first();
        if ($field) {
          $field_value = $field->getValue();
          $body_text = $field_value['value'] ?? '';

          if (!empty($body_text)) {
            // Strip HTML tags and decode HTML entities.
            $teaser_text = html_entity_decode(strip_tags($body_text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Truncate to specified length.
            $teaser_text = $this->smartTruncate($teaser_text, $length);

            // Use first available field.
            break;
          }
        }
      }
    }

    return $teaser_text;
  }

  /**
   * Extract summary text from an entity.
   *
   * Tries to find an existing summary field, otherwise generates from body.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to extract the summary from.
   * @param string|null $field_name
   *   Specific field name to use for generating summary.
   *
   * @return string
   *   The extracted summary text.
   */
  public function extractSummary(ContentEntityInterface $entity, ?string $field_name = NULL): string {
    // Try to use existing summary field first.
    $summary_candidates = ['field_summary', 'summary'];

    foreach ($summary_candidates as $candidate) {
      if ($entity->hasField($candidate) && !$entity->get($candidate)->isEmpty()) {
        $summary_field = $entity->get($candidate)->first();
        if ($summary_field) {
          $summary_value = $summary_field->getValue();
          $summary_text = $summary_value['value'] ?? '';
          if (!empty($summary_text)) {
            return html_entity_decode(strip_tags($summary_text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
          }
        }
      }
    }

    // If no summary field, generate from body.
    return $this->extractTeaser($entity, 200, $field_name);
  }

  /**
   * Extract full body text from an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to extract body text from.
   * @param bool $strip_tags
   *   Whether to strip HTML tags. Default is TRUE.
   *
   * @return string
   *   The extracted body text.
   */
  public function extractBodyText(ContentEntityInterface $entity, bool $strip_tags = TRUE): string {
    $body_text = '';

    $body_candidates = ['body', 'field_body', 'field_description'];

    foreach ($body_candidates as $candidate) {
      if ($entity->hasField($candidate) && !$entity->get($candidate)->isEmpty()) {
        $field = $entity->get($candidate)->first();
        if ($field) {
          $field_value = $field->getValue();
          $body_text = $field_value['value'] ?? '';

          if (!empty($body_text)) {
            if ($strip_tags) {
              $body_text = html_entity_decode(strip_tags($body_text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            // Use first available field.
            break;
          }
        }
      }
    }

    return $body_text;
  }

  /**
   * Smart truncate text at sentence or word boundaries.
   *
   * @param string $text
   *   The text to truncate.
   * @param int $length
   *   Maximum length.
   *
   * @return string
   *   Truncated text with ellipsis if needed.
   */
  protected function smartTruncate(string $text, int $length): string {
    // If text is shorter than limit, return as-is.
    if (strlen($text) <= $length) {
      return $text;
    }

    // Truncate to specified length.
    $truncated = substr($text, 0, $length);

    // Try to find the last complete sentence (within reasonable range).
    $last_period = strrpos($truncated, '.');
    if ($last_period !== FALSE && $last_period > ($length * 0.66)) {
      return substr($truncated, 0, $last_period + 1);
    }

    // Otherwise, find the last word boundary.
    $last_space = strrpos($truncated, ' ');
    if ($last_space !== FALSE) {
      return substr($truncated, 0, $last_space) . '...';
    }

    // If no word boundary, just add ellipsis.
    return $truncated . '...';
  }

}
