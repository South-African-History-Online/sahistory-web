<?php

namespace Drupal\feeds_tamper\Adapter;

use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\tamper\TamperableItemInterface;

/**
 * Provides an adapter to use the feed item as a tamperable item.
 */
class TamperableFeedItemAdapter implements TamperableItemInterface {

  /**
   * A feed item.
   *
   * @var \Drupal\feeds\Feeds\Item\ItemInterface
   */
  protected $feedItem;

  /**
   * Create a new instance of the adapter.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $feed_item
   *   A feed item.
   */
  public function __construct(ItemInterface $feed_item) {
    $this->feedItem = $feed_item;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->feedItem->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceProperty($property, $data) {
    $this->feedItem->set($property, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceProperty($property) {
    $this->feedItem->get($property);
  }

}
