<?php
if (
     theme_get_setting('headings_font_face', $theme)
  || theme_get_setting('body_font_face', $theme)
  || theme_get_setting('nav_font_face', $theme)
  || theme_get_setting('sitename_font_face', $theme)) {

  $font = theme_get_setting('body_font_face', $theme);
  $style = (strpos($font, 'italic')) ? 'italic' : 'normal';
  $weight = (preg_match('/[0-9]{3}/', $font, $matches)) ? $matches[0] : '400';
  $font = _glazed_get_font($font, $theme);
  $selector = theme_get_setting('body_font_face_selector', $theme);
  $CSS .= <<<EOT

$selector, .toooltip {
  font-family: $font;
  font-weight: $weight;
  font-style: $style;
}

EOT;
  $font = theme_get_setting('headings_font_face', $theme);
  $style = (strpos($font, 'italic')) ? 'italic' : 'normal';
  $weight = (preg_match('/[0-9]{3}/', $font, $matches)) ? $matches[0] : '400';
  $font = _glazed_get_font($font, $theme);
  $selector = theme_get_setting('headings_font_face_selector', $theme);
  $CSS .= <<<EOT

$selector {
  font-family: $font;
  font-weight: $weight;
  font-style: $style;
}

EOT;
  $font = theme_get_setting('nav_font_face', $theme);
  $style = (strpos($font, 'italic')) ? 'italic' : 'normal';
  $weight = (preg_match('/[0-9]{3}/', $font, $matches)) ? $matches[0] : '400';
  $font = _glazed_get_font($font, $theme);
  $selector = theme_get_setting('nav_font_face_selector', $theme);
  $CSS .= <<<EOT

$selector {
  font-family: $font;
  font-weight: $weight;
  font-style: $style;
}

EOT;
  $font = theme_get_setting('sitename_font_face', $theme);
  $style = (strpos($font, 'italic')) ? 'italic' : 'normal';
  $weight = (preg_match('/[0-9]{3}/', $font, $matches)) ? $matches[0] : '400';
  $font = _glazed_get_font($font, $theme);
  $selector = theme_get_setting('sitename_font_face_selector', $theme);
  $CSS .= <<<EOT

$selector {
  font-family: $font;
  font-weight: $weight;
  font-style: $style;
}

EOT;
  $font = theme_get_setting('blockquote_font_face', $theme);
  $style = (strpos($font, 'italic')) ? 'italic' : 'normal';
  $weight = (preg_match('/[0-9]{3}/', $font, $matches)) ? $matches[0] : '400';
  $font = _glazed_get_font($font, $theme);
  $selector = theme_get_setting('blockquote_font_face_selector', $theme);
  $CSS .= <<<EOT

$selector {
  font-family: $font;
  font-weight: $weight;
  font-style: $style;
}


EOT;

}

/**
 * Provides Font Stack values for theme settings which are written to custom.css
 * @see font_list()
 * @param $attributes
 * @return string
 */
function _glazed_font_stack($font = 'helvetica') {
  $fonts = [
    'helvetica' => 'Arial, Helvetica, "Nimbus Sans L", "Liberation Sans", "FreeSans", sans-serif',
    'myriad' => '"myriad pro",myriad,calibri,arial,tahoma,verdana,sans-serif',
    'verdana' => 'Verdana, "Bitstream Vera Sans", Arial, sans-serif',
    'lucida' => '"Lucida Sans Unicode", "Lucida Sans", "Lucida Grande", "DejaVu Sans", Arial, sans-serif',
    'geneva' => '"Geneva", "Bitstream Vera Serif", "Tahoma", sans-serif',
    'tahoma' => 'Tahoma, Geneva, "DejaVu Sans Condensed", sans-serif',
    'trebuchet' => '"Trebuchet MS",Trebuchet,Tahoma,arial,verdana,sans',
    'century' => '"Century Gothic", "URW Gothic L", Helvetica, Arial, sans-serif',
    'garamond' => 'Garamond,"Times New Roman",Times,Century,Cambria,Georgia,serif',
    'georgia' => 'Georgia, "Bitstream Vera Serif", serif',
    'palatino' => '"Palatino Linotype", "URW Palladio L", "Book Antiqua", "Palatino", serif',
    'times' => '"Free Serif", "Times New Roman", Times, serif',
  ];
  if (!isset($fonts[$font])) {
    $font = 'helvetica';
  }
  return $fonts[$font];
}

/**
 * Helper determine font type
 */
function _glazed_get_font($font, $theme) {
  if (substr($font, 0, 1) === '0') {
    $font = _glazed_format_google_font_name($font);
  }
  elseif (substr($font, 0, 1) === '1') {
    $font = _glazed_format_local_font_name($font);
  }
  else {
    $font = _glazed_font_stack($font);
  }
  return $font;
}


/**
 * Helper function to format the font name, following a pattern that seems
 * to hold for all google web fonts.
 */
function _glazed_format_google_font_name($key) {
  $font = explode(':', substr($key, 1));
  $font_basename = str_replace('+', ' ', $font[0]);
  $variant = $font[1];
  $goog_weight = '';
  switch ($variant) {
    case '100':
    case '100italic':
    case 'hairline':
    $goog_weight = ' Hairline';
    break;
    case '200':
    case '200italic':
    case 'extralight':
    $goog_weight = ' Extralight';
    break;
    case '300':
    case '300italic':
    case 'light':
    $goog_weight = ' Light';
    break;
    case '400':
    case '400italic':
    case 'regular':
    $goog_weight = ' Regular';
    break;
    case '500':
    case '500italic':
    case 'medium':
    $goog_weight = ' Medium';
    break;
    case '600':
    case '600italic':
    case 'semibold':
    $goog_weight = ' SemiBold';
    break;
    case '700':
    case '700italic':
    case 'bold':
    case 'bolditalic':
    $goog_weight = ' Bold';
    break;
    case '800':
    case '800italic':
    case 'extrabold':
    $goog_weight = ' Extrabold';
    break;
    case '900':
    case '900italic':
    case 'Black':
    $goog_weight = ' Black';
    break;
  }
  if (stristr($variant, 'italic') !== FALSE) {
    $goog_style = ' Italic';
  }
else {
    $goog_style = '';
  }

  $goog_name = '"' . $font_basename . $goog_weight . $goog_style . '", "' . $font_basename . '"';
  return $goog_name;
}

/**
 * Helper function to read the font name from the key. The hard part was already done in theme-settings.php
 */

function _glazed_format_local_font_name($key) {
  $font = explode('|', $key);
  $local_name = $font[2];
  return $local_name;
}
