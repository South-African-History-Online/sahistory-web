<?php

/**
 * @file
 * theme-settings.php
 *
 * Provides theme settings
 *
 * @see ./includes/settings.inc
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implementation of hook_form_system_theme_settings_alter()
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 *
 * @param $form_state
 *   A keyed array containing the current state of the form.
 */


function saho_shop_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {

  // Disable form caching to prevent serialization issues with managed_file elements
  $form_state->disableCache();

  // Theme info
  $theme = \Drupal::theme()->getActiveTheme()->getName();

  // Regions
  $region_list = system_region_list($theme, $show = REGIONS_ALL);
  $exclude_regions = array('hidden', 'page_top', 'page_bottom', 'navigation');

  // Vertical tabs
  $form['saho_shop'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => -10,
    '#description' => t('Cheatsheet of <a href="@link">Bootstrap components.</a>', ['@link' => Url::fromUri('internal:/' . \Drupal::service('extension.list.theme')->getPath($theme) . '/cheatsheet/index.html')->toString()]),
  );

  // Appearance settings
  $form['saho_shop_appearance'] = array(
    '#type' => 'details',
    '#title' => t('Appearance'),
    '#group' => 'saho_shop',
  );

  $form['saho_shop_appearance']['general'] = array(
    '#type' => 'details',
    '#title' => 'General',
    '#collapsible' => true,
    '#open' => true,
  );

  // Colors
  $form['#attached']['library'][] = 'saho_shop/color.picker';

  $color_config = [
    'colors' => [
      'base_primary_color' => 'Primary base color',
      'base_light_color' => 'Light base color',
      'base_dark_color' => 'Dark base color',
      'body_text_color' => 'Body text color',
      'body_background_color' => 'Body background color',
    ],
    'schemes' => [
      'default' => [
        'label' => 'SahoShop',
        'colors' => [
          'base_primary_color' => '#41449f',
          'base_light_color' => '#F0F1F5',
          'base_dark_color' => '#272727',
          'body_text_color' => '#333333',
          'body_background_color' => '#FFFFFF',
        ],
      ],
      'apple_blossom' => [
        'label' => 'Apple Blossom',
        'colors' => [
          'base_primary_color' => '#9F4143',
          'base_light_color' => '#F8F7F7',
          'base_dark_color' => '#592750',
          'body_text_color' => '#212121',
          'body_background_color' => '#FFFFFF',
        ],
      ],
      'marine' => [
        'label' => 'Marine',
        'colors' => [
          'base_primary_color' => '#437A9E',
          'base_light_color' => '#F7F8FA',
          'base_dark_color' => '#555B5E',
          'body_text_color' => '#333333',
          'body_background_color' => '#FFFFFF',
        ],
      ],
      'khaki' => [
        'label' => 'Khaki',
        'colors' => [
          'base_primary_color' => '#9F6941',
          'base_light_color' => '#F8F7F6',
          'base_dark_color' => '#272727',
          'body_text_color' => '#272727',
          'body_background_color' => '#FFFFFF',
        ],
      ],
      'monochrome' => [
        'label' => 'Monochrome',
        'colors' => [
          'base_primary_color' => '#2C2C2C',
          'base_light_color' => '#F5F5F5',
          'base_dark_color' => '#1A1A1A',
          'body_text_color' => '#333333',
          'body_background_color' => '#FFFFFF',
        ],
      ],
    ],
  ];

  $form['#attached']['drupalSettings']['saho_shop']['colorSchemes'] = $color_config['schemes'];

  $form['saho_shop_appearance']['general']['saho_shop_enable_color'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable Color Scheme'),
    '#default_value' => theme_get_setting('saho_shop_enable_color'),
  ];


  $form['saho_shop_appearance']['general']['saho_shop_color_scheme'] = [
    '#type' => 'fieldset',
    '#title' => t('Color Scheme Settings'),
    '#states' => array(
      'visible' => array(
        ':input[name="saho_shop_enable_color"]' => array('checked' => TRUE),
      ),
    ),
  ];

  $form['saho_shop_appearance']['general']['saho_shop_color_scheme']['description'] = [
    '#type' => 'html_tag',
    '#tag' => 'p',
    '#value' => t('These settings adjust the look and feel of the SahoShop theme. Changing the color below will change the base hue, saturation, and lightness values the SahoShop theme uses to determine its internal colors.'),
  ];

  $form['saho_shop_appearance']['general']['saho_shop_color_scheme']['color_scheme'] = [
    '#type' => 'select',
    '#title' => t('SahoShop Color Scheme'),
    '#empty_option' => t('Custom'),
    '#empty_value' => '',
    '#options' => [
      'default' => t('SahoShop (Default)'),
      'apple_blossom' => t('Apple Blossom'),
      'marine' => t('Marine'),
      'khaki' => t('Khaki'),
      'monochrome' => t('Monochrome'),
    ],
    '#input' => FALSE,
    '#wrapper_attributes' => [
      'style' => 'display:none;',
    ],
  ];

  foreach ($color_config['colors'] as $key => $title) {
    $form['saho_shop_appearance']['general']['saho_shop_color_scheme'][$key] = [
      '#type' => 'textfield',
      '#maxlength' => 7,
      '#size' => 10,
      '#title' => t($title),
      '#description' => t('Enter color in full hexadecimal format (#abc123).') . '<br/>' . t('Derivatives will be formed from this color.'),
      '#default_value' => theme_get_setting($key),
      '#attributes' => [
        'pattern' => '^#[a-fA-F0-9]{6}',
      ],
      '#wrapper_attributes' => [
        'data-drupal-selector' => 'saho_shop-color-picker',
      ],
    ];
  }

  // Inline SVG logo
  $form['saho_shop_appearance']['general']['inline_logo'] = array(
    '#type' => 'checkbox',
    '#title' => t('Inline SVG logo'),
    '#description' => t('Place the logo SVG code in the DOM.'),
    '#default_value' => theme_get_setting('inline_logo')
  );

  // Input submit button.
  $form['saho_shop_appearance']['general']['saho_shop_submit_button'] = [
    '#type' => 'checkbox',
    '#title' => t('Convert input submit to button element'),
    '#default_value' => theme_get_setting('saho_shop_submit_button'),
    '#description' => t('This can cause problems with AJAX.'),
  ];

  // Forms settings
  $form['saho_shop_forms'] = [
    '#type' => 'details',
    '#title' => t('Forms'),
    '#group' => 'saho_shop',
  ];

  // Messages.
  $form['saho_shop_appearance']['general']['message_type'] = [
    '#type' => 'select',
    '#title' => t('Messages type'),
    '#default_value' => theme_get_setting('message_type'),
    '#options' => [
      'alerts' => t('Alerts'),
      'toasts' => t('Toasts'),
      'color_toasts' => t('Colored Toasts'),
    ],
  ];

  $form['saho_shop_appearance']['general']['toast_placement'] = array(
    '#type' => 'select',
    '#title' => t('Toast placement'),
    '#default_value' => theme_get_setting('toast_placement'),
    '#options' => [
      'top_left' => t('Top left'),
      'top_center' => t('Top center'),
      'top_right' => t('Top right'),
      'middle_left' => t('Middle left'),
      'middle_center' => t('Middle center'),
      'middle_right' => t('Middle right'),
      'bottom_left' => t('Bottom left'),
      'bottom_center' => t('Bottom center'),
      'bottom_right' => t('Bottom right'),
    ],
    '#states' => [
      'invisible',
      'visible' => [
        'select[name="message_type"]' => [
          ['value' => 'toasts'],
          ['value' => 'color_toasts']
        ],
      ],
    ],
  );

  $form['saho_shop_forms']['form_style'] = [
    '#type' => 'select',
    '#title' => t('Form style'),
    '#default_value' => theme_get_setting('form_style'),
    '#options' => [
      'default' => t('Default'),
      'floating_labels' => t('Floating Labels'),
      'outlined' => t('Outlined'),
      'minimal' => t('Minimal'),
    ],
    '#description' => t('Choose the visual style for form elements.'),
  ];

  $form['saho_shop_forms']['form_required_indicator'] = [
    '#type' => 'select',
    '#title' => t('Required field indicator'),
    '#default_value' => theme_get_setting('form_required_indicator'),
    '#options' => [
      'icon' => t('Icon (Default)'),
      'text' => t('Text (Required)'),
      'none' => t('None'),
    ],
    '#description' => t('Choose how to indicate required form fields.'),
    '#states' => [
      'visible' => [
        ':input[name="form_style"]' => ['!value' => 'floating_labels'],
      ],
    ],
  ];

  // Add message for floating labels
  $form['saho_shop_forms']['floating_labels_notice'] = [
    '#type' => 'html_tag',
    '#tag' => 'p',
    '#value' => t('Required field indicators are hidden when using floating labels to maintain the clean floating label design.'),
    '#attributes' => [
      'style' => 'color: #666; font-style: italic; margin-top: 5px;',
    ],
    '#states' => [
      'visible' => [
        ':input[name="form_style"]' => ['value' => 'floating_labels'],
      ],
    ],
  ];

  $form['saho_shop_forms']['form_field_spacing'] = [
    '#type' => 'select',
    '#title' => t('Field spacing'),
    '#default_value' => theme_get_setting('form_field_spacing'),
    '#options' => [
      'compact' => t('Compact'),
      'normal' => t('Normal'),
      'spacious' => t('Spacious'),
    ],
    '#description' => t('Choose the spacing between form fields.'),
  ];

  $form['saho_shop_forms']['quantity_input_plus_minus'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable Plus/Minus Buttons for Quantity Inputs'),
    '#description' => t('Add plus and minus buttons to quantity input fields for easier quantity adjustment.'),
    '#default_value' => theme_get_setting('quantity_input_plus_minus'),
  ];

  $form['saho_shop_forms']['address_dialog'] = [
    '#type' => 'checkbox',
    '#title' => t('Display Address Operations in Dialog'),
    '#description' => t('When enabled, address book operations (add, edit, delete) will be displayed in modal dialogs instead of navigating to separate pages.'),
    '#default_value' => theme_get_setting('address_dialog'),
  ];

    $form['saho_shop_forms']['payment_method_dialog'] = [
      '#type' => 'checkbox',
      '#title' => t('Display Payment Method Operations in Dialog'),
      '#description' => t('When enabled, payment method operations (add, edit) will be displayed in modal dialogs instead of navigating to separate pages.'),
      '#default_value' => theme_get_setting('payment_method_dialog'),
    ];

  // Button styling settings
  $form['saho_shop_forms']['buttons'] = [
    '#type' => 'details',
    '#title' => t('Button Styling'),
    '#description' => t('Configure automatic button classes and icons based on button text.'),
  ];

  // Account Tab
  $form['saho_shop_user_auth'] = [
    '#type' => 'details',
    '#title' => t('Account'),
    '#group' => 'saho_shop',
  ];

  $form['saho_shop_user_auth']['focused_user_auth'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable Focused User Authentication Pages'),
    '#description' => t('Apply focused layout to user login, register, and password reset pages for a cleaner, distraction-free experience.'),
    '#default_value' => theme_get_setting('focused_user_auth'),
  ];

  $form['saho_shop_user_auth']['focused_user_auth_settings'] = [
    '#type' => 'details',
    '#title' => t('Focused Authentication Layout Settings'),
    '#collapsible' => true,
    '#open' => true,
    '#states' => [
      'visible' => [
        ':input[name="focused_user_auth"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_user_auth']['focused_user_auth_settings']['focused_user_auth_show_bg_image'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Background Image'),
    '#description' => t('Display the background image on focused user authentication pages.'),
    '#default_value' => theme_get_setting('focused_user_auth_show_bg_image'),
  ];

  // Background Image Upload Pane
  $form['saho_shop_user_auth']['focused_user_auth_settings']['bg_image_upload_pane'] = [
    '#type' => 'details',
    '#title' => t('Upload Background Image'),
    '#collapsible' => true,
    '#open' => true,
    '#states' => [
      'visible' => [
        ':input[name="focused_user_auth_show_bg_image"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_user_auth']['focused_user_auth_settings']['bg_image_upload_pane']['focused_user_auth_bg_image'] = [
    '#type' => 'managed_file',
    '#title' => t('Background Image'),
    '#description' => t('Upload a background image for focused user authentication pages. Recommended size: 1920x1080px or larger.'),
    '#default_value' => theme_get_setting('focused_user_auth_bg_image'),
    '#upload_location' => 'public://theme/',
    '#upload_validators' => [
      'FileExtension' => [
        'png gif jpg jpeg svg webp',
      ],
    ],
  ];

  // Background Color Settings Pane
  $form['saho_shop_user_auth']['focused_user_auth_settings']['bg_color_pane'] = [
    '#type' => 'details',
    '#title' => t('Background Color Settings'),
    '#collapsible' => true,
    '#open' => true,
    '#states' => [
      'visible' => [
        ':input[name="focused_user_auth_show_bg_image"]' => ['checked' => FALSE],
      ],
    ],
  ];

  $form['saho_shop_user_auth']['focused_user_auth_settings']['bg_color_pane']['focused_user_auth_bg_color'] = [
    '#type' => 'select',
    '#title' => t('Background Color'),
    '#description' => t('Choose the background color for focused user authentication pages when no background image is used. Note: A dark overlay is applied to all color options for better text readability.'),
    '#default_value' => theme_get_setting('focused_user_auth_bg_color'),
    '#options' => [
      'white' => t('White'),
      'light' => t('Light'),
      'dark' => t('Dark'),
      'primary' => t('Primary'),
    ],
  ];

  // Button class mappings
  $form['saho_shop_forms']['buttons']['button_classes'] = [
    '#type' => 'details',
    '#title' => t('Button Class Mappings'),
    '#description' => t('Define which CSS classes should be applied to buttons based on their text content.'),
  ];

  // Success button classes (exact matches)
  $form['saho_shop_forms']['buttons']['button_classes']['success_buttons_exact'] = [
    '#type' => 'textarea',
    '#title' => t('Success Button Text (Exact Match)'),
    '#description' => t('Enter button text that should get the "success" CSS class for exact matches. One per line.'),
    '#default_value' => theme_get_setting('success_buttons_exact'),
    '#rows' => 5,
  ];

  // Success button classes (contains)
  $form['saho_shop_forms']['buttons']['button_classes']['success_buttons_contains'] = [
    '#type' => 'textarea',
    '#title' => t('Success Button Text (Contains)'),
    '#description' => t('Enter button text that should get the "success" CSS class for partial matches. One per line.'),
    '#default_value' => theme_get_setting('success_buttons_contains'),
    '#rows' => 5,
  ];

  // Secondary button classes
  $form['saho_shop_forms']['buttons']['button_classes']['secondary_buttons'] = [
    '#type' => 'textarea',
    '#title' => t('Secondary Button Text'),
    '#description' => t('Enter button text that should get the "secondary" CSS class. One per line.'),
    '#default_value' => theme_get_setting('secondary_buttons'),
    '#rows' => 5,
  ];

  // Danger button classes
  $form['saho_shop_forms']['buttons']['button_classes']['danger_buttons'] = [
    '#type' => 'textarea',
    '#title' => t('Danger Button Text'),
    '#description' => t('Enter button text that should get the "danger" CSS class. One per line.'),
    '#default_value' => theme_get_setting('danger_buttons'),
    '#rows' => 5,
  ];

  // Warning button classes
  $form['saho_shop_forms']['buttons']['button_classes']['warning_buttons'] = [
    '#type' => 'textarea',
    '#title' => t('Warning Button Text'),
    '#description' => t('Enter button text that should get the "warning" CSS class. One per line.'),
    '#default_value' => theme_get_setting('warning_buttons'),
    '#rows' => 5,
  ];

  // Info button classes
  $form['saho_shop_forms']['buttons']['button_classes']['info_buttons'] = [
    '#type' => 'textarea',
    '#title' => t('Info Button Text'),
    '#description' => t('Enter button text that should get the "info" CSS class. One per line.'),
    '#default_value' => theme_get_setting('info_buttons'),
    '#rows' => 5,
  ];

  // Outline Primary button classes
  $form['saho_shop_forms']['buttons']['button_classes']['outline_primary_buttons'] = [
    '#type' => 'textarea',
    '#title' => t('Outline Primary Button Text'),
    '#description' => t('Enter button text that should get the "outline-primary" CSS class. One per line.'),
    '#default_value' => theme_get_setting('outline_primary_buttons'),
    '#rows' => 5,
  ];

  // Outline Secondary button classes
  $form['saho_shop_forms']['buttons']['button_classes']['outline_secondary_buttons'] = [
    '#type' => 'textarea',
    '#title' => t('Outline Secondary Button Text'),
    '#description' => t('Enter button text that should get the "outline-secondary" CSS class. One per line.'),
    '#default_value' => theme_get_setting('outline_secondary_buttons'),
    '#rows' => 5,
  ];

  // Outline Success button classes
  $form['saho_shop_forms']['buttons']['button_classes']['outline_success_buttons'] = [
    '#type' => 'textarea',
    '#title' => t('Outline Success Button Text'),
    '#description' => t('Enter button text that should get the "outline-success" CSS class. One per line.'),
    '#default_value' => theme_get_setting('outline_success_buttons'),
    '#rows' => 5,
  ];

  // Outline Danger button classes
  $form['saho_shop_forms']['buttons']['button_classes']['outline_danger_buttons'] = [
    '#type' => 'textarea',
    '#title' => t('Outline Danger Button Text'),
    '#description' => t('Enter button text that should get the "outline-danger" CSS class. One per line.'),
    '#default_value' => theme_get_setting('outline_danger_buttons'),
    '#rows' => 5,
  ];

  // Outline Warning button classes
  $form['saho_shop_forms']['buttons']['button_classes']['outline_warning_buttons'] = [
    '#type' => 'textarea',
    '#title' => t('Outline Warning Button Text'),
    '#description' => t('Enter button text that should get the "outline-warning" CSS class. One per line.'),
    '#default_value' => theme_get_setting('outline_warning_buttons'),
    '#rows' => 5,
  ];

  // Outline Info button classes
  $form['saho_shop_forms']['buttons']['button_classes']['outline_info_buttons'] = [
    '#type' => 'textarea',
    '#title' => t('Outline Info Button Text'),
    '#description' => t('Enter button text that should get the "outline-info" CSS class. One per line.'),
    '#default_value' => theme_get_setting('outline_info_buttons'),
    '#rows' => 5,
  ];

  // Button icon mappings
  $form['saho_shop_forms']['buttons']['button_icons'] = [
    '#type' => 'details',
    '#title' => t('Button Icon Mappings'),
    '#description' => t('Define which icons should be displayed on buttons based on their text content.'),
  ];

  // Icon mappings
  $form['saho_shop_forms']['buttons']['button_icons']['button_icon_mappings'] = [
    '#type' => 'textarea',
    '#title' => t('Button Icon Mappings'),
    '#description' => t('Enter button text and corresponding icon name. Format: "Button Text|icon-name". One per line.<br/><br/><strong>Available icons:</strong> add, arrow-repeat, basket, heart, cart-add, card, trash, edit, remove, arrow-right, arrow-left, arrow-up, arrow-down<br/><br/><strong>To add more icons:</strong> Edit the $icons map in scss/components/icons.scss and add the corresponding SVG file to the icons/ directory.'),
    '#default_value' => theme_get_setting('button_icon_mappings'),
    '#rows' => 10,
  ];

  // Cart Tab
  $form['saho_shop_cart'] = [
    '#type' => 'details',
    '#title' => t('Cart'),
    '#group' => 'saho_shop',
  ];

  // Cart Icon and Display Settings
  $form['saho_shop_cart']['cart_icon'] = [
    '#type' => 'select',
    '#title' => t('Cart icon'),
    '#default_value' => theme_get_setting('cart_icon'),
    '#options' => [
      'basket' => t('Basket'),
      'basket-fill' => t('Basket Fill'),
      'basket2' => t('Basket 2'),
      'basket2-fill' => t('Basket 2 Fill'),
      'basket3' => t('Basket 3'),
      'basket3-fill' => t('Basket 3 Fill'),
      'bag' => t('Bag'),
      'bag-fill' => t('Bag Fill'),
      'bag-heart' => t('Bag Heart'),
      'bag-heart-fill' => t('Bag Heart Fill'),
      'cart' => t('Cart'),
      'cart-fill' => t('Cart Fill'),
      'cart2' => t('Cart 2'),
      'cart3' => t('Cart 3'),
      'cart4' => t('Cart 4'),
      'shop' => t('Shop'),
      'shop-window' => t('Shop Window'),
    ],
    '#description' => t('Choose the Bootstrap icon to display in the cart block.'),
  ];

  $form['saho_shop_cart']['cart_show_label'] = [
    '#type' => 'checkbox',
    '#title' => t('Show item label'),
    '#description' => t('When enabled, shows label with count (e.g., "2 items"). When disabled, shows only the number (e.g., "2").'),
    '#default_value' => theme_get_setting('cart_show_label'),
  ];

  $form['saho_shop_cart']['cart_label'] = [
    '#type' => 'textfield',
    '#title' => t('Item label'),
    '#description' => t('Custom label to display with the cart count. Use @count as placeholder for the number (e.g., "@count items", "items: @count", "@count products in cart"). Leave empty to use default.'),
    '#default_value' => theme_get_setting('cart_label'),
    '#placeholder' => t('@count items'),
    '#states' => [
      'visible' => [
        ':input[name="cart_show_label"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_cart']['cart_count_circle'] = [
    '#type' => 'checkbox',
    '#title' => t('Display count in circle badge'),
    '#description' => t('When enabled, displays the count in a circular badge. When combined with "Show item label" disabled, the badge will be positioned over the icon.'),
    '#default_value' => theme_get_setting('cart_count_circle'),
  ];

  // Dropdown Behavior Settings
  $form['saho_shop_cart']['cart_hover_display'] = [
    '#type' => 'checkbox',
    '#title' => t('Display cart contents on hover'),
    '#description' => t('When enabled, the cart dropdown will appear when hovering over the cart icon and disappear when the mouse leaves.'),
    '#default_value' => theme_get_setting('cart_hover_display'),
  ];

  $form['saho_shop_cart']['cart_dropdown_animation'] = [
    '#type' => 'select',
    '#title' => t('Dropdown animation'),
    '#description' => t('Choose the animation style for the cart dropdown when it appears and disappears.'),
    '#default_value' => theme_get_setting('cart_dropdown_animation'),
    '#options' => [
      'null' => t('Default'),
      'fade' => t('Fade'),
      'no-animation' => t('No Animation'),
    ],
  ];

  $form['saho_shop_cart']['cart_dropdown_position'] = [
    '#type' => 'select',
    '#title' => t('Dropdown position'),
    '#description' => t('Choose the alignment of the cart dropdown relative to the cart icon.'),
    '#default_value' => theme_get_setting('cart_dropdown_position'),
    '#options' => [
      'right' => t('Right'),
      'left' => t('Left'),
      'center' => t('Center'),
    ],
  ];

  // Dropdown Content Settings
  $form['saho_shop_cart']['cart_contents_title'] = [
    '#type' => 'textfield',
    '#title' => t('Cart contents title'),
    '#description' => t('Title displayed in the cart dropdown. Use @count as placeholder for the number (e.g., "Shopping bag / @count", "Cart (@count)", "@count items in cart").'),
    '#default_value' => theme_get_setting('cart_contents_title'),
    '#placeholder' => t('Shopping bag / @count'),
  ];

  $form['saho_shop_cart']['cart_contents_colors'] = [
    '#type' => 'details',
    '#title' => t('Cart Contents Colors'),
    '#collapsible' => true,
    '#open' => false,
    '#description' => t('Customize the colors for the cart dropdown contents.'),
  ];

  $form['saho_shop_cart']['cart_contents_colors']['cart_contents_bg'] = [
    '#type' => 'select',
    '#title' => t('Background color'),
    '#description' => t('Choose the background color for the cart dropdown contents.'),
    '#default_value' => theme_get_setting('cart_contents_bg'),
    '#options' => [
      'primary' => t('Primary'),
      'light' => t('Light'),
      'dark' => t('Dark'),
    ],
  ];

  $form['saho_shop_cart']['cart_contents_colors']['cart_contents_text'] = [
    '#type' => 'select',
    '#title' => t('Text color'),
    '#description' => t('Choose the text color for the cart dropdown contents.'),
    '#default_value' => theme_get_setting('cart_contents_text'),
    '#options' => [
      'white' => t('White'),
      'black' => t('Black'),
    ],
  ];

  // Product Tab
  $form['saho_shop_product'] = [
    '#type' => 'details',
    '#title' => t('Product'),
    '#group' => 'saho_shop',
  ];

  $form['saho_shop_product']['product_teaser'] = [
    '#type' => 'select',
    '#title' => t('Product teaser'),
    '#empty_option' => t('None'),
    '#options' => [
      'card' => t('Card'),
      'saho_shop' => t('SahoShop'),
      'zoom' => t('Zoom')
    ],
    '#default_value' => theme_get_setting('product_teaser'),
  ];

  $form['saho_shop_product']['product_image_display'] = [
    '#type' => 'select',
    '#title' => t('Product image display'),
    '#default_value' => theme_get_setting('product_image_display'),
    '#options' => [
      'default' => t('Default - Basic image display'),
      'lightbox' => t('Lightbox - Click to enlarge with Fancybox'),
      'carousel' => t('Carousel - Thumbnail navigation with arrows'),
    ],
    '#description' => t('Choose how product images should be displayed.'),
  ];

  $form['saho_shop_product']['product_image_lightbox_theme'] = [
    '#type' => 'select',
    '#title' => t('Lightbox theme'),
    '#default_value' => theme_get_setting('product_image_lightbox_theme'),
    '#options' => [
      'dark' => t('Dark'),
      'light' => t('Light'),
      'auto' => t('Auto (based on user preference)'),
    ],
    '#description' => t('Choose the color scheme for the lightbox interface.'),
    '#states' => [
      'visible' => [
        ':input[name="product_image_display"]' => ['value' => 'lightbox'],
      ],
    ],
  ];

  // Carousel options
  $form['saho_shop_product']['carousel_options'] = [
    '#type' => 'details',
    '#title' => t('Carousel Options'),
    '#collapsible' => true,
    '#open' => true,
    '#states' => [
      'visible' => [
        ':input[name="product_image_display"]' => ['value' => 'carousel'],
      ],
    ],
  ];

  $form['saho_shop_product']['carousel_options']['carousel_arrows'] = [
    '#type' => 'checkbox',
    '#title' => t('Show navigation arrows'),
    '#default_value' => theme_get_setting('carousel_arrows'),
    '#description' => t('Display left/right arrow navigation buttons.'),
  ];

  $form['saho_shop_product']['carousel_options']['carousel_dots'] = [
    '#type' => 'checkbox',
    '#title' => t('Show navigation dots'),
    '#default_value' => theme_get_setting('carousel_dots'),
    '#description' => t('Display dot navigation below the carousel.'),
  ];

  $form['saho_shop_product']['carousel_options']['carousel_thumbs'] = [
    '#type' => 'checkbox',
    '#title' => t('Show thumbnail navigation'),
    '#default_value' => theme_get_setting('carousel_thumbs'),
    '#description' => t('Display thumbnail images below the carousel for navigation.'),
  ];

  $form['saho_shop_product']['carousel_options']['carousel_functionality'] = [
    '#type' => 'select',
    '#title' => t('Carousel functionality'),
    '#default_value' => theme_get_setting('carousel_functionality'),
    '#options' => [
      'basic' => t('Basic - Navigation only'),
      'fancybox' => t('Fancybox - Carousel with lightbox'),
      'panzoom' => t('Panzoom - Carousel with zoom functionality'),
    ],
    '#description' => t('Choose additional functionality for the carousel.'),
  ];

  // Checkout Tab
  $form['saho_shop_checkout'] = [
    '#type' => 'details',
    '#title' => t('Checkout'),
    '#group' => 'saho_shop',
  ];

  $form['saho_shop_checkout']['focused_checkout'] = [
    '#type' => 'checkbox',
    '#title' => t('Enable Focused Checkout'),
    '#description' => t('Remove header, footer, and navigation elements during checkout for a distraction-free, focused experience.'),
    '#default_value' => theme_get_setting('focused_checkout'),
  ];


  // Focused Checkout Settings
  $form['saho_shop_checkout']['focused_checkout_settings'] = [
    '#type' => 'details',
    '#title' => t('Focused Checkout Settings'),
    '#collapsible' => true,
    '#open' => true,
    '#states' => [
      'visible' => [
        ':input[name="focused_checkout"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['checkout_mobile_floating_order_summary'] = [
    '#type' => 'checkbox',
    '#title' => t('Mobile Floating Order Summary'),
    '#description' => t('Make the entire order summary float and stick to the bottom of the viewport on mobile devices only.'),
    '#default_value' => theme_get_setting('checkout_mobile_floating_order_summary'),
  ];

  // Content Pane Settings
  $form['saho_shop_checkout']['checkout_pane'] = [
    '#type' => 'details',
    '#title' => t('Checkout Pane Settings'),
    '#collapsible' => true,
    '#open' => true,
  ];

  $form['saho_shop_checkout']['checkout_pane']['checkout_pane_variant'] = [
    '#type' => 'select',
    '#title' => t('Checkout Pane Variant'),
    '#default_value' => theme_get_setting('checkout_pane_variant'),
    '#options' => [
      'default' => t('Default'),
      'minimal' => t('Minimal'),
      'title-outside' => t('Title Outside'),
    ],
    '#description' => t('Choose the visual variant for checkout panes (shipping methods, payment options, etc.).'),
  ];

  $form['saho_shop_checkout']['checkout_pane']['checkout_pane_collapsible'] = [
    '#type' => 'checkbox',
    '#title' => t('Make Checkout Panes Collapsible'),
    '#description' => t('Enable accordion-style collapsible behavior for checkout panes.'),
    '#default_value' => theme_get_setting('checkout_pane_collapsible'),
  ];

  $form['saho_shop_checkout']['checkout_pane']['checkout_pane_show_icons'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Icons in Checkout Panes'),
    '#description' => t('Display contextual icons in checkout pane headers (truck for shipping, credit-card for payment, etc.).'),
    '#default_value' => theme_get_setting('checkout_pane_show_icons'),
  ];

  // Checkout Completion Settings
  $form['saho_shop_checkout']['checkout_completion'] = [
    '#type' => 'details',
    '#title' => t('Checkout Completion Settings'),
    '#collapsible' => true,
    '#open' => false,
  ];

  $form['saho_shop_checkout']['checkout_completion']['checkout_completion_minimal'] = [
    '#type' => 'checkbox',
    '#title' => t('Minimal Checkout Completion'),
    '#description' => t('Remove padding, borders, and box shadow from the checkout completion message for a cleaner look.'),
    '#default_value' => theme_get_setting('checkout_completion_minimal'),
  ];

  // Paths
  $form['saho_shop_checkout']['focused_checkout_settings']['focused_paths'] = [
    '#type' => 'textarea',
    '#title' => t('Additional Focused Paths'),
    '#description' => t('Enter one path per line where focused page should be applied. Use <code>/user/*</code> for all user pages or specific paths like <code>/user/1</code>.'),
    '#default_value' => theme_get_setting('focused_paths'),
    '#rows' => 3,
  ];

  // Header Settings Group
  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_header'] = [
    '#type' => 'details',
    '#title' => t('Header Settings'),
    '#collapsible' => true,
    '#open' => true,
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_header']['focused_page_logo'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Logo'),
    '#description' => t('Display the site logo in the header.'),
    '#default_value' => theme_get_setting('focused_page_logo'),
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_header']['focused_page_use_default_theme_logo'] = [
    '#type' => 'checkbox',
    '#title' => t('Use default site logo'),
    '#description' => t('When SahoShop is used as a secondary theme (for example, for focused checkout pages), use the default theme logo instead of SahoShop.'),
    '#default_value' => theme_get_setting('focused_page_use_default_theme_logo'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_logo"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_header']['focused_page_show_back_button'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Back Button'),
    '#description' => t('Display a back button in the header.'),
    '#default_value' => theme_get_setting('focused_page_show_back_button'),
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_header']['focused_page_back_button_text'] = [
    '#type' => 'textfield',
    '#title' => t('Back Button Text'),
    '#description' => t('Text to display on the back button.'),
    '#default_value' => theme_get_setting('focused_page_back_button_text'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_show_back_button"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_header']['focused_page_show_cart_button'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Cart Button'),
    '#description' => t('Display a cart button in the header.'),
    '#default_value' => theme_get_setting('focused_page_show_cart_button'),
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_header']['focused_page_cart_button_text'] = [
    '#type' => 'textfield',
    '#title' => t('Cart Button Text'),
    '#description' => t('Text to display on the cart button.'),
    '#default_value' => theme_get_setting('focused_page_cart_button_text'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_show_cart_button"]' => ['checked' => TRUE],
      ],
    ],
  ];

  // Icon settings
  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_header']['focused_page_show_icons'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Icons in Header'),
    '#description' => t('Display icons alongside text in header buttons.'),
    '#default_value' => theme_get_setting('focused_page_show_icons'),
  ];

  // Content Settings
  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_content'] = [
    '#type' => 'details',
    '#title' => t('Content Settings'),
    '#collapsible' => true,
    '#open' => true,
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_content']['focused_checkout_form_step_title'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Form Step Title'),
    '#description' => t('Display the form step title in the layout.'),
    '#default_value' => theme_get_setting('focused_checkout_form_step_title'),
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_content']['focused_checkout_copyright'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Copyright'),
    '#description' => t('Display copyright text at the bottom of the focused checkout page.'),
    '#default_value' => theme_get_setting('focused_checkout_copyright'),
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_page_content']['focused_checkout_show_progress'] = [
    '#type' => 'checkbox',
    '#title' => t('Show Checkout Progress'),
    '#description' => t('Display the checkout progress indicator.'),
    '#default_value' => theme_get_setting('focused_checkout_show_progress'),
  ];

  // Layout
  $form['saho_shop_checkout']['focused_checkout_settings']['focused_layout'] = [
    '#type' => 'details',
    '#title' => t('Layout'),
    '#collapsible' => true,
    '#open' => true,
  ];


  $form['saho_shop_checkout']['focused_checkout_settings']['focused_layout']['focused_page_header_container'] = [
    '#type' => 'select',
    '#title' => t('Header Container Width'),
    '#default_value' => theme_get_setting('focused_page_header_container'),
    '#options' => [
      'container-fluid' => t('Full Width'),
      'container-lg' => t('Regular'),
      'container-md' => t('Narrow'),
      'custom' => t('Custom'),
    ],
    '#description' => t('Choose the maximum width for the header container.'),
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_layout']['focused_page_header_container_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Header Container Width'),
    '#description' => t('Enter a custom width in pixels (e.g., 800px) or percentage (e.g., 80%).'),
    '#default_value' => theme_get_setting('focused_page_header_container_custom'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_header_container"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_layout']['focused_page_container'] = [
    '#type' => 'select',
    '#title' => t('Content Container Width'),
    '#default_value' => theme_get_setting('focused_page_container'),
    '#options' => [
      'container-fluid' => t('Full Width'),
      'container-lg' => t('Regular'),
      'container-md' => t('Narrow'),
      'custom' => t('Custom'),
    ],
    '#description' => t('Choose the maximum width for the content container.'),
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_layout']['focused_page_container_custom'] = [
    '#type' => 'textfield',
    '#title' => t('Custom Content Container Width'),
    '#description' => t('Enter a custom width in pixels (e.g., 800px) or percentage (e.g., 80%).'),
    '#default_value' => theme_get_setting('focused_page_container_custom'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_container"]' => ['value' => 'custom'],
      ],
    ],
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_layout']['focused_checkout_main_column'] = [
    '#type' => 'select',
    '#title' => t('Main column width'),
    '#description' => t('Select the width for the main checkout form column. The sidebar will automatically take the remaining space.'),
    '#default_value' => theme_get_setting('focused_checkout_main_column'),
    '#options' => array(
      'col-xl-6' => t('6 columns (50%)'),
      'col-xl-7' => t('7 columns (58.33%)'),
      'col-xl-8' => t('8 columns (66.67%)'),
      'col-xl-9' => t('9 columns (75%)'),
    ),
  ];

  // Colors
  $form['saho_shop_checkout']['focused_checkout_settings']['focused_colors'] = [
    '#type' => 'details',
    '#title' => t('Colors'),
    '#collapsible' => true,
    '#open' => true,
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_colors']['focused_page_override_colors'] = [
    '#type' => 'checkbox',
    '#title' => t('Override Default Body Colors'),
    '#description' => t('Enable custom colors. If disabled, the theme\'s default colors will be used.'),
    '#default_value' => theme_get_setting('focused_page_override_colors'),
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_colors']['focused_page_bg_color'] = [
    '#type' => 'color',
    '#title' => t('Background Color'),
    '#description' => t('Choose the background color for the page.'),
    '#default_value' => theme_get_setting('focused_page_bg_color'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_override_colors"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_colors']['focused_page_text_color'] = [
    '#type' => 'color',
    '#title' => t('Text Color'),
    '#description' => t('Choose the text color for the page.'),
    '#default_value' => theme_get_setting('focused_page_text_color'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_override_colors"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_colors']['focused_page_sidebar_bg_color'] = [
    '#type' => 'color',
    '#title' => t('Order Summary Background Color'),
    '#description' => t('Choose the background color for the order summary section.'),
    '#default_value' => theme_get_setting('focused_page_sidebar_bg_color'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_override_colors"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_colors']['focused_page_sidebar_text_color'] = [
    '#type' => 'color',
    '#title' => t('Order Summary Text Color'),
    '#description' => t('Choose the text color for the order summary section.'),
    '#default_value' => theme_get_setting('focused_page_sidebar_text_color'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_override_colors"]' => ['checked' => TRUE],
      ],
    ],
  ];

  // Header Colors
  $form['saho_shop_checkout']['focused_checkout_settings']['focused_colors']['focused_page_override_header_colors'] = [
    '#type' => 'checkbox',
    '#title' => t('Override Default Header Colors'),
    '#description' => t('Enable custom colors for the header. If disabled, the theme\'s default colors will be used.'),
    '#default_value' => theme_get_setting('focused_page_override_header_colors'),
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_colors']['focused_page_header_bg_color'] = [
    '#type' => 'color',
    '#title' => t('Header Background Color'),
    '#description' => t('Choose the background color for the header.'),
    '#default_value' => theme_get_setting('focused_page_header_bg_color'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_override_header_colors"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_colors']['focused_page_header_text_color'] = [
    '#type' => 'color',
    '#title' => t('Header Text Color'),
    '#description' => t('Choose the text color for the header.'),
    '#default_value' => theme_get_setting('focused_page_header_text_color'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_override_header_colors"]' => ['checked' => TRUE],
      ],
    ],
  ];

  $form['saho_shop_checkout']['focused_checkout_settings']['focused_colors']['focused_page_header_border_color'] = [
    '#type' => 'color',
    '#title' => t('Header Border Color'),
    '#description' => t('Choose the border color for the header.'),
    '#default_value' => theme_get_setting('focused_page_header_border_color'),
    '#states' => [
      'visible' => [
        ':input[name="focused_page_override_header_colors"]' => ['checked' => TRUE],
      ],
    ],
  ];

  // Layout settings
  $form['saho_shop_layout'] = [
    '#type' => 'details',
    '#title' => t('Layout'),
    '#group' => 'saho_shop',
  ];

  // Regions
  $form['saho_shop_layout']['regions'] = array(
    '#type' => 'details',
    '#title' => t('Regions'),
    '#collapsible' => true,
    '#open' => true,
    '#description' => t('Additional classes and container settings for each region')
  );

  $form['saho_shop_layout']['regions']['main_container'] = [
    '#type' => 'select',
    '#title' => t('Main container size'),
    '#empty_option' => t('None'),
    '#options' => [
      'container' => t('Fixed'),
      'container-sm' => t('Container SM'),
      'container-md' => t('Container MD'),
      'container-lg' => t('Container LG'),
      'container-xl' => t('Container XL'),
      'container-xxl' => t('Container XXL'),
      'container-fluid' => t('Fluid'),
    ],
    '#default_value' => theme_get_setting('main_container'),
  ];

  $form['saho_shop_layout']['regions']['main_container_class'] = array(
    '#type' => 'textfield',
    '#title' => t('Main content classes'),
    '#default_value' => theme_get_setting('main_container_class')
  );

  $form['saho_shop_layout']['regions']['navigation'] = array(
    '#type' => 'details',
    '#title' => 'Navigation (Offcanvas)',
    '#collapsible' => true,
    '#open' => true,
  );

  $form['saho_shop_layout']['regions']['navigation']['navigation_toggle_visibility'] = array(
    '#type' => 'checkbox',
    '#title' => t('Disable navigation toggle on Desktop'),
    '#description' => t('Disables the navigation toggle button for larger screens'),
    '#default_value' => theme_get_setting('navigation_toggle_visibility')
  );

  $form['saho_shop_layout']['regions']['navigation']['navigation_toggle_text'] = array(
    '#type' => 'textfield',
    '#title' => t('Navigation toggle text'),
    '#default_value' => theme_get_setting('navigation_toggle_text')
  );

  $form['saho_shop_layout']['regions']['navigation']['navigation_position'] = array(
    '#type' => 'select',
    '#title' => t('Navigation placement'),
    '#options' => [
      'start' => t('Default (Left)'),
      'end' => t('Right'),
      'top' => t('Top'),
      'bottom' => t('Bottom'),
    ],
    '#default_value' => theme_get_setting('navigation_position'),
  );

  $form['saho_shop_layout']['regions']['navigation']['navigation_logo'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display Logo'),
    '#description' => t(' show or hide logo in the navigation region.'),
    '#default_value' => theme_get_setting('navigation_logo')
  );


  $form['saho_shop_layout']['regions']['navigation']['navigation_body_scrolling'] = array(
    '#type' => 'checkbox',
    '#title' => t('Body Scrolling'),
    '#description' => t('Enables scrolling on the body when navigation is open'),
    '#default_value' => theme_get_setting('navigation_body_scrolling')
  );

  $form['saho_shop_layout']['regions']['navigation']['navigation_backdrop'] = array(
    '#type' => 'checkbox',
    '#title' => t('Body Backdrop'),
    '#description' => t('Disables scrolling and creates a backdrop over the body when navigation is open'),
    '#default_value' => theme_get_setting('navigation_backdrop')
  );

  $form['saho_shop_layout']['regions']['navigation']['region_class_navigation']= array(
    '#type' => 'textfield',
    '#title' => t('Navigation region classes'),
    '#default_value' => theme_get_setting('region_class_navigation')
  );

  // Regions
  foreach ($region_list as $name => $description) {
    if (!in_array($name, $exclude_regions)){
      if (theme_get_setting('region_class_' . $name) !== null) {
        $region_class = theme_get_setting('region_class_' . $name);
      } else {
        $region_class = '';
      }

      $form['saho_shop_layout']['regions'][$name] = array(
        '#type' => 'details',
        '#title' => $description,
        '#collapsible' => true,
        '#open' => false,
      );
      $form['saho_shop_layout']['regions'][$name]['region_class_' . $name] = array(
        '#type' => 'textfield',
        '#title' => t('@description classes', array('@description' => $description)),
        '#default_value' => $region_class
      );
      $form['saho_shop_layout']['regions'][$name]['region_container_' . $name] = [
        '#type' => 'select',
        '#title' => t('Container type'),
        '#empty_option' => t('None'),
        '#options' => [
          'container' => t('Fixed'),
          'container-sm' => t('Container SM'),
          'container-md' => t('Container MD'),
          'container-lg' => t('Container LG'),
          'container-xl' => t('Container XL'),
          'container-xxl' => t('Container XXL'),
          'container-fluid' => t('Fluid'),
        ],
        '#description' => t('<code>.container</code>, sets a max-width at each responsive breakpoint<br/>
                                   <code>.container-fluid</code>, is width: 100% at all breakpoints<br/>
                                   <code>.container-{breakpoint}</code>, is width: 100% until the specified breakpoint'),
        '#default_value' => theme_get_setting('region_container_' . $name),
        '#group' => 'container',
      ];
    }
  }

  // Fonts & Icons
  $form['saho_shop_appearance']['fonts_and_icons'] = array(
    '#type' => 'details',
    '#title' => t('Fonts & Icons'),
    '#collapsible' => true,
    '#open' => true,
  );

  // Icons
  $form['saho_shop_appearance']['fonts_and_icons']['saho_shop_icons'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use icons'),
    '#description' => t('Checking this will add icons to certain buttons and links.'),
    '#default_value' => theme_get_setting('saho_shop_icons')
  );

  $form['saho_shop_appearance']['fonts_and_icons']['font_set'] = array(
    '#type' => 'select',
    '#title' => t('Font libraries'),
    '#default_value' => theme_get_setting('font_set'),
    '#empty_option' => t('None'),
    '#description' => t('A few predefined font libraries delivered from Google.<br/>All fonts are loaded with Regular, Italic and Bold variants.'),
    '#options' => array(
      'ibm_plex_sans' => t('IBM Plex Sans'),
      'lato' => t('Lato'),
      'montserrat' => t('Montserrat'),
      'open_sans' => t('Open Sans'),
      'raleway' => t('Raleway'),
      'roboto' => t('Roboto'),
    ),
  );

  // Layout Builder Tab
  $form['saho_shop_layout_builder'] = [
    '#type' => 'details',
    '#title' => t('Layout Builder'),
    '#group' => 'saho_shop',
  ];

  $form['saho_shop_layout_builder']['local_tasks_fixed'] = array(
    '#type' => 'checkbox',
    '#title' => t('Fixed local tasks'),
    '#description' => t('On pages that use layout builder position local tasks fixed to the left.'),
    '#default_value' => theme_get_setting('local_tasks_fixed')
  );

  // Change collapsible fieldsets (now details) to default #open => FALSE.
  $form['theme_settings']['#open'] = false;
  $form['logo']['#open'] = false;
  $form['favicon']['#open'] = false;
}
