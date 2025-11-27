<?php

use Drupal\Core\Template\Attribute;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;

/**
 * @file
 * Theme and preprocess functions for forms.
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function saho_shop_theme_suggestions_form_alter(array &$suggestions, array &$variables) {
  $element = $variables['element'];

  // Add a suggestion based on the form id name.
  if (isset($element['#form_id'])) {
    $suggestions[] = $variables['theme_hook_original'] . '__' . $element['#form_id'];
  }
}

/**
 * Implements hook_preprocess_commerce_checkout_form().
 */
function saho_shop_preprocess_commerce_checkout_form(&$variables) {
  // Add theme settings to template variables.
  $variables['focused_checkout_form_step_title'] = theme_get_setting('focused_checkout_form_step_title');
  $variables['focused_checkout_show_progress'] = theme_get_setting('focused_checkout_show_progress');
  $variables['focused_checkout_copyright'] = theme_get_setting('focused_checkout_copyright');
  $variables['checkout_mobile_floating_order_summary'] = theme_get_setting('checkout_mobile_floating_order_summary');

  // Add main column setting
  $variables['focused_checkout_main_column'] = theme_get_setting('focused_checkout_main_column');

  // Add form title to template variables.
  if (isset($variables['form']['#title'])) {
    $variables['form_title'] = $variables['form']['#title'];
  }

  // Add progress bar to template variables.
  if ($variables['focused_checkout_show_progress']) {
    $block = \Drupal\block\Entity\Block::load('saho_shop_checkout_progress');
    if ($block) {
      $variables['focused_checkout_progress'] = \Drupal::entityTypeManager()
        ->getViewBuilder('block')
        ->view($block);
    }
  }

  // Add current step information
  if (isset($variables['form']['#step_id'])) {
    $variables['step_id'] = $variables['form']['#step_id'];
  }

  // Add site name to template variables.
  $variables['site_name'] = \Drupal::config('system.site')->get('name');

  // Add order total for checkout forms
  $order = \Drupal::routeMatch()->getParameter('commerce_order');
  if ($order) {
    $variables['order_total'] = $order->getTotalPrice();
  }
}


/**
 * Implements hook_theme_suggestions_commerce_checkout_form_alter().
 */
