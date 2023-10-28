<?php

/**
 * @file
 * Theme functions for the uc_usps module.
 */

/**
 * Theme function to format the USPS service name and rate amount line-item
 * shown to the customer.
 *
 * @param array $variables
 *   Associative array containing information needed to theme a quote.
 *   Contains two keys:
 *   - service: The USPS service name.
 *   - packages: Package information.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_usps_option_label(array $variables) {
  $service = $variables['service'];
  $packages = $variables['packages'];

  $build['image'] = array(
    '#theme' => 'image',
    '#uri' => drupal_get_path('module', 'uc_usps') . '/images/uc_usps_logo.jpg',
    '#alt' => t('U.S.P.S.'),
    '#attributes' => array('class' => array('usps-logo')),
  );
  // Add USPS service name, removing any 'U.S.P.S.' prefix.
  $build['label'] = array(
    '#plain_text' => preg_replace('/^U\.S\.P\.S\./', '', $service),
  );
  // Add package information.
  $build['packages'] = array(
    '#plain_text' => ' (' . \Drupal::translation()->formatPlural(count($packages), '1 package', '@count packages') . ')',
  );

  return drupal_render($build);
}
