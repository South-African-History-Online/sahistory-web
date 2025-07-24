<?php

namespace Drupal\saho_media_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileExists;
use Drupal\saho_media_migration\Service\MediaMigrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin form for SAHO Media Migration.
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
   */
  public function __construct(
    FileSystemInterface $file_system,
    MediaMigrationService $migration_service,
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
    $stats = $this->migrationService->getMigrationStats();

    $form['#tree'] = TRUE;

    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t('Migration Status'),
      '#open' => TRUE,
    ];

    $progress = $stats['migration_progress'];
    $form['status']['progress'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['class' => ['migration-progress']],
      '#value' => $this->t('<strong>Migration Progress: @progress%</strong>', ['@progress' => $progress]),
    ];

    $form['status']['stats'] = [
      '#type' => 'table',
      '#header' => [$this->t('Metric'), $this->t('Count')],
      '#rows' => [
        [$this->t('Total Files'), number_format($stats['total_files'])],
        [$this->t('Files with Media'), number_format($stats['files_with_media'])],
        [$this->t('Files Needing Migration'), number_format($stats['files_without_media'])],
        [$this->t('Used Files'), number_format($stats['used_files'])],
      ],
    ];

    $form['actions_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Quick Actions'),
      '#open' => TRUE,
    ];

    $form['actions_section']['generate_csv'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate CSV Mapping'),
      '#submit' => ['::generateCsv'],
      '#attributes' => ['class' => ['button--primary']],
    ];

    $form['actions_section']['migrate_used'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate Used Files'),
      '#submit' => ['::migrateUsedFiles'],
      '#attributes' => ['class' => ['button--primary']],
      '#disabled' => $stats['files_without_media'] === 0,
    ];

    $form['actions_section']['validate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Validate Migration'),
      '#submit' => ['::validateMigration'],
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced Options'),
      '#open' => FALSE,
    ];

    $form['advanced']['migration_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Migration Options'),
    ];

    $form['advanced']['migration_options']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#description' => $this->t('Maximum number of files to migrate (0 = no limit)'),
      '#default_value' => 1000,
      '#min' => 0,
      '#max' => 10000,
    ];

    $form['advanced']['migrate_advanced'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Advanced Migration'),
      '#submit' => ['::migrateAdvanced'],
      '#disabled' => $stats['files_without_media'] === 0,
    ];

    $form['advanced']['csv_import'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CSV Import'),
    ];

    $form['advanced']['csv_import']['file_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('CSV File'),
      '#description' => $this->t('Upload a CSV file containing file migration data.'),
    ];

    $form['advanced']['csv_import']['import_csv'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import from CSV'),
      '#submit' => ['::importCsv'],
    ];

    $form['#attached']['library'][] = 'saho_media_migration/admin';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Default submit handler.
  }

  /**
   * Submit handler for generating CSV mapping.
   */
  public function generateCsv(array &$form, FormStateInterface $form_state) {
    try {
      $filename = $this->migrationService->generateCsvMapping();
      $this->messenger()->addStatus($this->t('CSV mapping file generated: @file', ['@file' => $filename]));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('CSV generation failed: @error', ['@error' => $e->getMessage()]));
    }
  }

  /**
   * Submit handler for migrating used files.
   */
  public function migrateUsedFiles(array &$form, FormStateInterface $form_state) {
    $files_to_migrate = $this->migrationService->getFilesNeedingMigration(1000);

    if (empty($files_to_migrate)) {
      $this->messenger()->addWarning($this->t('No files found to migrate.'));
      return;
    }

    $count = count($files_to_migrate);
    $batch = $this->migrationService->createMigrationBatch($files_to_migrate);

    batch_set($batch);
    $this->messenger()->addStatus($this->t('Started migration of @count files.', ['@count' => $count]));
  }

  /**
   * Submit handler for advanced migration.
   */
  public function migrateAdvanced(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['advanced', 'migration_options']);
    $limit = (int) $values['limit'] ?: 10000;

    $files_to_migrate = $this->migrationService->getFilesNeedingMigration($limit);

    if (empty($files_to_migrate)) {
      $this->messenger()->addWarning($this->t('No files found to migrate with the selected criteria.'));
      return;
    }

    $count = count($files_to_migrate);
    $batch = $this->migrationService->createMigrationBatch($files_to_migrate);

    batch_set($batch);
    $this->messenger()->addStatus($this->t('Started advanced migration of @count files.', ['@count' => $count]));
  }

  /**
   * Submit handler for validation.
   */
  public function validateMigration(array &$form, FormStateInterface $form_state) {
    $results = $this->migrationService->validateMigration();

    $issues_found = FALSE;
    foreach ($results as $check => $result) {
      if ($result['status'] === 'pass') {
        $this->messenger()->addStatus($result['message']);
      }
      elseif ($result['status'] === 'warning') {
        $this->messenger()->addWarning($result['message']);
        $issues_found = TRUE;
      }
      else {
        $this->messenger()->addError($result['message']);
        $issues_found = TRUE;
      }
    }

    if (!$issues_found) {
      $this->messenger()->addStatus($this->t('All validation checks passed!'));
    }
  }

  /**
   * Submit handler for CSV import.
   */
  public function importCsv(array &$form, FormStateInterface $form_state) {
    $all_files = $this->getRequest()->files->get('files', []);

    if (empty($all_files['advanced']['csv_import']['file_upload'])) {
      $this->messenger()->addError($this->t('No file was uploaded.'));
      return;
    }

    $file_upload = $all_files['advanced']['csv_import']['file_upload'];
    $filename = $file_upload->getClientOriginalName();
    $extension = pathinfo($filename, PATHINFO_EXTENSION);

    if (strtolower($extension) !== 'csv') {
      $this->messenger()->addError($this->t('Only CSV files are allowed.'));
      return;
    }

    $temp_file = 'temporary://' . $filename;
    $file_uri = $this->fileSystem->saveData(
      file_get_contents($file_upload->getRealPath()),
      $temp_file,
      FileExists::Replace
    );

    if (!$file_uri) {
      $this->messenger()->addError($this->t('Error saving the uploaded file.'));
      return;
    }

    try {
      $real_path = $this->fileSystem->realpath($file_uri);
      $file_data = $this->migrationService->processCsvFile($real_path);

      if (empty($file_data)) {
        $this->messenger()->addError($this->t('No valid file data found in the CSV file.'));
        return;
      }

      $count = count($file_data);
      $batch = $this->migrationService->createMigrationBatch($file_data);

      batch_set($batch);
      $this->messenger()->addStatus($this->t('Started migration of @count files from CSV.', ['@count' => $count]));

    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('CSV processing failed: @error', ['@error' => $e->getMessage()]));
    } finally {
      // $file_uri is always defined at this point, no need for isset()
      $this->fileSystem->delete($file_uri);
    }
  }

}
