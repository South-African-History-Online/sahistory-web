<?php

namespace Drupal\feeds\Plugin\QueueWorker;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\FeedsQueueExecutable;

/**
 * A queue worker for importing feeds.
 *
 * @QueueWorker(
 *   id = "feeds_feed_refresh",
 *   title = @Translation("Feed refresh"),
 *   cron = {"time" = 60},
 *   deriver = "Drupal\feeds\Plugin\Derivative\FeedQueueWorker"
 * )
 */
class FeedRefresh extends FeedQueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    list($feed, $stage, $params) = $data;

    if (!$feed instanceof FeedInterface) {
      return;
    }

    // Check if the feed still exists.
    if (!$this->feedExists($feed)) {
      // The feed in question has been deleted. Abort.
      return;
    }

    $this->getExecutable()->processItem($feed, $stage, $params);
  }

  /**
   * Returns Feeds executable.
   *
   * @return \Drupal\feed\FeedsExecutableInterface
   *   A feeds executable.
   */
  protected function getExecutable() {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(FeedsQueueExecutable::class);
  }

  /**
   * Returns if a feed entity still exists or not.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed entity to check for existance in the database.
   *
   * @return bool
   *   True if the feed still exists, false otherwise.
   */
  protected function feedExists(FeedInterface $feed) {
    // Check if the feed still exists.
    $result = $this->entityTypeManager->getStorage($feed->getEntityTypeId())->getQuery()->condition('fid', $feed->id())->execute();
    if (empty($result)) {
      // The feed in question has been deleted.
      return FALSE;
    }
    return TRUE;
  }

}
