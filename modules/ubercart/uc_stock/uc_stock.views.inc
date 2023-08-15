<?php

/**
 * @file
 * Views hooks and callback registries.
 */

/**
 * Implements hook_views_data().
 */
function uc_stock_views_data() {
  $data['uc_product_stock']['table']['group'] = t('Stock');

  // Attach stock data to nodes.
  $data['uc_product_stock']['table']['join']['node'] = array(
    'left_field' => 'nid',
    'field' => 'nid',
  );

  $data['uc_product_stock']['sku'] = array(
    'title' => t('SKU'),
    'help' => t('The model or SKU of the stock level.'),
    'field' => array(
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['uc_product_stock']['active'] = array(
    'title' => t('Active'),
    'help' => t('Whether or not stock is currently being tracked.'),
    'field' => array(
      'id' => 'boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Active'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['uc_product_stock']['stock'] = array(
    'title' => t('Level'),
    'help' => t('The current stock level.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['uc_product_stock']['threshold'] = array(
    'title' => t('Threshold'),
    'help' => t('The level at which a stock warning can be sent.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['uc_product_stock']['below_threshold'] = array(
    'title' => t('Is below threshold'),
    'help' => t('Filter the node based on whether its stock level is below the threshold for the SKU.'),
    'filter' => array(
      'id' => 'uc_stock_below_threshold',
      'label' => t('Is below threshold'),
      'type' => 'yes-no',
    ),
  );

  return $data;
}
