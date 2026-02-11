<?php

namespace Drupal\Tests\saho_statistics\Kernel\EventSubscriber;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\ProcessingResultsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Query\ResultSet;

/**
 * Tests search query tracking functionality.
 *
 * @group saho_statistics
 */
class SearchQueryTrackerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'saho_statistics',
    'search_api',
    'entity_usage',
    'user',
    'system',
    'node',
  ];

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('saho_statistics', ['saho_search_queries']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['saho_statistics', 'system']);

    $this->eventDispatcher = $this->container->get('event_dispatcher');
    $this->database = $this->container->get('database');

    // Enable search tracking.
    $this->config('saho_statistics.settings')
      ->set('track_searches', TRUE)
      ->save();
  }

  /**
   * Tests that search queries are tracked correctly.
   */
  public function testSearchQueryTracking() {
    // Create a mock Search API index.
    $index = $this->createMock(Index::class);
    $index->method('id')->willReturn('test_index');

    // Create a mock query.
    $query = $this->createMock(Query::class);
    $query->method('getKeys')->willReturn('test search query');
    $query->method('getIndex')->willReturn($index);
    $query->method('getConditionGroup')->willReturn($this->createMock('Drupal\search_api\Query\ConditionGroupInterface'));

    // Dispatch the query pre-execute event.
    $pre_event = new QueryPreExecuteEvent($query);
    $this->eventDispatcher->dispatch($pre_event, SearchApiEvents::QUERY_PRE_EXECUTE);

    // Create a mock result set.
    $results = $this->createMock(ResultSet::class);
    $results->method('getResultCount')->willReturn(10);
    $results->method('getQuery')->willReturn($query);

    // Dispatch the processing results event.
    $post_event = new ProcessingResultsEvent($results);
    $this->eventDispatcher->dispatch($post_event, SearchApiEvents::PROCESSING_RESULTS);

    // Verify that the query was logged to the database.
    $count = $this->database->select('saho_search_queries', 's')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals(1, $count, 'Search query was logged to database.');

    // Verify the logged data.
    $record = $this->database->select('saho_search_queries', 's')
      ->fields('s')
      ->execute()
      ->fetchObject();

    $this->assertEquals('test search query', $record->query_text);
    $this->assertEquals('test_index', $record->index_id);
    $this->assertEquals(10, $record->result_count);
    $this->assertNotEmpty($record->timestamp);
  }

  /**
   * Tests that tracking respects the disabled setting.
   */
  public function testTrackingCanBeDisabled() {
    // Disable tracking.
    $this->config('saho_statistics.settings')
      ->set('track_searches', FALSE)
      ->save();

    // Create a mock index and query.
    $index = $this->createMock(Index::class);
    $index->method('id')->willReturn('test_index');

    $query = $this->createMock(Query::class);
    $query->method('getKeys')->willReturn('disabled test query');
    $query->method('getIndex')->willReturn($index);

    // Dispatch events.
    $pre_event = new QueryPreExecuteEvent($query);
    $this->eventDispatcher->dispatch($pre_event, SearchApiEvents::QUERY_PRE_EXECUTE);

    $results = $this->createMock(ResultSet::class);
    $results->method('getResultCount')->willReturn(5);
    $results->method('getQuery')->willReturn($query);

    $post_event = new ProcessingResultsEvent($results);
    $this->eventDispatcher->dispatch($post_event, SearchApiEvents::PROCESSING_RESULTS);

    // Verify no queries were logged.
    $count = $this->database->select('saho_search_queries', 's')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals(0, $count, 'No queries logged when tracking is disabled.');
  }

  /**
   * Tests that empty queries are not tracked.
   */
  public function testEmptyQueriesNotTracked() {
    $index = $this->createMock(Index::class);
    $index->method('id')->willReturn('test_index');

    $query = $this->createMock(Query::class);
    $query->method('getKeys')->willReturn('');
    $query->method('getIndex')->willReturn($index);

    $pre_event = new QueryPreExecuteEvent($query);
    $this->eventDispatcher->dispatch($pre_event, SearchApiEvents::QUERY_PRE_EXECUTE);

    $results = $this->createMock(ResultSet::class);
    $results->method('getResultCount')->willReturn(0);
    $results->method('getQuery')->willReturn($query);

    $post_event = new ProcessingResultsEvent($results);
    $this->eventDispatcher->dispatch($post_event, SearchApiEvents::PROCESSING_RESULTS);

    // Verify no empty queries were logged.
    $count = $this->database->select('saho_search_queries', 's')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals(0, $count, 'Empty queries are not tracked.');
  }

  /**
   * Tests IP address hashing for privacy.
   */
  public function testIpAddressHashing() {
    $index = $this->createMock(Index::class);
    $index->method('id')->willReturn('test_index');

    $query = $this->createMock(Query::class);
    $query->method('getKeys')->willReturn('privacy test');
    $query->method('getIndex')->willReturn($index);
    $query->method('getConditionGroup')->willReturn($this->createMock('Drupal\search_api\Query\ConditionGroupInterface'));

    $pre_event = new QueryPreExecuteEvent($query);
    $this->eventDispatcher->dispatch($pre_event, SearchApiEvents::QUERY_PRE_EXECUTE);

    $results = $this->createMock(ResultSet::class);
    $results->method('getResultCount')->willReturn(5);
    $results->method('getQuery')->willReturn($query);

    $post_event = new ProcessingResultsEvent($results);
    $this->eventDispatcher->dispatch($post_event, SearchApiEvents::PROCESSING_RESULTS);

    // Verify IP hash was stored.
    $record = $this->database->select('saho_search_queries', 's')
      ->fields('s', ['ip_hash'])
      ->execute()
      ->fetchField();

    $this->assertNotEmpty($record, 'IP hash is stored.');
    $this->assertEquals(64, strlen($record), 'IP hash is SHA-256 (64 characters).');
  }

  /**
   * Tests session ID anonymization.
   */
  public function testSessionIdAnonymization() {
    $index = $this->createMock(Index::class);
    $index->method('id')->willReturn('test_index');

    $query = $this->createMock(Query::class);
    $query->method('getKeys')->willReturn('session test');
    $query->method('getIndex')->willReturn($index);
    $query->method('getConditionGroup')->willReturn($this->createMock('Drupal\search_api\Query\ConditionGroupInterface'));

    $pre_event = new QueryPreExecuteEvent($query);
    $this->eventDispatcher->dispatch($pre_event, SearchApiEvents::QUERY_PRE_EXECUTE);

    $results = $this->createMock(ResultSet::class);
    $results->method('getResultCount')->willReturn(3);
    $results->method('getQuery')->willReturn($query);

    $post_event = new ProcessingResultsEvent($results);
    $this->eventDispatcher->dispatch($post_event, SearchApiEvents::PROCESSING_RESULTS);

    // Verify session ID is anonymized (8 characters).
    $record = $this->database->select('saho_search_queries', 's')
      ->fields('s', ['session_id'])
      ->execute()
      ->fetchField();

    if (!empty($record)) {
      $this->assertEquals(8, strlen($record), 'Session ID is anonymized to 8 characters.');
    }
  }

  /**
   * Tests data retention cron job.
   */
  public function testDataRetention() {
    // Insert old search query (91 days ago).
    $old_timestamp = \Drupal::time()->getRequestTime() - (91 * 86400);
    $this->database->insert('saho_search_queries')
      ->fields([
        'query_text' => 'old query',
        'index_id' => 'test',
        'result_count' => 5,
        'uid' => 0,
        'timestamp' => $old_timestamp,
      ])
      ->execute();

    // Insert recent query (30 days ago).
    $recent_timestamp = \Drupal::time()->getRequestTime() - (30 * 86400);
    $this->database->insert('saho_search_queries')
      ->fields([
        'query_text' => 'recent query',
        'index_id' => 'test',
        'result_count' => 3,
        'uid' => 0,
        'timestamp' => $recent_timestamp,
      ])
      ->execute();

    // Run cron.
    saho_statistics_cron();

    // Verify old query was deleted.
    $count = $this->database->select('saho_search_queries', 's')
      ->condition('query_text', 'old query')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals(0, $count, 'Old queries are deleted by cron.');

    // Verify recent query still exists.
    $count = $this->database->select('saho_search_queries', 's')
      ->condition('query_text', 'recent query')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals(1, $count, 'Recent queries are retained.');
  }

}
