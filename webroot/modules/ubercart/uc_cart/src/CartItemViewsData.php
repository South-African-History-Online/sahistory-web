<?php

namespace Drupal\uc_cart;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the uc_order entity type.
 */
class CartItemViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Cart items table.
    $data['uc_cart_products']['table']['group'] = $this->t('Cart item');
    $data['uc_cart_products']['table']['base'] = array(
      'field' => 'cart_item_id',
      'title' => $this->t('Cart items'),
      'help' => $this->t('Products in customer carts.'),
    );

    $data['uc_cart_products']['nid'] = array(
      'title' => $this->t('Nid'),
      'help' => $this->t('The node ID of a product in the cart.'),
      'field' => array(
        'id' => 'node',
        'click sortable' => TRUE,
      ),
      'relationship' => array(
        'title' => $this->t('Node'),
        'help' => $this->t('Relate cart item to node.'),
        'id' => 'standard',
        'base' => 'node',
        'field' => 'nid',
        'label' => $this->t('node'),
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
      'title' => $this->t('Cart ID'),
      'help' => $this->t('The ID of the cart (user ID for authenticated users, session ID for anonymous users).'),
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
      'title' => $this->t('Quantity'),
      'help' => $this->t('The quantity to be ordered.'),
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

    return $data;
  }

}
