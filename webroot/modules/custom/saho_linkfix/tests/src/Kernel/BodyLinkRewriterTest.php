<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_linkfix\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Safety invariants for the guarded body link rewriter.
 *
 * @group saho_linkfix
 */
final class BodyLinkRewriterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'saho_linkfix',
  ];

  /**
   * The rewriter under test.
   *
   * @var \Drupal\saho_linkfix\Service\BodyLinkRewriter
   */
  protected $rewriter;

  /**
   * A body that mentions the legacy href both as a link and in plain prose.
   */
  protected string $originalBody;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'filter', 'node']);

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    FieldStorageConfig::create([
      'field_name' => 'body',
      'entity_type' => 'node',
      'type' => 'text_with_summary',
    ])->save();
    FieldConfig::create([
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Body',
    ])->save();

    $this->rewriter = $this->container->get('saho_linkfix.body_rewriter');
  }

  /**
   * Create a page node with the given body HTML.
   */
  protected function makeNode(string $body): Node {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'body' => ['value' => $body, 'format' => 'plain_text'],
    ]);
    $node->save();
    return $node;
  }

  /**
   * Reload a node's stored body value.
   */
  protected function bodyOf(int $nid): string {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $storage->resetCache([$nid]);
    return (string) $storage->load($nid)->get('body')->value;
  }

  /**
   * Only the mapped href is rewritten; identical prose is left alone.
   */
  public function testRewritesOnlyMappedHref(): void {
    $body = '<p>See <a href="../../bios/dadoo,y.htm">Dadoo</a>. '
      . 'The file ../../bios/dadoo,y.htm was the old path.</p>';
    $node = $this->makeNode($body);

    $result = $this->rewriter->apply([
      [
        'nid' => (int) $node->id(),
        'field' => 'body',
        'replacements' => [['from' => '../../bios/dadoo,y.htm', 'to' => '/people/dr-yusuf-mohamed-dadoo']],
      ],
    ], ['dry_run' => FALSE]);

    $this->assertSame(1, $result['stats']['nodes_changed']);
    $this->assertSame(1, $result['stats']['links_replaced']);
    $stored = $this->bodyOf((int) $node->id());
    // The href is rewritten.
    $this->assertStringContainsString('href="/people/dr-yusuf-mohamed-dadoo"', $stored);
    // The bare prose occurrence is preserved (not inside an href attribute).
    $this->assertStringContainsString('The file ../../bios/dadoo,y.htm was the old path', $stored);
  }

  /**
   * Re-running the same job changes nothing (idempotent).
   */
  public function testIdempotent(): void {
    $node = $this->makeNode('<a href="../x.htm">x</a>');
    $job = [[
      'nid' => (int) $node->id(),
      'field' => 'body',
      'replacements' => [['from' => '../x.htm', 'to' => '/node/123']],
    ]];
    $this->rewriter->apply($job, ['dry_run' => FALSE]);
    $afterFirst = $this->bodyOf((int) $node->id());

    $second = $this->rewriter->apply($job, ['dry_run' => FALSE]);
    $this->assertSame(0, $second['stats']['nodes_changed']);
    $this->assertSame($afterFirst, $this->bodyOf((int) $node->id()));
  }

  /**
   * Dry run computes a plan but writes nothing.
   */
  public function testDryRunWritesNothing(): void {
    $body = '<a href="../x.htm">x</a>';
    $node = $this->makeNode($body);
    $result = $this->rewriter->apply([[
      'nid' => (int) $node->id(),
      'field' => 'body',
      'replacements' => [['from' => '../x.htm', 'to' => '/node/9']],
    ]], ['dry_run' => TRUE]);

    $this->assertSame(1, $result['stats']['nodes_changed']);
    $this->assertSame($body, $this->bodyOf((int) $node->id()));
  }

  /**
   * A non-matching replacement leaves the body untouched.
   */
  public function testUnmappedHrefUntouched(): void {
    $body = '<a href="/already/clean">x</a>';
    $node = $this->makeNode($body);
    $result = $this->rewriter->apply([[
      'nid' => (int) $node->id(),
      'field' => 'body',
      'replacements' => [['from' => '../not-present.htm', 'to' => '/node/1']],
    ]], ['dry_run' => FALSE]);

    $this->assertSame(0, $result['stats']['nodes_changed']);
    $this->assertSame($body, $this->bodyOf((int) $node->id()));
  }

  /**
   * Revert restores the original body exactly.
   */
  public function testRevertRestores(): void {
    $body = '<a href="../x.htm">x</a>';
    $node = $this->makeNode($body);
    $job = [[
      'nid' => (int) $node->id(),
      'field' => 'body',
      'replacements' => [['from' => '../x.htm', 'to' => '/node/55']],
    ]];
    $applied = $this->rewriter->apply($job, ['dry_run' => FALSE])['applied'];
    $this->assertNotSame($body, $this->bodyOf((int) $node->id()));

    $stats = $this->rewriter->revert($applied, ['dry_run' => FALSE]);
    $this->assertSame(1, $stats['reverted_items']);
    $this->assertSame($body, $this->bodyOf((int) $node->id()));
  }

}
