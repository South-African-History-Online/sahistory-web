<?php

/**
 * @file
 * Token hooks for the uc_product module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function uc_product_token_info() {
  $node['product'] = array(
    'name' => t('Product'),
    'description' => t('Ubercart product data of the node.'),
    'type' => 'uc_product',
  );

  $types['uc_product'] = array(
    'name' => t('Product'),
    'description' => t('Tokens for Ubercart products.'),
    'needs-data' => 'node',
  );
  $product['model'] = array(
    'name' => t('SKU / Model'),
    'description' => t("The product's SKU or model number."),
  );
  $product['cost'] = array(
    'name' => t('Cost'),
    'description' => t("The product's cost."),
    'type' => 'uc_price',
  );
  $product['price'] = array(
    'name' => t('Price'),
    'description' => t("The product's price."),
    'type' => 'uc_price',
  );
  $product['weight'] = array(
    'name' => t('Weight'),
    'description' => t("The product's weight."),
    'type' => 'uc_weight',
  );
  $product['length'] = array(
    'name' => t('Length'),
    'description' => t("The product's length."),
    'type' => 'uc_dimension',
  );
  $product['width'] = array(
    'name' => t('Width'),
    'description' => t("The product's width."),
    'type' => 'uc_dimension',
  );
  $product['height'] = array(
    'name' => t('Height'),
    'description' => t("The product's height."),
    'type' => 'uc_dimension',
  );

  $types['uc_price'] = array(
    'name' => t('Price'),
    'description' => 'Tokens for price formats.',
    'needs-data' => 'uc_price',
  );
  $price['formatted'] = array(
    'name' => t('Formatted'),
    'description' => t('A formatted price.'),
  );
  $price['raw'] = array(
    'name' => t('Raw value'),
    'description' => t('A decimal price.'),
  );

  $types['uc_weight'] = array(
    'name' => t('Weight'),
    'description' => 'Tokens for weight formats.',
    'needs-data' => 'uc_weight',
  );
  $weight['formatted'] = array(
    'name' => t('Formatted'),
    'description' => t('A formatted weight.'),
  );
  $weight['raw'] = array(
    'name' => t('Raw value'),
    'description' => t('A decimal weight.'),
  );
  $weight['units'] = array(
    'name' => t('Units'),
    'description' => t('The unit of measurement.'),
  );

  $types['uc_dimension'] = array(
    'name' => t('Dimension'),
    'description' => 'Tokens for length, width, and height.',
    'needs-data' => 'uc_dimension',
  );
  $dimension['formatted'] = array(
    'name' => t('Formatted'),
    'description' => t('A formatted dimension.'),
  );
  $dimension['raw'] = array(
    'name' => t('Raw value'),
    'description' => t('A decimal dimension.'),
  );
  $dimension['units'] = array(
    'name' => t('Units'),
    'description' => t('The unit of measurement.'),
  );

  return array(
    'types' => $types,
    'tokens' => array(
      'node' => $node,
      'uc_product' => $product,
      'uc_price' => $price,
      'uc_weight' => $weight,
      'uc_dimension' => $dimension,
    ),
  );
}

/**
 * Implements hook_tokens().
 */
function uc_product_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  if ($type == 'node' && !empty($data['node']) && uc_product_is_product($data['node'])) {
    $product = &$data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'product:model':
          $replacements[$original] = $product->model->value;
          break;

        case 'product:cost':
        case 'product:cost:formatted':
          $replacements[$original] = uc_currency_format($product->cost->value);
          break;

        case 'product:price':
        case 'product:price:formatted':
          $replacements[$original] = uc_currency_format($product->price->value);
          break;

        case 'product:cost:raw':
          $replacements[$original] = $product->cost->value;
          break;

        case 'product:price:raw':
          $replacements[$original] = $product->price->value;
          break;

        case 'product:weight':
        case 'product:weight:formatted':
          $replacements[$original] = uc_weight_format($product->weight->value, $product->weight->units);
          break;

        case 'product:weight:raw':
          $replacements[$original] = $product->weight->value;
          break;

        case 'product:weight:units':
          $replacements[$original] = $product->weight->units;
          break;

        case 'product:length':
        case 'product:length:formatted':
          $replacements[$original] = uc_length_format($product->dimensions->length, $product->dimensions->units);
          break;

        case 'product:width':
        case 'product:width:formatted':
          $replacements[$original] = uc_length_format($product->dimensions->width, $product->dimensions->units);
          break;

        case 'product:height':
        case 'product:height:formatted':
          $replacements[$original] = uc_length_format($product->dimensions->height, $product->dimensions->units);
          break;

        case 'product:length:raw':
          $replacements[$original] = $product->dimensions->length;
          break;

        case 'product:width:raw':
          $replacements[$original] = $product->dimensions->width;
          break;

        case 'product:height:raw':
          $replacements[$original] = $product->dimensions->height;
          break;

        case 'product:length:units':
        case 'product:width:units':
        case 'product:height:units':
          $replacements[$original] = $product->dimensions->units;
          break;
      }
    }

    return $replacements;
  }
}
