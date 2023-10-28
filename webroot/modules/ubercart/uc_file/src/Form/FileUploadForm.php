<?php

namespace Drupal\uc_file\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Performs a file upload action.
 */
class FileUploadForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Upload file');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Upload file');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
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
    return 'uc_file_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
    $files = db_query('SELECT * FROM {uc_files}');
    foreach ($files as $file) {
      if (is_dir($this->config('uc_file.settings')->get('base_dir') . '/' . $file->filename)) {
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
      '#multiple' => TRUE,
      '#description' => $this->t('You may select more than one file by holding down the Cntrl key when you click the file name. The maximum file size that can be uploaded is %size bytes. You will need to use a different method to upload the file to the directory (e.g. (S)FTP, SCP) if your file exceeds this size. Files you upload using one of these alternate methods will be automatically detected.', ['%size' => number_format($max_bytes)]),
    );

    //$form['#attributes']['class'][] = 'foo';
    //$form['#attributes']['enctype'] = 'multipart/form-data';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $module_handler = \Drupal::moduleHandler();
    $hooks = $module_handler->getImplementations('uc_file_action');

    // Upload the files and get their objects.
    $temp_files = file_save_upload('upload', array('file_validate_extensions' => array()));
    foreach ($temp_files as $temp_file) {
      // Invoke any implemented hook_uc_file_action('upload_validate', $args).
      foreach ($hooks as $module) {
        $name = $module . '_uc_file_action';
        $name('upload_validate', array('file_object' => $temp_file, 'form_id' => $form_id, 'form_state' => $form_state));
      }
    }

    // Save the uploaded file for later processing.
    $form_state->set('temp_files', $temp_files);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Build the destination location. We start with the base directory,
    // then add any directory which was explicitly selected.
    $dir = $this->config('uc_file.settings')->get('base_dir') . '/' . $form_state->getValue('upload_dir');
    if (is_dir($dir)) {

      // Retrieve our uploaded files.
      $file_objects = $form_state->get('temp_files');
      foreach ($file_objects as $file_object) {
        // Copy the file to its final location.
        if (copy($file_object->getFileUri(), $dir . '/' . $file_object->getFilename())) {

          // Check if any hook_uc_file_action('upload', $args) are implemented
          $module_handler = \Drupal::moduleHandler();
          foreach ($module_handler->getImplementations('uc_file_action') as $module) {
            $name = $module . '_uc_file_action';
            $name('upload', array('file_object' => $file_object, 'form_id' => $form_id, 'form_state' => $form_state));
          }

          // Update the file list
          uc_file_refresh();

          drupal_set_message($this->t('The file %file has been uploaded to %dir', ['%file' => $file_object->getFilename(), '%dir' => $dir]));
        }
        else {
          drupal_set_message($this->t('An error occurred while copying the file to %dir', ['%dir' => $dir]), 'error');
        }
      }
    }
    else {
      drupal_set_message($this->t('Can not move file to %dir', ['%dir' => $dir]), 'error');
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
