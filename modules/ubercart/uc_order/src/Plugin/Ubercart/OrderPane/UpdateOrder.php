<?php

namespace Drupal\uc_order\Plugin\Ubercart\OrderPane;

use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\OrderPanePluginBase;

/**
 * Update an order's status or add comments to an order.
 *
 * @UbercartOrderPane(
 *   id = "update",
 *   title = @Translation("Update order"),
 *   weight = 10,
 * )
 */
class UpdateOrder extends OrderPanePluginBase {

  /**
   * {@inheritdoc}
   */
  public function view(OrderInterface $order, $view_mode) {
    if ($view_mode != 'customer') {
      // @todo Merge OrderUpdateForm into this plugin?
      return \Drupal::formBuilder()->getForm('\Drupal\uc_order\Form\OrderUpdateForm', $order);
    }
  }

}
