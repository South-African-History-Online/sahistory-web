<?php

 /**
 * Convert hexdec color string to rgb(a) string
 * http://mekshq.com/how-to-convert-hexadecimal-color-code-to-rgb-or-rgba-using-php/
 */
function _glazed_hex2rgba($color, $opacity = null) {
  $default = 'rgb(0,0,0)';
  //Return default if no color provided
  if(empty($color)) {
    return $default;
  }
  //Sanitize $color if "#" is provided
  if ($color[0] == '#') {
    $color = substr( $color, 1 );
  }

  //Check if color has 6 or 3 characters and get values
  if (strlen($color) == 6) {
    $hex = [ $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] ];
  } elseif ( strlen( $color ) == 3 ) {
    $hex = [ $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] ];
  } else {
    return $default;
  }

  //Convert hexadec to rgb
  $rgb =  array_map('hexdec', $hex);

  //Check if opacity is set(rgba or rgb)
  if($opacity !== null){
    if(abs($opacity) > 1)
    $opacity = 1.0;
    $output = 'rgba('.implode(",",$rgb).','.$opacity.')';
  } else {
    $output = 'rgb('.implode(",",$rgb).')';
  }

  //Return rgb(a) color string
  return $output;
}

//  Header Vars
$align = theme_get_setting('header_side_align', $theme);
$header_side_width = theme_get_setting('header_side_width', $theme);
$header_top_height = theme_get_setting('header_top_height', $theme);
$header_top_height_scroll = theme_get_setting('header_top_height_scroll', $theme);
$header_top_opacity = $header_top_opacity_scroll = 1;
$CSS .= <<<EOT
  #glazed-main-menu,
  #glazed-main-menu .menu__breadcrumbs,
  #glazed-main-menu .menu__level {
    text-align: $align;
  }

EOT;
if ($align == 'right') {
  $CSS .= <<<EOT
  #glazed-main-menu .menu__link  {
    padding: 10px 70px 10px 20px;
  }

EOT;
}

$CSS .= <<<EOT
.glazed-header--side .glazed-main-menu {
  width: {$header_side_width}px;
}
.glazed-header--side .menu__level {
  width: {$header_side_width}px;
}

EOT;

if (theme_get_setting('header_position', $theme)) {
  $CSS .= <<<EOT
.body--glazed-nav-desktop.body--glazed-header-side #navbar.glazed-header {
  text-align: $align;
}
.body--glazed-nav-desktop.body--glazed-header-side {
  padding-left: {$header_side_width}px;
}
.body--glazed-nav-desktop .glazed-header--side {
  width: {$header_side_width}px;
}

EOT;

}
else {
  // Top Header
  if (isset($palette) && !empty($palette['header'])) {
    $headerbg = $palette['header'];
  }
  else {
    $headerbg = '#ffffff';
  }
  $header_top_rgba = $header_top_scroll_rgba = $header_top_scroll_rgba = $palette['header'];
  if (theme_get_setting('header_top_sticky', $theme) && theme_get_setting('header_top_sticky_show', $theme) == 'after_scroll') {
      $header_top_opacity = 0;
  }
  if (theme_get_setting('header_style', $theme) == 'overlay') {
    if ($palette) {
      $color = $palette['header'];
    }
    else {
      $color = '#ffffff';
    }
    $opacity = theme_get_setting('header_top_bg_opacity', $theme);
    $opacity_scroll = theme_get_setting('header_top_bg_opacity_scroll', $theme);
    $header_top_rgba = _glazed_hex2rgba($color, $opacity);
    $header_top_scroll_rgba = _glazed_hex2rgba($color, $opacity_scroll);
  }
  $CSS .= <<<EOT

.body--glazed-nav-desktop #navbar.glazed-header--top {
  background-color: $header_top_rgba;
  opacity: $header_top_opacity;
}
.body--glazed-nav-desktop #navbar.glazed-header--top.affix {
  background-color: $header_top_scroll_rgba;
  opacity: $header_top_opacity_scroll;
}

