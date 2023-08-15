<?php

/**
 * @file
 * Menu callbacks for shipping quotes requested through AJAX.
 */

use Drupal\uc_quote\Entity\ShippingQuoteMethod;

/**
 * Pulls the get_quote_from_* triggers and assembles their returned data.
 */
function uc_quote_assemble_quotes($order) {
  $shipping_types = array();
  foreach ($order->products as $product) {
    $shipping_types[] = uc_product_get_shipping_type($product);
  }

  $shipping_types = array_unique($shipping_types);
  $all_types = uc_quote_get_shipping_types();
  $shipping_type = '';

  // Use the most prominent shipping type (highest weight).
  // In theory, you can ship lighter products with heavier by the same
  // method, but not vice versa.
  $type_weight = -1000; // arbitrary low number
  foreach ($shipping_types as $type) {
    if ($all_types[$type]['weight'] > $type_weight) {
      $shipping_type = $all_types[$type]['id'];
      $type_weight = $all_types[$type]['weight'];
    }
  }

  $manager = \Drupal::service('plugin.manager.uc_quote.method');
  $methods = ShippingQuoteMethod::loadMultiple();
  uasort($methods, 'Drupal\uc_quote\Entity\ShippingQuoteMethod::sort');

  /* @todo Implement shipping quote types
  foreach ($methods as $id => $method) {
    if (!isset($method['quote']) || ($method['quote']['type'] != 'order' && $method['quote']['type'] != $shipping_type)) {
      unset($methods[$id]);
    }
  }
  */

  $quotes = [];
  foreach ($methods as $method) {
    if ($method->status()) {
      $plugin = $manager->createInstance($method->getPluginId(), $method->getPluginConfiguration());
      foreach ($plugin->getQuotes($order) as $accessorial => $quote) {
        $quotes[$method->id()][$accessorial] = [
          'rate' => $quote,
          'format' => uc_currency_format($quote),
          'option_label' => $method->label(),
        ];
      }
    }
  }
  return $quotes;
}
