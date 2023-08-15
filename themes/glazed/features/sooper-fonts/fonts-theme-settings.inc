<?php

$weights = [
  '' => t('Theme defaults'),
  '100' => t('Thin') . ' ' . t('100'),
  '200' => t('UltraLight') . ' ' . t('200'),
  '300' => t('Light') . ' ' . t('300'),
  '400' => t('Normal') . ' ' . t('400'),
  '500' => t('Medium') . ' ' . t('500'),
  '600' => t('SemiBold') . ' ' . t('600'),
  '700' => t('Bold') . ' ' . t('700'),
  '800' => t('ExtraBold') . ' ' . t('800'),
  '900' => t('Black') . ' ' . t('900'),
  '950' => t('ExtraBlack') . ' ' . t('950'),
];

$font_list = glazed_font_list();
$font_count = count($font_list, COUNT_RECURSIVE);

$form['glazed_settings']['fonts'] = [
  '#title' => t('Fonts'),
  '#type' => 'details',
  '#group' => 'glazed_settings',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
   '#weight' => 4,
  '#weight' => 20,
];

// Regular Fonts Settings
$form['glazed_settings']['fonts']['body_font_face'] = [
  '#type' => 'select',
  '#title' => t('Base font'),
  '#default_value' =>  theme_get_setting('body_font_face'),
  '#options' => $font_list,
  '#prefix'        => '<p>' . t('Choose from @count webfonts', ['@count' => $font_count]) . '</p>',
];

$form['glazed_settings']['fonts']['body_font_face_selector'] = [
  '#type' => 'textfield',
  '#title' => t('CSS Selector'),
  '#default_value' => ((theme_get_setting('body_font_face_selector') !== null)) ? theme_get_setting('body_font_face_selector') : 'body',
];

$form['glazed_settings']['fonts']['headings_font_face'] = [
  '#type' => 'select',
  '#title' => t('Headings'),
  '#default_value' =>  theme_get_setting('headings_font_face'),
  '#options' => $font_list,
];

$form['glazed_settings']['fonts']['headings_font_face_selector'] = [
  '#type' => 'textfield',
  '#title' => '&nbsp;',
  '#default_value' => ((theme_get_setting('headings_font_face_selector') !== null)) ? theme_get_setting('headings_font_face_selector') : 'h1,h2,h3,h4,h5,h6,label,.field--label,.page-title, .html .tp-caption',
];

$form['glazed_settings']['fonts']['nav_font_face'] = [
  '#type' => 'select',
  '#title' => t('Navigation'),
  '#default_value' =>  theme_get_setting('nav_font_face'),
  '#options' => $font_list,
];

$form['glazed_settings']['fonts']['nav_font_face_selector'] = [
  '#type' => 'textfield',
  '#title' => '&nbsp;',
  '#default_value' => ((theme_get_setting('nav_font_face_selector') !== null)) ? theme_get_setting('nav_font_face_selector') : 'nav,nav ul li,nav a',
];

$form['glazed_settings']['fonts']['sitename_font_face'] = [
  '#type' => 'select',
  '#title' => t('Site name'),
  '#default_value' =>  theme_get_setting('sitename_font_face'),
  '#options' => $font_list,
];

$form['glazed_settings']['fonts']['sitename_font_face_selector'] = [
  '#type' => 'textfield',
  '#title' => '&nbsp;',
  '#default_value' => ((theme_get_setting('sitename_font_face_selector') !== null)) ? theme_get_setting('sitename_font_face_selector') : '.site-name, .navbar-brand',
];

$form['glazed_settings']['fonts']['blockquote_font_face'] = [
  '#type' => 'select',
  '#title' => t('Blockquote'),
  '#default_value' =>  theme_get_setting('blockquote_font_face'),
  '#options' => $font_list,
];

$form['glazed_settings']['fonts']['blockquote_font_face_selector'] = [
  '#type' => 'textfield',
  '#title' => '&nbsp;',
  '#default_value' => ((theme_get_setting('blockquote_font_face_selector') !== null)) ? theme_get_setting('blockquote_font_face_selector') : 'blockquote, blockquote p',
];

