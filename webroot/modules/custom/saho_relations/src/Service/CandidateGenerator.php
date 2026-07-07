<?php

declare(strict_types=1);

namespace Drupal\saho_relations\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\search_api\Entity\Index;

/**
 * Generates candidate relationship edges from two blind signals.
 *
 * 1. Deterministic name-match: anchor-token blocking finds dictionary entities
 *    whose full title appears verbatim in a node body. High precision, fully
 *    explainable (the matched phrase is the evidence).
 * 2. Solr More-Like-This: the existing saho_content index surfaces
 *    semantically similar nodes that share no proper nouns.
 *
 * Output is candidate edges only. Disambiguation, scoring and the keep/drop
 * decision happen in the downstream adjudication stage.
 */
final class CandidateGenerator {

  /**
   * Tokens that must never act as a sole anchor (too ambiguous on their own).
   *
   * Names reduced entirely to these are skipped for name-matching.
   */
  public const STOPLIST = [
    'the', 'and', 'of', 'in', 'a', 'an', 'de', 'van', 'van der', 'le', 'la',
    'south', 'africa', 'african', 'national', 'party', 'people', 'union',
    'congress', 'movement', 'organisation', 'organization', 'council', 'group',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
    protected readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Generate name-match candidate edges.
   *
   * @param array $dictionary
   *   Dictionary from EntityDictionaryBuilder, keyed by target nid.
   * @param string $field
   *   The relation field the edges target (e.g. field_people_related_tab).
   * @param array $options
   *   Keys:
   *   - source_bundles (string[]): node bundles to scan bodies of.
   *   - exclude_self (bool): never link a node to itself. Default TRUE.
   *   - min_mentions (int): minimum verbatim occurrences to emit. Default 1.
   *   - batch_size (int): source rows per query. Default 500.
   *
   * @return array
   *   List of candidate edges:
   *   ['source_nid','source_bundle','field','target_id','target_bundle',
   *    'signal'=>'name_match','mentions'=>int,'evidence'=>string].
   */
  public function nameMatchCandidates(array $dictionary, string $field, array $options = []): array {
    $source_bundles = $options['source_bundles'] ?? ['article', 'biography', 'event', 'place'];
    $exclude_self = $options['exclude_self'] ?? TRUE;
    $min_mentions = (int) ($options['min_mentions'] ?? 1);
    $batch_size = (int) ($options['batch_size'] ?? 500);
    // Require at least this many name tokens to match. Two-token minimum keeps
    // ambiguous single-word names out of the candidate set by default.
    $min_tokens = (int) ($options['min_tokens'] ?? 2);

    // Build the anchor index: token -> [target nids whose anchor is that token].
    $anchor_index = [];
    foreach ($dictionary as $nid => $entry) {
      if (count($entry['tokens']) < $min_tokens) {
        continue;
      }
      $anchor = $entry['anchor'];
      if (in_array($anchor, self::STOPLIST, TRUE) || mb_strlen($anchor) < 3) {
        continue;
      }
      $anchor_index[$anchor][] = $nid;
    }

    $logger = $this->loggerFactory->get('saho_relations');
    $candidates = [];
    $last_nid = 0;

    do {
      $rows = $this->fetchBodies($source_bundles, $last_nid, $batch_size);
      foreach ($rows as $row) {
        $last_nid = (int) $row->nid;
        $normalized_body = EntityDictionaryBuilder::normalize($row->body);
        if ($normalized_body === '') {
          continue;
        }
        $body_tokens = array_unique(EntityDictionaryBuilder::tokenize($normalized_body));
        // Pad with spaces so phrase checks honour word boundaries.
        $padded = ' ' . $normalized_body . ' ';

        $seen_targets = [];
        foreach ($body_tokens as $token) {
          if (!isset($anchor_index[$token])) {
            continue;
          }
          foreach ($anchor_index[$token] as $target_nid) {
            if (isset($seen_targets[$target_nid])) {
              continue;
            }
            if ($exclude_self && $target_nid === (int) $row->nid) {
              continue;
            }
            $entry = $dictionary[$target_nid];
            $needle = ' ' . $entry['match'] . ' ';
            $mentions = substr_count($padded, $needle);
            if ($mentions < $min_mentions) {
              continue;
            }
            $seen_targets[$target_nid] = TRUE;
            $candidates[] = [
              'source_nid' => (int) $row->nid,
              'source_bundle' => $row->type,
              'field' => $field,
              'target_id' => $target_nid,
              'target_bundle' => $entry['bundle'],
              'signal' => 'name_match',
              'mentions' => $mentions,
              // Search the matched phrase (honorifics stripped), not the raw
              // title, so the excerpt is centred on the real mention.
              'evidence' => $this->excerpt($row->body, $entry['match']),
            ];
          }
        }
      }
      $logger->info('Name-match scanned up to nid @nid, @count candidates so far', [
        '@nid' => $last_nid,
        '@count' => count($candidates),
      ]);
    } while (count($rows) === $batch_size);

    return $candidates;
  }

  /**
   * Generate Solr More-Like-This candidate edges for given source nodes.
   *
   * @param int[] $source_nids
   *   Source node ids to find similar content for.
   * @param string $field
   *   The relation field the edges target.
   * @param array $options
   *   Keys: index_id (default 'saho_content'), top_k (default 8),
   *   target_bundles (string[]|null) to restrict results, langcode (default 'en').
   *
   * @return array
   *   List of candidate edges with signal => 'mlt' and a 'score'.
   */
  public function mltCandidates(array $source_nids, string $field, array $options = []): array {
    $index_id = $options['index_id'] ?? 'saho_content';
    $top_k = (int) ($options['top_k'] ?? 8);
    $target_bundles = $options['target_bundles'] ?? NULL;
    $langcode = $options['langcode'] ?? 'en';

    $index = Index::load($index_id);
    if ($index === NULL) {
      $this->loggerFactory->get('saho_relations')
        ->warning('Search index @id not found; skipping MLT.', ['@id' => $index_id]);
      return [];
    }

    $candidates = [];
    foreach ($source_nids as $nid) {
      $nid = (int) $nid;
      $query = $index->query(['limit' => $top_k + 1]);
      // search_api_solr MLT convention: anchor the query on a document id.
      $query->setOption('search_api_mlt', [
        'id' => 'entity:node/' . $nid . ':' . $langcode,
        'fields' => ['body', 'title'],
      ]);
      if ($target_bundles) {
        $query->addCondition('type', $target_bundles, 'IN');
      }
      try {
        $results = $query->execute();
      }
      catch (\Throwable $e) {
        $this->loggerFactory->get('saho_relations')
          ->warning('MLT query failed for node @nid: @msg', ['@nid' => $nid, '@msg' => $e->getMessage()]);
        continue;
      }
      foreach ($results->getResultItems() as $item) {
        $target_nid = (int) preg_replace('/\D/', '', explode(':', $item->getId())[1] ?? '');
        if ($target_nid <= 0 || $target_nid === $nid) {
          continue;
        }
        $candidates[] = [
          'source_nid' => $nid,
          'field' => $field,
          'target_id' => $target_nid,
          'signal' => 'mlt',
          'score' => $item->getScore(),
        ];
      }
    }

    return $candidates;
  }

  /**
   * Fetch a keyset-paginated batch of node bodies.
   */
  protected function fetchBodies(array $bundles, int $after_nid, int $batch_size): array {
    $query = $this->database->select('node_field_data', 'n');
    $query->join('node__body', 'b', 'b.entity_id = n.nid AND b.deleted = 0');
    $query->fields('n', ['nid', 'type']);
    $query->addField('b', 'body_value', 'body');
    $query->condition('n.type', $bundles, 'IN');
    $query->condition('n.status', 1);
    $query->condition('n.nid', $after_nid, '>');
    $query->orderBy('n.nid', 'ASC');
    $query->range(0, $batch_size);
    return $query->execute()->fetchAll();
  }

  /**
   * Extract a short evidence excerpt around the first mention of a title.
   */
  protected function excerpt(string $body, string $title): string {
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');
    $pos = stripos($plain, $title);
    if ($pos === FALSE) {
      return mb_substr($plain, 0, 160);
    }
    $start = max(0, $pos - 60);
    return trim(mb_substr($plain, $start, 200));
  }

}
