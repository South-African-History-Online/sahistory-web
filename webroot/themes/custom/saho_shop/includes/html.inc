<?php

/**
 * @file
 * Theme and preprocess functions for html wrapper.
 */

use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_HOOK() for html templates.
 */
function saho_shop_preprocess_html(&$variables) {
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof NodeInterface) {
    // * Add content type machine name body class.
    $variables['attributes']['class'][] = 'node-type--' . str_replace('_', '-', $node->bundle());
  }

  // * Check different error conditions to add body classes.
  $route_name = \Drupal::request()->attributes->get('_route');
  if ('system.404' === $route_name) {
    $variables['attributes']['class'][] = 'error404';
  }
  if ('system.403' === $route_name) {
    $variables['attributes']['class'][] = 'error403';
  }

  // * Colors.
  $all_styles = [];

  if ((bool) theme_get_setting('saho_shop_enable_color')) {
    $variables['#attached']['library'][] = 'saho_shop/colors';

    $base_primary_color_hex = theme_get_setting('base_primary_color');
    $base_light_color_hex = theme_get_setting('base_light_color');
    $base_dark_color_hex = theme_get_setting('base_dark_color');
    $body_text_color_hex = theme_get_setting('body_text_color');
    $body_background_color_hex = theme_get_setting('body_background_color');

    $all_styles = [
      "--beo-primary: $base_primary_color_hex;",
      "--beo-primary-rgb:" . _saho_shop_hex2rgb($base_primary_color_hex) . ";",
      "--beo-light: $base_light_color_hex;",
      "--beo-light-rgb:" . _saho_shop_hex2rgb($base_light_color_hex) . ";",
      "--beo-dark: $base_dark_color_hex;",
      "--beo-dark-rgb:" . _saho_shop_hex2rgb($base_dark_color_hex) . ";",
      "--beo-body-bg: $body_background_color_hex;",
      "--beo-body-color: $body_text_color_hex;",
    ];
  }

  // * Focused page CSS variables.
  if ((bool) theme_get_setting('focused_checkout') && _saho_shop_is_focused_path()) {
    // Generate CSS variables for focused page
    $focused_styles = _saho_shop_generate_focused_checkout_css_variables();

    if (!empty($focused_styles)) {
      $all_styles = array_merge($all_styles, $focused_styles);
    }
  }

  // * Form field spacing CSS variables
  $form_field_spacing = theme_get_setting('form_field_spacing');

  // Define spacing values based on the setting
  $spacing_values = [
    'compact' => '1rem',
    'normal' => '2rem',
    'spacious' => '3rem',
  ];

  $fieldset_legend_spacing_values = [
    'compact' => '0.5rem',
    'normal' => '1.5rem',
    'spacious' => '2rem',
  ];

  $form_styles = [
    "--form-item-spacing: " . ($spacing_values[$form_field_spacing] ?? '2rem') . ";",
    "--fieldset-legend-spacing: " . ($fieldset_legend_spacing_values[$form_field_spacing] ?? '1.5rem') . ";",
  ];

  $all_styles = array_merge($all_styles, $form_styles);

  // * Apply all styles to HTML attributes
  if (!empty($all_styles)) {
    $variables['html_attributes']->setAttribute('style', implode(' ', $all_styles));
  }

  // * Get theme path in JS
  $variables['#attached']['drupalSettings']['path']['themeUrl'] = \Drupal::theme()->getActiveTheme()->getPath();
}

/**
 * Convert hex to rgb color.
 *
 * @param string $color
 *   The hex color value (e.g., #ff0000).
 *
 * @return string
 *   The RGB color value as a comma-separated string (e.g., "255, 0, 0").
 */
function _saho_shop_hex2rgb(string $color): string {
  list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
  $output = "$r, $g, $b";
  return $output;
}

/**
 * Convert hex to rgba color.
 *
 * @param string $color
 *   The hex color value (e.g., #ff0000).
 * @param float $opacity
 *   The opacity value between 0 and 1.
 *
 * @return string
 *   The RGBA color value (e.g., "rgba(255, 0, 0, 0.5)").
 */
function _saho_shop_hex2rgba(string $color, float $opacity): string {
  list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
  $output = "rgba($r, $g, $b, $opacity)";
  return $output;
}

/**
 * Generate CSS variables for focused checkout.
 *
 * @return array
 *   An array of CSS variable strings for focused checkout styling.
 */
function _saho_shop_generate_focused_checkout_css_variables(): array {
  $styles = [];

    // Container widths
  $container_widths = [
    'container-fluid' => '100%',
    'container-lg' => '1200px',
    'container-md' => '960px',
  ];

  $container_setting = theme_get_setting('focused_page_container');
  if ($container_setting && isset($container_widths[$container_setting])) {
    $styles[] = "--focused-page-container-width: {$container_widths[$container_setting]};";
  } elseif ($container_setting === 'custom') {
    $custom_width = theme_get_setting('focused_page_container_custom');
    if ($custom_width) {
      $styles[] = "--focused-page-container-width: $custom_width;";
    }
  }

  $header_container_setting = theme_get_setting('focused_page_header_container');
  if ($header_container_setting && isset($container_widths[$header_container_setting])) {
    $styles[] = "--focused-page-header-container-width: {$container_widths[$header_container_setting]};";
  } elseif ($header_container_setting === 'custom') {
    $custom_width = theme_get_setting('focused_page_header_container_custom');
    if ($custom_width) {
      $styles[] = "--focused-page-header-container-width: $custom_width;";
    }
  }

  // Colors
  if ((bool) theme_get_setting('focused_page_override_colors')) {
    // Primary color override
    $primary_color_override = theme_get_setting('focused_page_primary_color_override');
    if ($primary_color_override) {
      $styles[] = "--beo-primary: $primary_color_override;";
      $styles[] = "--beo-primary-rgb: " . _saho_shop_hex2rgb($primary_color_override) . ";";
      $styles[] = "--beo-primary-hover: " . _saho_shop_hex2rgba($primary_color_override, 0.8) . ";";
    }

    $styles[] = "--focused-page-bg-color: " . theme_get_setting('focused_page_bg_color') . ";";
    $styles[] = "--focused-page-text-color: " . theme_get_setting('focused_page_text_color') . ";";
    $styles[] = "--focused-page-sidebar-bg: " . theme_get_setting('focused_page_sidebar_bg_color') . ";";
    $styles[] = "--focused-page-sidebar-text: " . theme_get_setting('focused_page_sidebar_text_color') . ";";
  }

  if ((bool) theme_get_setting('focused_page_override_header_colors')) {
    $styles[] = "--focused-page-header-bg: " . theme_get_setting('focused_page_header_bg_color') . ";";
    $styles[] = "--focused-page-header-text: " . theme_get_setting('focused_page_header_text_color') . ";";
    $styles[] = "--focused-page-header-border: " . theme_get_setting('focused_page_header_border_color') . ";";
  }

  return $styles;
}