function saho_shop_theme_suggestions_commerce_checkout_form_alter(array &$suggestions, array $variables) {

  // Add suggestion for focused checkout
  if (theme_get_setting('focused_checkout') == 1) {
    $suggestions[] = 'commerce_checkout_form__focused';

    if (isset($variables['form']['sidebar']) && $variables['form']['sidebar']) {
      $suggestions[] = 'commerce_checkout_form__focused__with_sidebar';
    }

    // Force sidebar for login step.
    if (isset($variables['form']['#step_id']) && $variables['form']['#step_id'] == 'login') {
      $suggestions[] = 'commerce_checkout_form__focused__with_sidebar';
    }
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function saho_shop_theme_suggestions_form_element_alter(array &$suggestions, array &$variables) {
  $element = $variables['element'];

  // Add a suggestion based on the element type.
  if (isset($element['#type'])) {
    $suggestions[] = $variables['theme_hook_original'] . '__' . $element['#type'];
  }
}

/**
 * Implements hook_theme_suggestions_input_alter().
 */
function saho_shop_theme_suggestions_input_alter(array &$suggestions, array $variables) {
  $element = $variables['element'];
  $type = $element['#type'] ?? '';

  // Add quantity input template suggestions for number inputs
  if ($type === 'number') {
    $classes = $element['#attributes']['class'] ?? [];
    $has_quantity_class = in_array('quantity-edit-input', $classes) ||
                         in_array('form-number', $classes) ||
                         (isset($element['#name']) && strpos($element['#name'], 'quantity') !== FALSE);

    if ($has_quantity_class) {
      $suggestions[] = 'input__number__quantity';
    }
  }
}

/**
 * Implements hook_preprocess_input().
 */
function saho_shop_preprocess_input(&$variables) {
  $type = $variables['attributes']['type'];
  $form_control = [
    'text',
    'email',
    'tel',
    'file',
    'number',
    'search',
    'password',
    'url',
    'date',
    'time',
    'month',
    'week',
  ];

  if (in_array($type, $form_control)) {
    $variables['attributes']['class'][] = 'form-control';
  }

  if (in_array($type, ['checkbox', 'radio'])) {
    $variables['attributes']['class'][] = 'form-check-input';
  }

  // Add placeholder for floating labels
  $form_style = theme_get_setting('form_style');
  if ($form_style === 'floating_labels' && in_array($type, ['text', 'email', 'tel', 'password', 'url', 'number', 'search'])) {
    // Check if we're in a floating label context and need a placeholder
    $element = $variables['element'] ?? [];
    if (isset($element['#title']) && !isset($element['#placeholder'])) {
      $variables['attributes']['placeholder'] = $element['#title'];
    }
  }

  if ($type == 'submit') {
    $variables['attributes'] = new Attribute($variables['attributes']);

    if (theme_get_setting('saho_shop_submit_button')) {
      $variables['submit_button'] = TRUE;
      $variables['safe_value_label'] = Xss::filter($variables['attributes']['value']);
    }

    // Get value.
    $string = $variables['attributes']->offsetGet('value')->value();

    // Allow modules to override or add button classes.
    $button_classes = [];
    \Drupal::moduleHandler()->alter('saho_shop_button_classes', $button_classes, $string, $variables);

    // New code to call subtheme
    $subtheme = \Drupal::theme()->getActiveTheme()->getName();
    $function = $subtheme . '_saho_shop_button_classes_alter';
    if (function_exists($function)) {
      $function($button_classes, $string, $variables);
    }

    // If no classes were provided by modules, use the theme settings.
    if (empty($button_classes)) {
      $text_data = _saho_shop_get_button_class_mappings();

      // Allow sub themes to alter the array.
      \Drupal::service('theme.manager')->alter('saho_shop_submit_button_class', $text_data);

      $icon_data = _saho_shop_get_button_icon_mappings();

      // List of classes to add white icons.
      $classes = [
        "btn-primary",
        "btn-secondary",
        "btn-success",
        "btn-info",
        "btn-warning",
        "btn-danger",
        "btn-dark",
      ];

      // Check if classes are already there
      $add_white_icon = TRUE;

      foreach ($classes as $class) {
        if ($variables['attributes']->hasClass($class)) {
          $add_white_icon = FALSE;
        }
      }

      //  Iterate over the array.
      $button_class_applied = FALSE;
      foreach ($text_data as $pattern => $strings) {
        foreach ($strings as $text => $class) {
          switch ($pattern) {
            case 'matches':
              if ($string === $text) {
                if (!$variables['attributes']->hasClass('btn-' . $class)) {
                  $variables['attributes']->addClass(['btn', 'btn-' . $class]);
                  $button_class_applied = TRUE;
                }
              }
              break;

            case 'contains':
              if (strpos(mb_strtolower($string), mb_strtolower($text)) !== FALSE) {
                if (!$variables['attributes']->hasClass('btn-' . $class)) {
                  $variables['attributes']->addClass(['btn', 'btn-' . $class]);
                  $button_class_applied = TRUE;
                }
              }
              break;
          }
        }
      }

      // If no specific button class was applied, make it primary by default
      if (!$button_class_applied && !$variables['attributes']->hasClass('btn-primary')) {
        $variables['attributes']->addClass(['btn', 'btn-primary']);
      }

      if ($add_white_icon) {
        foreach ($icon_data as $pattern => $strings) {
          foreach ($strings as $text => $class) {
            switch ($pattern) {
              case 'matches':
                if ($string === $text) {
                  $variables['attributes']->addClass('bi-btn-' . $class);
                }
                break;

              case 'contains':
                if (strpos(mb_strtolower($string), mb_strtolower($text)) !== FALSE) {
                  $variables['attributes']->addClass(['bi-btn-' . $class]);
                }
                break;
            }
          }
        }
      }
    } else {
      // Add the classes provided by modules.
      $variables['attributes']->addClass($button_classes);
    }
  }

  // Quantity input enhancement
  if ($type === 'number') {
    $element = $variables['element'];


    // Check for quantity-related classes or attributes
    $classes = isset($element['#attributes']['class']) ? $element['#attributes']['class'] : [];
    $has_quantity_class = in_array('quantity-edit-input', $classes) ||
    in_array('form-number', $classes) ||
    (isset($element['#name']) && strpos($element['#name'], 'quantity') !== FALSE);

    if ($has_quantity_class) {
      // Add quantity-specific attributes
      $variables['attributes']['class'][] = 'quantity-edit-input';

      // Pass theme setting to template
      $variables['quantity_input_plus_minus'] = theme_get_setting('quantity_input_plus_minus');
    }
  }
}

/**
 * Implements hook_preprocess_form_element().
 */
function saho_shop_preprocess_form_element(&$variables) {
  $element = $variables['element'];

  // Add required class for checkbox and radio labels.
  if (in_array($element['#type'], ['checkbox', 'radio'])) {
    $variables['label']['#attributes']['class'][] = 'form-check-label';
  }

    // Apply form style classes based on theme setting.
  $form_style = theme_get_setting('form_style');
  if ($form_style && $form_style !== 'default') {
    $variables['attributes']['class'][] = 'form-style-' . $form_style;

    // Add floating label support for floating_labels style
    if ($form_style === 'floating_labels' && in_array($element['#type'], ['textfield', 'email', 'tel', 'password', 'url', 'number', 'search', 'textarea', 'select'])) {
      $variables['attributes']['class'][] = 'form-floating';

      // For floating labels, we need to ensure the input comes before the label
      // and the label has the proper for attribute
      if (isset($element['#id']) && isset($variables['label'])) {
        $variables['label']['#attributes']['for'] = $element['#id'];
      }

      // Set variable to indicate floating label structure is needed
      $variables['floating_label'] = TRUE;

      // Add placeholder for textarea and select elements
      if (in_array($element['#type'], ['textarea', 'select']) && isset($element['#title']) && !isset($element['#placeholder'])) {
        // For textarea and select, we need to add placeholder to the children
        if (isset($variables['children'])) {
          // This will be handled in the template preprocessing
          $variables['floating_placeholder'] = $element['#title'];
        }
      }

      // Pass placeholder to template for fallback labels
      if (isset($element['#attributes']) && isset($element['#attributes']['placeholder'])) {
        $variables['floating_placeholder'] = $element['#attributes']['placeholder'];
      }
    }
  }
  // Apply required field indicator style based on theme setting.
  $form_required_indicator = theme_get_setting('form_required_indicator');
  if ($form_style !== 'floating_labels' && $form_required_indicator && $form_required_indicator !== 'icon') {
    $variables['attributes']['class'][] = 'form-required-' . $form_required_indicator;
  }
}

/**
 * Implements hook_preprocess_form_element_label().
 */
function saho_shop_preprocess_form_element_label(&$variables) {
  // Check if we're using floating labels
  $form_style_setting = theme_get_setting('form_style');
  if ($form_style_setting === 'floating_labels') {
    $variables['floating_label'] = TRUE;
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function saho_shop_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Apply form style classes based on theme setting.
  $form_style_setting = theme_get_setting('form_style');
  if ($form_style_setting && $form_style_setting !== 'default') {
    $form['#attributes']['class'][] = 'form-style-' . $form_style_setting;
  }

  // Apply field spacing based on theme setting.
  $form_field_spacing = theme_get_setting('form_field_spacing');
  if ($form_field_spacing && $form_field_spacing !== 'normal') {
    $form['#attributes']['class'][] = 'form-spacing-' . $form_field_spacing;
  }

  switch ($form_id) {
    case 'search_block_form':
      $form['keys']['#title'] = '';
      $form['keys']['#size'] = 20;
      $form['keys']['#placeholder'] = t('Search');
      break;
    case 'views_exposed_form':
      $form['#attributes']['class'][] = 'form-inline';
      $form['search_api_fulltext']['#placeholder'] = t('Search products');
      $form['actions']['#attributes']['class'][] = 'ms-auto';
      break;
    case 'user_login_form':
      $form['forgot_password'] = [
        '#type' => 'link',
        '#title' => t('Forgot Password?'),
        '#url' => Url::fromRoute('user.pass'),
        '#attributes' => [
          'class' => ['forgot-password-link'],
        ],
        // Places it after the submit button.
        '#weight' => 10,
      ];
      $form['actions']['footer'] = [
        '#type' => 'container',
        '#title' => t('Sign up'),
        '#attributes' => [
          'class' => ['user-authenticate__footer'],
        ],
        'signup_description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => t('Don\'t have an account?'),
        ],
        'signup' => [
          '#type' => 'link',
          '#title' => t('Sign up'),
          '#url' => Url::fromRoute('user.register'),
          '#attributes' => [
            'class' => ['signup-link', 'btn', 'btn-outline-white'],
          ],
          '#weight' => 20,
        ],
      ];
      break;
    case 'user_pass':
      $form['actions']['submit']['#attributes']['class'][] = 'btn-primary';
      $form['actions']['footer'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['user-authenticate__footer'],
        ],
        'back_description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => t('Remember your password?'),
        ],
        'back' => [
          '#type' => 'link',
          '#title' => t('Back to login'),
          '#url' => Url::fromRoute('user.login'),
          '#attributes' => [
            'class' => ['back-to-login-link', 'btn', 'btn-outline-white'],
          ],
        ],
        '#weight' => 20,
      ];
      break;
    case 'user_register_form':
      $form['actions']['footer'] = [
        '#type' => 'container',
        '#title' => t('Sign up'),
        '#attributes' => [
          'class' => ['user-authenticate__footer'],
        ],
        'login_description' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => t('Already have an account?'),
        ],
        'login' => [
          '#type' => 'link',
          '#title' => t('Login'),
          '#url' => Url::fromRoute('user.login'),
          '#attributes' => [
            'class' => ['login-link', 'btn', 'btn-outline-white'],
          ],
        ],
        '#weight' => 20,
      ];
      break;
  }
}

