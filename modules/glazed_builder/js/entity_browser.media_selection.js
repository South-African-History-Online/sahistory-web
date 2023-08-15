/**
 * @file entity_browser.modal_selection.js
 *
 * Propagates selected entities from modal display.
 */

(function(Drupal, drupalSettings, window) {

  'use strict';

  // @todo return false here if parent document does not contain active Glazed Builder editor
  var instance = false;
  var entities = {};
  if (drupalSettings.entity_browser.hasOwnProperty('modal')) {
    instance = drupalSettings.entity_browser.modal.uuid;
    entities = drupalSettings.entity_browser.modal.entities;
  }
  if (drupalSettings.entity_browser.hasOwnProperty('iframe')) {
    instance = drupalSettings.entity_browser.iframe.uuid;
    entities = drupalSettings.entity_browser.iframe.entities;
  }

  // Below selector only matches if target element is a glazed builder image
  // input, this ensures we don't muck up an EB selection for some FAPI widget
  var $input = parent.jQuery(parent.document).find('input.glazed-builder-image-input[data-uuid*=' + instance + ']');
  if ($input.length > 0) {
    var fileIds = [];
    for (var i = entities.length - 1; i >= 0; i--) {
      fileIds.push(entities[i][0]);
    }
    parent.jQuery.ajax({
      type: 'POST',
      url: parent.drupalSettings.glazedBuilder.glazedAjaxUrl,
      data: {
        action: 'glazed_builder_get_image_urls',
        fileIds: fileIds,
        imageStyle: $input.siblings('.glazed-builder-image-styles:first').val(),
      },
      cache: false,
    }).done(function(data) {
      // We need to access parent window, find correct image field and close media modal
      if ($input.hasClass('glazed-builder-multi-image-input')) {
        if ($input.val()) {
          $input.val($input.val() + ',' + data);
        }
        else {
          $input.val(data);
        }
      }
      else {
        $input.val(data);
      }
      $input.trigger('change');
      parent.jQuery(parent.document).find('#az-media-modal').remove();
      $input.removeAttr('data-uuid');
    }).fail(function(data) {
      alert(Drupal.t('Image selection failed, please make sure to select only image files'));
      parent.jQuery(parent.document).find('#az-media-modal').remove();
      $input.removeAttr('data-uuid');
    });
  }

}(Drupal, drupalSettings, window));
