<?php

namespace Drupal\uc_quote\Plugin\Ubercart\LineItem;

use Drupal\uc_order\LineItemPluginBase;

/**
 * Handles the subtotal line item.
 *
 * @UbercartLineItem(
 *   id = "shipping",
 *   title = @Translation("Shipping"),
 *   weight = 1,
 *   stored = TRUE,
 *   calculated = TRUE,
 *   add_list = TRUE
 * )
 */
class Shipping extends LineItemPluginBase {
}
