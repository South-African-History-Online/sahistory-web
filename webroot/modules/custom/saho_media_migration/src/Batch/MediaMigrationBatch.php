<?php

namespace Drupal\saho_media_migration\Batch;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Batch operations for migrating files to media entities.
 */
class MediaMigrationBatch {

  use StringTranslationTrait;

  /**
   * Process a batch of files to create media entities.
   *
   * @param array $files
   *   An array of file data.
   * @param array $context
   *   The batch context.
   */
  public static function processBatch(array $files, array &$context) {
    // Initialize results array if it doesn't exist.
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['succeeded'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['skipped'] = 0;
    }

    // Get the migration service.
    $migration_service = \Drupal::service('saho_media_migration.migrator');

    // Process each file in the batch.
    foreach ($files as $file_data) {
      // Skip files that already have media entities.
      if (static::fileHasMediaEntity($file_data['fid'])) {
        $context['results']['skipped']++;
        continue;
      }

      // Create a media entity for the file.
      $media = $migration_service->createMediaEntity($file_data);

      if ($media) {
        // Update entity references to point to the new media entity.
        $updated = $migration_service->updateEntityReferences($file_data['fid'], $media->id());
        $context['results']['succeeded']++;
        $context['results']['references_updated'] = ($context['results']['references_updated'] ?? 0) + $updated;
      }
      else {
        $context['results']['failed']++;
      }

      $context['results']['processed']++;
    }

    // Determine the MIME types in this batch.
    $mime_types = [];
    foreach ($files as $file_data) {
      if (isset($file_data['filemime']) && !in_array($file_data['filemime'], $mime_types)) {
        $mime_types[] = $file_data['filemime'];
      }
    }

    // Create a description of the MIME types.
    $mime_type_desc = count($mime_types) > 0
      ? implode(', ', array_slice($mime_types, 0, 3)) . (count($mime_types) > 3 ? '...' : '')
      : 'various types';

    // Update progress message.
    $context['message'] = t('Processed @processed of @total files (@mime_type)', [
      '@processed' => $context['results']['processed'],
      '@total' => $context['results']['total'] ?? count($files),
      '@mime_type' => $mime_type_desc,
    ]);
  }

  /**
   * Finish batch processing.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The batch results.
   * @param array $operations
   *   The batch operations.
   */
  public static function finishBatch($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addStatus(t('Media migration completed.'));
      $messenger->addStatus(t('Processed: @processed', ['@processed' => $results['processed']]));
      $messenger->addStatus(t('Succeeded: @succeeded', ['@succeeded' => $results['succeeded']]));
      $messenger->addStatus(t('Failed: @failed', ['@failed' => $results['failed']]));
      $messenger->addStatus(t('Skipped: @skipped', ['@skipped' => $results['skipped']]));
      $messenger->addStatus(t('References updated: @updated', ['@updated' => $results['references_updated'] ?? 0]));
    }
    else {
      $messenger->addError(t('Media migration encountered an error.'));
    }
  }

  /**
   * Check if a file already has a media entity.
   *
   * @param int $fid
   *   The file ID.
   *
   * @return bool
   *   TRUE if the file has a media entity, FALSE otherwise.
   */
  protected static function fileHasMediaEntity($fid) {
    $database = \Drupal::database();

    // Check if the file is referenced by a media entity.
    $query = $database->select('media__field_media_image', 'mfmi')
      ->fields('mfmi', ['entity_id'])
      ->condition('mfmi.field_media_image_target_id', $fid)
      ->range(0, 1);
    $result = $query->execute()->fetchField();

    if ($result) {
      return TRUE;
    }

    // Check other media entity fields.
    $fields = [
      'media__field_media_audio_file' => 'field_media_audio_file_target_id',
      'media__field_media_video_file' => 'field_media_video_file_target_id',
      'media__field_media_file' => 'field_media_file_target_id',
    ];

    foreach ($fields as $table => $field) {
      $query = $database->select($table, 'mf')
        ->fields('mf', ['entity_id'])
        ->condition('mf.' . $field, $fid)
        ->range(0, 1);
      $result = $query->execute()->fetchField();

      if ($result) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
