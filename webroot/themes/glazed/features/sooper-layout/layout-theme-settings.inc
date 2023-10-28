<?php

$form['glazed_settings']['layout'] = [
  '#title' => t('Layout'),
  '#type' => 'details',
  '#group' => 'glazed_settings',
  '#weight' => 5,
];

$form['glazed_settings']['layout']['boxed_layout'] = [
  '#type' => 'checkbox',
  '#title' => t('Boxed Layout'),
  '#default_value' => ((theme_get_setting('boxed_layout') !== null)) ? theme_get_setting('boxed_layout') : 1,
  '#description' => t('By default containers and their backgrounds are full-width. Boxed containers will squeeze all container backgrounds into a centered column.'),
];

$form['glazed_settings']['layout']['sticky_footer'] = [
  '#type' => 'checkbox',
  '#title' => t('Sticky Footer'),
  '#default_value' => ((theme_get_setting('sticky_footer') !== null)) ? theme_get_setting('sticky_footer') : 1,
  '#description' => t('Makes footer stick to the bottom of the browser on short pages. (Does not work with boxed layout)'),
  '#states' => [
    'visible' => [
      ':input[name="boxed_layout"]' => ['checked' => FALSE],
    ],
  ],
];

$form['glazed_settings']['layout']['boxed_layout_boxbg'] = [
  '#type' => 'textfield',
  '#title' => t('Box Background'),
  '#default_value' => ((theme_get_setting('boxed_layout_boxbg') !== null)) ? theme_get_setting('boxed_layout_boxbg') : '#ffffff',
  '#description' => t('Background color for main content while in boxed mode'),
  '#states' => [
    'visible' => [
      ':input[name="boxed_layout"]' => ['checked' => TRUE],
    ],
  ],
];

