<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_connections\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\saho_connections\Controller\ConnectionsHubController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tests the connections hub controller: chips, paging, guards.
 *
 * @group saho_connections
 */
final class ConnectionsHubControllerTest extends KernelTestBase {

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
    'saho_refs',
    'saho_connections',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);

    foreach (['article', 'archive', 'biography', 'page'] as $bundle) {
      NodeType::create(['type' => $bundle, 'name' => $bundle])->save();
    }
    FieldStorageConfig::create([
      'field_name' => 'field_feature_parent',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => ['target_type' => 'node'],
    ])->save();
    foreach (['article', 'archive', 'biography'] as $bundle) {
      FieldConfig::create([
        'field_name' => 'field_feature_parent',
        'entity_type' => 'node',
        'bundle' => $bundle,
      ])->save();
    }
    // The curated inventory SQL joins this field's table unconditionally.
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

    $this->setUpCurrentUser(['uid' => 1]);
    $this->container->get('router.builder')->rebuild();
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
   * Builds a parent with 12 archive children and 1 biography child.
   */
  private function makeCollection(): Node {
    $parent = $this->makeNode('article', 'Collection head');
    for ($i = 1; $i <= 12; $i++) {
      $this->makeNode('archive', sprintf('Doc %02d', $i), [
        'field_feature_parent' => $parent->id(),
      ]);
    }
    $this->makeNode('biography', 'Child person', [
      'field_feature_parent' => $parent->id(),
    ]);
    return $parent;
  }

  /**
   * Runs the controller with the given query parameters.
   */
  private function runPage(Node $node, array $query = []): array {
    $request = Request::create('/node/' . $node->id() . '/connections', 'GET', $query);
    $this->container->get('request_stack')->push($request);
    try {
      return ConnectionsHubController::create($this->container)->page($node);
    }
    finally {
      $this->container->get('request_stack')->pop();
    }
  }

  /**
   * The hub renders chips and items matching the builder's inventory.
   */
  public function testHubStructure(): void {
    $parent = $this->makeCollection();
    $build = $this->runPage($node = $parent);

    $this->assertSame('saho_connections_hub', $build['#theme']);
    $this->assertSame('Collection head', $build['#record']['title']);
    $this->assertSame(13, $build['#record']['total']);

    $chips = array_column($build['#chips'], 'count', 'id');
    $this->assertSame(['people' => 1, 'archive' => 12], $chips);

    // Default tab = first inventory entry (rail order: people first).
    $this->assertSame('people', $build['#active']['id']);
    $this->assertCount(1, $build['#items']);
    $this->assertSame(1, $build['#total']);
    $this->assertSame('', $build['#collection_url']);

    // Cache metadata: host node tags + the builder's node_list set.
    $this->assertContains('node:' . $node->id(), $build['#cache']['tags']);
    $this->assertContains('node_list:archive', $build['#cache']['tags']);
    $this->assertContains('url.query_args:tab', $build['#cache']['contexts']);
  }

  /**
   * The archive tab pages the full list and links the archive index.
   */
  public function testArchiveTab(): void {
    $parent = $this->makeCollection();
    $build = $this->runPage($parent, ['tab' => 'archive']);

    $this->assertSame('archive', $build['#active']['id']);
    $this->assertSame(12, $build['#total']);
    $this->assertCount(12, $build['#items']);
    $this->assertStringContainsString('collection=' . $parent->id(), $build['#collection_url']);
    // Chips carry hub URLs with tab preselection.
    foreach ($build['#chips'] as $chip) {
      $this->assertStringContainsString('/node/' . $parent->id() . '/connections?tab=' . $chip['id'], $chip['href']);
    }
  }

  /**
   * A bogus ?tab= falls back to the default tab, never errors.
   */
  public function testBogusTabFallsBack(): void {
    $parent = $this->makeCollection();
    $build = $this->runPage($parent, ['tab' => 'bogus']);
    $this->assertSame('people', $build['#active']['id']);
    // Array abuse also falls back (the flood subscriber caps it upstream).
    $build = $this->runPage($parent, ['tab' => ['a', 'b']]);
    $this->assertSame('people', $build['#active']['id']);
  }

  /**
   * Non-record bundles and records without connections 404.
   */
  public function testGuards(): void {
    $page = $this->makeNode('page', 'About us');
    try {
      $this->runPage($page);
      $this->fail('Non-record bundle should 404.');
    }
    catch (NotFoundHttpException) {
    }

    $bare = $this->makeNode('article', 'No relations');
    try {
      $this->runPage($bare);
      $this->fail('A record without connections should 404.');
    }
    catch (NotFoundHttpException) {
    }
  }

  /**
   * The title callback names the record.
   */
  public function testTitle(): void {
    $parent = $this->makeCollection();
    $this->assertSame(
      'Connections · Collection head',
      ConnectionsHubController::create($this->container)->title($parent)
    );
  }

}
