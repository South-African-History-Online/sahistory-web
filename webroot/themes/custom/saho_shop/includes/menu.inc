<?php

/**
 * @file
 * Theme and preprocess functions for menus.
 */

/**
 * Implements template_preprocess_menu_local_action().
 */
function saho_shop_preprocess_menu_local_action(&$variables) {
  // Add button classes.
  $variables['link']['#options']['attributes']['class'][] = 'btn';
  $variables['link']['#options']['attributes']['class'][] = 'btn-success';
  $variables['link']['#options']['attributes']['class'][] = 'bi-btn-add';

  // Check if payment method dialog is enabled and this is an "Add payment method" link
  if (theme_get_setting('payment_method_dialog') &&
      isset($variables['link']['#title']) &&
      strpos($variables['link']['#title'], 'Add payment method') !== FALSE) {

    // Add AJAX dialog attributes
    $variables['link']['#options']['attributes']['class'][] = 'use-ajax';
    $variables['link']['#options']['attributes']['data-dialog-type'] = 'modal';

    // Set dialog options
    $dialog_options = [
      'width' => '80%',
      'maxWidth' => '600px',
    ];

    $variables['link']['#options']['attributes']['data-dialog-options'] = json_encode($dialog_options);
  }
}

/**
 * Implements template_preprocess_links().
 */
function saho_shop_preprocess_links(&$variables) {
  $links = &$variables['links'];

  foreach ($links as $key => $link) {
    // Check if this is a payment method operation link
    if (isset($link['link']['#url']) &&
        strpos($link['link']['#url']->toString(), '/payment-methods/') !== FALSE) {

      // Add reusable operation classes
      if ($key === 'edit') {
        $links[$key]['link']['#options']['attributes']['class'][] = 'operation-edit';
      }
      elseif ($key === 'delete') {
        $links[$key]['link']['#options']['attributes']['class'][] = 'operation-delete';
      }

      // Add dialog functionality if setting is enabled
      if (theme_get_setting('payment_method_dialog') && $key === 'edit') {
        // Add AJAX dialog attributes
        $links[$key]['link']['#options']['attributes']['class'][] = 'use-ajax';
        $links[$key]['link']['#options']['attributes']['data-dialog-type'] = 'modal';

        // Set dialog options
        $dialog_options = [
          'width' => '80%',
          'maxWidth' => '600px',
        ];

        $links[$key]['link']['#options']['attributes']['data-dialog-options'] = json_encode($dialog_options);
      }
    }
  }
}

/**
 * Implements template_preprocess_links__dropbutton().
 */
function saho_shop_preprocess_links__dropbutton(&$variables) {
  $links = &$variables['links'];

  // Do nothing if we have no links.
  if (!count($links)) {
    return;
  }

  // Get the first link and use it for the dropbutton.
  $link = reset($links);

  /** @var \Drupal\Core\Url $url */
  $variables['split'] = FALSE;
  if (isset($link['link']) && ($url = $link['link']['#url'])) {
    $button = $link['link'];

    if ($variables['split'] = $url->getRouteName() !== '<nolink>') {
      $button['#options']['attributes']['class'][] = 'btn';
      $button['#options']['attributes']['class'][] = 'btn-sm';
      $button['#options']['attributes']['class'][] = 'btn-outline-dark';
    }

    $variables['button'] = $button;

    // Remove first link from links.
    array_shift($links);
  }

  // Add required classes.
  foreach ($links as $key => $link) {
    $links[$key]['link']['#options']['attributes']['class'][] = 'dropdown-item';

    if (isset($links[$key]['text_attributes'])) {
      $links[$key]['text_attributes']->addClass('dropdown-item');
    }

    if (isset($links[$key]['attributes'])) {
      $links[$key]['attributes']->addClass('dropdown-item');
    }
  }
}

/**
 * Implements hook_preprocess_HOOK() for menu_local_task templates.
 */
function saho_shop_preprocess_menu_local_task(&$variables) {
  // Add classes based on task title/name
  if (isset($variables['link']['#title'])) {
    $title = $variables['link']['#title'];

    // Convert title to a clean CSS class
    $class = strtolower($title);
    $class = preg_replace('/[^a-z0-9]+/', '-', $class);
    $class = trim($class, '-');

    if (!empty($class)) {
      $variables['attributes']['class'][] = 'local-task--' . $class;
    }
  }
}
