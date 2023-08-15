<?php

namespace Drupal\uc_cart;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Controller class for cart items.
 */
class CartItemStorage extends SqlContentEntityStorage {

  /**
   * Overrides Drupal\Core\Entity\Sql\SqlContentEntityStorage::save().
   *
   * Cart items are deleted if saved with a quantity of zero.
   */
  public function save(EntityInterface $entity) {
    if ($entity->qty->value < 1) {
      if (isset($entity->cart_item_id->value)) {
        parent::delete(array($entity->cart_item_id->value => $entity));
      }
    }
    else {
      $entity->setChangedTime(REQUEST_TIME);
      parent::save($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['uc_cart_products']['fields']['cart_id']['not null'] = TRUE;

    $schema['uc_cart_products']['indexes'] += array(
     'cart_id' => array('cart_id'),
    );

    $schema['uc_cart_products']['foreign keys'] += array(
      'node' => array(
        'table' => 'node',
        'columns' => array('nid' => 'nid'),
      ),
    );
    return $schema;
  }

}
