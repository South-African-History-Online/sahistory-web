<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_relations\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the append-only, reversible guarantees of the relation writer.
 *
 * @group saho_relations
 */
final class RelationWriterTest extends KernelTestBase {

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
    'search_api',
    'saho_relations',
  ];

  /**
   * A reference field name under the writer's whitelist.
   */
  protected const FIELD = 'field_people_related_tab';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);

    foreach (['article', 'biography'] as $bundle) {
      NodeType::create(['type' => $bundle, 'name' => $bundle])->save();
    }

    FieldStorageConfig::create([
      'field_name' => self::FIELD,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => -1,
      'settings' => ['target_type' => 'node'],
    ])->save();
    FieldConfig::create([
      'field_name' => self::FIELD,
      'entity_type' => 'node',
      'bundle' => 'article',
      'settings' => ['handler_settings' => ['target_bundles' => ['article' => 'article', 'biography' => 'biography']]],
    ])->save();
  }

  /**
   * Helper to create a published node.
   */
  protected function makeNode(string $bundle, array $values = []): Node {
    // $values take precedence so callers can override status etc.
    $node = Node::create($values + ['type' => $bundle, 'title' => $this->randomMachineName(), 'status' => 1]);
    $node->save();
    return $node;
  }

  /**
   * Existing references are preserved when new ones are appended.
   */
  public function testAppendOnlyPreservesExisting(): void {
    $bio_a = $this->makeNode('biography');
    $bio_b = $this->makeNode('biography');
    $article = $this->makeNode('article', [self::FIELD => [['target_id' => $bio_a->id()]]]);

    $writer = $this->container->get('saho_relations.relation_writer');
    $result = $writer->apply([
      ['source_nid' => $article->id(), 'field' => self::FIELD, 'target_id' => $bio_b->id()],
    ], ['dry_run' => FALSE]);

    $reloaded = Node::load($article->id());
    $ids = array_column($reloaded->get(self::FIELD)->getValue(), 'target_id');
    sort($ids);
    $expected = [(string) $bio_a->id(), (string) $bio_b->id()];
    sort($expected);
    $this->assertSame($expected, $ids, 'Pre-existing reference is kept and the new one appended.');
    $this->assertSame(1, $result['stats']['edges_added']);
  }

  /**
   * Re-applying the same edge adds nothing (idempotent).
   */
  public function testIdempotent(): void {
    $bio = $this->makeNode('biography');
    $article = $this->makeNode('article');
    $writer = $this->container->get('saho_relations.relation_writer');
    $edge = [['source_nid' => $article->id(), 'field' => self::FIELD, 'target_id' => $bio->id()]];

    $writer->apply($edge, ['dry_run' => FALSE]);
    $second = $writer->apply($edge, ['dry_run' => FALSE]);

    $this->assertSame(0, $second['stats']['edges_added']);
    $this->assertSame(1, $second['stats']['skipped_existing']);
    $ids = array_column(Node::load($article->id())->get(self::FIELD)->getValue(), 'target_id');
    $this->assertCount(1, $ids);
  }

  /**
   * Edges targeting a field outside the whitelist are rejected, never written.
   */
  public function testFieldWhitelistEnforced(): void {
    $bio = $this->makeNode('biography');
    $article = $this->makeNode('article');
    $writer = $this->container->get('saho_relations.relation_writer');
    $result = $writer->apply([
      ['source_nid' => $article->id(), 'field' => 'field_not_allowed', 'target_id' => $bio->id()],
    ], ['dry_run' => FALSE]);

    $this->assertSame(0, $result['stats']['edges_added']);
    $this->assertSame(1, $result['stats']['rejected_field']);
  }

  /**
   * Targets whose bundle is not permitted by the field are rejected.
   */
  public function testTargetBundleValidation(): void {
    $page = NodeType::create(['type' => 'page', 'name' => 'page']);
    $page->save();
    $disallowed = $this->makeNode('page');
    $article = $this->makeNode('article');

    $writer = $this->container->get('saho_relations.relation_writer');
    $result = $writer->apply([
      ['source_nid' => $article->id(), 'field' => self::FIELD, 'target_id' => $disallowed->id()],
    ], ['dry_run' => FALSE]);

    $this->assertSame(0, $result['stats']['edges_added']);
    $this->assertSame(1, $result['stats']['rejected_bundle']);
  }

  /**
   * Unpublished targets are never linked.
   */
  public function testUnpublishedTargetRejected(): void {
    $bio = $this->makeNode('biography', ['status' => 0]);
    $article = $this->makeNode('article');
    $writer = $this->container->get('saho_relations.relation_writer');
    $result = $writer->apply([
      ['source_nid' => $article->id(), 'field' => self::FIELD, 'target_id' => $bio->id()],
    ], ['dry_run' => FALSE]);

    $this->assertSame(0, $result['stats']['edges_added']);
    $this->assertSame(1, $result['stats']['rejected_target']);
  }

  /**
   * Dry run reports additions but writes nothing.
   */
  public function testDryRunWritesNothing(): void {
    $bio = $this->makeNode('biography');
    $article = $this->makeNode('article');
    $writer = $this->container->get('saho_relations.relation_writer');
    $result = $writer->apply([
      ['source_nid' => $article->id(), 'field' => self::FIELD, 'target_id' => $bio->id()],
    ], ['dry_run' => TRUE]);

    $this->assertSame(1, $result['stats']['edges_added']);
    $this->assertCount(0, Node::load($article->id())->get(self::FIELD)->getValue());
  }

  /**
   * Rollback removes only what the writer added, leaving prior refs intact.
   */
  public function testRollbackRemovesOnlyAdded(): void {
    $bio_a = $this->makeNode('biography');
    $bio_b = $this->makeNode('biography');
    $article = $this->makeNode('article', [self::FIELD => [['target_id' => $bio_a->id()]]]);

    $writer = $this->container->get('saho_relations.relation_writer');
    $applied = $writer->apply([
      ['source_nid' => $article->id(), 'field' => self::FIELD, 'target_id' => $bio_b->id()],
    ], ['dry_run' => FALSE])['applied'];

    $rollback = $this->container->get('saho_relations.rollback_manager');
    $stats = $rollback->revert($applied, ['dry_run' => FALSE]);

    $this->assertSame(1, $stats['refs_removed']);
    $ids = array_column(Node::load($article->id())->get(self::FIELD)->getValue(), 'target_id');
    $this->assertSame([(string) $bio_a->id()], $ids, 'Only the writer-added reference is removed; the original remains.');
  }

}
