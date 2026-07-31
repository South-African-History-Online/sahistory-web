<?php

namespace Drupal\Tests\saho_statistics\Kernel\EventSubscriber;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\ProcessingResultsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Query\ResultSet;
use Symfony\Component\HttpFoundation\Request;

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

    // saho_statistics_cron() now updates saho_node_counter too - install both
    // tables here so the cron path in testDataRetention() doesn't blow up on
    // a missing table.
    $this->installSchema('saho_statistics', ['saho_search_queries', 'saho_node_counter']);
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
    // A real Views search page sets the 'search_api_view' option.
    $query->method('getOption')->willReturn('saho_global_search');
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

    $this->assertIsObject($record);
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
    // Comes from a real search view, but with no keys - must not be tracked.
    $query->method('getOption')->willReturn('saho_global_search');

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
   * Tests that typeahead suggest queries are not tracked.
   *
   * The suggest controller builds its query with $index->query() directly, so
   * it carries no 'search_api_view' option. Such queries must be ignored to
   * keep the per-keystroke prefixes out of the analytics table.
   */
  public function testSuggestQueriesNotTracked() {
    $index = $this->createMock(Index::class);
    $index->method('id')->willReturn('saho_content');

    $query = $this->createMock(Query::class);
    $query->method('getKeys')->willReturn('nel');
    $query->method('getIndex')->willReturn($index);
    // No 'search_api_view' option: simulates the typeahead suggest endpoint.
    $query->method('getOption')->willReturn(NULL);
    $query->method('getConditionGroup')->willReturn($this->createMock('Drupal\search_api\Query\ConditionGroupInterface'));

    $pre_event = new QueryPreExecuteEvent($query);
    $this->eventDispatcher->dispatch($pre_event, SearchApiEvents::QUERY_PRE_EXECUTE);

    $results = $this->createMock(ResultSet::class);
    $results->method('getResultCount')->willReturn(7);
    $results->method('getQuery')->willReturn($query);

    $post_event = new ProcessingResultsEvent($results);
    $this->eventDispatcher->dispatch($post_event, SearchApiEvents::PROCESSING_RESULTS);

    $count = $this->database->select('saho_search_queries', 's')
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals(0, $count, 'Suggest queries without a search_api_view option are not tracked.');
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
    $query->method('getOption')->willReturn('saho_global_search');
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
    $query->method('getOption')->willReturn('saho_global_search');
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

  /**
   * Tests that bot searches are not tracked.
   *
   * Bots hitting /search were inserting hundreds of rows per hour into
   * saho_search_queries (2026-07-31 incident), polluting the analytics the
   * popular-searches feature is built on.
   */
  public function testBotQueriesNotTracked() {
    $index = $this->createMock(Index::class);
    $index->method('id')->willReturn('test_index');

    $query = $this->createMock(Query::class);
    $query->method('getKeys')->willReturn('bot search');
    $query->method('getIndex')->willReturn($index);
    $query->method('getOption')->willReturn('saho_global_search');
    $query->method('getConditionGroup')->willReturn($this->createMock('Drupal\search_api\Query\ConditionGroupInterface'));

    $request_stack = $this->container->get('request_stack');

    // A crawler User-Agent must not produce a row.
    $bot_request = Request::create('/search');
    $bot_request->headers->set('User-Agent', 'Mozilla/5.0 (compatible; Amazonbot/0.1; +https://developer.amazon.com/support/amazonbot)');
    $request_stack->push($bot_request);

    $this->eventDispatcher->dispatch(new QueryPreExecuteEvent($query), SearchApiEvents::QUERY_PRE_EXECUTE);

    $results = $this->createMock(ResultSet::class);
    $results->method('getResultCount')->willReturn(12);
    $results->method('getQuery')->willReturn($query);
    $this->eventDispatcher->dispatch(new ProcessingResultsEvent($results), SearchApiEvents::PROCESSING_RESULTS);

    $request_stack->pop();

    $count = $this->database->select('saho_search_queries', 's')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(0, $count, 'Bot searches are not tracked.');

    // An empty User-Agent is also treated as a bot.
    $empty_ua_request = Request::create('/search');
    $empty_ua_request->headers->remove('User-Agent');
    $request_stack->push($empty_ua_request);

    $this->eventDispatcher->dispatch(new QueryPreExecuteEvent($query), SearchApiEvents::QUERY_PRE_EXECUTE);
    $this->eventDispatcher->dispatch(new ProcessingResultsEvent($results), SearchApiEvents::PROCESSING_RESULTS);

    $request_stack->pop();

    $count = $this->database->select('saho_search_queries', 's')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(0, $count, 'Searches without a User-Agent are not tracked.');

    // A regular browser User-Agent still produces a row.
    $browser_request = Request::create('/search');
    $browser_request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36');
    $request_stack->push($browser_request);

    $this->eventDispatcher->dispatch(new QueryPreExecuteEvent($query), SearchApiEvents::QUERY_PRE_EXECUTE);
    $this->eventDispatcher->dispatch(new ProcessingResultsEvent($results), SearchApiEvents::PROCESSING_RESULTS);

    $request_stack->pop();

    $count = $this->database->select('saho_search_queries', 's')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(1, $count, 'Browser searches are still tracked.');
  }

  /**
   * Tests that cron precomputes the popular-searches state value.
   *
   * The GROUP BY over saho_search_queries only runs from cron; the render
   * path reads the saho_statistics.popular_searches state entry. Covers the
   * JSON-encoded Search API keys decoding, the >=5-results and >=2-searches
   * thresholds, and ordering by search count.
   */
  public function testCronBuildsPopularSearches() {
    $now = \Drupal::time()->getRequestTime();
    $json_keys = json_encode(['#conjunction' => 'AND', 0 => 'nelson', 1 => 'mandela']);

    $rows = [
      // Three searches for "nelson mandela" (JSON-encoded keys shape).
      ['query_text' => $json_keys, 'result_count' => 20, 'timestamp' => $now - 100],
      ['query_text' => $json_keys, 'result_count' => 20, 'timestamp' => $now - 200],
      ['query_text' => $json_keys, 'result_count' => 20, 'timestamp' => $now - 300],
      // Two searches for a plain-text legacy query.
      ['query_text' => 'apartheid', 'result_count' => 15, 'timestamp' => $now - 400],
      ['query_text' => 'apartheid', 'result_count' => 15, 'timestamp' => $now - 500],
      // Only one search: below the >=2 threshold, must be excluded.
      ['query_text' => 'one-off typo', 'result_count' => 9, 'timestamp' => $now - 600],
      // Two searches but too few results: below the >=5 threshold.
      ['query_text' => 'obscure', 'result_count' => 1, 'timestamp' => $now - 700],
      ['query_text' => 'obscure', 'result_count' => 1, 'timestamp' => $now - 800],
    ];
    foreach ($rows as $row) {
      $this->database->insert('saho_search_queries')
        ->fields($row + ['index_id' => 'test', 'uid' => 0])
        ->execute();
    }

    saho_statistics_cron();

    $popular = \Drupal::state()->get('saho_statistics.popular_searches');
    $this->assertIsArray($popular);
    $this->assertCount(2, $popular, 'Only queries meeting both thresholds are included.');

    $this->assertEquals('Nelson Mandela', $popular[0]['label'], 'JSON keys are decoded and the most-searched query ranks first.');
    $this->assertEquals('/search?search_api_fulltext=' . rawurlencode('Nelson Mandela'), $popular[0]['url']);
    $this->assertEquals('Apartheid', $popular[1]['label'], 'Plain-text legacy rows are title-cased.');

    // A second cron run within the hour must not rebuild (throttle).
    \Drupal::state()->set('saho_statistics.popular_searches', [['label' => 'Sentinel', 'url' => '/x']]);
    saho_statistics_cron();
    $popular = \Drupal::state()->get('saho_statistics.popular_searches');
    $this->assertEquals('Sentinel', $popular[0]['label'], 'Rebuild is throttled to once per hour.');
  }

}
