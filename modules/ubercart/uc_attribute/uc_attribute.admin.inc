<?php

/**
 * @file
 * Attribute administration menu items.
 */

use Drupal\Core\Render\Element;

/**
 * Returns a themed set of attribute options for use in order displays.
 *
 * @param array $variables
 *   An associative array containing:
 *   - attributes: An associative array containing the set of attributes,
 *     with each element keyed by attribute ID:
 *     - <aid>: An associative array containing:
 *       - #attribute_name: Attribute name.
 *       - #options: Array of option names.
 *
 * @return
 *   Themed set of attribute options.
 *
 * @ingroup themeable
 */
function theme_uc_product_attributes(array $variables) {
  $attributes = $variables['attributes'];

  $option_rows = array();

  foreach (Element::children($attributes) as $key) {
    $optionstr = '';

    foreach ((array)$attributes[$key]['#options'] as $option) {
      // We only need to allow translation from the second option onward
      if (empty($optionstr)) {
        $optionstr .= $option;
      }
      else {
        $optionstr .= t(', @option', ['@option' => $option]);
      }
    }

    if ($optionstr != '') {
      $option_rows[$key] = t('@attribute: @option', ['@attribute' => $attributes[$key]['#attribute_name'], '@option' => $optionstr]);
    }
  }

  if (!empty($option_rows)) {
    $item_list = array(
      '#theme' => 'item_list',
      '#items' => array_values($option_rows),
      '#attributes' => array(
        'class' => array('product-description'),
      ),
    );
    return \Drupal::service('renderer')->render($item_list);
  }

  return '';
}
