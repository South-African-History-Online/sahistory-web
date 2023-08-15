<?php

/**
 * @file
 * Theme functions for the uc_quote module.
 */

use Drupal\Core\Render\Element;

/**
 * Displays the formatted quote cart pane.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_cart_pane_quotes(array $variables) {
  $form = $variables['form'];

  $output = '<p class="quote-title">' . t('Estimated shipping cost:') . '</p>';
  $output .= drupal_render_children($form);

  return $output;
}

/**
 * Displays the returned shipping rates.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_quote_returned_rates(array $variables) {
  $form = $variables['form'];
  $output = '';

  $keys = Element::children($form);

  // Render notes and error messages after each radio button.
  if (count($keys) > 1) {
    foreach ($keys as $key) {
      if ($key == 'quote_option') {
        continue;
      }

      if (isset($form['quote_option'][$key])) {
        $output .= drupal_render($form['quote_option'][$key]);
      }
      $output .= drupal_render($form[$key]);
    }
  }

  $output .= drupal_render_children($form);

  return $output;
}
