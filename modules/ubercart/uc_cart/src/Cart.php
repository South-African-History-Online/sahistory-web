<?php

namespace Drupal\uc_cart;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\uc_cart\Entity\CartItem;

class Cart implements CartInterface {

  /**
   * The cart ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Constructor.
   *
   * @param string $id
   *   The cart ID.
   */
  public function __construct($id) {
    $this->id = $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getContents() {
    $items = array();

    $result = \Drupal::entityQuery('uc_cart_item')
      ->condition('cart_id', $this->id)
      ->sort('cart_item_id', 'ASC')
      ->execute();

    if (!empty($result)) {
      $items = \Drupal::entityTypeManager()->getStorage('uc_cart_item')->loadMultiple(array_keys($result));
    }

    // Allow other modules a chance to alter the fully loaded cart object.
    \Drupal::moduleHandler()->alter('uc_cart', $items);

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function addItem($nid, $qty = 1, $data = NULL, $msg = TRUE) {
    $node = Node::load($nid);

    if (is_null($data) || !isset($data['module'])) {
      $data['module'] = 'uc_product';
    }

    // Invoke hook_uc_add_to_cart() to give other modules a chance to affect the process.
    $result = \Drupal::moduleHandler()->invokeAll('uc_add_to_cart', array($nid, $qty, $data));
    if (is_array($result) && !empty($result)) {
      foreach ($result as $row) {
        if ($row['success'] === FALSE) {
          // Module implementing the hook does NOT want this item added!
          if (isset($row['message']) && !empty($row['message'])) {
            $message = $row['message'];
          }
          else {
            $message = t('Sorry, that item is not available for purchase at this time.');
          }
          if (isset($row['silent']) && ($row['silent'] === TRUE)) {
            return $this->getAddItemRedirect();
          }
          else {
            drupal_set_message($message, 'error');
          }
          // Stay on this page.
          $query = \Drupal::request()->query;
          return Url::fromRoute('<current>', [], ['query' => UrlHelper::filterQueryParameters($query->all())]);
        }
      }
    }

    // Now we can go ahead and add the item because either:
    //   1) No modules implemented hook_uc_add_to_cart(), or
    //   2) All modules implementing that hook want this item added.
    $result = \Drupal::entityQuery('uc_cart_item')
      ->condition('cart_id', $this->id)
      ->condition('nid', $nid)
      ->condition('data', serialize($data))
      ->execute();

    if (empty($result)) {
      // If the item isn't in the cart yet, add it.
      $item_entity = CartItem::create(array(
        'cart_id' => $this->id,
        'nid' => $nid,
        'qty' => $qty,
        'data' => $data,
      ));
      $item_entity->save();
      if ($msg) {
        drupal_set_message(t('<strong>@product-title</strong> added to <a href=":url">your shopping cart</a>.', ['@product-title' => $node->label(), ':url' => Url::fromRoute('uc_cart.cart')->toString()]));
      }
    }
    else {
      // If it is in the cart, update the item instead.
      if ($msg) {
        drupal_set_message(t('Your item(s) have been updated.'));
      }
      $item_entity = CartItem::load(current(array_keys($result)));
      $qty += $item_entity->qty->value;
      \Drupal::moduleHandler()->invoke($data['module'], 'uc_update_cart_item', array($nid, $data, min($qty, 999999), $this->id));
    }

    // Invalidate the cache.
    Cache::invalidateTags(['uc_cart:' . $this->id]);

    // Invalidate the cart order.
    // @todo Remove this and cache the order object with a tag instead?
    $session = \Drupal::service('session');
    $session->set('uc_cart_order_rebuild', TRUE);

    return $this->getAddItemRedirect();
  }

  /**
   * Computes the destination Url for an add-to-cart action.
   *
   * Redirect Url is chosen in the following order:
   *  - Query parameter "destination"
   *  - Cart config variable "uc_cart.settings.add_item_redirect"
   *
   * @return \Drupal\Core\Url
   *   A Url destination for redirection.
   */
  protected function getAddItemRedirect() {
    // Check for destination= query string
    $query = \Drupal::request()->query;
    $destination = $query->get('destination');
    if (!empty($destination)) {
      return Url::fromUri('base:' . $destination);
    }

    // Save current Url to session before redirecting
    // so we can go "back" here from the cart.
    $session = \Drupal::service('session');
    $session->set('uc_cart_last_url', Url::fromRoute('<current>')->toString());
    $redirect = \Drupal::config('uc_cart.settings')->get('add_item_redirect');
    if ($redirect != '<none>') {
      return Url::fromUri('base:' . $redirect);
    }
    else {
      return Url::fromRoute('<current>', [], ['query' => UrlHelper::filterQueryParameters($query->all())]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function emptyCart() {
    $result = \Drupal::entityQuery('uc_cart_item')
      ->condition('cart_id', $this->id)
      ->execute();

    if (!empty($result)) {
      $storage = \Drupal::entityTypeManager()->getStorage('uc_cart_item');
      $entities = $storage->loadMultiple(array_keys($result));
      $storage->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isShippable() {
    $items = $this->getContents();

    foreach ($items as $item) {
      if (uc_order_product_is_shippable($item)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
