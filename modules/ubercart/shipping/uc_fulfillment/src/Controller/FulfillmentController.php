<?php

namespace Drupal\uc_fulfillment\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_order\OrderInterface;

/**
 * Controller routines for order routes.
 */
class FulfillmentController extends ControllerBase {

  /**
   * Checks access to fulfill this order.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The Order to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessOrder(OrderInterface $uc_order) {
    $account = \Drupal::currentUser();
    return AccessResult::allowedIf(
      $account->hasPermission('fulfill orders') &&
      $uc_order->isShippable()
    );
  }

  /**
   * Checks access to the Shipments tab for this order.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessNewShipment(OrderInterface $uc_order) {
    return AccessResult::allowedIf(
      $this->accessOrder($uc_order) &&
      // Order has packages that are not part of a shipment.
      db_query('SELECT COUNT(*) FROM {uc_packages} WHERE order_id = :id AND sid IS NULL', [':id' => $uc_order->id()])->fetchField()
    );
  }

}
