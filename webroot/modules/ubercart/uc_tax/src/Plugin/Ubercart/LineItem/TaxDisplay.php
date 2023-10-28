<?php

namespace Drupal\uc_tax\Plugin\Ubercart\LineItem;

use Drupal\uc_order\LineItemPluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Handles the tax line item.
 *
 * @UbercartLineItem(
 *   id = "tax_display",
 *   title = @Translation("Tax"),
 *   weight = 5,
 *   calculated = TRUE,
 *   display_only = TRUE
 * )
 */
class TaxDisplay extends LineItemPluginBase {

  public function display(OrderInterface $order) {
    $lines = array();
    $taxes = uc_tax_calculate($order);
    foreach ($taxes as $tax) {
      foreach ($order->line_items as $line_item) {
        if ($line_item['type'] == 'tax' && $line_item['data']['tax_id'] == $tax->id) {
          continue 2;
        }
      }
      $lines[] = _uc_tax_to_line_item($tax);
    }
    return $lines;
  }

}
