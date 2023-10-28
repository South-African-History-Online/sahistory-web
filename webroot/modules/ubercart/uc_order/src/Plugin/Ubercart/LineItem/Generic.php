<?php

namespace Drupal\uc_order\Plugin\Ubercart\LineItem;

use Drupal\uc_order\LineItemPluginBase;

/**
 * Handles the generic line item.
 *
 * @UbercartLineItem(
 *   id = "generic",
 *   title = @Translation("Generic"),
 *   weight = 2,
 *   stored = TRUE,
 *   add_list = TRUE,
 *   calculated = TRUE
 * )
 */
class Generic extends LineItemPluginBase {
}
