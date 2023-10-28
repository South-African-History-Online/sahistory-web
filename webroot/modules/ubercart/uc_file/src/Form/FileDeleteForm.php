<?php

namespace Drupal\uc_file\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * Performs delete file action.
 */
class FileDeleteForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Delete file(s)?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a file will remove all its associated file downloads and product features. Removing a directory will remove any files it contains and their associated file downloads and product features.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('No');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('uc_file.downloads');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_file_deletion_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $file_ids = array_filter($form_state->getValue('file_select'));

    $form['file_ids'] = array('#type' => 'value', '#value' => $file_ids);
    $form['action'] = array('#type' => 'value', '#value' => $form_state->getValue(['uc_file_action', 'action']));

    $file_ids = _uc_file_sort_names(_uc_file_get_dir_file_ids($file_ids, FALSE));

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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

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

    $form_state->setRedirectUrl($this->getCancelUrl());
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
