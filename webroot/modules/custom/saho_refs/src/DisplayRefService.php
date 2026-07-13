<?php

declare(strict_types=1);

namespace Drupal\saho_refs;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\node\NodeInterface;

/**
 * Derives stable display references and record status for the Open Record.
 *
 * The "REF" is a display reference, NOT a formal accession number: it is
 * derived from the permanent node id (so it is stable and resolvable) or read
 * from a real reference field if the site ever gains one. This is the single
 * source of truth for refs across the record header, index tables and citations.
 */
final class DisplayRefService {

  /**
   * Optional real reference field, preferred over the derived ref when present.
   */
  private const REAL_REF_FIELD = 'field_accession_ref';

  /**
   * Content-type to single-letter ref prefix. Archive is R (A is article).
   */
  private const PREFIX_MAP = [
    'biography' => 'B',
    'article' => 'A',
    'event' => 'E',
    'place' => 'P',
    'archive' => 'R',
    'topic' => 'T',
  ];

  /**
   * Zero-padding width for the numeric portion of a derived ref.
   */
  private const PAD_WIDTH = 7;

  /**
   * Constructs the service.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    private readonly TimeInterface $time,
  ) {}

  /**
   * Returns the display reference for a node (e.g. "B-0085550").
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return string
   *   The display reference.
   */
  public function getRef(NodeInterface $node): string {
    if ($node->hasField(self::REAL_REF_FIELD) && !$node->get(self::REAL_REF_FIELD)->isEmpty()) {
      return trim((string) $node->get(self::REAL_REF_FIELD)->value);
    }
    return $this->prefix($node->bundle()) . '-' . str_pad((string) $node->id(), self::PAD_WIDTH, '0', STR_PAD_LEFT);
  }

  /**
   * Returns the ref prefix letter for a bundle.
   *
   * @param string $bundle
   *   The node bundle.
   *
   * @return string
   *   The uppercase prefix letter.
   */
  public function prefix(string $bundle): string {
    return self::PREFIX_MAP[$bundle] ?? strtoupper(substr($bundle, 0, 1));
  }

  /**
   * Returns the record status label: New, Revised or Verified.
   *
   * Content_moderation is not enabled on these bundles, so status is derived
   * from node timestamps (a heuristic, swappable for a real field later).
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return string
   *   One of "New", "Revised", "Verified".
   */
  public function getStatus(NodeInterface $node): string {
    $now = $this->time->getRequestTime();
    $created = (int) $node->getCreatedTime();
    $changed = (int) $node->getChangedTime();
    if ($created > $now - (60 * 86400)) {
      return 'New';
    }
    if (($changed - $created) > (180 * 86400)) {
      return 'Revised';
    }
    return 'Verified';
  }

  /**
   * Returns the lowercase status key (new|revised|verified) for CSS/state.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return string
   *   The lowercase status key.
   */
  public function getStatusKey(NodeInterface $node): string {
    return strtolower($this->getStatus($node));
  }

  /**
   * Extracts a node id from a display reference, or NULL if none.
   *
   * @param string $ref
   *   A display reference such as "B-0085550".
   *
   * @return int|null
   *   The node id, or NULL.
   */
  public function nidFromRef(string $ref): ?int {
    if (preg_match('/(\d+)\s*$/', $ref, $matches) === 1) {
      return (int) $matches[1];
    }
    return NULL;
  }

}
