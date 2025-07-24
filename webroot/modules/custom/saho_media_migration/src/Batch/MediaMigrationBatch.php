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
   */
  public static function processBatch(array $files, $total_files, array &$context) {
    if (!isset($context['results']['processed'])) {
      $context['results']['processed'] = 0;
      $context['results']['succeeded'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['skipped'] = 0;
      $context['results']['total'] = $total_files;
      $context['results']['start_time'] = time();
    }

    $migration_service = \Drupal::service('saho_media_migration.migrator');

    foreach ($files as $file_data) {
      try {
        if ($migration_service->hasMediaEntity($file_data['fid'])) {
          $context['results']['skipped']++;
          continue;
        }

        $media = $migration_service->createMediaEntity($file_data);

        if ($media) {
          $context['results']['succeeded']++;
        }
        else {
          $context['results']['failed']++;
        }

      }
      catch (\Exception $e) {
        $context['results']['failed']++;
      }

      $context['results']['processed']++;
    }

    $processed = $context['results']['processed'];
    $total = $context['results']['total'];
    $percent = $total > 0 ? round(($processed / $total) * 100, 1) : 0;

    $elapsed = time() - $context['results']['start_time'];
    $rate = $elapsed > 0 ? $processed / $elapsed : 0;
    $remaining = $total - $processed;
    $eta_seconds = $rate > 0 ? round($remaining / $rate) : 0;
    $eta = static::formatDuration($eta_seconds);

    $context['message'] = t('Processing (@percent%): @processed of @total files. Success: @succeeded, Failed: @failed, Skipped: @skipped. ETA: @eta', [
      '@percent' => $percent,
      '@processed' => number_format($processed),
      '@total' => number_format($total),
      '@succeeded' => number_format($context['results']['succeeded']),
      '@failed' => number_format($context['results']['failed']),
      '@skipped' => number_format($context['results']['skipped']),
      '@eta' => $eta,
    ]);

    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $total;
    }
    $context['sandbox']['progress'] = $processed;
    $context['finished'] = $total > 0 ? $processed / $total : 1;
  }

  /**
   * Finish batch processing.
   */
  public static function finishBatch($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();

    $total_time = time() - ($results['start_time'] ?? time());
    $time_desc = static::formatDuration($total_time);

    if ($success) {
      $messenger->addStatus(t('Media migration completed successfully in @time!', [
        '@time' => $time_desc,
      ]));

      $messenger->addStatus(t('Results: @processed files processed, @succeeded succeeded, @failed failed, @skipped skipped', [
        '@processed' => number_format($results['processed'] ?? 0),
        '@succeeded' => number_format($results['succeeded'] ?? 0),
        '@failed' => number_format($results['failed'] ?? 0),
        '@skipped' => number_format($results['skipped'] ?? 0),
      ]));

      if ($total_time > 0 && !empty($results['processed'])) {
        $rate = round($results['processed'] / $total_time, 2);
        $messenger->addStatus(t('Performance: @rate files per second', ['@rate' => $rate]));
      }

    }
    else {
      $messenger->addError(t('Migration batch failed and could not complete.'));

      if (!empty($results['processed'])) {
        $messenger->addWarning(t('Partial results: @processed files were processed before the error occurred.', [
          '@processed' => number_format($results['processed']),
        ]));
      }
    }
  }

  /**
   * Format duration in seconds to human-readable format.
   */
  protected static function formatDuration($seconds) {
    if ($seconds < 60) {
      return t('@seconds sec', ['@seconds' => $seconds]);
    }
    if ($seconds < 3600) {
      $minutes = floor($seconds / 60);
      $remaining_seconds = $seconds % 60;
      return t('@minutes min @seconds sec', [
        '@minutes' => $minutes,
        '@seconds' => $remaining_seconds,
      ]);
    }

    $hours = floor($seconds / 3600);
    $remaining_minutes = floor(($seconds % 3600) / 60);
    return t('@hours hr @minutes min', [
      '@hours' => $hours,
      '@minutes' => $remaining_minutes,
    ]);
  }

}
