<?php

/**
 * @file
 * Hooks provided by the Tax module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Calculates tax line items for an order.
 *
 * @param $order
 *   An order object or an order id.
 *
 * @return
 *   An array of tax line item objects keyed by a module-specific id.
 */
function hook_uc_calculate_tax($order) {
  if (!is_object($order)) {
    return array();
  }
  if (empty($order->delivery_postal_code)) {
    $order->delivery_postal_code = $order->billing_postal_code;
  }
  if (empty($order->delivery_zone)) {
    $order->delivery_zone = $order->billing_zone;
  }
  if (empty($order->delivery_country)) {
    $order->delivery_country = $order->billing_country;
  }

  $order->tax = array();

  if ($order->getStatusId()) {
    $use_same_rates = in_array($order->getStateId(), array('payment_received', 'completed'));
  }
  else {
    $use_same_rates = FALSE;
  }

  foreach (uc_tax_rate_load() as $tax) {
    if ($use_same_rates) {
      foreach ((array)$order->line_items as $old_line) {
        if ($old_line['type'] == 'tax' && $old_line['data']['tax_id'] == $tax->id) {
          $tax->rate = $old_line['data']['tax_rate'];
          break;
        }
      }
    }

    $set = rules_config_load('uc_tax_' . $tax->id);
    if ($set->execute($order)) {
      $line_item = uc_tax_apply_tax($order, $tax);
      if ($line_item) {
        $order->tax[$line_item->id] = $line_item;
      }
    }
  }

  return $order->tax;
}

/**
 * @} End of "addtogroup hooks".
 */
