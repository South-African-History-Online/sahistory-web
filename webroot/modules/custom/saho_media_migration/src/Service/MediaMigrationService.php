<?php

namespace Drupal\saho_media_migration\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\FileInterface;

/**
 * Service for migrating file entity references to media entities.
 */
class MediaMigrationService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new MediaMigrationService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger,
    Connection $database,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('saho_media_migration');
    $this->messenger = $messenger;
    $this->database = $database;
  }

  /**
   * Process a CSV file containing file usage data.
   *
   * @param string $file_path
   *   The path to the CSV file.
   *
   * @return array
   *   An array of file data from the CSV.
   */
  public function processCsvFile($file_path) {
    $file_data = [];

    if (!file_exists($file_path)) {
      $this->messenger->addError(t('CSV file not found at @path.', ['@path' => $file_path]));
      return $file_data;
    }

    $handle = fopen($file_path, 'r');
    if (!$handle) {
      $this->messenger->addError(t('Could not open CSV file at @path.', ['@path' => $file_path]));
      return $file_data;
    }

    // Read the header row.
    $header = fgetcsv($handle);
    if (!$header) {
      $this->messenger->addError(t('CSV file is empty or has an invalid format.'));
      fclose($handle);
      return $file_data;
    }

    // Map the header columns to their indices.
    $header_map = array_flip($header);

    // Define required and optional columns.
    $required_columns = ['fid', 'filename', 'uri', 'filemime', 'usage_count'];
    $optional_columns = ['uuid', 'uid'];

    // Check for required columns.
    foreach ($required_columns as $column) {
      if (!isset($header_map[$column])) {
        $this->messenger->addError(t('CSV file is missing required column: @column.', ['@column' => $column]));
        fclose($handle);
        return $file_data;
      }
    }

    // Read the data rows.
    while (($row = fgetcsv($handle)) !== FALSE) {
      $row_data = [];
      foreach ($header_map as $column => $index) {
        if (isset($row[$index])) {
          $row_data[$column] = $row[$index];
        }
      }
      if (!empty($row_data['fid'])) {
        $file_data[] = $row_data;
      }
    }

    fclose($handle);
    return $file_data;
  }

  /**
   * Create a batch process for migrating files to media entities.
   *
   * @param array $file_data
   *   An array of file data from the CSV.
   *
   * @return array
   *   A batch array.
   */
  public function createMigrationBatch(array $file_data) {
    $operations = [];
    $batch_size = 50;
    $chunks = array_chunk($file_data, $batch_size);

    foreach ($chunks as $chunk) {
      $operations[] = [
        ['\Drupal\saho_media_migration\Batch\MediaMigrationBatch', 'processBatch'],
        [$chunk],
      ];
    }

    return [
      'title' => t('Migrating files to media entities'),
      'operations' => $operations,
      'finished' => ['\Drupal\saho_media_migration\Batch\MediaMigrationBatch', 'finishBatch'],
      'file' => \Drupal::service('extension.list.module')->getPath('saho_media_migration') . '/src/Batch/MediaMigrationBatch.php',
    ];
  }

  /**
   * Create a media entity for a file.
   *
   * @param array $file_data
   *   The file data.
   *
   * @return \Drupal\media\MediaInterface|null
   *   The created media entity, or NULL if creation failed.
   */
  public function createMediaEntity(array $file_data) {
    try {
      // Load the file entity.
      $file = $this->entityTypeManager->getStorage('file')->load($file_data['fid']);

      if (!$file instanceof FileInterface) {
        // Using the logger's error method to log the error.
        $this->logger->error('File with ID @fid not found.', ['@fid' => $file_data['fid']]);
        return NULL;
      }
      // Determine the media bundle based on the MIME type.
      $bundle = $this->getMediaBundleFromMimeType($file_data['filemime']);
      if (!$bundle) {
        // Using the logger's error method to log the error.
        $this->logger->error('No media bundle found for MIME type @mime.', ['@mime' => $file_data['filemime']]);
        return NULL;
      }

      // Create the media entity.
      $media_storage = $this->entityTypeManager->getStorage('media');
      // Default to user 1 (admin) if uid is not provided.
      $media = $media_storage->create([
        'bundle' => $bundle,
        'uid' => $file_data['uid'] ?? 1,
        'name' => $file_data['filename'],
        'status' => 1,
      ]);
      // Set the file field based on the bundle.
      $field_name = $this->getSourceFieldName($bundle);
      if ($field_name) {
        // Use array access notation instead of the set method.
        $media->{$field_name} = $file;
      }
      $media->save();

      // Using the logger's notice method to log the success message.
      $this->logger->notice('Created media entity @mid for file @fid.', [
        '@mid' => $media->id(),
        '@fid' => $file_data['fid'],
      ]);

      return $media;
    }
    catch (\Exception $e) {
      // Using the logger's error method to log the exception.
      $this->logger->error('Error creating media entity for file @fid: @error', [
        '@fid' => $file_data['fid'],
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Get the media bundle for a given MIME type.
   *
   * @param string $mime_type
   *   The MIME type.
   *
   * @return string|null
   *   The media bundle, or NULL if no bundle is found.
   */
  protected function getMediaBundleFromMimeType($mime_type) {
    if (strpos($mime_type, 'image/') === 0) {
      return 'image';
    }
    elseif (strpos($mime_type, 'audio/') === 0) {
      return 'audio';
    }
    elseif (strpos($mime_type, 'video/') === 0) {
      return 'video';
    }
    elseif (strpos($mime_type, 'application/') === 0 || strpos($mime_type, 'text/') === 0) {
      return 'file';
    }

    return NULL;
  }

  /**
   * Get the source field name for a given media bundle.
   *
   * @param string $bundle
   *   The media bundle.
   *
   * @return string|null
   *   The source field name, or NULL if no field is found.
   */
  protected function getSourceFieldName($bundle) {
    $field_map = [
      'image' => 'field_media_image',
      'audio' => 'field_media_audio_file',
      'video' => 'field_media_video_file',
      'file' => 'field_media_file',
    ];

    return $field_map[$bundle] ?? NULL;
  }

  /**
   * Handle entity presave operations for nodes.
   *
   * This method is called from the entity_presave hook to handle
   * any special cases during entity saving after migration.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  public function handleEntityPresave($entity) {
    // Implementation will be added as needed.
  }

  /**
   * Update entity references to point to media entities instead of files.
   *
   * @param int $fid
   *   The file ID.
   * @param int $mid
   *   The media entity ID.
   *
   * @return int
   *   The number of references updated.
   */
  public function updateEntityReferences($fid, $mid) {
    // This is a placeholder for the actual implementation.
    // The actual implementation would need to:
    // 1. Find all entity references to the file
    // 2. Update them to point to the media entity
    // 3. Save the entities.
    // For now, we'll just return 0.
    return 0;
  }

}
