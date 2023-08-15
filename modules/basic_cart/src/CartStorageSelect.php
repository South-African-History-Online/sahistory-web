<?php

namespace Drupal\basic_cart;

/**
 * Class CartStorageSelect to select either db or session.
 */
class CartStorageSelect {

  private $cart = NULL;
  private $cartStorage;

  /**
   * Construct object using  whether it is table class or session class.
   *
   * @param object $user
   *   Drupal user object.
   * @param bool $use_table
   *   Boolean to define the storage type.
   */
  public function __construct($user, $use_table = NULL) {
    $enable = $user->id() && $use_table ? $user->id() : 0;
    switch ($enable) {
      case 0:
        $this->cart = new CartSession($user);
        break;

      default:
        $cartStorage = new CartStorage();
        $this->cart  = new CartTable($cartStorage, $user);
        break;
    }
  }

  /**
   * Get the cart data.
   *
   * @param int $nid
   *   Node id of the basic cart content.
   *
   * @return array
   *   array of cart data
   */
  public  function getCart($nid = NULL) {
    return $this->cart->getCart($nid);
  }

  /**
   * Remove the cart data by nid.
   *
   * @param int $nid
   *   Removes the node from cart.
   *
   * @return bool
   *   return true or false
   */
  public  function removeFromCart($nid) {
    return $this->cart->removeFromCart($nid);
  }

  /**
   * Empty the cart.
   *
   * @return string
   *   Text message
   */
  public  function emptyCart() {
    return $this->cart->emptyCart();
  }

  /**
   * Add the content to cart.
   *
   * @param int $id
   *   Node id of node.
   * @param array $params
   *   Will contain quantity and entity type if exists.
   *
   * @return string
   *   Message
   */
  public  function addToCart($id, array $params = array()) {
    return $this->cart->addToCart($id, $params);
  }

  /**
   * Sync cart data in session and database.
   */
  public function loggedInActionCart() {
    return $this->cart->loggedInActionCart();
  }

}
