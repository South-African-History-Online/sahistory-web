<?php

/**
 * @file
 * Hooks provided by the Payment module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter payment methods.
 *
 * @param $methods
 *   Array of payment methods plugins passed by reference.
 */
function hook_uc_payment_method_alter(&$methods) {
  // Change the title of the Check payment method.
  $methods['check']['name'] = t('Cheque');
}

/**
 * Alter payment methods available at checkout.
 *
 * @param $methods
 *   Array of payment methods passed by reference. Keys are payment method IDs,
 *   strings are payment method titles.
 * @param $order
 *   The order that is being checked out.
 */
function hook_uc_payment_method_checkout_alter(&$methods, $order) {
  // Remove the Check payment method for orders under $100.
  if ($order->getTotal() < 100) {
    unset($methods['check']);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
