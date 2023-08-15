<?php

/**
 * @file
 * Basic cart inc file.
 */

use Drupal\basic_cart\Utility;

/**
 * Get Cart data.
 */
function basic_cart_get_cart() {
  $cart = Utility::getCart();
  return $cart;
}

/**
 * Empty cart data.
 */
function basic_cart_empty_cart() {
  $cart = Utility::emptyCart();
  return $cart;
}
