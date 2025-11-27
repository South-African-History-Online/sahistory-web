<?php

/**
 * @file
 * Custom theme hooks.
 */
use Drupal\node\NodeInterface;
use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Implements hook_theme_suggestions_HOOK_alter() for node templates.
 */
function saho_shop_theme_suggestions_node_alter(array &$suggestions, array $variables) {
  $node = $variables['elements']['#node'];
  $view_mode = $variables['elements']['#view_mode'];
  if(!empty($node) && $node instanceof NodeInterface && !is_null($view_mode)) {
    _saho_shop_theme_alter_entity_suggestions($suggestions, $node, $view_mode);
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter() for commerce_product templates.
 */
function saho_shop_theme_suggestions_commerce_product_alter(array &$suggestions, array $variables) {
  // View mode and theme settings
  $product = $variables['elements']['#commerce_product'];
  $view_mode = $variables['elements']['#view_mode'];
  if (!empty($product) && $product instanceof ProductInterface && !is_null($view_mode)) {
    _saho_shop_theme_alter_entity_suggestions($suggestions, $product, $view_mode);
  }
  // Limiting to catalog display mode
  $display = theme_get_setting('product_teaser');
  if ($display) {
    switch($view_mode) {
      case 'teaser':
        $suggestions[] = 'commerce_product__' . $view_mode . '__' . $display;
        break;
    }
  }
}

/**
 * Implements hook_theme_suggestions_field_alter().
 */
function saho_shop_theme_suggestions_field_alter(array &$suggestions, array $variables) {
  $element = $variables['element'];

  // Check if this is a commerce product images field
  if (isset($element['#entity_type']) &&
      $element['#entity_type'] === 'commerce_product' &&
      isset($element['#field_name']) &&
      $element['#field_name'] === 'images' &&
      $element['#view_mode'] === '_custom') {

    $display_type = theme_get_setting('product_image_display');

    // Map display types to template suggestions.
    $display_suggestions = [
      'carousel' => 'field__commerce_product__images__carousel',
      'lightbox' => 'field__commerce_product__images__fancybox',
      'carousel_fancybox' => 'field__commerce_product__images__carousel_fancybox',
    ];

    if (isset($display_suggestions[$display_type])) {
      $suggestions[] = $display_suggestions[$display_type];
    }
  }
}
