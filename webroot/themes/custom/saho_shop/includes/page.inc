<?php

/**
 * @file
 * Theme and preprocess functions for pages.
 */

/**
 * Adds theme path alias suggestion for use in system pages bundle.
 */

use Drupal\Component\Utility\Html;
use Drupal\node\NodeInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Implements hook_preprocess_HOOK() for page templates.
 */
function saho_shop_preprocess_page(&$variables) {
  // Main container
  if (theme_get_setting('main_container_class')) {
    $main_container_class = explode(' ', theme_get_setting('main_container_class'));
    foreach ($main_container_class as $class) {
      $variables['attributes']['class'][] = Html::cleanCssIdentifier($class);
    }
  }

  if (theme_get_setting('main_container')) {
    $main_container = theme_get_setting('main_container');
    $variables['main_container'] = $main_container;
  }

  $variables['local_tasks_fixed'] = (bool) theme_get_setting('local_tasks_fixed');

  // Check if address dialog is enabled and we're on an address book page
  if (theme_get_setting('address_dialog') &&
      \Drupal::routeMatch()->getRouteName() == 'commerce_order.address_book.overview') {

    // Attach the address dialog library to the page
    $variables['#attached']['library'][] = 'saho_shop/address.dialog';

    // Pass the setting to JavaScript
    $variables['#attached']['drupalSettings']['saho_shop']['addressDialog'] = TRUE;
  }

  // Add background image variable for focused user auth pages
  if (_saho_shop_is_user_auth_page() && (bool) theme_get_setting('focused_user_auth')) {
    // Pass the show background image setting to template
    $variables['focused_user_auth_show_bg_image'] = (bool) theme_get_setting('focused_user_auth_show_bg_image');

    // Pass the background color setting to template
    $variables['focused_user_auth_bg_color'] = theme_get_setting('focused_user_auth_bg_color');

    // Check if background image should be shown
    if ($variables['focused_user_auth_show_bg_image']) {
      $bg_image_fid = theme_get_setting('focused_user_auth_bg_image');

      if (!empty($bg_image_fid) && is_array($bg_image_fid) && !empty($bg_image_fid[0])) {
        $file = \Drupal\file\Entity\File::load($bg_image_fid[0]);
        if ($file) {
          $variables['focused_user_auth_bg_image_url'] = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
        }
      }
    }
  }

    // Add focused page class to body if focused path is set on the path.
  if ((bool) theme_get_setting('focused_checkout') && _saho_shop_is_focused_path()) {
    // Set all focused page variables
    $variables['focused_page_logo'] = (bool) theme_get_setting('focused_page_logo');

    // Header settings
    $variables['focused_page_show_back_button'] = (bool) theme_get_setting('focused_page_show_back_button');
    $back_button_text = theme_get_setting('focused_page_back_button_text');
    $variables['focused_page_back_button_text'] = !empty($back_button_text) ? t($back_button_text) : '';
    $variables['focused_page_show_cart_button'] = (bool) theme_get_setting('focused_page_show_cart_button');
    $cart_button_text = theme_get_setting('focused_page_cart_button_text');
    $variables['focused_page_cart_button_text'] = !empty($cart_button_text) ? t($cart_button_text) : '';
    $variables['focused_page_show_icons'] = (bool) theme_get_setting('focused_page_show_icons');

    // Logo and site name
    $variables['site_name'] = \Drupal::config('system.site')->get('name');

    $active_theme = \Drupal::theme()->getActiveTheme()->getName();
    $default_theme = \Drupal::config('system.theme')->get('default');

    $logo_url = theme_get_setting('logo.url');
    $inline_logo = (bool) theme_get_setting('inline_logo');
    $use_default_theme_logo = (bool) theme_get_setting('focused_page_use_default_theme_logo');

    // When SahoShop renders focused checkout while another theme is the site
    // default we can use the default theme's logo.
    if ($use_default_theme_logo && !empty($default_theme) && $default_theme !== $active_theme) {
      $default_logo_url = theme_get_setting('logo.url', $default_theme);
      if (!empty($default_logo_url)) {
        $logo_url = $default_logo_url;
      }
    }

    // Check if the logo should be an inline SVG.
    $logo_path_info = pathinfo($logo_url);
    if ($inline_logo && $logo_path_info['extension'] === 'svg') {
      $variables['inline_logo'] = true;
    }
    $variables['logo_url'] = $logo_url;
    // Cart icon for focused page cart button
    $variables['cart_icon'] = theme_get_setting('cart_icon');
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter() for page templates.
 * @param array $suggestions
 * @param array $variables
 */
function saho_shop_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  $current_path = \Drupal::service('path.current')->getPath();
  $result = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
  $path_alias = trim($result, '/');
  $path_alias = str_replace('/', '-', $path_alias);
  $path_alias = str_replace('-', '_', $path_alias);

  $suggestions[] = 'page__path__' . $path_alias;

  // Defines custom theme suggestions based on the route.
  $route_name = \Drupal::request()->attributes->get('_route');

  if ('system.404' === $route_name) {
    $suggestions[] = 'page__404';
  }
  if ('system.403' === $route_name) {
    $suggestions[] = 'page__403';
  }

  if ($product = \Drupal::routeMatch()->getParameter('commerce_product')) {
    $type = $product->bundle();
    // Add product type suggestions
    if (!empty($product) && $product instanceof ProductInterface) {
      array_splice($suggestions, 1, 0, 'page__product__' . $type);
      // Add layout builder enabled template suggestions
      _saho_shop_theme_alter_page_suggestions($suggestions, $product);
    }
  }

  // * Adds page node type theme suggestion.
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    if ($node_revision = \Drupal::routeMatch()->getParameter('node_revision')) {
      if ($node_revision instanceof NodeInterface) {
        $node = $node_revision;
      }
      elseif ($node_revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($node_revision)) {
        $node = $node_revision;
      }
    }
    if (!empty($node) && $node instanceof NodeInterface) {
      $type = $node->getType();
      // Add content type suggestions.
      array_splice($suggestions, 1, 0, 'page__node__' . $type);
      // Add layout builder enabled template suggestions
      _saho_shop_theme_alter_page_suggestions($suggestions, $node);
    }
  }

  // * Add view mode theme suggestions based on the vocabulary
  if (\Drupal::routeMatch()->getRouteName() == 'entity.taxonomy_term.canonical' && $tid = \Drupal::routeMatch()->getRawParameter('taxonomy_term')) {
    $term = Term::load($tid);
    $suggestions[] = 'page__taxonomy__' . $term->bundle();
  }

  // Add focused checkout template suggestion based on theme settings.
  if ((bool) theme_get_setting('focused_checkout') && _saho_shop_is_focused_path()) {
    // Add 'focused' suffix to all existing page suggestions.
    $focused_suggestions = [];
    foreach ($suggestions as $suggestion) {
      $focused_suggestions[] = 'page__focused';
      $focused_suggestions[] = $suggestion . '__focused';
    }
    // Add base focused suggestion
    $suggestions = array_merge($suggestions, $focused_suggestions);
  }

  // Check if focused user auth is enabled and we're on a user auth page
  if (_saho_shop_is_user_auth_page()) {
    $suggestions[] = 'page__user__authenticate';
    if ((bool) theme_get_setting('focused_user_auth') ) {
      $suggestions[] = 'page__user__authenticate__focused';
    }
  }
}

