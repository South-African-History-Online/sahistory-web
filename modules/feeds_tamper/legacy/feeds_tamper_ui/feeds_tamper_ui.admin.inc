<?php

/**
 * @file
 * Forms and their accompanying validation and submit functions for Feeds Tamper
 * UI.
 */

/**
 * Helper functions for ajax.
 */
function feeds_tamper_machine_name_callback($id, $form, $form_state) {
  // Importer id's are machine safe.
  $importer_id = $form_state['importer']->id;
  $source = feeds_tamper_make_machine($form_state['source']);
  return feeds_tamper_load_instance("$importer_id-$source-$id");
}

/**
 * Ajax callback for add plugin form.
 */
function feeds_tamper_ajax_callback($form, $form_state) {
  // The form has already been submitted and updated. We can return the replaced
  // item as it is.
  return $form['plugin'];
}
