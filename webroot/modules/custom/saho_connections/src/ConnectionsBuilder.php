<?php

declare(strict_types=1);

namespace Drupal\saho_connections;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Builds a record's cross-reference tabs, counts and full paged lists.
 *
 * Extracted verbatim from the saho theme's _saho_record_related_props()
 * pipeline (typed *_related_tab fields + curated feature-parent inventory +
 * reverse gallery edges, with rerouting, same-id merge, href dedupe and
 * threshold collapse) so that modules - the connections hub controller in
 * particular - can read the same spec the rail renders. tabs() output is
 * shape-identical to the pre-extraction theme output; items() exposes the
 * full lists the rail deliberately bounds.
 */
final class ConnectionsBuilder implements ConnectionsBuilderInterface {

  /**
   * Cross-reference lists past this length collapse to count + samples.
   *
   * "A rail is for apparatus; 20k records is a query": high-degree nodes
   * render a bounded collection summary, and the index page IS the list.
   */
  public const THRESHOLD = 10;

  /**
   * Term 51 "Organisations" - the classifier the old view filtered on.
   */
  public const ORG_TID = 51;

  /**
   * Article-type 2985 "Timeline" - the other feature_children classifier.
   */
  public const TIMELINE_TYPE = 2985;

  /**
   * Bundles whose typed fields feed the tabs, keyed by field name.
   */
  private const TYPED_FIELD_MAP = [
    'field_people_related_tab' => ['id' => 'people', 'label' => 'People'],
    'field_organizations_related_tab' => ['id' => 'organisations', 'label' => 'Organisations'],
    'field_topics_related_tab' => ['id' => 'topics', 'label' => 'Topics'],
    'field_timelines_related_tab' => ['id' => 'timelines', 'label' => 'Timelines'],
  ];

  /**
   * Per-request memo of built specs, keyed by nid.
   *
   * A record page builds its rail more than once per render; an instance
   * property (not a static) so kernel tests can reset it.
   */
  private array $memo = [];

