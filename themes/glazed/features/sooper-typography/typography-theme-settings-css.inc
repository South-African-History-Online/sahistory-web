<?php

$CSS .= "body, p, .lead { line-height:  " . theme_get_setting('body_line_height', $theme) . " } \n";
$CSS .= "body { font-size:  " . theme_get_setting('body_font_size', $theme) . "px } \n";
if (theme_get_setting('menu_type', $theme) != 'lead') {
  $CSS .= ".glazed-header--top #glazed-main-menu .nav > li > a, .glazed-header--side #glazed-main-menu .nav a, .body--glazed-nav-desktop .glazed-header--top .nav .dropdown-menu li>a { font-size:  " . theme_get_setting('nav_font_size', $theme) . "px } \n";
}
$CSS .= "h1,h2,h3,h4,h5,h6 { line-height:  " . theme_get_setting('headings_line_height', $theme) . "; } \n";
$CSS .= "h1, .html #page-title .page-title { font-size:  " . theme_get_setting('h1_font_size', $theme) . "px; } \n";
$CSS .= "h2 { font-size:  " . theme_get_setting('h2_font_size', $theme) . "px; } \n";
$CSS .= "h3 { font-size:  " . theme_get_setting('h3_font_size', $theme) . "px; } \n";
$CSS .= "h4 { font-size:  " . theme_get_setting('h4_font_size', $theme) . "px; } \n";
$CSS .= "blockquote, .blockquote p { font-size:  " . theme_get_setting('blockquote_font_size', $theme) . "px } \n";

if ($letter_spacing = theme_get_setting('headings_letter_spacing', $theme)) {
  $CSS .= "h1,h2,h3,h4,h5,h6 { letter-spacing:  " . $letter_spacing . "em; } \n";
}
if (theme_get_setting('headings_uppercase', $theme)) {
  $CSS .= "h1,h2,h3,h4,h5,h6 { text-transform: uppercase; } \n";
}
if ($height = theme_get_setting('divider_thickness', $theme)) {
  $CSS .= "hr { height:  " . $height . "px; } \n";
}
if ($length = theme_get_setting('divider_length', $theme)) {
  $CSS .= "hr { width:  " . $length . "px; } \n";
}
$position = theme_get_setting('divider_position', $theme);
switch ($position) {
  case 1:
    $CSS .= "hr { margin-left: 0;margin-right: auto; }\n";
    break;
  case 2:
    $CSS .= "hr { margin-left: auto;margin-right: auto; }\n";
    break;
  case 3:
    $CSS .= "hr { margin-left: auto;margin-right: 0; }\n";
    break;
}

if ($value = theme_get_setting('divider_color', $theme)) {
  if (theme_get_setting('divider_color', $theme) == 'custom') {
    $value = theme_get_setting('divider_color_custom', $theme);
  }
  elseif (isset($palette[$value])) {
    $value = $palette[$value];
  }
  $CSS .= "hr {  background-color:  $value; } \n";
}


$CSS .= "@media screen and (max-width: 768px) { \n";
  $CSS .= "  body, p { font-size:  " . theme_get_setting('body_mobile_font_size', $theme) . "px; } \n";
  if (theme_get_setting('menu_type', $theme) != 'lead') {
    $CSS .= "  .glazed-header--top #glazed-main-menu .nav > li > a, .glazed-header--side #glazed-main-menu .nav a, .body--glazed-nav-desktop .glazed-header--top .nav .dropdown-menu li>a { font-size:  " . theme_get_setting('nav_mobile_font_size', $theme) . "px } \n";
  }
  $CSS .= "  h1, .html #page-title .page-title { font-size:  " . theme_get_setting('h1_mobile_font_size', $theme) . "px; } \n";
  $CSS .= "  h2 { font-size:  " . theme_get_setting('h2_mobile_font_size', $theme) . "px; } \n";
  $CSS .= "  h3 { font-size:  " . theme_get_setting('h3_mobile_font_size', $theme) . "px; } \n";
  $CSS .= "  blockquote, .blockquote p { font-size:  " . theme_get_setting('blockquote_mobile_font_size', $theme) . "px; } \n";
$CSS .= "} \n\n";