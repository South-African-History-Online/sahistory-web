<?php

/**
 * @file
 * Theme and preprocess functions for fields.
 */

function saho_shop_preprocess_field(&$variables, $hook) {
  $element = $variables['element'];

  // Handle CKLB CTA field
  if ($element['#field_name'] == 'cklb_cta') {
    $variables['items'][0]['content']['#options']['attributes']['class'][] = 'btn btn-primary btn-lg';
  }

  // Handle commerce product images field
  if (isset($element['#entity_type']) &&
      $element['#entity_type'] === 'commerce_product' &&
      isset($element['#field_name']) &&
      $element['#field_name'] === 'images' &&
      $element['#view_mode'] === '_custom') {

        // Get theme settings
    $display_type = theme_get_setting('product_image_display');
    $fancybox_theme = theme_get_setting('product_image_lightbox_theme');
    $carousel_arrows = (bool) theme_get_setting('carousel_arrows');
    $carousel_dots = (bool) theme_get_setting('carousel_dots');
    $carousel_thumbs = (bool) theme_get_setting('carousel_thumbs');
    $carousel_functionality = theme_get_setting('carousel_functionality');

    // Pass carousel functionality setting to template
    $variables['carousel_functionality'] = $carousel_functionality;

    // Pass settings to JavaScript
    $variables['#attached']['drupalSettings']['saho_shop'] = [
      'productImageDisplay' => $display_type,
      'productImageLightboxTheme' => $fancybox_theme,
      'carouselArrows' => $carousel_arrows,
      'carouselDots' => $carousel_dots,
      'carouselThumbs' => $carousel_thumbs,
      'carouselFunctionality' => $carousel_functionality,
    ];
  }
}
