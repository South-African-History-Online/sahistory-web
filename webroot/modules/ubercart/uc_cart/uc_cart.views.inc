<?php

/**
 * @file
 * Views hooks.
 */

/**
 * Implements hook_views_data().
 */
function uc_cart_views_data() {
  // Cart items table.
  $data['uc_cart_products']['table']['group'] = t('Cart item');
  $data['uc_cart_products']['table']['base'] = array(
    'field' => 'cart_item_id',
    'title' => t('Cart items'),
    'help' => t('Products in customer carts.'),
  );

  $data['uc_cart_products']['nid'] = array(
    'title' => t('Nid'),
    'help' => t('The node ID of a product in the cart.'),
    'field' => array(
      'id' => 'node',
      'click sortable' => TRUE,
    ),
    'relationship' => array(
      'title' => t('Node'),
      'help' => t('Relate cart item to node.'),
      'id' => 'standard',
      'base' => 'node',
      'field' => 'nid',
      'label' => t('node'),
    ),
    'argument' => array(
      'id' => 'node_nid',
      'name field' => 'title',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_cart_products']['cart_id'] = array(
    'title' => t('Cart ID'),
    'help' => t('The ID of the cart (user ID for authenticated users, session ID for anonymous users).'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'user_uid',
      'name field' => 'name',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'standard',
    ),
  );

  $data['uc_cart_products']['qty'] = array(
    'title' => t('Quantity'),
    'help' => t('The quantity to be ordered.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_cart_products']['changed'] = array(
    'title' => t('Last modified'),
    'help' => t('The time the cart item was last modified.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  return $data;
}
