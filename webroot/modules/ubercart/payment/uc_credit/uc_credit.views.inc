<?php

/**
 * @file
 * Views hooks and callback registries.
 */

/**
 * Implements hook_views_data().
 */
function uc_credit_views_data() {
  $data['uc_orders']['cc_type'] = array(
    'title' => t('Credit card type'),
    'help' => t('Credit card type used, if the payment method was credit.'),
    'real field' => 'data',
    'field' => array(
      'id' => 'uc_credit_data',
      'cc field' => 'cc_type',
    ),
  );

  $data['uc_orders']['cc_number'] = array(
    'title' => t('Credit card number'),
    'help' => t('Credit card number (last 4) used, if the payment method was credit.'),
    'real field' => 'data',
    'field' => array(
      'id' => 'uc_credit_data',
      'cc field' => 'cc_number',
    ),
  );

  return $data;
}
