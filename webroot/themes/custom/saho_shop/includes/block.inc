<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\block\Entity\Block;

/**
 *
 */
function saho_shop_theme_suggestions_block_alter(array &$suggestions, array $variables) {
  if (isset($variables['elements']['#id']) && isset($variables['elements']['#base_plugin_id'])) {
    if ($block = Block::load($variables['elements']['#id'])) {
      $region = $block->getRegion();
      if (!empty($region)) {
        $suggestions[] = 'block__' . $variables['elements']['#base_plugin_id'] . '__' . $region;
      }
    }
  }

  if (isset($variables['elements']['content']['#block_content'])) {
    $suggestions[] = 'block__' . $variables['elements']['content']['#block_content']->bundle();
  }
}

/**
 *
 */
function saho_shop_preprocess_block(&$variables) {
  switch ($variables['base_plugin_id']) {
    case 'system_branding_block':
      if (!empty($variables['site_logo'])) {
        // Inline SVG logo.
        $logo_path_info = pathinfo($variables['site_logo']);
        if ((bool) theme_get_setting('inline_logo') && $logo_path_info['extension'] === 'svg') {
          $variables['inline_logo'] = true;
        }
      }
      break;
  }

  // Adding custom attribute class for block.
  if ($variables['elements']['#base_plugin_id'] == 'block_content') {
    $blockType = strtr($variables['content']['#block_content']->bundle(), '_', '-');
    $variables['attributes']['class'][] = 'block--type-' . $blockType;
  }
}

/**
 * Implements hook_preprocess_HOOK() for commerce_cart_block.
 */
function saho_shop_preprocess_commerce_cart_block(&$variables) {
  $count = $variables['count'];

  $variables['cart_icon'] = theme_get_setting('cart_icon');
  $variables['cart_show_label'] = (bool) theme_get_setting('cart_show_label');
  $variables['cart_count_circle'] = (bool) theme_get_setting('cart_count_circle');
  $variables['cart_contents_bg'] = theme_get_setting('cart_contents_bg');
  $variables['cart_contents_text'] = theme_get_setting('cart_contents_text');
  $variables['cart_dropdown_position'] = theme_get_setting('cart_dropdown_position');
  $variables['cart_dropdown_animation'] = theme_get_setting('cart_dropdown_animation');
  $variables['cart_hover_display'] = (bool) theme_get_setting('cart_hover_display');

  // Pass cart contents title to template.
  $cart_contents_title = theme_get_setting('cart_contents_title');
  if (!empty($cart_contents_title)) {
    $variables['cart_contents_title'] = t($cart_contents_title, ['@count' => $count]);
  }

  $cart_label = theme_get_setting('cart_label');

  // Override count_text if a custom cart_label is set
  if (!empty($cart_label)) {
    // Translate the label first
    $translated_label = t($cart_label);

    // Build the count display with optional badge
    $count_display = $count;
    if ($variables['cart_count_circle']) {
      $count_display = '<span class="cart-block__count--badge">' . $count . '</span>';
    }

    // Replace @count placeholder with actual count (using str_replace to avoid escaping)
    $variables['count_text'] = str_replace('@count', $count_display, $translated_label);
  } else {
    // When no custom label, wrap the count in badge if enabled
    $count_display = $count;
    if ($variables['cart_count_circle']) {
      $count_display = '<span class="cart-block__count--badge">' . $count . '</span>';
    }
    $variables['count_text'] = str_replace($count, $count_display, $variables['count_text']);
  }
}
