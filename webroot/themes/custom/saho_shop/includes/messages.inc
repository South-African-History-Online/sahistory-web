<?php

use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Html;

/**
 * Implements hook_preprocess_HOOK() for status_messages.
 */
function saho_shop_preprocess_status_messages(&$variables) {
  $variables['title_ids'] = [];
  foreach ($variables['message_list'] as $message_type => $messages) {
    $variables['title_ids'][$message_type] = Html::getUniqueId("message-$message_type-title");
  }
  // Add classes to toast wrappers
  $variables['wrapper_attributes'] = new Attribute($variables['attributes']);

  switch (theme_get_setting('toast_placement')) {
    case 'top_left':
      $variables['wrapper_attributes']->addClass(['top-0', 'start-0']);
      break;
    case 'top_center':
      $variables['wrapper_attributes']->addClass(['top-0', 'start-50', 'translate-middle-x']);
      break;
    case 'top_right':
      $variables['wrapper_attributes']->addClass(['top-0', 'end-0']);
      break;
    case 'middle_left':
      $variables['wrapper_attributes']->addClass(['top-50', 'start-0', 'translate-middle-y']);
      break;
    case 'middle_center':
      $variables['wrapper_attributes']->addClass(['top-50', 'start-50', 'translate-middle-x']);
      break;
    case 'middle_right':
      $variables['wrapper_attributes']->addClass(['top-50', 'end-0', 'translate-middle-y']);
      break;
    case 'bottom_left':
      $variables['wrapper_attributes']->addClass(['bottom-0', 'start-0']);
      break;
    case 'bottom_center':
      $variables['wrapper_attributes']->addClass(['bottom-0', 'start-50', 'translate-middle-x']);
      break;
    case 'bottom_right':
      $variables['wrapper_attributes']->addClass(['bottom-0', 'end-0']);
      break;
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter() for status messages templates.
 */
function saho_shop_theme_suggestions_status_messages_alter(array &$suggestions, array $variables) {
  switch (theme_get_setting('message_type')) {
    case 'toasts':
      $suggestions[] = 'status_messages__toasts';
      break;
    case 'color_toasts':
      $suggestions[] = 'status_messages__color_toasts';
      break;
  }
}