.body--glazed-nav-desktop .glazed-header--top .nav > li > a,
.body--glazed-nav-desktop .glazed-header--top .wrap-branding,
.body--glazed-nav-desktop .glazed-header--top .block-glazed-helper {
  height: {$header_top_height}px;
  line-height: {$header_top_height}px;
}
.body--glazed-nav-desktop .glazed-header--top .glazed-main-menu {
  height: {$header_top_height}px;
}
.body--glazed-nav-desktop .glazed-header--top #logo {
  max-height: {$header_top_height}px;
}
.body--glazed-nav-desktop .glazed-header--top.affix .nav > li > a,
.body--glazed-nav-desktop .glazed-header--top.affix .navbar-header .wrap-branding,
.body--glazed-nav-desktop .glazed-header--top.affix .block-glazed-helper  {
  height: {$header_top_height_scroll}px;
  line-height: {$header_top_height_scroll}px;
}
.body--glazed-nav-desktop .glazed-header--top.affix .glazed-main-menu {
  height: {$header_top_height_scroll}px;
}
.body--glazed-nav-desktop .glazed-header--top.affix #logo {
  max-height: {$header_top_height_scroll}px;
}
.body--glazed-nav-desktop .nav-child-fixed-width {
  top: {$header_top_height}px;
}
.body--glazed-nav-desktop .glazed-header--top.affix .nav-child-fixed-width {
  top: $header_top_height_scroll;
}

EOT;

  if (theme_get_setting('header_style', $theme) == 'overlay') {
    // For overlay header add a big gap in the top of the page title to make room for the header
    $CSS .= ".body--glazed-nav-desktop.body--glazed-header-overlay .page-title-full-width-container  { padding-top: " . ($header_top_height + 30) . "px }\n\n";
  }
  elseif (theme_get_setting('header_top_fixed', $theme)) {
    // For fixed headers create small padding to prevent fixed header from hiding toolbars
    $CSS .= ".body--glazed-nav-desktop.body--glazed-header-fixed.body--glazed-header-not-overlay { padding-top: " . $header_top_height . "px !important }\n\n";
    $CSS .= "  html.js .body--glazed-nav-desktop.toolbar-fixed.toolbar-horizontal.body--glazed-header-fixed.body--glazed-header-not-overlay { padding-top: " . ($header_top_height + 39) . "px !important }\n\n";
    $CSS .= "  html.js .body--glazed-nav-desktop.toolbar-fixed.toolbar-horizontal.toolbar-tray-open.toolbar-horizontal.body--glazed-header-fixed.body--glazed-header-not-overlay { padding-top: " . ($header_top_height + 80) . "px !important }\n\n";
  }

  if (theme_get_setting('header_style', $theme) == 'navbar_pull_down') {
    $CSS .= ".body--glazed-nav-desktop.body--glazed-header-not-overlay .glazed-header--top .col-sm-12 {\n margin-bottom: -" . ($header_top_height / 2) . "px; }\n\n";
  }


}

// Mobile Header
$header_mobile_height = theme_get_setting('header_mobile_height', $theme);
$CSS .= <<<EOT
.body--glazed-nav-mobile .glazed-header .navbar-header .wrap-branding,
.body--glazed-nav-mobile .glazed-header .navbar-toggle {
  height: {$header_mobile_height}px;
}
.body--glazed-nav-mobile .glazed-header .block-glazed-helper {
  height: {$header_mobile_height}px;
  line-height: {$header_mobile_height}px;
}
.body--glazed-nav-mobile .glazed-header #logo {
  max-height: {$header_mobile_height}px;
}

EOT;

if (theme_get_setting('sticky_footer', $theme)) {
  if (theme_get_setting('header_top_layout', $theme) == 'centered') {
    $header_top_height = 2 * $header_top_height;
  }
  if (theme_get_setting('header_style', $theme) == 'navbar_pull_down') {
    $header_top_height = $header_top_height / 2;
  }
  $CSS .= <<<EOT
.html--glazed-sticky-footer .body--glazed-nav-desktop .glazed-header--top {
  min-height: {$header_top_height}px;
}
.html--glazed-sticky-footer .body--glazed-nav-desktop .glazed-header--top.affix {
  min-height: {$header_top_height_scroll}px;
}
.html--glazed-sticky-footer .body--glazed-nav-mobile .glazed-header {
  min-height: {$header_mobile_height}px;
}
.html--glazed-sticky-footer .body--glazed-nav-mobile .glazed-header.affix {
  min-height: {$header_mobile_height}px;
}

EOT;
}

/*
 * MENU OPTIONS
*/

