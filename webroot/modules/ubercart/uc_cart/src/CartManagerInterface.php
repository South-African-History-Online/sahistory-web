<?php

namespace Drupal\uc_cart;

use Drupal\uc_order\OrderInterface;

/**
 * Defines a common interface for cart managers.
 */
interface CartManagerInterface {

  /**
   * Loads a cart object.
   *
   * @param string|null $id
   *   (optional) The ID of the cart to load, or NULL to load the current cart.
   *
   * @return \Drupal\uc_cart\CartInterface
   *   An object representing the cart.
   */
  public function get($id = NULL);

  /**
   * Empties a cart.
   *
   * @param int $id
   *   The ID of the cart to empty.
   */
  public function emptyCart($id);

  /**
   * Completes a sale, including adjusting order status and creating an account.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order entity that has just been completed.
   * @param bool $login
   *   TRUE if the user should be logged in (where configured), FALSE otherwise.
   *
   * @return array
   *   A render array for the default order completion page.
   */
  public function completeSale(OrderInterface $order, $login = TRUE);

}
