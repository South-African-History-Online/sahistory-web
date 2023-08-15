<?php

namespace Drupal\feeds;

use Drupal\Core\File\FileSystemInterface;
use Drupal\feeds\Exception\LockException;
use Drupal\feeds\Result\RawFetcherResult;

/**
 * Runs the actual import on a feed.
 */
class FeedImportHandler extends FeedHandlerBase {

  /**
   * Imports the whole feed at once.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to import for.
   *
   * @throws \Exception
   *   In case of an error.
   */
  public function import(FeedInterface $feed) {
    $this->getExecutable(FeedsExecutable::class)
      ->processItem($feed, FeedsExecutable::BEGIN);
  }

  /**
   * Starts importing a feed via the batch API.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to import.
   *
   * @throws \Drupal\feeds\Exception\LockException
   *   Thrown if a feed is locked.
   */
  public function startBatchImport(FeedInterface $feed) {
    $this->getExecutable(FeedsBatchExecutable::class)
      ->processItem($feed, FeedsBatchExecutable::BEGIN);
  }

  /**
   * Starts importing a feed via cron.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed to queue.
   *
   * @throws \Drupal\feeds\Exception\LockException
   *   Thrown if a feed is locked.
   */
  public function startCronImport(FeedInterface $feed) {
    if ($feed->isLocked()) {
      $args = ['@id' => $feed->bundle(), '@fid' => $feed->id()];
      throw new LockException($this->t('The feed @id / @fid is locked.', $args));
    }

    $this->getExecutable(FeedsQueueExecutable::class)
      ->processItem($feed, FeedsQueueExecutable::BEGIN);

    // Add timestamp to avoid queueing item more than once.
    $feed->setQueuedTime($this->getRequestTime());
    $feed->save();
  }

  /**
   * Handles a push import.
   *
   * @param \Drupal\feeds\FeedInterface $feed
   *   The feed receiving the push.
   * @param string $payload
   *   The feed contents.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   (optional) The file system service.
   */
  public function pushImport(FeedInterface $feed, $payload, FileSystemInterface $file_system = NULL) {
    $feed->lock();
    $fetcher_result = new RawFetcherResult($payload, $file_system);

    $this->getExecutable(FeedsQueueExecutable::class)
      ->processItem($feed, FeedsQueueExecutable::PARSE, [
        'fetcher_result' => $fetcher_result,
      ]);
  }

  /**
   * Returns the timestamp for the current request.
   *
   * @return int
   *   A Unix timestamp.
   */
  protected function getRequestTime() {
    return \Drupal::time()->getRequestTime();
  }

  /**
   * Returns the executable.
   *
   * @param string $class
   *   The class to load.
   *
   * @return \Drupal\feeds\FeedsExecutableInterface
   *   A feeds executable.
   */
  protected function getExecutable($class) {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition($class);
  }

}
