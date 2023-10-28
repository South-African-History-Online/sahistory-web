<?php

$glazed_fonts = [];
$glazed_fonts[] = theme_get_setting('body_font_face');
$glazed_fonts[] = theme_get_setting('headings_font_face');
$glazed_fonts[] = theme_get_setting('nav_font_face');
$glazed_fonts[] = theme_get_setting('sitename_font_face');
$glazed_fonts[] = theme_get_setting('blockquote_font_face');

$glazed_fonts = array_unique($glazed_fonts);

foreach ($glazed_fonts as $glazed_font) {
  if (isset($glazed_font[0]) && $glazed_font[0] === '0') {
    // If the font is loaded without a weight or style specified
    // try to load a complete set
    if (substr($glazed_font, -1) == ':') {
      $glazed_font .= '400italic,700italic,400,700';
    }
    _glazed_add_goog_font($glazed_font, $variables);
  }
  elseif (isset($glazed_font[0]) && $glazed_font[0] === '1') {
    _glazed_add_local_font($glazed_font, $variables);
  }
}

/**
 * Helper function to add google fonts to the page
 */
function _glazed_add_goog_font($font, &$variables) {
  $font = substr($font, 1);
  $element = [
    '#tag' => 'link',
    '#attributes' => [
      'href' => '//fonts.googleapis.com/css?family=' . $font,
      'rel' => 'stylesheet',
      'type' => 'text/css',
    ],
  ];
  $variables['#attached']['html_head'][] = [$element, $font];
}

/**
 * Helper function to add local fonts to the page. Add query string to stylesheet to prevent
 * identically named files to overwrite eachother.
 */
function _glazed_add_local_font($font, &$variables) {
  global $base_url;

  $font = explode('|', substr($font, 1));
  $element = [
    '#tag' => 'link',
    '#attributes' => [
      'href' => $base_url . '/' . drupal_get_path('theme', $font[0]) . $font[1],
      'rel' => 'stylesheet',
      'type' => 'text/css',
    ],
  ];
  $variables['#attached']['html_head'][] = [$element, $font[2]];
}
