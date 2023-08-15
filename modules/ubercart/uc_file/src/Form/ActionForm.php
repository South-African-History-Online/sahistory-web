<?php

namespace Drupal\uc_file\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;

/**
 * Performs file action (upload, delete, hooked in actions).
 */
class ActionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_file_admin_files_form_action';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $file_ids = array_filter($form_state->getValue('file_select'));

    $form['file_ids'] = array('#type' => 'value', '#value' => $file_ids);
    $form['action'] = array('#type' => 'value', '#value' => $form_state->getValue(['uc_file_action', 'action']));

    $file_ids = _uc_file_sort_names(_uc_file_get_dir_file_ids($file_ids, FALSE));

    switch ($form_state->getValue(['uc_file_action', 'action'])) {
      case 'uc_file_delete':
        $affected_list = $this->buildJsFileDisplay($file_ids);

        $has_directory = FALSE;
        foreach ($file_ids as $file_id) {

          // Gather a list of user-selected filenames.
          $file = uc_file_get_by_id($file_id);
          $filename = $file->filename;
          $file_list[] = (substr($filename, -1) == "/") ? $filename . ' (' . $this->t('directory') . ')' : $filename;

          // Determine if there are any directories in this list.
          $path = uc_file_qualify_file($filename);
          if (is_dir($path)) {
            $has_directory = TRUE;
          }
        }

        // Base files/dirs the user selected.
        $form['selected_files'] = array(
          '#theme' => 'item_list',
          '#items' => $file_list,
          '#attributes' => array(
            'class' => array('selected-file-name'),
          ),
        );

        $form = confirm_form(
          $form, $this->t('Delete file(s)'),
          'admin/store/products/files',
          $this->t('Deleting a file will remove all its associated file downloads and product features. Removing a directory will remove any files it contains and their associated file downloads and product features.'),
          $this->t('Delete affected files'), $this->t('Cancel')
        );

        // Don't even show the recursion checkbox unless we have any directories.
        if ($has_directory && $affected_list[TRUE] !== FALSE ) {
          $form['recurse_directories'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Delete selected directories and their sub directories'),
          );

          // Default to FALSE. Although we have the JS behavior to update with the
          // state of the checkbox on load, this should improve the experience of
          // users who don't have JS enabled over not defaulting to any info.
          $form['affected_files'] = array(
            '#theme' => 'item_list',
            '#items' => $affected_list[FALSE],
            '#title' => $this->t('Affected files'),
            '#attributes' => array(
              'class' => array('affected-file-name'),
            ),
          );
        }
        break;

      case 'uc_file_upload':
        // Calculate the max size of uploaded files, in bytes.
        $max_bytes = trim(ini_get('post_max_size'));
        switch (strtolower($max_bytes{strlen($max_bytes)-1})) {
          case 'g':
            $max_bytes *= 1024;
          case 'm':
            $max_bytes *= 1024;
          case 'k':
            $max_bytes *= 1024;
        }

        // Gather list of directories under the selected one(s).
        // '/' is always available.
        $directories = array('' => '/');
        $files = db_query("SELECT * FROM {uc_files}");
        foreach ($files as $file) {
          if (is_dir($this->config('uc_file.settings')->get('base_dir') . "/" . $file->filename)) {
            $directories[$file->filename] = $file->filename;
          }
        }

        $form['upload_dir'] = array(
          '#type' => 'select',
          '#title' => $this->t('Directory'),
          '#description' => $this->t('The directory on the server where the file should be put. The default directory is the root of the file downloads directory.'),
          '#options' => $directories,
        );
        $form['upload'] = array(
          '#type' => 'file',
          '#title' => $this->t('File'),
          '#description' => $this->t('The maximum file size that can be uploaded is %size bytes. You will need to use a different method to upload the file to the directory (e.g. (S)FTP, SCP) if your file exceeds this size. Files you upload using one of these alternate methods will be automatically detected.', ['%size' => number_format($max_bytes)]),
        );

        $form['#attributes']['class'][] = 'foo';
        $form = confirm_form(
          $form, $this->t('Upload file'),
          'admin/store/products/files',
          '',
          $this->t('Upload file'), $this->t('Cancel')
        );

        // Must add this after confirm_form, as it runs over $form['#attributes'].
        // Issue logged at d#319723
        $form['#attributes']['enctype'] = 'multipart/form-data';
        break;

      default:
        // This action isn't handled by us, so check if any
        // hook_uc_file_action('form', $args) are implemented.
        $module_handler = \Drupal::moduleHandler();
        foreach ($module_handler->getImplementations('uc_file_action') as $module) {
          $name = $module . '_uc_file_action';
          $result = $name('form', array('action' => $form_state->getValue(['uc_file_action', 'action']), 'file_ids' => $file_ids));
          $form = (is_array($result)) ? array_merge($form, $result) : $form;
        }
        break;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    switch ($form_state->getValue('action')) {

      case 'uc_file_upload':

        // Upload the file and get its object.
        if ($temp_file = file_save_upload('upload', array('file_validate_extensions' => array()))) {

          // Check if any hook_uc_file_action('upload_validate', $args)
          // are implemented.
          $module_handler = \Drupal::moduleHandler();
          foreach ($module_handler->getImplementations('uc_file_action') as $module) {
            $name = $module . '_uc_file_action';
            $name('upload_validate', array('file_object' => $temp_file, 'form_id' => $form_id, 'form_state' => $form_state));
          }

          // Save the uploaded file for later processing.
          $form_state->set('temp_file', $temp_file);
        }
        else {
          $form_state->setErrorByName('', $this->t('An error occurred while uploading the file'));
        }

        break;

      default:

        // This action isn't handled by us, so check if any
        // hook_uc_file_action('validate', $args) are implemented
        $module_handler = \Drupal::moduleHandler();
        foreach ($module_handler->getImplementations('uc_file_action') as $module) {
          $name = $module . '_uc_file_action';
          $name('validate', array('form_id' => $form_id, 'form_state' => $form_state));
        }

        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    switch ($form_state->getValue('action')) {

      case 'uc_file_delete':

        // File deletion status.
        $status = TRUE;

        // Delete the selected file(s), with recursion if selected.
        $status = uc_file_remove_by_id($form_state->getValue('file_ids'), !$form_state->isValueEmpty('recurse_directories')) && $status;

        if ($status) {
          drupal_set_message($this->t('The selected file(s) have been deleted.'));
        }
        else {
          drupal_set_message($this->t('One or more files could not be deleted.'), 'warning');
        }

        break;

      case 'uc_file_upload':

        // Build the destination location. We start with the base directory,
        // then add any directory which was explicitly selected.
        $dir = $this->config('uc_file.settings')->get('base_dir') . '/' . $form_state->getValue('upload_dir');
        if (is_dir($dir)) {

          // Retrieve our uploaded file.
          $file_object = $form_state->get('temp_file');

          // Copy the file to its final location.
          if (copy($file_object->uri, $dir . '/' . $file_object->filename)) {

            // Check if any hook_uc_file_action('upload', $args) are implemented
            $module_handler = \Drupal::moduleHandler();
            foreach ($module_handler->getImplementations('uc_file_action') as $module) {
              $name = $module . '_uc_file_action';
              $name('upload', array('file_object' => $file_object, 'form_id' => $form_id, 'form_state' => $form_state));
            }

            // Update the file list
            uc_file_refresh();

            drupal_set_message($this->t('The file %file has been uploaded to %dir', ['%file' => $file_object->filename, '%dir' => $dir]));
          }
          else {
            drupal_set_message($this->t('An error occurred while copying the file to %dir', ['%dir' => $dir]), 'error');
          }
        }
        else {
          drupal_set_message($this->t('Can not move file to %dir', ['%dir' => $dir]), 'error');
        }

        break;

      default:

        // This action isn't handled by us, so check if any
        // hook_uc_file_action('submit', $args) are implemented
        $module_handler = \Drupal::moduleHandler();
        foreach ($module_handler->getImplementations('uc_file_action') as $module) {
          $name = $module . '_uc_file_action';
          $name('submit', array('form_id' => $form_id, 'form_state' => $form_state));
        }
        break;
    }

    // Return to the original form state.
    $form_state->setRebuild(FALSE);
    $this->redirect('uc_file.downloads');
  }

  /**
   * TODO: Replace with == operator?
   */
  protected function displayArraysEquivalent($recur, $no_recur) {

    // Different sizes.
    if (count($recur) != count($no_recur)) {
      return FALSE;
    }

    // Check the elements.
    for ($i = 0; $i < count($recur); $i++) {
      if ($recur[$i] != $no_recur[$i]) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Shows all possible files in selectable list.
   */
  protected function buildJsFileDisplay($file_ids) {

    // Gather the files if recursion IS selected.
    // Get 'em all ready to be punched into the file list.
    $recursion_file_ids = _uc_file_sort_names(_uc_file_get_dir_file_ids($file_ids, TRUE));
    foreach ($recursion_file_ids as $file_id) {
      $file = uc_file_get_by_id($file_id);
      $recursion[] = '<li>' . $file->filename . '</li>';
    }

    // Gather the files if recursion ISN'T selected.
    // Get 'em all ready to be punched into the file list.
    $no_recursion_file_ids = $file_ids;
    foreach ($no_recursion_file_ids as $file_id) {
      $file = uc_file_get_by_id($file_id);
      $no_recursion[] = '<li>' . $file->filename . '</li>';
    }

    // We'll disable the recursion checkbox if they're equal.
    $equivalent = $this->displayArraysEquivalent($recursion_file_ids, $no_recursion_file_ids);

    // The list to show if the recursion checkbox is $key.
    $result[TRUE] = $equivalent ? FALSE : $recursion;
    $result[FALSE] = $no_recursion;

    // Set up some JS to dynamically update the list based on the
    // recursion checkbox state.
    drupal_add_js('uc_file_list[false] = ' . Json::encode('<li>' . implode('</li><li>', $no_recursion) . '</li>') . ';', 'inline');
    drupal_add_js('uc_file_list[true] = ' . Json::encode('<li>' . implode('</li><li>', $recursion) . '</li>') . ';', 'inline');

    return $result;
  }

}
