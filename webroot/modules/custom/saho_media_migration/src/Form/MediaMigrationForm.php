<?php

namespace Drupal\saho_media_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for uploading a CSV file and triggering the media migration.
 */
class MediaMigrationForm extends FormBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The media migration service.
   *
   * @var \Drupal\saho_media_migration\Service\MediaMigrationService
   */
  protected $migrationService;

  /**
   * Constructs a new MediaMigrationForm object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\saho_media_migration\Service\MediaMigrationService $migration_service
   *   The media migration service.
   */
  public function __construct(
    FileSystemInterface $file_system,
    $migration_service,
  ) {
    $this->fileSystem = $file_system;
    $this->migrationService = $migration_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file_system'),
      $container->get('saho_media_migration.migrator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saho_media_migration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<p>Upload a CSV file containing file usage data to migrate files to media entities.</p>'),
    ];

    $form['file_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('CSV File'),
      '#description' => $this->t('Upload a CSV file containing file usage data. The file should have the following columns: fid, uuid, filename, uri, filemime, uid, usage_count.'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Migration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure a file was uploaded.
    if (empty($form_state->getValue('file_upload'))) {
      $form_state->setErrorByName('file_upload', $this->t('Please upload a CSV file.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the uploaded file.
    $all_files = $this->getRequest()->files->get('files', []);
    if (empty($all_files['file_upload'])) {
      $this->messenger()->addError($this->t('No file was uploaded.'));
      return;
    }

    // Validate the file extension.
    $file_upload = $all_files['file_upload'];
    $filename = $file_upload->getClientOriginalName();
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    if (strtolower($extension) !== 'csv') {
      $this->messenger()->addError($this->t('Only CSV files are allowed.'));
      return;
    }

    // Save the file to a temporary location.
    $temp_file = 'temporary://' . $filename;
    $file_uri = $this->fileSystem->saveData(
      file_get_contents($file_upload->getRealPath()),
      $temp_file,
      FileSystemInterface::EXISTS_REPLACE
    );

    if (!$file_uri) {
      $this->messenger()->addError($this->t('Error saving the uploaded file.'));
      return;
    }

    // Get the real path of the file.
    $real_path = $this->fileSystem->realpath($file_uri);

    // Process the CSV file.
    $file_data = $this->migrationService->processCsvFile($real_path);

    if (empty($file_data)) {
      $this->messenger()->addError($this->t('No file data found in the CSV file.'));
      return;
    }

    // Create a batch process for the migration.
    $batch = $this->migrationService->createMigrationBatch($file_data);
    batch_set($batch);
  }

}