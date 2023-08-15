<?php

namespace Drupal\Tests\feeds\Functional;

/**
 * Tests behavior involving the queue.
 *
 * @group feeds
 */
class QueueTest extends FeedsBrowserTestBase {

  /**
   * Tests if a feed gets imported via cron after adding it to the queue.
   */
  public function testCronImport() {
    $feed_type = $this->createFeedType();

    // Create a feed and ensure it gets imported on cron.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $feed->startCronImport();

    // Run cron to import.
    $this->cronRun();

    // Assert that 6 nodes have been created.
    $this->assertNodeCount(6);
  }

  /**
   * Tests if a feed gets imported via a push.
   */
  public function testPushImport() {
    $feed_type = $this->createFeedType();

    // Create a feed without a source.
    $feed = $this->createFeed($feed_type->id());

    // Push file contents.
    $feed->pushImport(file_get_contents($this->resourcesPath() . '/rss/googlenewstz.rss2'));

    // Run cron to import.
    $this->cronRun();

    // Assert that 6 nodes have been created.
    $this->assertNodeCount(6);
  }

  /**
   * Tests on a push import, if only the file pushed is imported.
   */
  public function testPushImportWithSavedSource() {
    $feed_type = $this->createFeedType();

    // Create a feed with a source.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/drupalplanet.rss2',
    ]);

    // Push file contents.
    $feed->pushImport(file_get_contents($this->resourcesPath() . '/rss/googlenewstz.rss2'));

    // Run cron to import.
    $this->cronRun();

    // Assert that 6 nodes have been created.
    $this->assertNodeCount(6);
  }

  /**
   * Tests if a feed is removed from the queue when the feed gets deleted.
   */
  public function testQueueAfterDeletingFeed() {
    $feed_type = $this->createFeedType();

    // Create a feed and ensure it gets imported on cron.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesUrl() . '/rss/googlenewstz.rss2',
    ]);
    $feed->startCronImport();

    // Run cron to import.
    $this->cronRun();

    // Assert that 6 nodes have been created.
    $this->assertNodeCount(6);

    // Add feed to queue again but delete the feed before cron has run.
    $feed->startCronImport();
    $feed->delete();

    // Run cron again.
    $this->cronRun();

    // Assert that the queue is empty.
    $queue = \Drupal::service('queue')->get('feeds_feed_refresh:' . $feed_type->id());
    $this->assertEquals(0, $queue->numberOfItems());
  }

}
