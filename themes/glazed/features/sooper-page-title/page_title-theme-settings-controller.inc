<?php

$page_title_animate = theme_get_setting('page_title_animate');
if ($page_title_animate) {
  $variables['#attached']['library'][] = 'glazed/glazed-animatecss-custom';
}
