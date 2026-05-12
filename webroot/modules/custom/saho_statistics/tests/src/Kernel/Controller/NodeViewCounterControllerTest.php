<?php

namespace Drupal\Tests\saho_statistics\Kernel\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Flood\MemoryBackend;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\saho_statistics\Controller\NodeViewCounterController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the lightweight node-view counter endpoint.
 *
 * @group saho_statistics
 * @coversDefaultClass \Drupal\saho_statistics\Controller\NodeViewCounterController
 */
class NodeViewCounterControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'saho_statistics',
    'entity_usage',
    'system',
    'user',
    'node',
    'field',
    'text',
    'filter',
  ];

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The controller under test.
   *
   * @var \Drupal\saho_statistics\Controller\NodeViewCounterController
   */
  protected $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('saho_statistics', ['saho_node_counter']);
    $this->installConfig(['system', 'node', 'filter', 'saho_statistics']);

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();

    // Swap the flood backend to in-memory so tests don't need the DB flood
    // schema and state stays per-test.
    $this->container->set(
      'flood',
      new MemoryBackend($this->container->get('request_stack'))
    );

    $this->database = $this->container->get('database');
    $this->controller = NodeViewCounterController::create($this->container);
  }

  /**
   * Builds a POST request to /saho-statistics/node/view.
   */
  protected function makeRequest(?int $nid = NULL, string $ip = '127.0.0.1'): Request {
    $params = [];
    if ($nid !== NULL) {
      $params['nid'] = $nid;
    }
    return Request::create(
      '/saho-statistics/node/view',
      'POST',
      $params,
      [],
      [],
      ['REMOTE_ADDR' => $ip]
    );
  }

  /**
   * @covers ::record
   */
  public function testRecordsViewForPublishedNode(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Hello',
      'status' => 1,
    ]);
    $node->save();

    $response = $this->controller->record($this->makeRequest($node->id()));
    $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

    $row = $this->database->select('saho_node_counter', 'c')
      ->fields('c')
      ->condition('nid', $node->id())
      ->execute()
      ->fetchAssoc();
    $this->assertNotFalse($row, 'Row written on first view.');
    $this->assertSame('1', (string) $row['totalcount']);
    $this->assertSame('1', (string) $row['daycount']);
  }

  /**
   * @covers ::record
   */
  public function testSubsequentViewIncrements(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Hello',
      'status' => 1,
    ]);
    $node->save();

    $this->controller->record($this->makeRequest($node->id()));
    $this->controller->record($this->makeRequest($node->id()));

    $row = $this->database->select('saho_node_counter', 'c')
      ->fields('c')
      ->condition('nid', $node->id())
      ->execute()
      ->fetchAssoc();
    $this->assertSame('2', (string) $row['totalcount']);
    $this->assertSame('2', (string) $row['daycount']);
  }

  /**
   * @covers ::record
   */
  public function testRejectsZeroNid(): void {
    $response = $this->controller->record($this->makeRequest(0));
    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * @covers ::record
   */
  public function testRejectsMissingNid(): void {
    $response = $this->controller->record($this->makeRequest());
    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * @covers ::record
   */
  public function testRejectsUnknownNid(): void {
    $response = $this->controller->record($this->makeRequest(999999));
    $this->assertSame(404, $response->getStatusCode());

    $count = $this->database->select('saho_node_counter', 'c')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertSame('0', (string) $count, 'Unknown nid must not produce a row.');
  }

  /**
   * @covers ::record
   */
  public function testRejectsUnpublishedNid(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Draft',
      'status' => 0,
    ]);
    $node->save();

    $response = $this->controller->record($this->makeRequest($node->id()));
    $this->assertSame(404, $response->getStatusCode());

    $count = $this->database->select('saho_node_counter', 'c')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertSame('0', (string) $count, 'Unpublished nid must not produce a row.');
  }

  /**
   * @covers ::record
   */
  public function testFloodCapsRepeatedHitsFromSameIp(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Hello',
      'status' => 1,
    ]);
    $node->save();

    // FLOOD_THRESHOLD = 5. Hits 6 and 7 must return 204 silently AND must
    // not increment the counter.
    for ($i = 0; $i < 7; $i++) {
      $r = $this->controller->record($this->makeRequest($node->id()));
      $this->assertSame(Response::HTTP_NO_CONTENT, $r->getStatusCode());
    }

    $row = $this->database->select('saho_node_counter', 'c')
      ->fields('c')
      ->condition('nid', $node->id())
      ->execute()
      ->fetchAssoc();
    $this->assertSame('5', (string) $row['totalcount'], 'Flood cap of 5 must hold.');
  }

  /**
   * @covers ::record
   */
  public function testRecordInvalidatesSahoNodeCounterCacheTag(): void {
    $node = Node::create([
      'type' => 'article',
      'title' => 'Hello',
      'status' => 1,
    ]);
    $node->save();

    $cache = $this->container->get('cache.default');
    $cache->set('saho_statistics_test_marker', 'stale', Cache::PERMANENT, ['saho_node_counter']);
    $this->assertNotFalse($cache->get('saho_statistics_test_marker'), 'Test fixture cached.');

    $this->controller->record($this->makeRequest($node->id()));

    $this->assertFalse(
      $cache->get('saho_statistics_test_marker'),
      'saho_node_counter cache tag was invalidated after a successful view write.'
    );
  }

}
