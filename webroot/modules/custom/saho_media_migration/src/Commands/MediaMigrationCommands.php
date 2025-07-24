<?php

namespace Drupal\saho_media_migration\Commands;

use Drupal\saho_media_migration\Service\MediaMigrationService;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Drush commands for SAHO Media Migration.
 */
class MediaMigrationCommands extends DrushCommands {

  /**
   * The media migration service.
   *
   * @var \Drupal\saho_media_migration\Service\MediaMigrationService
   */
  protected $migrationService;

  /**
   * Constructs a new MediaMigrationCommands object.
   */
  public function __construct(MediaMigrationService $migration_service) {
    parent::__construct();
    $this->migrationService = $migration_service;
  }

  /**
   * Show migration status and statistics.
   *
   * @command saho:status
   * @aliases sms
   * @usage saho:status
   *   Display current migration status
   */
  public function migrationStatus() {
    try {
      $stats = $this->migrationService->getMigrationStats();

      // Output statistics in plain text format.
      $this->output()->writeln('');
      $this->output()->writeln('Migration Statistics:');
      $this->output()->writeln('---------------------');
      $this->output()->writeln('Total Files: ' . number_format($stats['total_files']));
      $this->output()->writeln('Files with Media: ' . number_format($stats['files_with_media']));
      $this->output()->writeln('Files Needing Migration: ' . number_format($stats['files_without_media']));
      $this->output()->writeln('Migration Progress: ' . $stats['migration_progress'] . '%');
      $this->output()->writeln('Used Files: ' . number_format($stats['used_files']));
      $this->output()->writeln('');

      $progress = $stats['migration_progress'];
      if ($progress < 100) {
        $this->output()->writeln("⚠️  Migration incomplete. Run 'drush saho:migrate' to continue.");
      }
      else {
        $this->output()->writeln("✅ Migration complete! All files have media entities.");
      }

    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Could not get migration status: ' . $e->getMessage());
    }
  }

  /**
   * Generate CSV mapping of file entities.
   *
   * @command saho:generate-csv
   * @aliases sgc
   * @usage saho:generate-csv
   *   Generate CSV file mapping file entities to their usage
   */
  public function generateCsv() {
    $this->output()->writeln('');
    $this->output()->writeln('=== GENERATING CSV MAPPING FILE ===');
    $this->output()->writeln('');

    try {
      $filename = $this->migrationService->generateCsvMapping();
      $this->output()->writeln("✅ CSV file generated: {$filename}");

      $stats = $this->migrationService->getMigrationStats();
      $this->output()->writeln('');
      $this->output()->writeln('Summary:');
      $this->output()->writeln('--------');
      $this->output()->writeln('Total files mapped: ' . number_format($stats['total_files']));
      $this->output()->writeln('Files already with media: ' . number_format($stats['files_with_media']));
      $this->output()->writeln('Files needing migration: ' . number_format($stats['files_without_media']));
      $this->output()->writeln('');

    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ CSV generation failed: ' . $e->getMessage());
      return self::EXIT_FAILURE;
    }
  }

  /**
   * Migrate files to media entities.
   *
   * @command saho:migrate
   * @aliases smig
   * @option limit Maximum number of files to migrate
   * @usage saho:migrate
   *   Migrate files to media entities
   * @usage saho:migrate --limit=1000
   *   Migrate with limit of 1000 files
   */
  public function migrate($options = ['limit' => 1000]) {
    $limit = (int) $options['limit'];

    $this->output()->writeln('');
    $this->output()->writeln('=== SAHO MEDIA MIGRATION ===');
    $this->output()->writeln('');

    try {
      $stats = $this->migrationService->getMigrationStats();

      // Display migration statistics.
      $this->output()->writeln('Migration Statistics:');
      $this->output()->writeln('---------------------');
      $this->output()->writeln('Total files: ' . number_format($stats['total_files']));
      $this->output()->writeln('Files with media: ' . number_format($stats['files_with_media']));
      $this->output()->writeln('Files needing migration: ' . number_format($stats['files_without_media']));
      $this->output()->writeln('Migration progress: ' . $stats['migration_progress'] . '%');
      $this->output()->writeln('');

      if ($stats['files_without_media'] === 0) {
        $this->output()->writeln('✅ All files already have media entities! Migration complete.');
        return self::EXIT_SUCCESS;
      }

      $files_to_migrate = $this->migrationService->getFilesNeedingMigration($limit);

      if (empty($files_to_migrate)) {
        $this->output()->writeln('⚠️ No files found to migrate.');
        return self::EXIT_SUCCESS;
      }

      $count = count($files_to_migrate);
      $this->output()->writeln("ℹ️ Found {$count} files to migrate.");
      $this->output()->writeln('');

      if ($count > 0) {
        $sample = array_slice($files_to_migrate, 0, 3);
        $this->output()->writeln('Sample files to migrate:');
        $this->output()->writeln('----------------------');

        foreach ($sample as $file) {
          $filename = substr($file['filename'], 0, 40) . (strlen($file['filename']) > 40 ? '...' : '');
          $type = $this->getFileTypeFromMime($file['filemime']);
          $size = $this->formatFileSize((int) $file['filesize']);
          $this->output()->writeln("FID: {$file['fid']}, Name: {$filename}, Type: {$type}, Size: {$size}");
        }

        if ($count > 3) {
          $this->output()->writeln("... and " . ($count - 3) . " more files");
        }
        $this->output()->writeln('');
      }

      $this->output()->writeln("Proceed with migrating {$count} files? [y/n]");
      $answer = trim(fgets(STDIN));
      if (strtolower($answer) !== 'y') {
        throw new UserAbortException();
      }

      // Process the files directly instead of using batch API which can have
      // compatibility issues in CLI context.
      $this->output()->writeln("Starting migration of {$count} files...");
      $this->output()->writeln('');

      // Get the operations from the batch and process them directly.
      $processed = 0;

      foreach ($files_to_migrate as $file) {
        try {
          $media = $this->migrationService->createMediaEntity($file);
          if ($media) {
            $this->output()->writeln("✅ Migrated file {$file['filename']} to media entity #{$media->id()}");
            $processed++;
          }
        }
        catch (\Exception $inner_exception) {
          $this->output()->writeln("❌ Failed to migrate file {$file['filename']}: {$inner_exception->getMessage()}");
        }
      }

      $this->output()->writeln('');
      $this->output()->writeln("Migration completed: {$processed} of {$count} files processed successfully.");

    }
    catch (UserAbortException $e) {
      $this->output()->writeln('ℹ️ Migration cancelled by user.');
      throw $e;
    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Migration failed: ' . $e->getMessage());
      return self::EXIT_FAILURE;
    }
  }

