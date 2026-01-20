<?php

namespace Drupal\saho_media_migration\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\FileInterface;

/**
 * Core service for migrating file entities to media entities.
 */
class MediaMigrationService {

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
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;


  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new MediaMigrationService object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    FileSystemInterface $file_system,
    MessengerInterface $messenger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
  }

  /**
   * Get migration statistics.
   */
  public function getMigrationStats() {
    $stats = [];

    $stats['total_files'] = $this->database->select('file_managed', 'f')
      ->countQuery()
      ->execute()
      ->fetchField();

    $files_with_media = $this->getFilesWithMediaEntities();
    $stats['files_with_media'] = count($files_with_media);
    $stats['files_without_media'] = $stats['total_files'] - $stats['files_with_media'];

    $stats['used_files'] = $this->database->select('file_usage', 'fu')
      ->distinct()
      ->fields('fu', ['fid'])
      ->countQuery()
      ->execute()
      ->fetchField();

    $stats['migration_progress'] = $stats['total_files'] > 0
      ? round(($stats['files_with_media'] / $stats['total_files']) * 100, 2)
      : 0;

    return $stats;
  }

  /**
   * Get files that need migration.
   */
  public function getFilesNeedingMigration($limit = 1000, $offset = 0) {
    $query = $this->database->select('file_managed', 'f');
    $query->fields('f', [
      'fid', 'uuid', 'uid', 'filename', 'uri', 'filemime', 'filesize', 'status', 'created', 'changed',
    ]);

    $files_with_media = $this->getFilesWithMediaEntities();
    if (!empty($files_with_media)) {
      $query->condition('f.fid', $files_with_media, 'NOT IN');
    }

    $query->leftJoin('file_usage', 'fu', 'f.fid = fu.fid');
    $query->addField('fu', 'count', 'usage_count');

    $query->orderBy('fu.count', 'DESC');
    $query->orderBy('f.filesize', 'ASC');
    $query->range($offset, $limit);

    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Generate CSV mapping file.
   */
  public function generateCsvMapping() {
    $csv_dir = 'private://migration_csv';
    $this->fileSystem->prepareDirectory($csv_dir, FileSystemInterface::CREATE_DIRECTORY);

    $filename = $csv_dir . '/media_migration_' . date('Y-m-d_H-i-s') . '.csv';
    $file_path = $this->fileSystem->realpath($filename);

    $handle = fopen($file_path, 'w');
    if (!$handle) {
      throw new \Exception('Cannot create CSV file: ' . $filename);
    }

    fputcsv($handle, [
      'file_id',
      'filename',
      'uri',
      'filemime',
      'filesize',
      'usage_count',
      'existing_media_id',
      'suggested_bundle',
    ]);

    $query = $this->database->select('file_managed', 'f');
    $query->fields('f', ['fid', 'filename', 'uri', 'filemime', 'filesize']);
    $query->leftJoin('file_usage', 'fu', 'f.fid = fu.fid');
    $query->addField('fu', 'count', 'usage_count');
    $query->orderBy('fu.count', 'DESC');

    $results = $query->execute();
    $processed = 0;

    foreach ($results as $result) {
      $existing_media_id = $this->getMediaIdForFile($result->fid);
      $suggested_bundle = $this->getMediaBundle($result->filemime);

      fputcsv($handle, [
        $result->fid,
        $result->filename,
        $result->uri,
        $result->filemime,
        $result->filesize,
        $result->usage_count ?? 0,
        $existing_media_id ?: '',
        $suggested_bundle,
      ]);
      $processed++;
    }

    fclose($handle);

    return $filename;
  }

  /**
   * Create media entity for a file.
   */
  public function createMediaEntity(array $file_data) {
    try {
      if ($this->hasMediaEntity($file_data['fid'])) {
        return NULL;
      }

      $file = $this->entityTypeManager->getStorage('file')->load($file_data['fid']);
      if (!$file instanceof FileInterface) {
        return NULL;
      }

      if (!file_exists($file->getFileUri())) {
        return NULL;
      }

      $bundle = $this->getMediaBundle($file_data['filemime']);
      if (!$bundle) {
        return NULL;
      }

      $media_name = $this->generateMediaName($file_data['filename']);
      $media = $this->entityTypeManager->getStorage('media')->create([
        'bundle' => $bundle,
        'uid' => $file_data['uid'] ?? 1,
        'name' => $media_name,
        'status' => 1,
        'created' => $file_data['created'] ?? time(),
        'changed' => $file_data['changed'] ?? time(),
      ]);

      $source_field = $this->getSourceField($bundle);
      if ($source_field && $media->hasField($source_field)) {
        $media->set($source_field, $file);
        $media->save();
        return $media;
      }

      return NULL;

    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Create migration batch.
   */
  public function createMigrationBatch(array $file_data) {
    $operations = [];
    $batch_size = 50;
    $chunks = array_chunk($file_data, $batch_size);

    foreach ($chunks as $chunk) {
      $operations[] = [
        ['\Drupal\saho_media_migration\Batch\MediaMigrationBatch', 'processBatch'],
        [$chunk, count($file_data)],
      ];
    }

    return [
      'title' => t('Migrating @count files to media entities', ['@count' => count($file_data)]),
      'operations' => $operations,
      'finished' => ['\Drupal\saho_media_migration\Batch\MediaMigrationBatch', 'finishBatch'],
      'progressive' => TRUE,
      'init_message' => t('Starting media migration...'),
      'progress_message' => t('Processing @current of @total batches.'),
      'error_message' => t('Migration encountered an error.'),
    ];
  }

  /**
   * Validate migration integrity.
   */
  public function validateMigration() {
    $results = [];

    $orphaned_media = $this->findOrphanedMedia();
    $results['orphaned_media'] = [
      'status' => empty($orphaned_media) ? 'pass' : 'warning',
      'count' => count($orphaned_media),
      'message' => empty($orphaned_media)
        ? 'No orphaned media entities found'
        : count($orphaned_media) . ' orphaned media entities found',
    ];

    $broken_refs = $this->findBrokenFileReferences();
    $results['broken_references'] = [
      'status' => empty($broken_refs) ? 'pass' : 'error',
      'count' => count($broken_refs),
      'message' => empty($broken_refs)
        ? 'No broken file references found'
        : count($broken_refs) . ' broken file references found',
    ];

    $missing_files = $this->findMissingFiles();
    $results['missing_files'] = [
      'status' => empty($missing_files) ? 'pass' : 'error',
      'count' => count($missing_files),
      'message' => empty($missing_files)
        ? 'All files exist on disk'
        : count($missing_files) . ' file records point to missing files',
    ];

    return $results;
  }

  /**
   * Get files that already have media entities.
   */
  protected function getFilesWithMediaEntities() {
    $files_with_media = [];

    $media_tables = [
      'media__field_media_image' => 'field_media_image_target_id',
      'media__field_media_file' => 'field_media_file_target_id',
      'media__field_media_audio_file' => 'field_media_audio_file_target_id',
      'media__field_media_video_file' => 'field_media_video_file_target_id',
    ];

    foreach ($media_tables as $table => $field) {
      if (!$this->database->schema()->tableExists($table)) {
        continue;
      }

      $query = $this->database->select($table, 't')
        ->fields('t', [$field])
        ->isNotNull($field);

      $results = $query->execute()->fetchCol();
      $files_with_media = array_merge($files_with_media, $results);
    }

    return array_unique($files_with_media);
  }

  /**
   * Check if file has media entity.
   *
   * Uses direct single-record lookup for O(1) performance instead of
   * loading all files with media entities into memory.
   *
   * @param int $fid
   *   The file ID to check.
   *
   * @return bool
   *   TRUE if a media entity exists for this file, FALSE otherwise.
   */
  public function hasMediaEntity($fid) {
    $media_tables = [
      'media__field_media_image' => 'field_media_image_target_id',
      'media__field_media_file' => 'field_media_file_target_id',
      'media__field_media_audio_file' => 'field_media_audio_file_target_id',
      'media__field_media_video_file' => 'field_media_video_file_target_id',
    ];

    foreach ($media_tables as $table => $field) {
      if (!$this->database->schema()->tableExists($table)) {
        continue;
      }

      $result = $this->database->select($table, 't')
        ->fields('t', ['entity_id'])
        ->condition($field, $fid)
        ->range(0, 1)
        ->execute()
        ->fetchField();

      if ($result) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get media entity ID for a file.
   */
  protected function getMediaIdForFile($fid) {
    $media_tables = [
      'media__field_media_image' => 'field_media_image_target_id',
      'media__field_media_file' => 'field_media_file_target_id',
      'media__field_media_audio_file' => 'field_media_audio_file_target_id',
      'media__field_media_video_file' => 'field_media_video_file_target_id',
    ];

    foreach ($media_tables as $table => $field) {
      if (!$this->database->schema()->tableExists($table)) {
        continue;
      }

      $query = $this->database->select($table, 't')
        ->fields('t', ['entity_id'])
        ->condition($field, $fid)
        ->range(0, 1);

      $result = $query->execute()->fetchField();
      if ($result) {
        return (int) $result;
      }
    }

    return NULL;
  }

  /**
   * Get media bundle from MIME type.
   */
  protected function getMediaBundle($mime_type) {
    if (strpos($mime_type, 'image/') === 0) {
      return 'image';
    }
    if (strpos($mime_type, 'audio/') === 0) {
      return 'audio';
    }
    if (strpos($mime_type, 'video/') === 0) {
      return 'video';
    }
    if (strpos($mime_type, 'application/') === 0 || strpos($mime_type, 'text/') === 0) {
      return 'file';
    }
    return 'file';
  }

  /**
   * Get source field name for bundle.
   */
  protected function getSourceField($bundle) {
    $field_map = [
      'image' => 'field_media_image',
      'audio' => 'field_media_audio_file',
      'video' => 'field_media_video_file',
      'file' => 'field_media_file',
    ];

    return $field_map[$bundle] ?? NULL;
  }

  /**
   * Generate clean media name from filename.
   */
  protected function generateMediaName($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = str_replace(['_', '-', '.'], ' ', $name);
    $name = preg_replace('/\s+/', ' ', trim($name));
    $name = ucwords(strtolower($name));

    if (strlen($name) > 255) {
      $name = substr($name, 0, 252) . '...';
    }

    return $name ?: 'Untitled Media';
  }

  /**
   * Find orphaned media entities.
   */
  protected function findOrphanedMedia() {
    $query = $this->database->select('media', 'm');
    $query->fields('m', ['mid']);
    $query->leftJoin('media__field_media_file', 'mf', 'm.mid = mf.entity_id');
    $query->leftJoin('media__field_media_image', 'mi', 'm.mid = mi.entity_id');
    $query->leftJoin('media__field_media_audio_file', 'ma', 'm.mid = ma.entity_id');
    $query->leftJoin('media__field_media_video_file', 'mv', 'm.mid = mv.entity_id');
    $query->isNull('mf.field_media_file_target_id');
    $query->isNull('mi.field_media_image_target_id');
    $query->isNull('ma.field_media_audio_file_target_id');
    $query->isNull('mv.field_media_video_file_target_id');

    return $query->execute()->fetchCol();
  }

  /**
   * Find broken file references.
   */
  protected function findBrokenFileReferences() {
    $broken = [];

    $media_tables = [
      'media__field_media_image' => 'field_media_image_target_id',
      'media__field_media_file' => 'field_media_file_target_id',
      'media__field_media_audio_file' => 'field_media_audio_file_target_id',
      'media__field_media_video_file' => 'field_media_video_file_target_id',
    ];

    foreach ($media_tables as $table => $field) {
      if (!$this->database->schema()->tableExists($table)) {
        continue;
      }

      $query = $this->database->select($table, 't');
      $query->fields('t', ['entity_id', $field]);
      $query->leftJoin('file_managed', 'f', "t.$field = f.fid");
      $query->isNull('f.fid');
      $query->isNotNull("t.$field");

      $results = $query->execute()->fetchAll();
      $broken = array_merge($broken, $results);
    }

    return $broken;
  }

  /**
   * Find missing physical files.
   */
  protected function findMissingFiles() {
    $missing = [];

    $query = $this->database->select('file_managed', 'f');
    $query->fields('f', ['fid', 'uri']);
    $query->range(0, 1000);

    $files = $query->execute()->fetchAll();

    foreach ($files as $file) {
      if (!file_exists($file->uri)) {
        $missing[] = $file->fid;
      }
    }

    return $missing;
  }

  /**
   * Process CSV file.
   */
  public function processCsvFile($file_path) {
    $file_data = [];

    if (!file_exists($file_path)) {
      $this->messenger->addError(t('CSV file not found: @path', ['@path' => $file_path]));
      return $file_data;
    }

    $handle = fopen($file_path, 'r');
    if (!$handle) {
      $this->messenger->addError(t('Cannot open CSV file: @path', ['@path' => $file_path]));
      return $file_data;
    }

    $header = fgetcsv($handle);
    if (!$header) {
      $this->messenger->addError(t('CSV file is empty or invalid.'));
      fclose($handle);
      return $file_data;
    }

    $header_map = array_flip($header);
    $required_columns = ['file_id', 'filename', 'filemime'];

    foreach ($required_columns as $column) {
      if (!isset($header_map[$column])) {
        $this->messenger->addError(t('CSV missing required column: @column', ['@column' => $column]));
        fclose($handle);
        return $file_data;
      }
    }

    while (($row = fgetcsv($handle)) !== FALSE) {
      $row_data = [];
      foreach ($header_map as $column => $index) {
        if (isset($row[$index])) {
          $row_data[$column] = $row[$index];
        }
      }

      if (isset($row_data['file_id'])) {
        $row_data['fid'] = $row_data['file_id'];
      }

      if (!empty($row_data['fid'])) {
        $file_data[] = $row_data;
      }
    }

    fclose($handle);
    return $file_data;
  }

}