/**
 * Implements hook_page_attachments_alter().
 */
function saho_shop_page_attachments_alter(array &$page) {
  $route_name = \Drupal::routeMatch()->getRouteName();

  // Cart page
  if ($route_name === 'commerce_cart.page') {
    $page['#attached']['library'][] = 'saho_shop/page.cart';
  }

  // Catalog page
  if ($route_name === 'view.product_catalog.catalog_page') {
    $page['#attached']['library'][] = 'saho_shop/page.catalog';
  }

  // Font libraries attachments
  $font = theme_get_setting('font_set');
  if ($font) {
    $page['#attached']['library'][] = 'saho_shop/font.' . $font;
  }

  // SahoShop icons.
  if ((bool) theme_get_setting('saho_shop_icons')) {
    $page['#attached']['library'][] = 'saho_shop/icons';
  }

  // Add Breakpoints to drupalSettings
  $breakpoints = \Drupal::service('breakpoint.manager')->getBreakpointsByGroup('saho_shop');

  if (!empty($breakpoints)) {
    $media_queries = [];
    foreach ($breakpoints as $id => $breakpoint) {
      $media_queries[$id] = $breakpoint->getMediaQuery();
    }
    $page['#attached']['drupalSettings']['responsive']['breakpoints'] = $media_queries;
  }
}