$form['glazed_settings']['layout']['grid'] = [
  '#title' => t('Grid Settings'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];

$form['glazed_settings']['layout']['grid']['box_max_width'] = [
  '#type' => 'textfield',
  '#title' => t('Boxed Container Max-width'),
  '#default_value' => ((theme_get_setting('box_max_width') !== null)) ? theme_get_setting('box_max_width') : '1170',
  '#size' => 9,
  '#maxlength' => 9,
  '#description' => t('Width of the boxed layout container. Should be higher than content width.'),
  '#states' => [
    'visible' => [
      ':input[name="boxed_layout"]' => ['checked' => TRUE],
    ],
  ],
];

$form['glazed_settings']['layout']['grid']['layout_max_width'] = [
  '#type' => 'textfield',
  '#title' => t('Content Max-width'),
  '#default_value' => ((theme_get_setting('layout_max_width') !== null)) ? theme_get_setting('layout_max_width') : '1170',
  '#size' => 9,
  '#maxlength' => 9,
  '#description' => t('Max width of the website container. 480px - 4100px. Default is 1280px. With Side-Header enabled we typically use 980px.'),
];

$form['glazed_settings']['layout']['grid']['gutter_horizontal'] = [
  '#type' => 'textfield',
  '#title' => t('Space between columns'),
  '#default_value' => ((theme_get_setting('gutter_horizontal') !== null)) ? theme_get_setting('gutter_horizontal') : '30',
  '#size' => 9,
  '#maxlength' => 9,
  '#description' => t('Default is 30px.'),
];

$form['glazed_settings']['layout']['grid']['gutter_vertical'] = [
  '#type' => 'textfield',
  '#title' => t('Space between rows'),
  '#default_value' => ((theme_get_setting('gutter_vertical') !== null)) ? theme_get_setting('gutter_vertical') : '30',
  '#size' => 9,
  '#maxlength' => 9,
  '#description' => t('Default is 30px.'),
];

$form['glazed_settings']['layout']['grid']['gutter_container'] = [
  '#type' => 'textfield',
  '#title' => t('Container Space'),
  '#default_value' => ((theme_get_setting('gutter_container') !== null)) ? theme_get_setting('gutter_container') : '60',
  '#size' => 9,
  '#maxlength' => 9,
  '#description' => t('Default is 60px.'),
];


$form['glazed_settings']['layout']['background'] = [
  '#title' => t('Background Image'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];

// @see glazed_helper.module glazed_helper_settings_form_submit()
$form['glazed_settings']['layout']['background']['background_image_path'] = [
  '#type' => 'textfield',
  '#title' => t('Path to  background image'),
  '#default_value' => theme_get_setting('background_image_path'),
  '#description' => t('The path to the file you would like to use as your page title background image. If you upload a file below it will automatically populate this text field'),
];
$form['glazed_settings']['layout']['background']['background_image'] = [
  '#type' => 'file',
  '#title' => t('Upload Background'),
  '#description' => t('The default page title image can also be changed per node, using the Header image field in the node form.'),
];

$form['glazed_settings']['layout']['background']['background_image_style'] = [
  '#type' => 'radios',
  '#title' => t('Background Style'),
  '#default_value' => ((theme_get_setting('background_image_style') !== null)) ? theme_get_setting('background_image_style') : 'cover',
  '#options' => [
    'cover' => t('Cover'),
    'contain' => t('Contain'),
    'no_repeat' => t('No Repeat'),
    'repeat' => t('Repeat'),
  ],
];

$form['glazed_settings']['layout']['background']['background_image_position'] = [
  '#type' => 'radios',
  '#title' => t('Background Position'),
  '#default_value' => ((theme_get_setting('background_image_position') !== null)) ? theme_get_setting('background_image_position') : 'center_center',
  '#options' => [
    'center_center' => t('Center Center'),
    'left_top' => t('Left Top'),
    'left_center' => t('Left Center'),
    'left_bottom' => t('Left Bottom'),
    'right_top' => t('Right Top'),
    'right_center' => t('Right Center'),
    'right_bottom' => t('Right Bottom'),
    'center_bottom' => t('Center Bottom'),
  ],
];


$form['glazed_settings']['layout']['mobile_layout'] = [
  '#title' => t('Mobile view'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];

$form['glazed_settings']['layout']['mobile_layout']['gutter_horizontal_mobile'] = [
  '#type' => 'textfield',
  '#title' => t('Space between columns'),
  '#default_value' => ((theme_get_setting('gutter_horizontal_mobile') !== null)) ? theme_get_setting('gutter_horizontal_mobile') : '30',
  '#size' => 9,
  '#maxlength' => 9,
  '#description' => t('Width of the horizontal gutter in Glazed mobile view (<1200px). 0 - 100px. Default is 30.'),
];

$form['glazed_settings']['layout']['mobile_layout']['gutter_vertical_mobile'] = [
  '#type' => 'textfield',
  '#title' => t('Space between rows'),
  '#default_value' => ((theme_get_setting('gutter_vertical_mobile') !== null)) ? theme_get_setting('gutter_vertical_mobile') : '30',
  '#size' => 9,
  '#maxlength' => 9,
  '#description' => t('Width of the vertical gutter in Glazed mobile view (<1200px). 0 - 100px. Default is 30px.'),
];

$form['glazed_settings']['layout']['mobile_layout']['gutter_container_mobile'] = [
  '#type' => 'textfield',
  '#title' => t('Mobile Container space'),
  '#default_value' => ((theme_get_setting('gutter_container_mobile') !== null)) ? theme_get_setting('gutter_container_mobile') : '30',
  '#size' => 9,
  '#maxlength' => 9,
  '#description' => t('Default is 30px.'),
];

// Secondary Header
$form['glazed_settings']['layout']['mobile_layout']['secondary_header'] = [
  '#title' => t('Secondary Header'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];

$form['glazed_settings']['layout']['mobile_layout']['secondary_header']['secondary_header_hide'] = [
    '#type' => 'radios',
    '#title' => t('Hide in small devices'),
    '#default_value' => ((theme_get_setting('secondary_header_hide') !== null)) ? theme_get_setting('secondary_header_hide') : 'hidden_none',
    '#options' => [
      'hidden_none' => t('Never hide'),
      'hidden_xs' => t('Extra small devices (<768px)'),
      'hidden_sm' => t('Small devices (768px)'),
      'hidden_md' => t('Medium devices (992px)'),
    ],
];

$form['glazed_settings']['layout']['granular_boxed'] = [
  '#title' => t('Full Width Regions and Content Types'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];

$form['glazed_settings']['layout']['granular_boxed']['full_width_containers'] = [
    '#type' => 'checkboxes',
    '#multiple' => TRUE,
    '#title' => t('Full width regions'),
    '#description' => t('Full width content regions have 2 use cases: creating blocks that span across the whole browser and creating layouts with full screen backgrounds within your content. If you want all content to be full width, don\'t use these settings but instead set the "Layout Max-width" setting to the maximum value.'),
    '#default_value' => ((theme_get_setting('full_width_containers') !== null)) ? theme_get_setting('full_width_containers') : [],
    '#options' => [
      'cnt_secondary_header' => t('Secondary Header'),
      'cnt_header_nav' => t('Header / Navigation'),
      'cnt_page_title' => t('Page Title'),
      'cnt_content_top' => t('Content Top'),
      'cnt_content' => t('Content'),
      'cnt_content_bottom' => t('Content Bottom'),
      'cnt_footer' => t('Footer'),
    ],
];

$default_options = _glazed_node_types_options();
foreach ($default_options as $key => $value) {
  if (strpos($key, 'drag_drop') !== FALSE) {
    $default_options[$key] = $key;
  }
  else {
    $default_options[$key] = 0;
  }
}
$form['glazed_settings']['layout']['granular_boxed']['full_width_content_types'] = [
    '#type' => 'checkboxes',
    '#title' => t('Full width content types'),
    '#description' => t('If you want to create Drag and Drop pages with full-width sections you need this setting. This only works if the pages have no sidebar blocks. When you enable sidebar blocks alongside your full-width content pages they will simply squish into fixed containers to make room for the sidebar.'),
    '#default_value' => ((theme_get_setting('full_width_content_types') !== null)) ? theme_get_setting('full_width_content_types') : $default_options,
    '#options' => _glazed_node_types_options(),
];

$default_options = _glazed_node_types_options();
foreach ($default_options as $key => $value) {
  if (in_array($key, ['blog', 'event', 'news', 'portfolio'])) {
    $default_options[$key] = $key;
  }
  else {
    $default_options[$key] = 0;
  }
}

$form['glazed_settings']['layout']['hide_regions'] = [
  '#title' => t('Hide Regions'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];

$form['glazed_settings']['layout']['hide_regions']['hidden_regions'] = [
    '#type' => 'checkboxes',
    '#multiple' => TRUE,
    '#title' => t('Hide Regions'),
    '#description' => t('Globally disables regions.'),
    '#default_value' => ((theme_get_setting('hidden_regions') !== null)) ? theme_get_setting('hidden_regions') : [],
    '#options' => [
      'navigation' => t('Navigation (branding)'),
      'navigation_collapsible' => t('Navigation Collapsible (menu)'),
      'secondary_header' => t('Secondary Header'),
      'page_title' => t('Page Title'),
      'highlighted' => t('Hero Region'),
      'header' => t('Header'),
      'content_top' => t('Content Top'),
      'content' => t('Content'),
      'content_bottom' => t('Content Bottom'),
      'sidebar_first' => t('Primary'),
      'sidebar_second' => t('Secondary'),
      'footer' => t('Footer'),

    ],
];
