<?php

/**
 * @file
 * Hooks provided by the Cart module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Performs extra processing when an item is added to the shopping cart.
 *
 * Some modules need to be able to hook into the process of adding items to a
 * cart. For example, an inventory system may need to check stock levels and
 * prevent an out of stock item from being added to a customer's cart. This hook
 * lets developers squeeze right in at the end of the process after the product
 * information is all loaded and the product is about to be added to the cart.
 * In the event that a product should not be added to the cart, you simply have
 * to return a failure message described below. This hook may also be used
 * simply to perform some routine action when products are added to the cart.
 *
 * @param $nid
 *   The node ID of the product.
 * @param $qty
 *   The quantity being added.
 * @param $data
 *   The data array, including attributes and model number adjustments.
 *
 * @return
 *   The function can use this data to whatever purpose to see if the item
 *   can be added to the cart or not. The function should return an array
 *   containing the result array. (This is due to the nature of Drupal's
 *   \Drupal::moduleHandler()->invokeAll() function. You must return an
 *   array within an array or other module data will end up getting ignored.)
 *   At this moment, there are only three keys:
 *   - success: TRUE or FALSE for whether the specified quantity of the item
 *     may be added to the cart or not; defaults to TRUE.
 *   - message: The fail message to display in the event of a failure; if
 *     omitted, Ubercart will display a default fail message.
 *   - silent: Return TRUE to suppress the display of any messages; useful
 *     when a module simply needs to do some other processing during an add
 *     to cart or fail silently.
 */
function hook_uc_add_to_cart($nid, $qty, $data) {
  if ($qty > 1) {
    $result[] = array(
      'success' => FALSE,
      'message' => t('Sorry, you can only add one of those at a time.'),
    );
  }
  return $result;
}

/**
 * Adds extra information to a cart item's "data" array.
 *
 * This is effectively the submit handler of any alterations to the Add to Cart
 * form. It provides a standard way to store the extra information so that it
 * can be used by hook_uc_add_to_cart().
 *
 * @param $form_values
 *   The values submitted to the Add to Cart form.
 *
 * @return
 *   An array of data to be merged into the item added to the cart.
 */
function hook_uc_add_to_cart_data($form_values) {
  $node = node_load($form_values['nid']);
  return array('module' => 'uc_product', 'shippable' => $node->shippable->value);
}

/**
 * Controls the display of an item in the cart.
 *
 * Product type modules allow the creation of nodes that can be added to the
 * cart. The cart determines how they are displayed through this hook. This is
 * especially important for product kits, because it may be displayed as a
 * single unit in the cart even though it is represented as several items.
 *
 * This hook is only called for the module that owns the cart item in
 * question, as set in $item->module.
 *
 * @param $item
 *   The item in the cart to display.
 *
 * @return
 *   A form array containing the following elements:
 *   - "nid"
 *     - #type: value
 *     - #value: The node id of the $item.
 *   - "module"
 *     - #type: value
 *     - #value: The module implementing this hook and the node represented by
 *       $item.
 *   - "remove"
 *     - #type: submit
 *     - #value: t('Remove'); when clicked, will remove $item from the cart.
 *   - "description"
 *     - #type: markup
 *     - #value: Themed markup (usually an unordered list) displaying extra
 *       information.
 *   - "title"
 *     - #type: markup
 *     - #value: The displayed title of the $item.
 *   - "#total"
 *     - type: float
 *     - value: Numeric price of $item. Notice the '#' signifying that this is
 *       not a form element but just a value stored in the form array.
 *   - "data"
 *     - #type: hidden
 *     - #value: The serialized $item->data.
 *   - "qty"
 *     - #type: textfield
 *     - #value: The quantity of $item in the cart. When "Update cart" is
 *       clicked, the customer's input is saved to the cart.
 */
