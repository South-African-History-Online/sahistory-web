<?php

use Drupal\Component\Utility\Html;

/**
 * Implements hook_preprocess_HOOK() for region.html.twig.
 */
function saho_shop_preprocess_region(&$variables) {

  $region = $variables['elements']['#region'];

  if (theme_get_setting('region_class_' . $region) !== NULL) {
    $classes = explode(' ', theme_get_setting('region_class_' . $region));
    foreach ($classes as $class) {
      $variables['attributes']['class'][] = Html::cleanCssIdentifier($class);
    }
  }

  if (theme_get_setting('region_container_' . $region) !== NULL) {
    $variables['region_container'] = theme_get_setting('region_container_' . $region);
  }

  if ($region === 'top_bar') {
    // Menu toggle
    $navigation_toggle_text = theme_get_setting('navigation_toggle_text');
    $variables['navigation_toggle_text'] = !empty($navigation_toggle_text) ? t($navigation_toggle_text) : '';
    $variables['navigation_toggle_visibility'] = theme_get_setting('navigation_toggle_visibility');
  }

  if ($region === 'navigation') {
    $variables['navigation_body_scrolling'] = "false";
    $variables['navigation_backdrop'] = "false";

    if ((bool) theme_get_setting('navigation_logo')) {
      $logo_uri = theme_get_setting('logo.url');

      if (!empty($logo_uri)) {
        $variables['site_logo'] = \Drupal::service('file_url_generator')->generateString($logo_uri);
        $logo_path_info = pathinfo($variables['site_logo']);

        if ((bool) theme_get_setting('inline_logo') && isset($logo_path_info['extension']) && $logo_path_info['extension'] === 'svg') {
          $variables['inline_logo'] = true;
        }
      }
    }


    $variables['navigation_position'] = theme_get_setting('navigation_position');
    if ((bool) theme_get_setting('navigation_body_scrolling')) {
      $variables['navigation_body_scrolling'] = "true";
    }
    if ((bool) theme_get_setting('navigation_backdrop')) {
      $variables['navigation_backdrop'] = "true";
    }
  }
}
