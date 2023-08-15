<?php

if ((theme_get_setting('block_preset')) && ($regions = array_filter(theme_get_setting('block_design_regions')))) {
  // Block Styling
  $border_width = theme_get_setting('block_border', $theme);
  ($border_width) ? $border_style = 'solid' : $border_style = 'none';
  $border_radius = theme_get_setting('block_border_radius', $theme);

  $selector = _glazed_regions2selectors($regions, '.block');
  $CSS .= <<<EOT
$selector {
  border-width: {$border_width}px;
  border-style: $border_style;
  border-radius: {$border_radius}px;

EOT;
  if ($value = theme_get_setting('block_padding', $theme)) {
    $CSS .= "  padding:  {$value}px; \n";
  }

  if ($value = theme_get_setting('block_background', $theme)) {
    if (theme_get_setting('block_background', $theme) == 'custom') {
      $value = theme_get_setting('block_background_custom', $theme);
    }
    elseif (isset($palette[$value])) {
      $value = $palette[$value];
    }
    $CSS .= "  background-color:  $value; \n";
  }

  if ($value = theme_get_setting('block_border_color', $theme)) {
    if (theme_get_setting('block_border_color', $theme) == 'custom') {
      $value = theme_get_setting('block_border_color_custom', $theme);
    }
    elseif (isset($palette[$value])) {
      $value = $palette[$value];
    }
    $CSS .= "  border-color:  $value; \n";
  }
  $CSS .= "} \n\n";

  // Block Title Styling
  $selector = _glazed_regions2selectors($regions, '.block-title');

  $font_size = theme_get_setting('title_font_size', $theme);
  $font_size_mobile = theme_get_setting($font_size . '_mobile_font_size', $theme);
  $font_size = theme_get_setting($font_size . '_font_size', $theme);
  $align = theme_get_setting('title_align', $theme);
  $border_width = theme_get_setting('title_border', $theme);
  ($border_width) ? $border_style = 'solid' : $border_style = 'none';
  $border_radius = theme_get_setting('title_border_radius', $theme);
  (theme_get_setting('title_sticker', $theme)) ? $display = 'inline-block' : $display = 'block';

  $CSS .= <<<EOT
$selector {
  font-size: {$font_size}px;
  text-align: $align;
  border-width: {$border_width}px;
  border-style: $border_style;
  border-radius: {$border_radius}px;
  display: $display;

EOT;

  if ($value = theme_get_setting('title_padding', $theme)) {
    $CSS .= "  padding:  {$value}px; \n";
  }

  if ($value = theme_get_setting('title_background', $theme)) {
    if (theme_get_setting('title_background', $theme) == 'custom') {
      $value = theme_get_setting('title_background_custom', $theme);
    }
    elseif (isset($palette[$value])) {
      $value = $palette[$value];
    }
    $CSS .= "  background-color:  $value; \n";
  }

  if ($value = theme_get_setting('title_border_color', $theme)) {
    if (theme_get_setting('title_border_color', $theme) == 'custom') {
      $value = theme_get_setting('title_border_color_custom', $theme);
    }
    elseif (isset($palette[$value])) {
      $value = $palette[$value];
    }
    $CSS .= "  border-color:  $value; \n";
  }
  $CSS .= "} \n\n";

$CSS .= "@media screen and (max-width: 768px) { \n";
  $CSS .= "  $selector { font-size: {$font_size_mobile}px; } \n";
$CSS .= "} \n\n";

  // Divider Styling
  if (theme_get_setting('block_divider') && theme_get_setting('block_divider_custom')) {
    $selector = _glazed_regions2selectors($regions, '.block-hr');
    if ($value = theme_get_setting('block_divider_color', $theme)) {
      if (theme_get_setting('block_divider_color', $theme) == 'custom') {
        $value = theme_get_setting('block_divider_color_custom', $theme);
      }
      elseif (isset($palette[$value])) {
        $value = $palette[$value];
      }
      $CSS .= "$selector {  background-color:  $value; } \n";
    }

    if (theme_get_setting('block_divider_custom') && ($height = theme_get_setting('block_divider_thickness', $theme))) {
      if ($width = theme_get_setting('block_divider_length', $theme)) {
        $width = $width;
      }
      else {
        $width = '100%';
      }
      $margin = theme_get_setting('block_divider_spacing', $theme);
      $CSS .= <<<EOT
$selector {
  height: {$height}px;
  width: {$width}px;
  margin-top: {$margin}px;
  margin-bottom: {$margin}px;
}

EOT;
    }

  }

}

function _glazed_regions2selectors($regions, $element) {
  $selector_array = [];
  foreach ($regions as $region) {
    $selector_array[] .= '.region-' . str_replace('_', '-', $region) . ' ' . $element;
  }
  $selector_array[] = '.blocks-preview ' . $element;
  $selector = implode(', ', $selector_array);
  return $selector;
}
