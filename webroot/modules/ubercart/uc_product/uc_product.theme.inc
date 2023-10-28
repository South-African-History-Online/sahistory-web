<?php

/**
 * @file
 * Theme functions for uc_product module.
 */

use Drupal\Core\Template\Attribute;

/**
 * Formats a product's price.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array render element containing:
 *     - #value: Price to be formatted.
 *     - #attributes: (optional) Array of attributes to apply to enclosing DIV.
 *     - #title: (optional) Title to be used as label.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_product_price(array $variables) {
  $element = $variables['element'];
  $price = $element['#value'];
  $attributes = new Attribute($element['#attributes']);
  $attributes['class'][] = 'product-info';
  $label = isset($element['#title']) ? $element['#title'] : '';

  $output = '<div ' . $attributes . '>';
  if ($label) {
    $output .= '<span class="uc-price-label">' . $label . '</span> ';
  }
  $vars = array(
    '#theme' => 'uc_price',
    '#price' => $price,
  );
  if (!empty($element['#suffixes'])) {
    $vars['#suffixes'] = $element['#suffixes'];
  }
  $output .= drupal_render($vars);
  $output .= drupal_render_children($element);
  $output .= '</div>';

  return $output;
}
