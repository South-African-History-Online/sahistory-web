<?php

namespace Drupal\uc_tax\Plugin\Ubercart\LineItem;

use Drupal\uc_order\LineItemPluginBase;

/**
 * Handles the tax line item.
 *
 * @UbercartLineItem(
 *   id = "tax",
 *   title = @Translation("Tax"),
 *   weight = 9,
 *   stored = TRUE,
 *   calculated = TRUE
 * )
 */
class Tax extends LineItemPluginBase {
}
