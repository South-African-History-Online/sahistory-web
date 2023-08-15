<?php

namespace Drupal\basic_cart;

/**
 * Cart interface definition for basic_cart plugins.
 */
interface CartInterface {

  /**
   * Get Cart.
   *
   * @param int $nid
   *   Node id.
   */
  public function getCart($nid = NULL);

  /**
   * Remove from Cart.
   *
   * @param int $nid
   *   Node id.
   */
  public function removeFromCart($nid);

  /**
   * Empty cart.
   */
  public function emptyCart();

  /**
   * Add to cart.
   *
   * @param int $id
   *   Node id.
   * @param array $params
   *   Array to define quantity and entity type.
   */
  public function addToCart($id, array $params = array());

}
