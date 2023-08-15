<?php

namespace Drupal\feeds_tamper;

use Drupal\feeds\FeedTypeInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Manager for FeedTypeTamperMeta instances.
 */
class FeedTypeTamperManager implements FeedTypeTamperManagerInterface {

  use ContainerAwareTrait;

  /**
   * An array of FeedsTamper instances.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperMeta[]
   */
  protected $tamperMetas = [];

  /**
   * {@inheritdoc}
   */
  public function getTamperMeta(FeedTypeInterface $feed_type, $reset = FALSE) {
    $id = $feed_type->id();

    if ($reset || !isset($this->tamperMetas[$id])) {
      $this->tamperMetas[$id] = FeedTypeTamperMeta::create($this->container, $feed_type);
    }

    return $this->tamperMetas[$id];
  }

}
