<?php

namespace Drupal\Tests\feeds_tamper\Kernel;

/**
 * Tests removing tampers when mapping is removed.
 *
 * @group feeds_tamper
 */
class TamperRemoveTest extends FeedsTamperKernelTestBase {

  /**
   * Tests if the Tamper plugins are removed when the mapping is removed.
   */
  public function testRemoveTamperWhenMappingIsRemoved() {
    // Create a feed type.
    $feed_type = $this->createFeedType();

    // Initiate feed type tamper manager.
    $feed_type_tamper_manager = \Drupal::service('feeds_tamper.feed_type_tamper_manager');

    // Add a tamper plugin for this feed type.
    $feed_type_tamper_manager->getTamperMeta($feed_type, TRUE)
      ->addTamper([
        'plugin' => 'convert_case',
        'operation' => 'ucfirst',
        'source' => 'title',
        'description' => 'Start text with uppercase character',
      ]);
    $feed_type->save();

    // Assert that the feed type has one tamper plugin when reloaded.
    $feed_type = $this->reloadEntity($feed_type);
    $this->assertCount(1, $feed_type_tamper_manager->getTamperMeta($feed_type, TRUE)->getTampers());

    // Now remove the mapping for 'title' (which is at position 1).
    $feed_type->removeMapping(1);
    $feed_type->save();

    // Assert that the tamper instance no longer exists on the feed.
    $this->assertCount(0, $feed_type_tamper_manager->getTamperMeta($feed_type, TRUE)->getTampers());
  }

}
