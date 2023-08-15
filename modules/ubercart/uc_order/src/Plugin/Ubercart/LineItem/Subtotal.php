<?php

namespace Drupal\uc_order\Plugin\Ubercart\LineItem;

use Drupal\uc_order\LineItemPluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Handles the subtotal line item.
 *
 * @UbercartLineItem(
 *   id = "subtotal",
 *   title = @Translation("Subtotal"),
 *   weight = 0,
 * )
 */
class Subtotal extends LineItemPluginBase {

  public function load(OrderInterface $order) {
    $lines[] = array(
      'id' => 'subtotal',
      'title' => $this->t('Subtotal'),
      'amount' => $order->getSubtotal(),
    );
    return $lines;
  }

}
