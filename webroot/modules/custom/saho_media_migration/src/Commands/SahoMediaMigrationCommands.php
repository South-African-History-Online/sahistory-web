<?php

namespace Drupal\saho_media_migration\Commands;

use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Drush commands for SAHO media migration.
 */
class SahoMediaMigrationCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new SahoMediaMigrationCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    LoggerChannelFactoryInterface $logger_factory,
    FileSystemInterface $file_system,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
    $this->fileSystem = $file_system;
  }

  /**
   * Creates a new instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container interface.
   *
   * @return static
   *   A new instance of this class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('logger.factory'),
      $container->get('file_system')
    );
  }

  /**
   * Generates a CSV file of existing file references.
   *
   * @command saho:generate-csv
   * @aliases sgc
   * @usage drush saho:generate-csv
   *   Generates a CSV file mapping file entities to their usages.
   */
  public function generateCsv() {
    $this->output()->writeln('Starting CSV generation...');

    try {
      // Create CSV directory if it doesn't exist.
      $csv_dir = 'private://migration_csv';
      $this->fileSystem->prepareDirectory($csv_dir, FileSystemInterface::CREATE_DIRECTORY);

      // Generate unique filename.
      $filename = $csv_dir . '/media_migration_' . date('Y-m-d_H-i-s') . '.csv';
      $handle = fopen($this->fileSystem->realpath($filename), 'w');

      // Write CSV header.
      fputcsv($handle, ['file_id', 'filename', 'entity_type', 'entity_id', 'field_name', 'existing_media_id']);

      // Get all file references.
      $query = $this->database->select('file_usage', 'fu');
      $query->join('file_managed', 'fm', 'fu.fid = fm.fid');
      $query->fields('fu', ['fid', 'type', 'id', 'module', 'count'])
        ->fields('fm', ['filename']);
      $results = $query->execute();

      foreach ($results as $result) {
        // Check for existing media reference.
        $media_query = $this->database->select('media__field_media_file', 'mff')
          ->fields('mff', ['entity_id'])
          ->condition('field_media_file_target_id', $result->fid);
        $existing_media = $media_query->execute()->fetchField();

        fputcsv($handle, [
          $result->fid,
          $result->filename,
          $result->type,
          $result->id,
          'field_media_file',
          $existing_media ?: '',
        ]);
      }

      fclose($handle);
      $this->output()->writeln(sprintf('CSV file generated: %s', $filename));
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('saho_media_migration')->error(
        'Error during CSV generation: @message', ['@message' => $e->getMessage()]
      );
      throw new \Exception('CSV generation failed. Check logs for details.');
    }
  }

  /**
   * Migrates file entities to media entities.
   *
   * @command saho:migrate-files
   * @aliases smf
   * @usage drush saho:migrate-files
   *   Migrates file entities to media entities.
   */
  public function migrateFiles() {
    $this->output()->writeln('Starting file to media migration...');

    try {
      // Get all file references from node fields.
      $query = $this->database->select('node__field_file_upload', 'f');
      $query->fields('f', ['entity_id', 'field_file_upload_target_id']);
      $results = $query->execute()->fetchAll();

      $count = 0;
      foreach ($results as $result) {
        // Load the file.
        $file = $this->entityTypeManager->getStorage('file')->load($result->field_file_upload_target_id);
        if (!$file) {
          continue;
        }

        // Check for existing media entity.
        $existing_query = $this->database->select('media__field_media_file', 'mff')
          ->fields('mff', ['entity_id'])
          ->condition('field_media_file_target_id', $file->id());
        $existing_media_id = $existing_query->execute()->fetchField();

        if ($existing_media_id) {
          $this->output()->writeln(sprintf('Media entity already exists for file %d, skipping.', $file->id()));
          continue;
        }

        // Create new media entity.
        $media = $this->entityTypeManager->getStorage('media')->create([
          'bundle' => 'file',
          'name' => $file->filename->value,
          'field_media_file' => [
            'target_id' => $file->id(),
          ],
        ]);
        $media->save();

        // Update node reference.
        $node = $this->entityTypeManager->getStorage('node')->load($result->entity_id);
        if ($node) {
          $node->field_media_file[] = [
            'target_id' => $media->id(),
          ];
          $node->save();
          $count++;
        }
      }

      $this->output()->writeln(sprintf('Successfully migrated %d file references to media entities.', $count));
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('saho_media_migration')->error(
        'Error during file migration: @message', ['@message' => $e->getMessage()]
      );
      throw new \Exception('Migration failed. Check logs for details.');
    }
  }

  /**
   * Updates file references in content.
   *
   * @command saho:update-references
   * @aliases sur
   * @usage drush saho:update-references
   *   Updates file references to media references in content.
   */
  public function updateReferences() {
    $this->output()->writeln('Starting reference update...');

    try {
      // Update node body field references.
      $query = $this->database->select('node__body', 'nb');
      $query->fields('nb', ['entity_id', 'body_value']);
      $results = $query->execute()->fetchAll();

      $count = 0;
      foreach ($results as $result) {
        $body = $result->body_value;
        // Replace file URLs with media URLs.
        $updated_body = preg_replace(
          '/\/sites\/default\/files\/([^"\']+)/',
          '/media/$1',
          $body
        );

        if ($body !== $updated_body) {
          $this->database->update('node__body')
            ->fields(['body_value' => $updated_body])
            ->condition('entity_id', $result->entity_id)
            ->execute();
          $count++;
        }
      }

      $this->output()->writeln(sprintf('Successfully updated %d content references.', $count));
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('saho_media_migration')->error(
        'Error during reference update: @message', ['@message' => $e->getMessage()]
      );
      throw new \Exception('Reference update failed. Check logs for details.');
    }
  }

  /**
   * Validates media entities.
   *
   * @command saho:validate-media
   * @aliases svm
   * @usage drush saho:validate-media
   *   Validates media entities and their references.
   */
  public function validateMedia() {
    $this->output()->writeln('Starting media validation...');

    try {
      $issues = [];

      // Check for orphaned media entities.
      $query = $this->database->select('media', 'm');
      $query->leftJoin('media__field_media_file', 'mff', 'm.mid = mff.entity_id');
      $query->fields('m', ['mid'])
        ->isNull('mff.field_media_file_target_id');
      $orphaned = $query->execute()->fetchCol();

      if (!empty($orphaned)) {
        $issues[] = sprintf('Found %d orphaned media entities.', count($orphaned));
      }

      // Check for broken file references.
      $query = $this->database->select('media__field_media_file', 'mff');
      $query->leftJoin('file_managed', 'fm', 'mff.field_media_file_target_id = fm.fid');
      $query->fields('mff', ['entity_id'])
        ->isNull('fm.fid');
      $broken = $query->execute()->fetchCol();

      if (!empty($broken)) {
        $issues[] = sprintf('Found %d broken file references.', count($broken));
      }

      if (empty($issues)) {
        $this->output()->writeln('No issues found. All media entities are valid.');
      }
      else {
        $this->output()->writeln('Found the following issues:');
        foreach ($issues as $issue) {
          $this->output()->writeln('- ' . $issue);
        }
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('saho_media_migration')->error(
        'Error during media validation: @message', ['@message' => $e->getMessage()]
      );
      throw new \Exception('Validation failed. Check logs for details.');
    }
  }

}