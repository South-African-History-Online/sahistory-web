<?php

namespace Drupal\uc_cart;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Ubercart cart item entity.
 */
interface CartItemInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Converts a cart item into an order product.
   *
   * @return \Drupal\uc_order\OrderProductInterface
   *   The order product.
   */
  public function toOrderProduct();

}
