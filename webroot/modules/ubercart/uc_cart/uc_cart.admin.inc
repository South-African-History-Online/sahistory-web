<?php

/**
 * @file
 * Cart administration menu items.
 */

/**
 * Checkout rules configuration.
 */
function uc_cart_checkout_rules() {
  $conditions = array(
    'event' => 'uc_checkout_complete',
    'plugin' => 'reaction rule',
  );
  $options = array(
    'base path' => 'admin/store/config/checkout/rules',
    'show plugin' => FALSE,
  );
  $content['rules'] = rules_ui()->overviewTable($conditions, $options);
  return $content;
}