function hook_uc_cart_display($item) {
  $node = $item->nid->entity;

  $element = array();
  $element['nid'] = array('#type' => 'value', '#value' => $node->id());
  $element['module'] = array('#type' => 'value', '#value' => 'uc_product');
  $element['remove'] = array('#type' => 'submit', '#value' => t('Remove'));

  if ($node->access('view')) {
    $element['title'] = array(
      '#type' => 'link',
      '#title' => $item->title,
      '#url' => $node->toUrl(),
    );
  }
  else {
    $element['title'] = array(
      '#markup' => $item->title,
    );
  }

  $element['#total'] = $item->price->value * $item->qty->value;
  $element['#suffixes'] = array();
  $element['data'] = array('#type' => 'hidden', '#value' => serialize($item->data->first()->toArray()));
  $element['qty'] = array(
    '#type' => 'uc_quantity',
    '#title' => t('Quantity'),
    '#title_display' => 'invisible',
    '#default_value' => $item->qty->value,
    '#allow_zero' => TRUE,
  );

  $element['description'] = array('#markup' => '');
  if ($description = uc_product_get_description($item)) {
    $element['description']['#markup'] = $description;
  }

  return $element;
}

/**
 * Act on a cart item before it is about to be created or updated.
 *
 * @param $entity
 *   The cart item entity object.
 */
function hook_uc_cart_item_presave($entity) {
  $entity->changed = REQUEST_TIME;
}

/**
 * Act on cart item entities when inserted.
 *
 * @param $entity
 *   The cart item entity object.
 */
function hook_uc_cart_item_insert($entity) {
  drupal_set_message(t('An item was added to your cart'));
}

/**
 * Act on cart item entities when updated.
 *
 * @param $entity
 *   The cart item entity object.
 */
function hook_uc_cart_item_update($entity) {
  drupal_set_message(t('An item was updated in your cart'));
}

/**
 * Act on cart item entities when deleted.
 *
 * @param $entity
 *   The cart item entity object.
 */
function hook_uc_cart_item_delete($entity) {
  drupal_set_message(t('An item was deleted from your cart'));
}

/**
 * Takes action when checkout is completed.
 *
 * @param $order
 *   The resulting order object from the completed checkout.
 * @param $account
 *   The customer that completed checkout, either the current user, or the
 *   account created for an anonymous customer.
 */
function hook_uc_checkout_complete($order, $account) {
  // Get previous records of customer purchases.
  $nids = array();
  $result = db_query("SELECT uid, nid, qty FROM {uc_customer_purchases} WHERE uid = :uid", [':uid' => $account->id()]);
  foreach ($result as $record) {
    $nids[$record->nid] = $record->qty;
  }

  // Update records with new data.
  $record = array('uid' => $account->id());
  foreach ($order->products as $product) {
    $record['nid'] = $product->nid;
    if (isset($nids[$product->nid])) {
      $record['qty'] = $nids[$product->nid] + $product->qty;
      db_write_record($record, 'uc_customer_purchases', array('uid', 'nid'));
    }
    else {
      $record['qty'] = $product->qty;
      db_write_record($record, 'uc_customer_purchases');
    }
  }
}

/**
 * Takes action immediately before bringing up the checkout page.
 *
 * Use drupal_goto() in the hook implementation to abort checkout and
 * enforce restrictions on the order.
 *
 * @param $order
 *   The order object to check out.
 */
function hook_uc_cart_checkout_start($order) {
  if (in_array('administrator', $order->getOwner()->roles)) {
    drupal_set_message(t('Administrators may not purchase products.', 'error'));
    drupal_goto('cart');
  }
}

/**
 * Alters checkout pane plugin definitions.
 *
 * @param array[] $panes
 *   Keys are plugin IDs. Values are plugin definitions.
 */
function hook_uc_checkout_pane_alter(array &$panes) {
  $panes['cart']['title'] = 'Your shopping cart';
}

/**
 * Handles requests to update a cart item.
 *
 * @param $nid
 *   Node id of the cart item.
 * @param $data
 *   Array of extra information about the item.
 * @param $qty
 *   The quantity of this item in the cart.
 * @param $cid
 *   The cart id. Defaults to NULL, which indicates that the current user's cart
 *   should be retrieved with uc_cart_get_id().
 */
function hook_uc_update_cart_item($nid, $data = array(), $qty, $cid = NULL) {
  $cid = !(is_null($cid) || empty($cid)) ? $cid : uc_cart_get_id();

  $result = \Drupal::entityQuery('uc_cart_item')
    ->condition('cart_id', $cid)
    ->condition('nid', $nid)
    ->condition('data', serialize($data))
    ->execute();

  if (!empty($result)) {
    $item = \Drupal\uc_cart\Entity\CartItem::load(current(array_keys($result)));
    if ($item->qty->value != $qty) {
      $item->qty->value = $qty;
      $item->save();
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
