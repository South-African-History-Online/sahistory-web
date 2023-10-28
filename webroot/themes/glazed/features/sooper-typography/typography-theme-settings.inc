<?php
$form['glazed_settings']['typography'] = [
  '#title' => t('Typography'),
  '#type' => 'details',
  '#group' => 'glazed_settings',
  '#weight' => 4,
  '#weight' => 15,
];

$form['glazed_settings']['typography']['body_line_height'] = [
  '#type' => 'textfield',
  '#title' => t('Body Line Height'),
  '#default_value' => ((theme_get_setting('body_line_height') !== null)) ? theme_get_setting('body_line_height') : '1.42857',
  '#attributes' => ['class' => ['line-height-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['body_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('Body Font Size'),
  '#default_value' => ((theme_get_setting('body_font_size') !== null)) ? theme_get_setting('body_font_size') : '14',
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['nav_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('Main menu Font Size'),
  '#default_value' => ((theme_get_setting('nav_font_size') !== null)) ? theme_get_setting('nav_font_size') : '14',
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['headings_line_height'] = [
  '#type' => 'textfield',
  '#title' => t('Headings Line Height'),
  '#default_value' => ((theme_get_setting('headings_line_height') !== null)) ? theme_get_setting('headings_line_height') : '1.05',
  '#attributes' => ['class' => ['line-height-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['scale_factor'] = [
  '#type' => 'textfield',
  '#title' => t('Typography Scale Factor') . ' <small>' . t('(This overrides advanced type controls)') . '</small>',
  '#default_value' => ((theme_get_setting('scale_factor') !== null)) ? theme_get_setting('scale_factor') : '1.25',
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type'] = [
  '#title' => t('Advanced Type Controls'),
  '#description' => t('If you change the main contorls above these advanced controls are automatically generated and need might re-adjustment.'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];

$form['glazed_settings']['typography']['advanced_type']['h1_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('H1 Font Size (Page Title)'),
  '#default_value' => ((theme_get_setting('h1_font_size') !== null)) ? theme_get_setting('h1_font_size') : '36',
  '#attributes' => ['class' => ['font-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['h2_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('H2 Font Size'),
  '#default_value' => ((theme_get_setting('h2_font_size') !== null)) ? theme_get_setting('h2_font_size') : '30',
  '#attributes' => ['class' => ['font-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['h3_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('H3 Font Size'),
  '#default_value' => ((theme_get_setting('h3_font_size') !== null)) ? theme_get_setting('h3_font_size') : '24',
  '#attributes' => ['class' => ['font-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['h4_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('H4 Font Size'),
  '#default_value' => ((theme_get_setting('h4_font_size') !== null)) ? theme_get_setting('h4_font_size') : '18',
  '#attributes' => ['class' => ['font-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['blockquote_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('Blockquote Font Size'),
  '#default_value' => ((theme_get_setting('blockquote_font_size') !== null)) ? theme_get_setting('blockquote_font_size') : '18',
  '#attributes' => ['class' => ['font-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['blockquote_line_height'] = [
  '#type' => 'textfield',
  '#title' => t('Blockquote Line Height'),
  '#default_value' => ((theme_get_setting('blockquote_line_height') !== null)) ? theme_get_setting('blockquote_line_height') : '1.5',
  '#attributes' => ['class' => ['line-height-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['headings_letter_spacing'] = [
  '#type' => 'textfield',
  '#title' => t('Headings Letter Spacing'),
  '#default_value' => ((theme_get_setting('headings_letter_spacing') !== null)) ? theme_get_setting('headings_letter_spacing') : '0',
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['headings_uppercase'] = [
  '#type' => 'checkbox',
  '#title' => t('Headings Uppercase'),
  '#default_value' => ((theme_get_setting('headings_uppercase') !== null)) ? theme_get_setting('headings_uppercase') : FALSE,
  '#size' => 9,
  '#maxlength' => 9,
];


$form['glazed_settings']['typography']['advanced_type']['mobile_type'] = [
  '#title' => t('Mobile Type Controls'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];

$form['glazed_settings']['typography']['advanced_type']['mobile_type']['body_mobile_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('Body Font Size'),
  '#default_value' => ((theme_get_setting('body_mobile_font_size') !== null)) ? theme_get_setting('body_mobile_font_size') : '14',
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['mobile_type']['nav_mobile_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('Nav Font Size'),
  '#default_value' => ((theme_get_setting('nav_mobile_font_size') !== null)) ? theme_get_setting('nav_mobile_font_size') : '14',
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['mobile_type']['h1_mobile_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('H1 Font Size (Page Title)'),
  '#default_value' => ((theme_get_setting('h1_mobile_font_size') !== null)) ? theme_get_setting('h1_mobile_font_size') : '36',
  '#attributes' => ['class' => ['font-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['mobile_type']['h2_mobile_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('H2 Font Size'),
  '#default_value' => ((theme_get_setting('h2_mobile_font_size') !== null)) ? theme_get_setting('h2_mobile_font_size') : '30',
  '#attributes' => ['class' => ['font-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['mobile_type']['h3_mobile_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('H3 Font Size'),
  '#default_value' => ((theme_get_setting('h3_mobile_font_size') !== null)) ? theme_get_setting('h3_mobile_font_size') : '24',
  '#attributes' => ['class' => ['font-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['advanced_type']['mobile_type']['blockquote_mobile_font_size'] = [
  '#type' => 'textfield',
  '#title' => t('Blockquote Font Size'),
  '#default_value' => ((theme_get_setting('blockquote_mobile_font_size') !== null)) ? theme_get_setting('blockquote_mobile_font_size') : '18',
  '#attributes' => ['class' => ['font-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['divider'] = [
  '#title' => t('Divider'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
  '#suffix' => _glazed_type_preview(),
];

$form['glazed_settings']['typography']['divider']['divider_thickness'] = [
  '#type' => 'textfield',
  '#title' => t('Divider Thickness'),
  '#default_value' => ((theme_get_setting('divider_thickness') !== null)) ? theme_get_setting('divider_thickness') : '4',
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['divider']['divider_length'] = [
  '#type' => 'textfield',
  '#title' => t('Divider Length') . ' <small>' . t('(0 = full width)') . '</small>',
  '#default_value' => ((theme_get_setting('divider_length') !== null)) ? theme_get_setting('divider_length') : '0',
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['typography']['divider']['divider_position'] = [
  '#type' => 'textfield',
  '#title' => t('Divider Position'),
  '#default_value' => ((theme_get_setting('divider_position') !== null)) ? theme_get_setting('divider_position') : '0',
  '#attributes' => ['class' => ['element-invisible']],
  '#size' => 9,
  '#maxlength' => 9,
  '#states' => [
    'invisible' => [
      ':input[name="divider_length"]' => ['value' => '0'],
    ],
  ],
];

$form['glazed_settings']['typography']['divider']['divider_color'] = [
  '#type' => 'select',
  '#title' => t('Divider Color'),
  '#default_value' =>  theme_get_setting('divider_color'),
  '#options' => _glazed_color_options($theme),
];

$form['glazed_settings']['typography']['divider']['divider_color_custom'] = [
  '#type' => 'textfield',
  '#title' => t('Custom Value'),
  '#default_value' => ((theme_get_setting('divider_color_custom') !== null)) ? theme_get_setting('divider_color_custom') : '#eeeeee',
  '#states' => [
    'visible' => [
      'select[name="divider_color"]' => ['value' => 'custom'],
    ],
  ],
];

