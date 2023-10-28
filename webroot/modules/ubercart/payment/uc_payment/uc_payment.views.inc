<?php

/**
 * @file
 * Views hooks and callback registries.
 */

/**
 * Implements hook_views_data().
 */
function uc_payment_views_data() {
  $data['uc_orders']['payment_method'] = array(
    'title' => t('Payment method'),
    'help' => t('The method of payment.'),
    'field' => array(
      'id' => 'uc_payment_method',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'uc_payment_method',
    ),
  );

  $data['uc_orders']['payments'] = array(
    'relationship' => array(
      'title' => t('Payments'),
      'help' => t('Relate payments to an order. This relationship will create one record for each payment received.'),
      'id' => 'standard',
      'base' => 'uc_payment_receipts',
      'base field' => 'order_id',
      'relationship field' => 'order_id',
      'label' => t('payments'),
    ),
  );

  $data['uc_payment_receipts']['table']['group'] = t('Payment');

  $data['uc_payment_receipts']['method'] = array(
    'title' => t('Payment method'),
    'help' => t('The method of payment.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $data['uc_payment_receipts']['amount'] = array(
    'title' => t('Amount'),
    'help' => t('The amount paid.'),
    'field' => array(
      'id' => 'uc_price',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  $data['uc_payment_receipts']['uid'] = array(
    'title' => t('User'),
    'help' => t('Relate a payment to the user who made it.'),
    'relationship' => array(
      'base' => 'users',
      'field' => 'uid',
      'id' => 'standard',
      'label' => t('user'),
    ),
  );

  $data['uc_payment_receipts']['comment'] = array(
    'title' => t('Comment'),
    'help' => t('Any remarks that were included with the payment.'),
    'field' => array(
      'id' => 'xss',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $data['uc_payment_receipts']['received'] = array(
    'title' => t('Receipt date'),
    'help' => t('The date and time the payment was received.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  return $data;
}
