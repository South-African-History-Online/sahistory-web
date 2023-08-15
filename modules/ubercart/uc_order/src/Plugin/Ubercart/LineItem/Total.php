<?php

namespace Drupal\uc_order\Plugin\Ubercart\LineItem;

use Drupal\uc_order\LineItemPluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Handles the total line item.
 *
 * @UbercartLineItem(
 *   id = "total",
 *   title = @Translation("Total"),
 *   weight = 0,
 *   display_only = TRUE
 * )
 */
class Total extends LineItemPluginBase {

  public function display(OrderInterface $order) {
    $lines[] = array(
      'id' => 'total',
      'title' => $this->t('Order total'),
      'amount' => $order->getTotal(),
    );
    return $lines;
  }

}
