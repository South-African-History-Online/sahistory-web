<?php

namespace Drupal\uc_tax\Plugin\Ubercart\LineItem;

use Drupal\uc_order\LineItemPluginBase;
use Drupal\uc_order\OrderInterface;

/**
 * Handles the tax line item.
 *
 * @UbercartLineItem(
 *   id = "tax_subtotal",
 *   title = @Translation("Subtotal excluding taxes"),
 *   weight = 7,
 *   display_only = TRUE
 * )
 */
class TaxSubtotal extends LineItemPluginBase {

  public function display(OrderInterface $order) {
    $amount = 0;
    $has_taxes = FALSE;
    $different = FALSE;

    if (is_array($order->products)) {
      foreach ($order->products as $item) {
        $amount += $item->price->value * $item->qty->value;
      }
    }
    if (is_array($order->line_items)) {
      foreach ($order->line_items as $line_item) {
        if ($line_item['type'] == 'subtotal') {
          continue;
        }
        if (substr($line_item['type'], 0, 3) != 'tax') {
          $amount += $line_item['amount'];
          $different = TRUE;
        }
        else {
          $has_taxes = TRUE;
        }
      }
    }

    if (isset($order->tax) && is_array($order->tax) && count($order->tax)) {
      $has_taxes = TRUE;
    }

    if ($different && $has_taxes) {
      return array(array(
        'id' => 'tax_subtotal',
        'title' => $this->t('Subtotal excluding taxes'),
        'amount' => $amount,
        'weight' => \Drupal::config('uc_tax.settings')->get('tax_line_item.subtotal_weight'),
      ));
    }
  }

}