  /**
   * Validate media entities and migration integrity.
   *
   * @command saho:validate
   * @aliases sval
   * @usage saho:validate
   *   Validate media entities and their references
   */
  public function validate() {
    $this->output()->writeln('');
    $this->output()->writeln('=== VALIDATING MEDIA MIGRATION ===');
    $this->output()->writeln('');

    try {
      $results = $this->migrationService->validateMigration();

      $all_passed = TRUE;
      foreach ($results as $check => $result) {
        $status_icon = $result['status'] === 'pass' ? '✅' : ($result['status'] === 'warning' ? '⚠️' : '❌');
        $this->output()->writeln("{$status_icon} {$result['message']}");

        if ($result['status'] !== 'pass') {
          $all_passed = FALSE;
        }
      }

      $this->output()->writeln('');
      if ($all_passed) {
        $this->output()->writeln('✅ All validation checks passed!');
      }
      else {
        $this->output()->writeln('⚠️ Some validation issues found. Check results above.');
      }

      $this->output()->writeln('');
      $this->output()->writeln('=== MIGRATION STATISTICS ===');
      $this->migrationStatus();

    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Validation failed: ' . $e->getMessage());
      return self::EXIT_FAILURE;
    }
  }

  /**
   * Import migration data from CSV file.
   *
   * @command saho:import-csv
   * @aliases simp
   * @argument file Path to CSV file to import
   * @usage saho:import-csv /path/to/migration.csv
   *   Import and migrate files from CSV
   */
  public function importCsv($file) {
    if (!file_exists($file)) {
      $this->output()->writeln('❌ CSV file not found: ' . $file);
      return self::EXIT_FAILURE;
    }

    $this->output()->writeln('');
    $this->output()->writeln('=== IMPORTING MIGRATION DATA FROM CSV ===');
    $this->output()->writeln($file);
    $this->output()->writeln('');

    try {
      $file_data = $this->migrationService->processCsvFile($file);

      if (empty($file_data)) {
        $this->output()->writeln('❌ No valid file data found in CSV.');
        return self::EXIT_FAILURE;
      }

      $count = count($file_data);
      $this->output()->writeln("ℹ️ Found {$count} files in CSV.");
      $this->output()->writeln('');

      $this->output()->writeln("Proceed with migrating {$count} files from CSV? [y/n]");
      $answer = trim(fgets(STDIN));
      if (strtolower($answer) !== 'y') {
        throw new UserAbortException();
      }

      // Process the files directly instead of using batch API which can have
      // compatibility issues in CLI context.
      $this->output()->writeln("Starting import of {$count} files from CSV...");
      $this->output()->writeln('');

      // Get the operations from the batch and process them directly.
      $processed = 0;

      foreach ($file_data as $file) {
        try {
          $media = $this->migrationService->createMediaEntity($file);
          if ($media) {
            $this->output()->writeln("✅ Migrated file {$file['filename']} to media entity #{$media->id()}");
            $processed++;
          }
        }
        catch (\Exception $inner_exception) {
          $this->output()->writeln("❌ Failed to migrate file {$file['filename']}: {$inner_exception->getMessage()}");
        }
      }

      $this->output()->writeln('');
      $this->output()->writeln("CSV import completed: {$processed} of {$count} files processed successfully.");

    }
    catch (UserAbortException $e) {
      $this->output()->writeln('ℹ️ CSV import cancelled by user.');
      throw $e;
    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ CSV import failed: ' . $e->getMessage());
      return self::EXIT_FAILURE;
    }
  }

  /**
   * Format a file size in bytes to a human-readable string.
   *
   * @param int $size
   *   The file size in bytes.
   *
   * @return string
   *   A formatted string representing the file size.
   */
  protected function formatFileSize($size) {
    if ($size < 1024) {
      return $size . ' bytes';
    }
    else {
      $units = ['KB', 'MB', 'GB', 'TB'];
      $exp = floor(log($size, 1024));
      $exp = min($exp, count($units) - 1);
      // $exp starts at 1 for KB, so $exp-1 is the correct array index
      return number_format($size / pow(1024, $exp), 2) . ' ' . $units[$exp - 1];
    }
  }

  /**
   * Get file type description from MIME type.
   */
  protected function getFileTypeFromMime($mime_type) {
    if (strpos($mime_type, 'image/') === 0) {
      return 'Image';
    }
    if (strpos($mime_type, 'video/') === 0) {
      return 'Video';
    }
    if (strpos($mime_type, 'audio/') === 0) {
      return 'Audio';
    }
    if (strpos($mime_type, 'application/pdf') === 0) {
      return 'PDF';
    }
    if (strpos($mime_type, 'application/') === 0) {
      return 'Document';
    }
    if (strpos($mime_type, 'text/') === 0) {
      return 'Text';
    }
    return 'File';
  }

}