if (theme_get_setting('menu_type', $theme) == 'uppercase') {
  $CSS .= <<<EOT
.glazed-header .menu a {
  text-transform: uppercase;
}

EOT;
}
elseif (theme_get_setting('menu_type', $theme) == 'bold') {
  $CSS .= <<<EOT
.glazed-header .menu a {
  font-weight: bold;
}

EOT;
}
elseif (theme_get_setting('menu_type', $theme) == 'lead') {
  $CSS .= <<<EOT
.glazed-header .menu a ,
.glazed-header .menu span {
  font-size: 21px;
  font-weight: 300;
}

EOT;
}

// We only add border on header side if this is the desktop view too
// To prevent header-top configuration and spacing to look broken in mobile menus
if (theme_get_setting('menu_hover', $theme) == 'border') {
  $CSS .= <<<EOT
.body--glazed-header-side .glazed-header--side.glazed-header--hover-border .menu a::before,
.glazed-header--top.glazed-header--hover-border .nav > li > a::before {

EOT;
  if ($height = theme_get_setting('menu_border_size', $theme)) {
    $CSS .= "  height: {$height}px; \n";
  }
  if ($position = theme_get_setting('menu_border_position', $theme)) {
    $CSS .= "  {$position}: 0; \n";
    if (($margin = theme_get_setting('menu_border_position_offset', $theme)) &&  (theme_get_setting('header_position', $theme) == FALSE)) {
      $CSS .= "  margin-{$position}: {$margin}px; \n";
    }
  }
  if ($value = theme_get_setting('menu_border_color', $theme)) {
    if (theme_get_setting('menu_border_color', $theme) == 'custom') {
      $value = theme_get_setting('menu_border_color_custom', $theme);
    }
    elseif (isset($palette[$value])) {
      $value = $palette[$value];
    }
    $CSS .= "  background-color:  $value; \n";
  }
  if ($animation = theme_get_setting('menu_border_animate', $theme)) {
    $CSS .= "  transition: transform 300ms; \n";
    switch ($animation) {
      case 'ltr':
        $CSS .= "  transform-origin: 0 50%; \n";
        break;
      case 'rtl':
        $CSS .= "  transform-origin: 100% 50%; \n";
        break;
      case 'drop':
        $CSS .= "  transform: scale(0, 0) translate(10px, -10px); \n";
        $CSS .= "  transform-origin: 50% 0; \n";
        break;
    }
  }
  $CSS .= "\n } \n\n";
}

if (($position = theme_get_setting('menu_border_position', $theme))
  && ($margin_sticky = theme_get_setting('menu_border_position_offset_sticky', $theme))
  &&  (theme_get_setting('header_position', $theme) == FALSE)) {
  $CSS .= <<<EOT
.glazed-header--top.glazed-header--hover-border.affix .nav > li > a::before {
  margin-{$position}: {$margin_sticky}px;
}
EOT;
}

// Custom Colors
if ($navbarbg = theme_get_setting('navbar_background', $theme)) {
  if (theme_get_setting('navbar_background', $theme) == 'custom') {
    $navbarbg = theme_get_setting('navbar_background_custom', $theme);
  }
  elseif (isset($palette[$navbarbg])) {
    $navbarbg = $palette[$navbarbg];
  }
  $CSS .= ".html #navbar.glazed-header--top .container-row > .col-sm-12 { background-color: $navbarbg }\n\n";
}
if ($blockbg = theme_get_setting('header_block_background', $theme)) {
  if (theme_get_setting('header_block_background', $theme) == 'custom') {
    $blockbg = theme_get_setting('header_block_background_custom', $theme);
  }
  elseif (isset($palette[$blockbg])) {
    $blockbg = $palette[$blockbg];
  }
  $CSS .= "#navbar.glazed-header--top .block { background-color: $blockbg }\n\n";
}
if ($blocktxt = theme_get_setting('header_block_text_color', $theme)) {
  if (theme_get_setting('header_block_text_color', $theme) == 'custom') {
    $blocktxt = theme_get_setting('header_block_text_color_custom', $theme);
  }
  elseif (isset($palette[$blocktxt])) {
    $blocktxt = $palette[$blocktxt];
  }
  $CSS .= "#navbar.glazed-header--top .block, #navbar.glazed-header--top .block a, #navbar.glazed-header--top .block .btn-link { color: $blocktxt }\n\n";
}
if ($menubg = theme_get_setting('menu_background', $theme)) {
  if (theme_get_setting('menu_background', $theme) == 'custom') {
    $menubg = theme_get_setting('menu_background_custom', $theme);
  }
  elseif (isset($palette[$menubg])) {
    $menubg = $palette[$menubg];
  }
  $CSS .= "#navbar.glazed-header--top .menu { background-color: $menubg }\n\n";
}
if ($menutxt = theme_get_setting('menu_text_color', $theme)) {
  if (theme_get_setting('menu_text_color', $theme) == 'custom') {
    $menutxt = theme_get_setting('menu_text_color_custom', $theme);
  }
  elseif (isset($palette[$menutxt])) {
    $menutxt = $palette[$menutxt];
  }
  $CSS .= "#navbar.glazed-header--top .menu, #navbar.glazed-header--top .menu a { color: $menutxt }\n\n";
}
if ($menuhoverbg = theme_get_setting('menu_hover_background', $theme)) {
  if (theme_get_setting('menu_hover_background', $theme) == 'custom') {
    $menuhoverbg = theme_get_setting('menu_hover_background_custom', $theme);
  }
  elseif (isset($palette[$menuhoverbg])) {
    $menuhoverbg = $palette[$menuhoverbg];
  }
  $CSS .= "#navbar.glazed-header--top .menu a:hover, #navbar.glazed-header--top .menu a:focus { background-color: $menuhoverbg }\n\n";
}
if ($menuhovertxt = theme_get_setting('menu_hover_text_color', $theme)) {
  if (theme_get_setting('menu_hover_text_color', $theme) == 'custom') {
    $menuhovertxt = theme_get_setting('menu_hover_text_color_custom', $theme);
  }
  elseif (isset($palette[$menuhovertxt])) {
    $menuhovertxt = $palette[$menuhovertxt];
  }
  $CSS .= "#navbar.glazed-header--top .menu a:hover, #navbar.glazed-header--top .menu a:focus { color: $menuhovertxt }\n\n";
}
if ($dropdownbg = theme_get_setting('dropdown_background', $theme)) {
  if (theme_get_setting('dropdown_background', $theme) == 'custom') {
    $dropdownbg = theme_get_setting('dropdown_background_custom', $theme);
  }
  elseif (isset($palette[$dropdownbg])) {
    $dropdownbg = $palette[$dropdownbg];
  }
  $CSS .= "#navbar.glazed-header--top .dropdown-menu { background-color: $dropdownbg }\n\n";
}
if ($dropdowntxt = theme_get_setting('dropdown_text_color', $theme)) {
  if (theme_get_setting('dropdown_text_color', $theme) == 'custom') {
    $dropdowntxt = theme_get_setting('dropdown_text_color_custom', $theme);
  }
  elseif (isset($palette[$dropdowntxt])) {
    $dropdowntxt = $palette[$dropdowntxt];
  }
  $CSS .= "#navbar.glazed-header--top .dropdown, #navbar.glazed-header--top .dropdown-menu a { color: $dropdowntxt }\n\n";
}
if ($dropdownhoverbg = theme_get_setting('dropdown_hover_background', $theme)) {
  if (theme_get_setting('dropdown_hover_background', $theme) == 'custom') {
    $dropdownhoverbg = theme_get_setting('dropdown_hover_background_custom', $theme);
  }
  elseif (isset($palette[$dropdownhoverbg])) {
    $dropdownhoverbg = $palette[$dropdownhoverbg];
  }
  $CSS .= "#navbar.glazed-header--top .dropdown-menu a:hover,#navbar.glazed-header--top .dropdown-menu a:focus { background-color: $dropdownhoverbg }\n\n";
}
if ($dropdownhovertxt = theme_get_setting('dropdown_hover_text_color', $theme)) {
  if (theme_get_setting('dropdown_hover_text_color', $theme) == 'custom') {
    $dropdownhovertxt = theme_get_setting('dropdown_hover_text_color_custom', $theme);
  }
  elseif (isset($palette[$dropdownhovertxt])) {
    $dropdownhovertxt = $palette[$dropdownhovertxt];
  }
  $CSS .= "#navbar.glazed-header--top .dropdown-menu a:hover,#navbar.glazed-header--top .dropdown-menu a:focus { color: $dropdownhovertxt }\n\n";
}