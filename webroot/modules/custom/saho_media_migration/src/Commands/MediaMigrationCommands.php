<?php

namespace Drupal\saho_media_migration\Commands;

use Drupal\saho_media_migration\Service\MediaMigrationService;
use Drupal\Component\Utility\Bytes;
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

      $rows = [
        ['Total Files', number_format($stats['total_files'])],
        ['Files with Media', number_format($stats['files_with_media'])],
        ['Files Needing Migration', number_format($stats['files_without_media'])],
        ['Migration Progress', $stats['migration_progress'] . '%'],
        ['Used Files', number_format($stats['used_files'])],
      ];

      $this->io()->table(['Metric', 'Count'], $rows);

      $progress = $stats['migration_progress'];
      if ($progress < 100) {
        $this->io()->warning("Migration incomplete. Run 'drush saho:migrate' to continue.");
      }
      else {
        $this->io()->success("Migration complete! All files have media entities.");
      }

    }
    catch (\Exception $e) {
      $this->io()->error('Could not get migration status: ' . $e->getMessage());
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
    $this->io()->title('Generating CSV mapping file');

    try {
      $filename = $this->migrationService->generateCsvMapping();
      $this->io()->success("CSV file generated: {$filename}");

      $stats = $this->migrationService->getMigrationStats();
      $this->io()->definitionList([
        'Total files mapped' => number_format($stats['total_files']),
        'Files already with media' => number_format($stats['files_with_media']),
        'Files needing migration' => number_format($stats['files_without_media']),
      ]);

    }
    catch (\Exception $e) {
      $this->io()->error('CSV generation failed: ' . $e->getMessage());
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
  public function migrate($options = ['limit' => 10000]) {
    $limit = (int) $options['limit'];

    $this->io()->title('SAHO Media Migration');

    try {
      $stats = $this->migrationService->getMigrationStats();

      $this->io()->definitionList([
        'Total files' => number_format($stats['total_files']),
        'Files with media' => number_format($stats['files_with_media']),
        'Files needing migration' => number_format($stats['files_without_media']),
        'Migration progress' => $stats['migration_progress'] . '%',
      ]);

      if ($stats['files_without_media'] === 0) {
        $this->io()->success('All files already have media entities! Migration complete.');
        return self::EXIT_SUCCESS;
      }

      $files_to_migrate = $this->migrationService->getFilesNeedingMigration($limit);

      if (empty($files_to_migrate)) {
        $this->io()->warning('No files found to migrate.');
        return self::EXIT_SUCCESS;
      }

      $count = count($files_to_migrate);
      $this->io()->note("Found {$count} files to migrate.");

      if ($count > 0) {
        $sample = array_slice($files_to_migrate, 0, 3);
        $sample_rows = [];
        foreach ($sample as $file) {
          $sample_rows[] = [
            $file['fid'],
            substr($file['filename'], 0, 40) . (strlen($file['filename']) > 40 ? '...' : ''),
            $this->getFileTypeFromMime($file['filemime']),
            $this->formatFileSize((int) $file['filesize']),
          ];
        }
        $this->io()->table(['FID', 'Filename', 'Type', 'Size'], $sample_rows);

        if ($count > 3) {
          $this->io()->note("... and " . ($count - 3) . " more files");
        }
      }

      if (!$this->io()->confirm("Proceed with migrating {$count} files?", FALSE)) {
        throw new UserAbortException();
      }

      $batch = $this->migrationService->createMigrationBatch($files_to_migrate);
      batch_set($batch);
      // Use Drupal's batch processor instead of deprecated Drush function.
      \Drupal::service('batch.processor')->process();

      $this->io()->success('Migration batch completed! Check results above.');

    }
    catch (UserAbortException $e) {
      $this->io()->note('Migration cancelled by user.');
      throw $e;
    }
    catch (\Exception $e) {
      $this->io()->error('Migration failed: ' . $e->getMessage());
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
    $this->io()->title('Validating Media Migration');

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

      if ($all_passed) {
        $this->io()->success('All validation checks passed!');
      }
      else {
        $this->io()->warning('Some validation issues found. Check results above.');
      }

      $this->io()->section('Migration Statistics');
      $this->migrationStatus();

    }
    catch (\Exception $e) {
      $this->io()->error('Validation failed: ' . $e->getMessage());
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
      $this->io()->error("CSV file not found: {$file}");
      return self::EXIT_FAILURE;
    }

    $this->io()->title("Importing migration data from CSV: {$file}");

    try {
      $file_data = $this->migrationService->processCsvFile($file);

      if (empty($file_data)) {
        $this->io()->error('No valid file data found in CSV.');
        return self::EXIT_FAILURE;
      }

      $count = count($file_data);
      $this->io()->note("Found {$count} files in CSV.");

      if (!$this->io()->confirm("Proceed with migrating {$count} files from CSV?", FALSE)) {
        throw new UserAbortException();
      }

      $batch = $this->migrationService->createMigrationBatch($file_data);
      batch_set($batch);
      // Use Drupal's batch processor instead of deprecated Drush function.
      \Drupal::service('batch.processor')->process();

      $this->io()->success('CSV import batch completed!');

    }
    catch (UserAbortException $e) {
      $this->io()->note('CSV import cancelled by user.');
      throw $e;
    }
    catch (\Exception $e) {
      $this->io()->error('CSV import failed: ' . $e->getMessage());
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
