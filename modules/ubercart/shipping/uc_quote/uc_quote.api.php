<?php

/**
 * @file
 * Hooks provided by the Shipping Quotes module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter shipping quote methods.
 *
 * @param $methods
 *   Array of shipping quote plugins passed by reference.
 */
function hook_uc_quote_method_alter(&$methods) {
  // Change the label of the flat rate shipping quote plugin.
  $methods['flatrate']['admin_label'] = t('Simple');
}

/**
 * Defines shipping types for shipping methods.
 *
 * This hook defines a shipping type that this module is designed to handle.
 * These types are specified by a machine- and human-readable name called 'id',
 * and 'title' respectively. Shipping types may be set for individual products,
 * manufacturers, and for the entire store catalog. Shipping modules should be
 * careful to use the same shipping type ids as other similar shipping modules
 * (i.e., FedEx and UPS both operate on "small package" shipments). Modules that
 * do not fulfill orders may not need to implement this hook.
 *
 * @return
 *   An array of shipping types keyed by a machine-readable name.
 */
function hook_uc_shipping_type() {
  $weight = \Drupal::config('uc_quote.settings')->get('type_weight');

  $types = array();
  $types['small_package'] = array(
    'id' => 'small_package',
    'title' => t('Small package'),
    'weight' => $weight['small_package'],
  );

  return $types;
}

/**
 * @} End of "addtogroup hooks".
 */
