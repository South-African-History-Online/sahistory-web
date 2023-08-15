<?php

namespace Drupal\uc_cart\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an action that can add a product to the cart.
 *
 * @Action(
 *   id = "uc_cart_add_product_action",
 *   label = @Translation("Add to cart"),
 *   type = "node"
 * )
 */
class AddToCart extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    uc_cart_add_item($entity->id(), 1, NULL, NULL, TRUE, FALSE, TRUE);
  }

}