/**
 * Implements hook_theme_suggestions_container_alter().
 */
function saho_shop_theme_suggestions_container_alter(array &$suggestions, array $variables) {

  $suggestions = [];

  $element = $variables['element'];

  if (isset($element['#type']) && $element['#type'] != 'container') {
    $suggestions[] = 'container__' . $element['#type'];
  }

  if (isset($element['#id'])) {
    $suggestions[] =
      'container__' . str_replace('-', '_', $element['#id']);
  }

  if (isset($element['keys']['#theme'])) {
    $suggestions[] =
      'container__' . str_replace('-', '_', $element['keys']['#theme']);
  }

  if (isset($element['#type']) && $element['#type'] == 'container' && isset($element['children']['#type'])) {
    $suggestions[] = 'container__' . $element['children']['#type'];
  }

  return $suggestions;
}


/**
 * Implements hook_preprocess_commerce_checkout_pane().
 *
 * This function automatically:
 * - Adds pane title to template variables
 * - Attaches specific libraries based on pane type
 */
function saho_shop_preprocess_commerce_checkout_pane(&$variables) {
  // Add pane title if available
  if (isset($variables['elements']['#title'])) {
    $variables['pane_title'] = $variables['elements']['#title'];
  }

  // Add pane description if available
  if (isset($variables['elements']['#description'])) {
    $variables['pane_description'] = $variables['elements']['#description'];
  }

  // Add content pane settings to template variables
  _saho_shop_add_checkout_pane_settings($variables);
}