$form['glazed_settings']['fonts']['font_preview'] = [
  '#title' => t('Browse & View Fonts'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
  '#prefix' => '<br class="clear-both"><br class="clear-both">',
  '#suffix' => _glazed_type_preview(),
];

$form['glazed_settings']['fonts']['font_preview']['preview'] = [
 '#type' => 'markup',
 '#markup' => _glazed_font_previews(),
];


/**
 * Helper function to get list of locally hosted fonts
 */
function glazed_local_webfonts() {
  global $base_path, $base_url;
  $theme_chain = ['glazed'];
  $fonts = [];
  foreach ($theme_chain as $theme) {
    foreach (file_scan_directory(drupal_get_path('theme', $theme) . '/fonts', '/.css/i') as $file) {
      $font_path = str_replace(drupal_get_path('theme', $theme), '', $file->uri);
      $css = file_get_contents($file->uri);
      if ($css) {
        preg_match_all('/font-family: \'(.*?)\'/i', $css, $matches);
      }
      else {
        \Drupal::messenger()->addMessage(t('Cannot read font files, please check permissions on the fonts folder in the theme.'), 'error');
      }
      foreach ($matches[1] as $font) {
        if ($font) {
          $key = '1' . $theme . '|' . $font_path . '|' . $font;
          $fonts[$key] = preg_replace('/(?<!\ ) {A-Z]/', ' $0', $font);
        }
      }
    }
  }

  asort($fonts, SORT_STRING);
  return $fonts;
}

/**
 * Helper function to provide a list of fonts for select list in theme settings.
 */
function glazed_font_list() {
  $fonts = [
    'Sans-serif web-safe' => [
      'helvetica' => 'Helvetica/Arial',
      'myriad' => 'Myriad/Calibri',
      'verdana' => 'Verdana',
      'lucida' => 'Lucida Sans',
      'geneva' => 'Geneva',
      'tahoma' => 'Tahoma',
      'trebuchet' => 'Trebuchet',
      'century' => 'Century Gothic',
    ],
    'Serif web-safe' => [
      'garamond' => 'Garamond',
      'georgia' => 'Georgia',
      'palatino' => 'Palatino Linotype',
      'times' => 'Times New Roman',
    ],
  ];
  $fonts['Local webfonts'] = glazed_local_webfonts();
  $fonts['Google webfonts'] = glazed_google_webfonts();

  return $fonts;
}

/**
 * Helper function to get list of google web fonts
 *
 * Source: https://raw.githubusercontent.com/jonathantneal/google-fonts-complete/master/api-response.json
 */
function glazed_google_webfonts() {
  $json_webfonts = file_get_contents(dirname(__FILE__) . '/google-webfonts.json');
  $webfonts = json_decode($json_webfonts);
  $fonts = [];
  foreach ($webfonts as $font_family) {
    $fam_name = $font_family->family;
    foreach ($font_family->variants as $font) {
      if ($font == 'regular') $font = '';
      $font_name = $fam_name . ' ' . $font;
      $font_name_safe = str_replace(' ', '+', $fam_name) . ':' . $font;
      $font_name_key = '0' . $font_name_safe;
      $fonts[$font_name_key] = $font_name;
    }
  }
  return $fonts;
}

/**
 * Helper function to display font previews in the settings form
 */
function _glazed_font_previews() {
  $output = '<div class="fonts-previews">';
  $output .= '  <h3>Google fonts preview</h3>';
  $output .= '  <p>Check out <a href="http://www.google.com/webfonts">google.com/webfonts</a> for previews of google web fonts.</p>';
  $output .= '  <h3>Local fonts preview</h3>';
  $output .= '  <p>Glazed will automatically detect and load any Fontsquirrel-generated @font-face package in the exmapletheme/fonts folder. See <a href="https://www.fontsquirrel.com/tools/webfont-generator">Fontsquirrel.com</a></p>';
  foreach (glazed_local_webfonts(TRUE) as $key => $font_name) {
    $font = explode('|', $key);
    $local_name = $font[2];
    $output .= '  <div class="font-preview font-local" style="font-family:' . $local_name . '">';
    $output .= '  <p class="font-name">' . $font_name . '</p><p class="font-test">The quick brown fox jumps over the lazy dog.</p>';
    $output .= '  <p class="font-test-small">The quick brown fox jumps over the lazy dog.</p>';
    $output .= '  <p class="font-test-accents">&#36; &euro; &pound; &yen; &agrave; &egrave; &igrave; &ograve; &ugrave; &Agrave; &Egrave; &Igrave; &Ograve; &Ugrave; &aacute; &eacute; &iacute; &oacute; &uacute; &yacute; &Aacute; &Eacute; &Iacute; &Oacute; &Uacute; &Yacute;</p>';
    $output .= '  </div>';
  }
  $output .= '</div>';
  return $output;
}