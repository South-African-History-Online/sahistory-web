<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_connections\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\saho_connections\ConnectionsBuilder;
use Drupal\saho_connections\ConnectionsBuilderInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the connections builder: rail parity, counts, full paged lists.
 *
 * @group saho_connections
 */
final class ConnectionsBuilderTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'taxonomy',
    'saho_connections',
  ];

  /**
   * The builder under test.
   */
  private ConnectionsBuilderInterface $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    // maintain_index_table: the curated org classifier reads taxonomy_index.
    $this->installConfig(['taxonomy']);

    foreach (['article', 'biography', 'place', 'archive', 'image', 'event'] as $bundle) {
      NodeType::create(['type' => $bundle, 'name' => $bundle])->save();
    }
    Vocabulary::create(['vid' => 'tags', 'name' => 'Tags'])->save();

    // The typed relation field lives on biographies (enrichment) and images
    // (reverse gallery edges).
    FieldStorageConfig::create([
      'field_name' => 'field_people_related_tab',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'node'],
      'cardinality' => -1,
    ])->save();
    foreach (['biography', 'image'] as $bundle) {
      FieldConfig::create([
        'field_name' => 'field_people_related_tab',
        'entity_type' => 'node',
        'bundle' => $bundle,
      ])->save();
    }

    // Curated collection membership.
    FieldStorageConfig::create([
      'field_name' => 'field_feature_parent',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'node'],
    ])->save();
    foreach (['article', 'biography', 'place', 'archive'] as $bundle) {
      FieldConfig::create([
        'field_name' => 'field_feature_parent',
        'entity_type' => 'node',
        'bundle' => $bundle,
      ])->save();
    }

    // The two curated classifiers: term ORG_TID via taxonomy_index, and the
    // article-type target TIMELINE_TYPE (a raw target_id - the builder never
    // loads the term).
    FieldStorageConfig::create([
      'field_name' => 'field_article_type',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'taxonomy_term'],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_article_type',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'taxonomy_term'],
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_tags',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    // Admin current user: relatedItem() honours access('view').
    $this->setUpCurrentUser(['uid' => 1]);
    // The per-tab hub CTAs generate saho_connections.hub URLs.
    $this->container->get('router.builder')->rebuild();

    $this->builder = $this->container->get('saho_connections.builder');
  }

  /**
   * Creates a published node.
   */
  private function makeNode(string $bundle, string $title, array $values = []): Node {
    $node = Node::create($values + ['type' => $bundle, 'title' => $title, 'status' => 1]);
    $node->save();
    return $node;
  }

  /**
   * Returns the term whose tid is the organisations classifier.
   */
  private function makeOrgTerm(): Term {
    $term = NULL;
    do {
      $term = Term::create(['vid' => 'tags', 'name' => 'term']);
      $term->save();
    } while ((int) $term->id() < ConnectionsBuilder::ORG_TID);
    $this->assertSame(ConnectionsBuilder::ORG_TID, (int) $term->id());
    return $term;
  }

  /**
   * Returns the tab with the given id from a tabs() result.
   */
  private function tab(array $props, string $id): ?array {
    foreach ($props['tabs'] ?? [] as $tab) {
      if ($tab['id'] === $id) {
        return $tab;
      }
    }
    return NULL;
  }

  /**
   * Typed field items build tabs; non-biography refs reroute by bundle.
   */
  public function testTypedTabsAndRerouting(): void {
    $person = $this->makeNode('biography', 'Person A');
    $place = $this->makeNode('place', 'Place A');
    $article = $this->makeNode('article', 'Article A');
    $host = $this->makeNode('biography', 'Host', [
      'field_people_related_tab' => [$person->id(), $place->id(), $article->id()],
    ]);

    $props = $this->builder->tabs($host);
    $this->assertSame('Cross-references', $props['title']);

    $people = $this->tab($props, 'people');
    $this->assertNotNull($people);
    $this->assertSame(1, $people['count']);
    $this->assertSame('Person A', $people['items'][0]['label']);
    $this->assertSame('biography', $people['items'][0]['type']);
    $this->assertArrayNotHasKey('summary', $people);

    $places = $this->tab($props, 'places');
    $this->assertSame(1, $places['count']);
    $this->assertSame('Place A', $places['items'][0]['label']);

    // Articles land on Topics (the dictionary's article -> topic mapping).
    $topics = $this->tab($props, 'topics');
    $this->assertSame(1, $topics['count']);
    $this->assertSame('Article A', $topics['items'][0]['label']);
  }

  /**
   * Curated buckets classify children and collapse past the threshold.
   */
  public function testCuratedBucketsAndCollapse(): void {
    $parent = $this->makeNode('article', 'Collection head');
    $org_term = $this->makeOrgTerm();

    // Twelve archive children - past the threshold, title-sorted.
    for ($i = 1; $i <= 12; $i++) {
      $this->makeNode('archive', sprintf('Doc %02d', $i), [
        'field_feature_parent' => $parent->id(),
      ]);
    }
    // Classified articles: organisation, timeline, both, neither.
    $this->makeNode('article', 'Org child', [
      'field_feature_parent' => $parent->id(),
      'field_tags' => [$org_term->id()],
    ]);
    $this->makeNode('article', 'Timeline child', [
      'field_feature_parent' => $parent->id(),
      'field_article_type' => ['target_id' => ConnectionsBuilder::TIMELINE_TYPE],
    ]);
    $this->makeNode('article', 'Both child', [
      'field_feature_parent' => $parent->id(),
      'field_tags' => [$org_term->id()],
      'field_article_type' => ['target_id' => ConnectionsBuilder::TIMELINE_TYPE],
    ]);
    $this->makeNode('article', 'Plain child', [
      'field_feature_parent' => $parent->id(),
    ]);
    // An unpublished child never counts.
    $this->makeNode('archive', 'Hidden doc', [
      'field_feature_parent' => $parent->id(),
      'status' => 0,
    ]);

    $props = $this->builder->tabs($parent);

    $archive = $this->tab($props, 'archive');
    $this->assertSame(12, $archive['count']);
    $this->assertCount(ConnectionsBuilder::THRESHOLD, $archive['items']);
    $this->assertSame('12 ARCHIVE ITEMS', $archive['summary']['count_line']);
    $this->assertSame('Browse the Collection head collection', $archive['summary']['cta']['label']);
    $this->assertStringContainsString('collection=' . $parent->id(), $archive['summary']['cta']['href']);
    // Every tab carries a hub exit; nouns match the count-line vocabulary.
    $this->assertSame('View all 12 archive items', $archive['cta']['label']);
    $this->assertStringContainsString('/node/' . $parent->id() . '/connections?tab=archive', $archive['cta']['href']);
    $this->assertSame('View all 2 organisations', $this->tab($props, 'organisations')['cta']['label']);
    // Single-item tabs get the neutral label ("View all 1 articles" reads
    // wrong).
    $this->assertSame('View in the connections register', $this->tab($props, 'articles')['cta']['label']);
    // Title-ascending order.
    $this->assertSame('Doc 01', $archive['items'][0]['label']);
    $this->assertSame('Doc 10', $archive['items'][9]['label']);

    // "Both child" appears under Organisations AND Timelines, as the old
    // views did; "Plain child" only under Articles.
    $orgs = $this->tab($props, 'organisations');
    $this->assertSame(2, $orgs['count']);
    $timelines = $this->tab($props, 'timelines');
    $this->assertSame(2, $timelines['count']);
    $articles = $this->tab($props, 'articles');
    $this->assertSame(1, $articles['count']);
    $this->assertSame('Plain child', $articles['items'][0]['label']);

    // The hub pages the FULL archive list in rail order.
    $page_0 = $this->builder->items($parent, 'archive', 0, 10);
    $this->assertSame(12, $page_0['total']);
    $this->assertSame(array_column($archive['items'], 'href'), array_column($page_0['items'], 'href'));
    $page_1 = $this->builder->items($parent, 'archive', 1, 10);
    $this->assertCount(2, $page_1['items']);
    $this->assertSame('Doc 11', $page_1['items'][0]['label']);
    $this->assertSame('Doc 12', $page_1['items'][1]['label']);
    $this->assertSame([], $this->builder->items($parent, 'archive', 2, 10)['items']);

    // An unpublished collection head renders no curated tabs at all.
    $parent->setUnpublished()->save();
    $this->builder->resetCache();
    $this->assertSame([], $this->builder->tabs($parent));
  }

  /**
   * A collapsed curated tab owns its slot; typed same-id items yield.
   */
  public function testCuratedSummaryOwnsSlot(): void {
    $typed_ref = $this->makeNode('biography', 'Typed-only person');
    $host = $this->makeNode('biography', 'Host', [
      'field_people_related_tab' => [$typed_ref->id()],
    ]);
    for ($i = 1; $i <= 11; $i++) {
      $this->makeNode('biography', "Child person $i", [
        'field_feature_parent' => $host->id(),
      ]);
    }

    $props = $this->builder->tabs($host);
    $people = $this->tab($props, 'people');
    // The curated summary replaces the typed tab wholesale.
    $this->assertSame(11, $people['count']);
    $this->assertSame('11 PEOPLE', $people['summary']['count_line']);
    $this->assertArrayNotHasKey('cta', $people['summary']);

    // The hub list is the curated collection - the typed-only ref is not in
    // it (rail parity: the rail dropped it too).
    $all = $this->builder->items($host, 'people', 0, 50);
    $this->assertSame(11, $all['total']);
    $this->assertCount(11, $all['items']);
    $this->assertNotContains('Typed-only person', array_column($all['items'], 'label'));
  }

  /**
   * Reverse gallery edges build the Galleries tab, paged for the hub.
   */
  public function testGalleryReverseTab(): void {
    $bio = $this->makeNode('biography', 'Photographed person');
    for ($i = 1; $i <= 12; $i++) {
      $this->makeNode('image', "Photo $i", [
        'field_people_related_tab' => [$bio->id()],
        'created' => 1000000 + $i,
      ]);
    }

    $props = $this->builder->tabs($bio);
    $galleries = $this->tab($props, 'galleries');
    $this->assertSame(12, $galleries['count']);
    $this->assertCount(ConnectionsBuilder::THRESHOLD, $galleries['items']);
    $this->assertSame('12 IMAGES', $galleries['summary']['count_line']);
    $this->assertSame('View all 12 images', $galleries['cta']['label']);
    // Newest first.
    $this->assertSame('Photo 12', $galleries['items'][0]['label']);

    $page_1 = $this->builder->items($bio, 'galleries', 1, 10);
    $this->assertSame(12, $page_1['total']);
    $this->assertCount(2, $page_1['items']);
    $this->assertSame(['Photo 2', 'Photo 1'], array_column($page_1['items'], 'label'));
  }

  /**
   * Inventory and totalCount agree with tabs() everywhere.
   */
  public function testCountParity(): void {
    $parent = $this->makeNode('article', 'Head');
    for ($i = 1; $i <= 12; $i++) {
      $this->makeNode('archive', "Doc $i", ['field_feature_parent' => $parent->id()]);
    }
    $this->makeNode('biography', 'Child person', ['field_feature_parent' => $parent->id()]);

    $props = $this->builder->tabs($parent);
    $inventory = $this->builder->inventory($parent);
    $this->assertSame(count($props['tabs']), count($inventory));
    $sum = 0;
    foreach ($props['tabs'] as $tab) {
      $this->assertSame($tab['count'], $inventory[$tab['id']]['count']);
      $this->assertSame($tab['label'], $inventory[$tab['id']]['label']);
      $sum += $tab['count'];
    }
    $this->assertSame($sum, $this->builder->totalCount($parent));
  }

  /**
   * Unknown tabs and empty records return empty, never errors.
   */
  public function testEmptyAndUnknown(): void {
    $bare = $this->makeNode('event', 'No relations');
    $this->assertSame([], $this->builder->tabs($bare));
    $this->assertSame([], $this->builder->inventory($bare));
    $this->assertSame(0, $this->builder->totalCount($bare));
    $result = $this->builder->items($bare, 'people', 0, 10);
    $this->assertSame(0, $result['total']);
    $this->assertSame([], $result['items']);

    $parent = $this->makeNode('article', 'Head');
    $this->makeNode('archive', 'Doc', ['field_feature_parent' => $parent->id()]);
    $this->assertSame(0, $this->builder->items($parent, 'bogus', 0, 10)['total']);
    $this->assertSame([], $this->builder->items($parent, 'archive', -1, 10)['items']);
    $this->assertSame([], $this->builder->items($parent, 'archive', 0, 0)['items']);
  }

}