  public function __construct(
    private readonly Connection $database,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function tabs(NodeInterface $node): array {
    $tabs = array_column($this->build($node)['tabs'], 'props');
    return $tabs !== [] ? ['title' => 'Cross-references', 'tabs' => $tabs] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function inventory(NodeInterface $node): array {
    $inventory = [];
    foreach ($this->build($node)['tabs'] as $id => $tab) {
      $inventory[$id] = [
        'label' => $tab['props']['label'],
        'count' => $tab['props']['count'],
      ];
    }
    return $inventory;
  }

  /**
   * {@inheritdoc}
   */
  public function totalCount(NodeInterface $node): int {
    $total = 0;
    foreach ($this->build($node)['tabs'] as $tab) {
      $total += $tab['props']['count'];
    }
    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function items(NodeInterface $node, string $tab_id, int $page, int $page_size): array {
    $spec = $this->build($node)['tabs'][$tab_id] ?? NULL;
    if ($spec === NULL || $page < 0 || $page_size < 1) {
      return ['label' => '', 'total' => 0, 'items' => []];
    }
    $offset = $page * $page_size;
    $items = match ($spec['source']) {
      // Typed/merged/small tabs: the full list is already materialized in
      // the props (the rail never truncates these).
      'materialized' => array_slice($spec['props']['items'], $offset, $page_size),
      // Curated collection tabs: the rail holds a bounded sample; page over
      // the full ordered nid list captured from the inventory query.
      'curated' => $this->materialize(array_slice($spec['nids'], $offset, $page_size)),
      // Reverse gallery edges: re-run the indexed query for the window.
      'gallery' => $this->materialize($this->galleryWindowNids((int) $node->id(), $offset, $page_size)),
    };
    return [
      'label' => $spec['props']['label'],
      'total' => $spec['props']['count'],
      'items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function cacheTags(): array {
    // Image joins the set for the reverse Galleries tab (images referencing
    // a record change what the tab shows).
    return [
      'node_list:article',
      'node_list:biography',
      'node_list:place',
      'node_list:archive',
      'node_list:image',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(): void {
    $this->memo = [];
  }

  /**
   * Builds the record's full connections spec, memoized per nid.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The record node.
   *
   * @return array
   *   ['tabs' => [tab_id => ['props' => array, 'source' => string, ...]]]
   *   where props is the exact rail tab array and source is one of
   *   'materialized' (props items ARE the full list), 'curated' (full
   *   ordered nid list in 'nids'), or 'gallery' (window re-queried).
   */
  private function build(NodeInterface $node): array {
    $nid = (int) $node->id();
    if (isset($this->memo[$nid])) {
      return $this->memo[$nid];
    }

    $threshold = self::THRESHOLD;
    $tabs = [];
    $rerouted = [];
    foreach (self::TYPED_FIELD_MAP as $field => $tab) {
      if (!$node->hasField($field) || $node->get($field)->isEmpty()) {
        continue;
      }
      $items = [];
      foreach ($node->get($field)->referencedEntities() as $ref) {
        $item = $this->relatedItem($ref);
        if ($item === NULL) {
          continue;
        }
        // The People tab must list people. field_people_related_tab's legacy
        // config accepts article targets, and the sibling enrichment fills it
        // with whole collections ("people-relation-capable" was the only typed
        // field biographies carry) - so route every non-biography reference to
        // the tab its actual bundle belongs on instead of mislabelling it.
        // The other typed fields are article-target fields where the editor's
        // field choice IS the classification; they pass through untouched.
        if ($field === 'field_people_related_tab' && $item['type'] !== 'biography') {
          $home = $this->tabForBundle($item['type']);
          $rerouted[$home['id']]['tab'] = $home;
          $rerouted[$home['id']]['items'][] = $item;
          continue;
        }
        $items[] = $item;
      }
      if ($items !== []) {
        $tabs[$tab['id']] = $tab + ['count' => count($items), 'items' => $items];
      }
    }
    foreach ($rerouted as $id => $bucket) {
      if (!isset($tabs[$id])) {
        $tabs[$id] = $bucket['tab'] + ['count' => 0, 'items' => []];
      }
      $seen = array_column($tabs[$id]['items'], 'href');
      foreach ($bucket['items'] as $item) {
        if (!in_array($item['href'], $seen, TRUE)) {
          $tabs[$id]['items'][] = $item;
        }
      }
      $tabs[$id]['count'] = count($tabs[$id]['items']);
    }

    // Every typed tab is fully materialized: its props items ARE the full
    // list, whatever the merge below does to them.
    $sources = array_fill_keys(array_keys($tabs), ['source' => 'materialized']);

    // The curated feature-children (the legacy rail accordion) join the same
    // cross-reference engine as tabs, per wireframes wf03/wf04: one engine,
    // typed dots, counts. Same-id tabs merge and dedupe by href; a collapsed
    // curated tab (collection summary) owns its slot wholesale - the whole
    // point is that the full list never renders in-page.
    $curated = $this->curatedTabs($node);
    $gallery = $this->galleryTab($node);
    $merged_tabs = [];
    foreach ($curated['tabs'] as $curated_tab) {
      $merged_tabs[] = ['tab' => $curated_tab, 'is_gallery' => FALSE];
    }
    if ($gallery !== NULL) {
      $merged_tabs[] = ['tab' => $gallery, 'is_gallery' => TRUE];
    }
    foreach ($merged_tabs as $merged) {
      $tab = $merged['tab'];
      $id = $tab['id'];
      $is_gallery_source = $merged['is_gallery'];
      if (isset($tab['summary']) && isset($tabs[$id]['summary'])) {
        // Two collapsed summaries for one slot cannot merge honestly; the
        // curated one (first in) wins.
        continue;
      }
      if (isset($tab['summary']) || !isset($tabs[$id])) {
        $tabs[$id] = $tab;
        if (isset($tab['summary'])) {
          // Collapsed slot: the props hold a bounded sample; record where the
          // full list lives so items() can page it.
          $sources[$id] = $is_gallery_source
            ? ['source' => 'gallery']
            : ['source' => 'curated', 'nids' => $curated['full'][$id]['nids']];
        }
        else {
          $sources[$id] = ['source' => 'materialized'];
        }
        continue;
      }
      $seen = array_column($tabs[$id]['items'], 'href');
      foreach ($tab['items'] as $item) {
        if (!in_array($item['href'], $seen, TRUE)) {
          $tabs[$id]['items'][] = $item;
        }
      }
      $tabs[$id]['count'] = count($tabs[$id]['items']);
      $sources[$id] = ['source' => 'materialized'];
    }

    // Past the threshold a tab gets a count line, but the typed *_related_tab
    // sets are editorially curated, already fully loaded, and have no index
    // page to defer to - so keep every item and let the panel scroll (the
    // count line pins to the top), instead of stranding the reader at three
    // rows with no way to reach the rest. Curated feature-children tabs keep
    // their own count-plus-samples summary and browse CTA (set above),
    // untouched here.
    foreach ($tabs as $id => $tab) {
      if (!isset($tab['summary']) && count($tab['items']) > $threshold) {
        $tabs[$id]['summary'] = [
          'count_line' => number_format($tab['count']) . ' ' . mb_strtoupper($tab['label']),
        ];
      }
    }

    // Every tab gets a hub exit: the rail is a bounded sample, the hub page
    // IS the list. Pinned to the panel foot by the component.
    foreach ($tabs as $id => $tab) {
      $tabs[$id]['cta'] = [
        'label' => $tab['count'] > 1
          ? 'View all ' . number_format($tab['count']) . ' ' . $this->ctaNoun($id, $tab['label'])
          : 'View in the connections register',
        'href' => Url::fromRoute('saho_connections.hub', ['node' => $nid], ['query' => ['tab' => $id]])->toString(),
      ];
    }

    $spec = ['tabs' => []];
    foreach ($tabs as $id => $tab) {
      $spec['tabs'][$id] = $sources[$id] + ['props' => $tab];
    }
    return $this->memo[$nid] = $spec;
  }

  /**
   * Builds cross-reference tabs from the curated feature-parent inventory.
   *
   * These curated per-bundle child listings used to render as the rail
   * accordion; the Open Record design folds them into related-tabs.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The record node.
   *
   * @return array
   *   ['tabs' => tab arrays keyed by id,
   *    'full' => [tab_id => ['nids' => int[], 'total' => int]] for every
   *    bucket, in the same order the rail samples them].
   */
  private function curatedTabs(NodeInterface $node): array {
    $empty = ['tabs' => [], 'full' => []];
    if (!in_array($node->bundle(), ['article', 'archive', 'biography', 'place'], TRUE)) {
      return $empty;
    }
    $nid = (int) $node->id();
    // Parity with the retired feature_children views, whose parent-status
    // filter meant an unpublished record rendered no curated tabs.
    if (!$node->isPublished()) {
      return $empty;
    }

    // One indexed inventory query replaces the seven feature_children view
    // executions (and their per-display COUNTs) this pipeline used to run.
    // The views' rendered output was discarded anyway - only each child's
    // bundle plus two flags decide its bucket, mirroring the view displays:
    // Organisations = article carrying term 51, Timelines = article with
    // field_article_type 2985, Articles = article with neither (an article
    // carrying both appears under Organisations AND Timelines, as the views
    // did). Children are deduped across translations (default_langcode) and,
    // unlike the views, counted only when published - the old totals silently
    // included unpublished children the visitor could never see.
    $rows = $this->database->query(
      'SELECT DISTINCT c.nid, c.type, c.title, c.created,
        (org.nid IS NOT NULL) AS is_org,
        (tl.entity_id IS NOT NULL) AS is_timeline
      FROM {node__field_feature_parent} p
      INNER JOIN {node_field_data} c
        ON c.nid = p.entity_id AND c.status = 1 AND c.default_langcode = 1
      LEFT JOIN {taxonomy_index} org
        ON org.nid = c.nid AND org.tid = :org_tid
      LEFT JOIN {node__field_article_type} tl
        ON tl.entity_id = c.nid AND tl.deleted = 0
        AND tl.field_article_type_target_id = :timeline_type
      WHERE p.field_feature_parent_target_id = :nid AND p.deleted = 0',
      [
        ':nid' => $nid,
        ':org_tid' => self::ORG_TID,
        ':timeline_type' => self::TIMELINE_TYPE,
      ]
    )->fetchAll();
    if ($rows === []) {
      return $empty;
    }

    $buckets = [
      'articles' => ['label' => 'Articles', 'rows' => []],
      'people' => ['label' => 'People', 'rows' => []],
      'places' => ['label' => 'Places', 'rows' => []],
      'organisations' => ['label' => 'Organisations', 'rows' => []],
      'timelines' => ['label' => 'Timelines', 'rows' => []],
      'archive' => ['label' => 'Archive', 'rows' => []],
    ];
    foreach ($rows as $row) {
      switch ($row->type) {
        case 'biography':
          $buckets['people']['rows'][] = $row;
          break;

        case 'place':
          $buckets['places']['rows'][] = $row;
          break;

        case 'archive':
          $buckets['archive']['rows'][] = $row;
          break;

        case 'article':
          if ($row->is_org) {
            $buckets['organisations']['rows'][] = $row;
          }
          if ($row->is_timeline) {
            $buckets['timelines']['rows'][] = $row;
          }
          if (!$row->is_org && !$row->is_timeline) {
            $buckets['articles']['rows'][] = $row;
          }
          break;
      }
    }

    // Ordering parity: the Timelines/Archive views sorted the child title
    // ascending; the other displays only sorted parent columns (constant per
    // query), leaving child order undefined - newest-first is the
    // deterministic replacement. Nid tiebreaks keep runs stable.
    $by_title = static fn(object $a, object $b): int => strcasecmp($a->title, $b->title) ?: $a->nid <=> $b->nid;
    $by_newest = static fn(object $a, object $b): int => $b->created <=> $a->created ?: $b->nid <=> $a->nid;
    $threshold = self::THRESHOLD;

    // Fetch one row past the collapse threshold per bucket (never an unbounded
    // child list - the DISA head has 20k+); one loadMultiple covers every tab.
    // The full ordered nid list per bucket is retained so the hub page can
    // page over it without a second counting path.
    $slices = [];
    $full = [];
    $load_nids = [];
    foreach ($buckets as $id => $bucket) {
      if ($bucket['rows'] === []) {
        continue;
      }
      usort($bucket['rows'], in_array($id, ['timelines', 'archive'], TRUE) ? $by_title : $by_newest);
      $ordered_nids = array_map(static fn(object $row): int => (int) $row->nid, $bucket['rows']);
      $full[$id] = [
        'nids' => $ordered_nids,
        'total' => count($ordered_nids),
      ];
      $slice_nids = array_slice($ordered_nids, 0, $threshold + 1);
      $slices[$id] = [
        'label' => $bucket['label'],
        'total' => count($ordered_nids),
        'nids' => $slice_nids,
      ];
      $load_nids = array_merge($load_nids, $slice_nids);
    }
    $children = $this->entityTypeManager->getStorage('node')->loadMultiple(array_unique($load_nids));

    $tabs = [];
    foreach ($slices as $id => $spec) {
      $items = [];
      foreach ($spec['nids'] as $child_nid) {
        $item = isset($children[$child_nid]) ? $this->relatedItem($children[$child_nid]) : NULL;
        if ($item !== NULL) {
          $items[] = $item;
        }
      }
      if ($items === []) {
        continue;
      }
      $total = max($spec['total'], count($items));
      if ($total > $threshold) {
        // Collection summary: mono count line, then the bounded set of already
        // loaded rows (up to the threshold) inside a scrollable panel, and one
        // CTA to the pre-filtered index. The full list still lives on the
        // index - we never load 20k rows into the rail - but a dead 3-row stub
        // gave no sense of the collection, so surface as many as were cheaply
        // fetched. Only the archive index exists today, so only the Archive
        // tab gets the browse CTA.
        $summary = [
          'count_line' => number_format($total) . ' ' . ($id === 'archive' ? 'ARCHIVE ITEMS' : mb_strtoupper($spec['label'])),
        ];
        if ($id === 'archive') {
          $summary['cta'] = [
            'label' => 'Browse the ' . $node->label() . ' collection',
            'href' => Url::fromUserInput('/archives', ['query' => ['collection' => $node->id()]])->toString(),
          ];
        }
        $tabs[$id] = [
          'id' => $id,
          'label' => $spec['label'],
          'count' => $total,
          'items' => array_slice($items, 0, $threshold),
          'summary' => $summary,
        ];
      }
      else {
        $tabs[$id] = [
          'id' => $id,
          'label' => $spec['label'],
          'count' => $total,
          'items' => $items,
        ];
      }
    }

    // Galleries parity: the block_7 display aggregated the record's OWN
    // field_gallery_tag (no child in sight), so a gallery-tagged collection
    // head surfaced itself as a single Galleries item. Quirk preserved
    // verbatim - the tab still only appears when the record has children,
    // exactly as the old has-children short-circuit guaranteed.
    if ($node->hasField('field_gallery_tag') && !$node->get('field_gallery_tag')->isEmpty()) {
      $self = $this->relatedItem($node);
      if ($self !== NULL) {
        $tabs['galleries'] = [
          'id' => 'galleries',
          'label' => 'Galleries',
          'count' => 1,
          'items' => [$self],
        ];
      }
    }

    return ['tabs' => $tabs, 'full' => $full];
  }

  /**
   * Builds the reverse Galleries tab: images that reference this record.
   *
   * The image -> biography edges written by the saho_relations title-match
   * pipeline live on the IMAGE side (field_people_related_tab), so the
   * biography surfaces them at render time - one indexed COUNT when empty
   * (the common case), threshold+1 loads when not. Rolling back the image
   * edges empties this tab automatically.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The record node.
   *
   * @return array|null
   *   A tab shaped like a curated tab (id 'galleries', so the same-id merge
   *   folds it into any existing Galleries tab), or NULL when no images
   *   reference this record.
   */
  private function galleryTab(NodeInterface $node): ?array {
    // Phase A: biographies. Places follow once image.field_topics_related_tab
    // accepts place targets.
    if ($node->bundle() !== 'biography') {
      return NULL;
    }
    $nid = (int) $node->id();
    $threshold = self::THRESHOLD;
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'image')
      ->condition('status', 1)
      ->condition('field_people_related_tab', $nid)
      ->accessCheck(FALSE);
    $total = (int) (clone $query)->count()->execute();
    if ($total === 0) {
      return NULL;
    }
    $image_nids = $query->sort('created', 'DESC')
      ->range(0, $threshold + 1)
      ->execute();
    $items = $this->materialize(array_map('intval', array_values($image_nids)));
    if ($items === []) {
      return NULL;
    }
    $tab = [
      'id' => 'galleries',
      'label' => 'Galleries',
      'count' => max($total, count($items)),
      'items' => $total > $threshold ? array_slice($items, 0, $threshold) : $items,
    ];
    if ($total > $threshold) {
      $tab['summary'] = [
        'count_line' => number_format($total) . ' IMAGES',
      ];
    }
    return $tab;
  }

  /**
   * Fetches one page window of reverse-gallery image nids.
   *
   * @param int $nid
   *   The record nid the images reference.
   * @param int $offset
   *   Zero-based row offset.
   * @param int $limit
   *   Window size.
   *
   * @return int[]
   *   Image nids, newest first (nid tiebreak keeps pages stable).
   */
  private function galleryWindowNids(int $nid, int $offset, int $limit): array {
    $image_nids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'image')
      ->condition('status', 1)
      ->condition('field_people_related_tab', $nid)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->sort('nid', 'DESC')
      ->range($offset, $limit)
      ->execute();
    return array_map('intval', array_values($image_nids));
  }

  /**
   * Loads nodes and builds item rows, preserving the given nid order.
   *
   * @param int[] $nids
   *   Node ids in display order.
   *
   * @return array
   *   Item rows; nodes the current user cannot view are skipped.
   */
  private function materialize(array $nids): array {
    if ($nids === []) {
      return [];
    }
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    $items = [];
    foreach ($nids as $nid) {
      $item = isset($nodes[$nid]) ? $this->relatedItem($nodes[$nid]) : NULL;
      if ($item !== NULL) {
        $items[] = $item;
      }
    }
    return $items;
  }

  /**
   * Builds one related-tabs item from a referenced node, or NULL.
   *
   * @param mixed $ref
   *   The candidate entity.
   *
   * @return array|null
   *   Item props (label/href/note/type), or NULL when not viewable.
   */
  private function relatedItem($ref): ?array {
    $known_types = ['article', 'biography', 'place', 'archive', 'event', 'topic', 'image'];
    if (!$ref instanceof NodeInterface || !$ref->access('view')) {
      return NULL;
    }
    $note = '';
    foreach (['field_synopsis', 'body'] as $note_field) {
      if ($ref->hasField($note_field) && !$ref->get($note_field)->isEmpty()) {
        $note = trim(html_entity_decode(strip_tags((string) $ref->get($note_field)->value), ENT_QUOTES | ENT_HTML5));
        if ($note !== '') {
          break;
        }
      }
    }
    return [
      'label' => (string) $ref->label(),
      'href' => $ref->toUrl()->toString(),
      'note' => $note !== '' ? Unicode::truncate($note, 90, TRUE, TRUE) : '',
      'type' => in_array($ref->bundle(), $known_types, TRUE) ? $ref->bundle() : 'article',
    ];
  }

  /**
   * The noun for a tab's "View all N ..." hub exit.
   *
   * Matches the count-line vocabulary: the archive tab counts ARCHIVE
   * ITEMS, the galleries tab counts IMAGES; every other tab's label
   * lowercases cleanly.
   *
   * @param string $id
   *   The tab id.
   * @param string $label
   *   The tab label.
   *
   * @return string
   *   A plural noun phrase.
   */
  private function ctaNoun(string $id, string $label): string {
    return match ($id) {
      'archive' => 'archive items',
      'galleries' => 'images',
      default => mb_strtolower($label),
    };
  }

  /**
   * Maps a related item's bundle to the cross-reference tab it belongs on.
   *
   * Ids reuse the curated-tab slots so the same-id merge folds everything
   * into one tab per kind. Articles read as Topics (the dictionary's own
   * article -> topic kind mapping); unknown bundles land there too rather
   * than under People.
   *
   * @param string $bundle
   *   The referenced node's bundle.
   *
   * @return array
   *   ['id' => string, 'label' => string].
   */
  private function tabForBundle(string $bundle): array {
    return match ($bundle) {
      'biography' => ['id' => 'people', 'label' => 'People'],
      'place' => ['id' => 'places', 'label' => 'Places'],
      'archive' => ['id' => 'archive', 'label' => 'Archive'],
      'event' => ['id' => 'events', 'label' => 'Events'],
      'image' => ['id' => 'galleries', 'label' => 'Galleries'],
      default => ['id' => 'topics', 'label' => 'Topics'],
    };
  }

}
