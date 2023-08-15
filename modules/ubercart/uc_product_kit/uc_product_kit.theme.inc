<?php

/**
 * @file
 * Theme functions for the uc_product_kit module.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\node\NodeInterface;

/**
 * Renders a product kit component.
 *
 * @ingroup themeable
 */
function theme_uc_product_kit_list_item(array $variables) {
  $product = $variables['product'];

  if ($product instanceof NodeInterface) {
    $node = node_load($product->id());
    if ($node->access('view')) {
      $title = $product->toLink()->toString();
    }
    else {
      $title = SafeMarkup::checkPlain($product->label());
    }
    $qty = $product->qty;
  }
  else {
    $node = $product->nid->entity;
    if ($node->access('view')) {
      $title = $node->toLink($product->title)->toString();
    }
    else {
      $title = SafeMarkup::checkPlain($product->title);
    }
    $qty = $product->qty->value;
  }

  $build = array(
    '#theme' => 'uc_qty',
    '#qty' => $qty,
  );
  return drupal_render($build) . ' ' . $title;
}
