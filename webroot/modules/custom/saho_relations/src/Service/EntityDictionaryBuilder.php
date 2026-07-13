<?php

declare(strict_types=1);

namespace Drupal\saho_relations\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Builds the dictionary of linkable entities mined from the local database.
 *
 * The dictionary is the precision backbone of the pipeline: candidate edges
 * are only ever drawn against entities that already exist here, so the writer
 * can never link to something that is not a real, published node.
 */
final class EntityDictionaryBuilder {

  /**
   * Linkable target bundles mapped to a semantic kind.
   *
   * The kind is a hint for adjudication and field routing; an article may end
   * up routed to topics or organisations depending on its own categorisation.
   */
  public const LINKABLE_BUNDLES = [
    'biography' => 'person',
    'article' => 'topic',
    'event' => 'event',
    'place' => 'place',
  ];

  /**
   * Leading honorifics/titles stripped so body mentions match the bare name.
   *
   * Body text rarely repeats the honorific ("Abdullah Abdurahman", not "Dr
   * Abdullah Abdurahman"), so the match phrase drops these from the front.
   */
  public const HONORIFICS = [
    'dr', 'prof', 'professor', 'mr', 'mrs', 'ms', 'miss', 'mx', 'chief',
    'rabbi', 'sir', 'dame', 'rev', 'reverend', 'father', 'fr', 'advocate',
    'adv', 'judge', 'justice', 'king', 'queen', 'prince', 'princess',
    'captain', 'capt', 'col', 'colonel', 'general', 'gen', 'major', 'maj',
    'lt', 'lieutenant', 'comrade', 'imam', 'sheikh', 'sheik', 'hon',
    'honourable', 'president', 'minister', 'mahatma', 'bishop', 'archbishop',
    'pastor', 'nkosi', 'inkosi', 'mama', 'tata',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
  ) {}

  /**
   * Build the entity dictionary.
   *
   * @param array $options
   *   Keys:
   *   - bundles (string[]|null): restrict to these target bundles.
   *   - published_only (bool): default TRUE.
   *   - min_title_length (int): drop titles shorter than this many characters
   *     after normalisation (guards against ultra-ambiguous anchors).
   *
   * @return array
   *   A list of entries keyed by nid, each:
   *   ['nid', 'bundle', 'kind', 'title', 'normalized', 'tokens', 'anchor'].
   */
  public function build(array $options = []): array {
    $bundles = $options['bundles'] ?? array_keys(self::LINKABLE_BUNDLES);
    $bundles = array_values(array_intersect($bundles, array_keys(self::LINKABLE_BUNDLES)));
    $published_only = $options['published_only'] ?? TRUE;
    $min_length = (int) ($options['min_title_length'] ?? 5);

    $query = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid', 'type', 'title']);
    $query->condition('n.type', $bundles, 'IN');
    if ($published_only) {
      $query->condition('n.status', 1);
    }
    $result = $query->execute();

    $dictionary = [];
    foreach ($result as $row) {
      // Drop parenthetical nicknames ("(Nanna)") before normalising so they do
      // not become a required token in the match phrase.
      $cleaned = preg_replace('/\([^)]*\)/u', ' ', (string) $row->title) ?? $row->title;
      $normalized = self::normalize($cleaned);
      $tokens = self::tokenize($normalized);
      // Strip leading honorifics to get the phrase as it appears in prose.
      while ($tokens && in_array($tokens[0], self::HONORIFICS, TRUE)) {
        array_shift($tokens);
      }
      $match = implode(' ', $tokens);
      if ($tokens === [] || mb_strlen($match) < $min_length) {
        continue;
      }
      $dictionary[(int) $row->nid] = [
        'nid' => (int) $row->nid,
        'bundle' => $row->type,
        'kind' => self::LINKABLE_BUNDLES[$row->type] ?? 'unknown',
        'title' => $row->title,
        'match' => $match,
        'tokens' => $tokens,
        // Anchor = rarest token, chosen later once global frequencies known.
        'anchor' => $tokens[count($tokens) - 1],
      ];
    }

    $this->assignAnchors($dictionary);
    return $dictionary;
  }

  /**
   * Choose the rarest token of each title as its blocking anchor.
   *
   * Matching only has to examine entities sharing a body token with their
   * anchor, which turns an O(nodes x entities) scan into a near-linear one.
   */
  protected function assignAnchors(array &$dictionary): void {
    $frequency = [];
    foreach ($dictionary as $entry) {
      foreach ($entry['tokens'] as $token) {
        $frequency[$token] = ($frequency[$token] ?? 0) + 1;
      }
    }
    foreach ($dictionary as &$entry) {
      $rarest = NULL;
      $rarest_freq = PHP_INT_MAX;
      foreach ($entry['tokens'] as $token) {
        $freq = $frequency[$token] ?? 1;
        if ($freq < $rarest_freq) {
          $rarest_freq = $freq;
          $rarest = $token;
        }
      }
      $entry['anchor'] = $rarest ?? $entry['tokens'][0];
    }
  }

  /**
   * Normalise a title for matching.
   *
   * Lowercases, strips punctuation and collapses whitespace. Diacritics are
   * preserved because South African names rely on them.
   */
  public static function normalize(string $value): string {
    $value = mb_strtolower(trim($value));
    // Replace punctuation with spaces so "F.W. de Klerk" -> "f w de klerk".
    $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? '';
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return trim($value);
  }

  /**
   * Split a normalised string into tokens.
   */
  public static function tokenize(string $normalized): array {
    if ($normalized === '') {
      return [];
    }
    return array_values(array_filter(explode(' ', $normalized), static fn($t) => $t !== ''));
  }

}