/**
 * Implements hook_theme_suggestions_fieldset().
 */
function saho_shop_theme_suggestions_fieldset_alter(array &$suggestions, &$variables, $hook) {

  $element = $variables['element'];

  $fieldset_checkout_panes = [
    'edit-contact-information',
    'edit-shipping-information',
    'edit-payment-information',
    'edit-review-contact-information',
    'edit-review-shipping-information',
    'edit-review-payment-information',
    'edit-completion-message-contact-information',
    'edit-completion-message-shipping-information',
    'edit-completion-message-payment-information',
  ];

  // Add suggestion based on form context
  if (in_array($element['#id'], $fieldset_checkout_panes)) {
    $suggestions[] = 'fieldset__checkout_pane';
  }

  // Add suggestion based on fieldset ID
  if (isset($element['#id'])) {
    $fieldset_id = $element['#id'];

    // Extract meaningful parts from the ID
    $parts = explode('-', $fieldset_id);
    if (count($parts) > 1) {
      $suggestions[] = 'fieldset__' . implode('__', $parts);
    }
  }
}

/**
 * Implements hook_preprocess_fieldset().
 *
 * This function prepares fieldset data for the fieldset-pane component.
 */
function saho_shop_preprocess_fieldset(&$variables) {
  $element = $variables['element'];
  $fieldset_id = $element['#id'] ?? '';

  // Define checkout pane IDs (same as in theme suggestions)
  $fieldset_checkout_panes = [
    'edit-contact-information',
    'edit-shipping-information',
    'edit-payment-information',
    'edit-review-contact-information',
    'edit-review-shipping-information',
    'edit-review-payment-information',
    'edit-completion-message-contact-information',
    'edit-completion-message-shipping-information',
    'edit-completion-message-payment-information',
  ];

  // Check if this is a checkout-related fieldset
  if (in_array($fieldset_id, $fieldset_checkout_panes)) {
    // Use checkout pane theme settings for checkout fieldsets
    _saho_shop_add_checkout_pane_settings($variables);

    // Determine icon based on fieldset type
    if ($variables['pane_show_icons']) {
      $icon_mapping = [
        'shipping' => 'truck',
        'payment' => 'credit-card',
        'billing' => 'credit-card',
        'contact' => 'person-vcard',
      ];

      foreach ($icon_mapping as $keyword => $icon) {
        if (strpos($fieldset_id, $keyword) !== FALSE) {
          $variables['pane_icon'] = $icon;
          break;
        }
      }
    }

    // Add pane description if available
    if (isset($element['#description'])) {
      $variables['pane_description'] = $element['#description'];
    }
  }
  else {
    // Use regular fieldset theme settings for non-checkout fieldsets
    $variables['fieldset_variant'] = theme_get_setting('fieldset_variant') ?? 'default';
    $variables['fieldset_collapsible'] = (bool) theme_get_setting('fieldset_collapsible');
    $variables['fieldset_collapsed'] = (bool) theme_get_setting('fieldset_collapsed');
  }
}