/**
 * Implements hook_preprocess_commerce_checkout_completion_message().
 */
function saho_shop_preprocess_commerce_checkout_completion_message(&$variables) {
  // Add checkout completion theme setting
  $variables['checkout_completion_minimal'] = (bool) theme_get_setting('checkout_completion_minimal');
}

/**
 * Implements hook_preprocess_commerce_checkout_completion_register().
 */
function saho_shop_preprocess_commerce_checkout_completion_register(&$variables) {
  _saho_shop_add_checkout_pane_settings($variables);
}

/* Helpers */

/**
 * Check if focused checkout should be applied to current path.
 *
 * @param string|null $current_path
 *   The current path to check against. If NULL, will get current path automatically.
 *
 * @return bool
 *   TRUE if focused checkout should be applied, FALSE otherwise.
 */
function _saho_shop_is_focused_path($current_path = NULL) {

  if ($current_path === NULL) {
    $current_path = \Drupal::service('path.current')->getPath();
  }

  // Check if current route is commerce_checkout.form
  $route_name = \Drupal::routeMatch()->getRouteName();
  if ($route_name === 'commerce_checkout.form') {
    return TRUE;
  }

  // Check configured paths
  $focused_paths = theme_get_setting('focused_paths');
  if (!empty($focused_paths)) {
    $paths = array_filter(array_map('trim', explode("\n", $focused_paths)));

    foreach ($paths as $path) {
      if (strpos($path, '*') !== FALSE) {
        // Handle wildcard paths like /user/*
        $pattern = str_replace('*', '.*', $path);
        if (preg_match('#^' . $pattern . '$#', $current_path)) {
          return TRUE;
        }
      } else {
        // Handle exact paths
        if ($current_path === $path) {
          return TRUE;
        }
      }
    }
  }

  return FALSE;
}

/**
 * SahoShop theme alter entity suggestions.
 */
function _saho_shop_theme_alter_entity_suggestions(array &$suggestions, ContentEntityInterface $entity, string $view_mode) {
  $entity_type_id = $entity->getEntityTypeId();
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
  $entity_display_repository = \Drupal::service('entity_display.repository');
  $display = $entity_display_repository->getViewDisplay($entity_type_id, $entity->bundle());
  if ($display instanceof LayoutEntityDisplayInterface && $display->isLayoutBuilderEnabled()) {
    if ($view_mode === "full") {
      array_splice($suggestions, 1, 0, $entity_type_id . '__layout__full__' . $entity->bundle());
      array_splice($suggestions, 1, 0, $entity_type_id . '__layout__full');
    }
    array_splice($suggestions, 1, 0, $entity_type_id . '__layout__' . $entity->bundle());
    array_splice($suggestions, 1, 0, $entity_type_id . '__layout');
  }
}

/**
 * SahoShop theme alter page suggestions.
 */
function _saho_shop_theme_alter_page_suggestions(array &$suggestions, ContentEntityInterface $entity) {
  $entity_type_id = $entity->getEntityTypeId();
  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
  $entity_display_repository = \Drupal::service('entity_display.repository');
  $display = $entity_display_repository->getViewDisplay($entity_type_id, $entity->bundle());
  if ($display instanceof LayoutEntityDisplayInterface && $display->isLayoutBuilderEnabled()) {
    array_splice($suggestions, 1, 0, 'page__' . str_replace('commerce_', '', $entity_type_id) . '__layout__' . $entity->bundle());
    array_splice($suggestions, 1, 0, 'page__' . str_replace('commerce_', '', $entity_type_id) . '__layout');
  }
}

/**
 * Helper function to add checkout pane theme settings to variables.
 */
function _saho_shop_add_checkout_pane_settings(&$variables) {
  $variables['pane_variant'] = theme_get_setting('checkout_pane_variant');
  $variables['pane_collapsible'] = (bool) theme_get_setting('checkout_pane_collapsible');
  $variables['pane_show_icons'] = (bool) theme_get_setting('checkout_pane_show_icons');
}

/**
 * Check if the current page is a user authentication page.
 *
 * @return bool
 *   TRUE if the current page is a user auth page, FALSE otherwise.
 */
function _saho_shop_is_user_auth_page() {
  $route_name = \Drupal::routeMatch()->getRouteName();
  $user_auth_routes = [
    'user.login',
    'user.register',
    'user.pass',
  ];

  return in_array($route_name, $user_auth_routes);
}
