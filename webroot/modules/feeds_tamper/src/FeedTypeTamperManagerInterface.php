<?php

namespace Drupal\feeds_tamper;

use Drupal\feeds\FeedTypeInterface;

/**
 * Interface for managing FeedTypeTamperMeta instances.
 */
interface FeedTypeTamperManagerInterface {

  /**
   * Gets Tamper functionality for a feed type.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feed_type
   *   The feed type to manage tamper plugins for.
   * @param bool $reset
   *   Whether to force creating a new instance (useful in automated tests).
   *   Defaults to false.
   *
   * @return \Drupal\feeds_tamper\FeedTypeTamperMetaInterface
   *   A feed type tamper meta object.
   */
  public function getTamperMeta(FeedTypeInterface $feed_type, $reset = FALSE);

}