/**
 * Get button class mappings from theme settings.
 *
 * @return array
 *   Array of button class mappings.
 */
function _saho_shop_get_button_class_mappings() {
  $text_data = [
    'matches' => [],
    'contains' => [],
  ];

  // Get button class settings from theme configuration
  $button_types = [
    'success_buttons_exact' => ['success', 'matches'],
    'success_buttons_contains' => ['success', 'contains'],
    'secondary_buttons' => ['secondary', 'matches'],
    'danger_buttons' => ['danger', 'matches'],
    'warning_buttons' => ['warning', 'matches'],
    'info_buttons' => ['info', 'matches'],
    'outline_primary_buttons' => ['outline-primary', 'matches'],
    'outline_secondary_buttons' => ['outline-secondary', 'matches'],
    'outline_success_buttons' => ['outline-success', 'matches'],
    'outline_danger_buttons' => ['outline-danger', 'matches'],
    'outline_warning_buttons' => ['outline-warning', 'matches'],
    'outline_info_buttons' => ['outline-info', 'matches'],
  ];

  foreach ($button_types as $setting_name => $config) {
    list($class, $match_type) = $config;
    $button_texts = theme_get_setting($setting_name);
    if (!empty($button_texts)) {
      $lines = explode("\n", $button_texts);
      foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
          // For exact matches, use the translated text
          $translated_text = t($line)->render();
          $text_data[$match_type][$translated_text] = $class;
        }
      }
    }
  }

  return $text_data;
}

/**
 * Get button icon mappings from theme settings.
 *
 * @return array
 *   Array of button icon mappings.
 */
function _saho_shop_get_button_icon_mappings() {
  $icon_data = [
    'matches' => [],
    'contains' => [],
  ];

  $icon_mappings = theme_get_setting('button_icon_mappings');
  if (!empty($icon_mappings)) {
    $lines = explode("\n", $icon_mappings);
    foreach ($lines as $line) {
      $line = trim($line);
      if (!empty($line) && strpos($line, '|') !== FALSE) {
        list($button_text, $icon_name) = explode('|', $line, 2);
        $button_text = trim($button_text);
        $icon_name = trim($icon_name);

        if (!empty($button_text) && !empty($icon_name)) {
          // For exact matches, use the translated text
          $translated_text = t($button_text)->render();
          $icon_data['matches'][$translated_text] = $icon_name;
        }
      }
    }
  }

  return $icon_data;
}
