<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_refs\DisplayRefService;

/**
 * Aggregates archive counts and recent records for the catalogue front page.
 *
 * The archive spans 60k+ published nodes, so the aggregate counts are cached
 * in cache.default under COUNTS_CID (invalidated by node_list, capped at one
 * hour) and must never be recomputed per anonymous request.
 */
final class ArchiveCountsService {

  /**
   * Cache id for the aggregated counts.
   */
  public const COUNTS_CID = 'saho_frontpage:counts';

  /**
   * Seconds the aggregated counts stay cached.
   */
  private const CACHE_MAX_AGE = 3600;

  /**
   * The record bundles that make up the public archive.
   */
  private const RECORD_BUNDLES = ['biography', 'event', 'place', 'archive', 'article'];

  /**
   * Constructs the service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache bin.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\saho_refs\DisplayRefService $displayRef
   *   The display reference service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CacheBackendInterface $cache,
    private readonly Connection $database,
    private readonly DisplayRefService $displayRef,
    private readonly DateFormatterInterface $dateFormatter,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Returns raw published counts keyed by bundle, plus sources and total.
   *
   * @return array<string, int>
   *   Counts keyed by bundle name, plus "sources" (records with a non-empty
   *   reference field) and "records" (sum of the record bundles).
   */
  public function getRawCounts(): array {
    $cached = $this->cache->get(self::COUNTS_CID);
    if ($cached !== FALSE) {
      return $cached->data;
    }
    $counts = [];
    $storage = $this->entityTypeManager->getStorage('node');
    foreach (self::RECORD_BUNDLES as $bundle) {
      $counts[$bundle] = (int) $storage->getQuery()
        ->condition('type', $bundle)
        ->condition('status', 1)
        ->accessCheck(TRUE)
        ->count()
        ->execute();
    }
    // An entity query cannot cheaply COUNT non-empty across the field table
    // at this scale, so the sources count is a raw DISTINCT query.
    $counts['sources'] = (int) $this->database->query(
      "SELECT COUNT(DISTINCT entity_id) FROM {node__field_ref_str} WHERE field_ref_str_value IS NOT NULL AND field_ref_str_value <> ''"
    )->fetchField();
    $counts['records'] = array_sum(array_intersect_key($counts, array_flip(self::RECORD_BUNDLES)));
    $this->cache->set(self::COUNTS_CID, $counts, $this->time->getRequestTime() + self::CACHE_MAX_AGE, ['node_list']);
    return $counts;
  }

  /**
   * Returns the status-bar rows, pre-formatted for display.
   *
   * @return array<int, array{label: string, value: string}>
   *   Label/value pairs for the archive status bar.
   */
  public function getCounts(): array {
    $raw = $this->getRawCounts();
    return [
      ['label' => 'Records', 'value' => number_format($raw['records'])],
      ['label' => 'Biographies', 'value' => number_format($raw['biography'])],
      ['label' => 'Events', 'value' => number_format($raw['event'])],
      ['label' => 'Places', 'value' => number_format($raw['place'])],
      ['label' => 'Documents', 'value' => number_format($raw['archive'])],
      ['label' => 'Records with sources', 'value' => number_format($raw['sources'])],
    ];
  }

  /**
   * Returns the six browse-index squares.
   *
   * Topics and Events point at the closest existing landings
   * (/politics-society, /timelines) until S5 builds dedicated indexes.
   * Classroom has no clean bundle count, so its count is NULL (hidden).
   *
   * @return array<int, array{label: string, type: string, href: string, count: string|null}>
   *   Browse items for the saho-browse-index component.
   */
  public function getBrowseTypes(): array {
    $raw = $this->getRawCounts();
    return [
      [
        'label' => 'Biographies',
        'type' => 'biography',
        'href' => '/biographies',
        'count' => number_format($raw['biography']),
      ],
      [
        'label' => 'Topics',
        'type' => 'topic',
        'href' => '/politics-society',
        'count' => number_format($raw['article']),
      ],
      [
        'label' => 'Places',
        'type' => 'place',
        'href' => '/places',
        'count' => number_format($raw['place']),
      ],
      [
        'label' => 'Events',
        'type' => 'event',
        'href' => '/timelines',
        'count' => number_format($raw['event']),
      ],
      [
        'label' => 'Archive',
        'type' => 'archive',
        'href' => '/archives',
        'count' => number_format($raw['archive']),
      ],
      [
        'label' => 'Classroom',
        'type' => 'article',
        'href' => '/classroom',
        'count' => NULL,
        'note' => 'Lessons in 11 languages',
      ],
    ];
  }

  /**
   * Returns index-table rows for the most recently added records.
   *
   * @param int $limit
   *   Maximum number of rows.
   *
   * @return array<int, array<string, string>>
   *   Rows shaped for the saho-index-table component.
   */
  public function getRecent(int $limit = 12): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $nids = $storage->getQuery()
      ->condition('type', self::RECORD_BUNDLES, 'IN')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->execute();
    $rows = [];
    foreach ($storage->loadMultiple($nids) as $node) {
      if (!$node instanceof NodeInterface) {
        continue;
      }
      $rows[] = [
        'ref' => $this->displayRef->getRef($node),
        'type' => $node->bundle(),
        'title' => (string) $node->label(),
        'href' => $node->toUrl()->toString(),
        'dates' => $this->recordDates($node),
        'status' => $this->displayRef->getStatus($node),
      ];
    }
    return $rows;
  }

  /**
   * Builds the dates cell for a record.
   *
   * The biography date fields are free-text strings, so they are shown
   * verbatim and never run through date formatting. Records without them
   * fall back to the created timestamp.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The record node.
   *
   * @return string
   *   The dates string.
   */
  private function recordDates(NodeInterface $node): string {
    $parts = [];
    foreach (['field_dob', 'field_dod'] as $field) {
      if ($node->hasField($field) && !$node->get($field)->isEmpty()) {
        $parts[] = trim((string) $node->get($field)->value);
      }
    }
    $parts = array_filter($parts);
    if ($parts !== []) {
      return implode(' - ', $parts);
    }
    return $this->dateFormatter->format((int) $node->getCreatedTime(), 'custom', 'j M Y');
  }

}
