<?php

namespace Drupal\uc_order\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Deletes an order.
 *
 * @Action(
 *   id = "uc_order_delete_action",
 *   label = @Translation("Delete order"),
 *   type = "uc_order"
 * )
 */
class DeleteOrderAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\uc_order\OrderInterface $object */
    return $object->access('delete', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($order = NULL) {
    $order->delete();
  }

}
