<?php

namespace Drupal\saho_media_migration\Commands;

use Drush\Commands\DrushCommands;

/**
 * Simple SAHO commands that work.
 */
class DirectCommands extends DrushCommands {

  /**
   * Test SAHO migration commands.
   *
   * @command saho:test
   * @aliases st
   * @usage saho:test
   *   Test if SAHO commands work
   */
  public function test() {
    $this->output()->writeln('✅ SAHO commands are working!');

    try {
      $service = \Drupal::service('saho_media_migration.migrator');
      $stats = $service->getMigrationStats();
      $this->output()->writeln("📊 Found {$stats['total_files']} files, {$stats['migration_progress']}% migrated");
    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Service error: ' . $e->getMessage());
    }
  }

  /**
   * Show SAHO migration status.
   *
   * @command saho:status
   * @aliases sms
   * @usage saho:status
   *   Show migration status
   */
  public function status() {
    try {
      $service = \Drupal::service('saho_media_migration.migrator');
      $stats = $service->getMigrationStats();

      // Output table in plain text format.
      $this->output()->writeln('');
      $this->output()->writeln('Migration Status:');
      $this->output()->writeln('----------------');
      $this->output()->writeln('Total Files: ' . number_format($stats['total_files']));
      $this->output()->writeln('Files with Media: ' . number_format($stats['files_with_media']));
      $this->output()->writeln('Files Needing Migration: ' . number_format($stats['files_without_media']));
      $this->output()->writeln('Migration Progress: ' . $stats['migration_progress'] . '%');
      $this->output()->writeln('');

      if ($stats['migration_progress'] < 100) {
        $this->output()->writeln('⚠️  Migration incomplete. Run \'drush saho:migrate\' to continue.');
      }
      else {
        $this->output()->writeln('✅ Migration complete!');
      }

    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Error: ' . $e->getMessage());
    }
  }

  /**
   * Migrate SAHO files to media entities.
   *
   * @command saho:migrate
   * @aliases smig
   * @option limit Maximum number of files to migrate
   * @usage saho:migrate --limit=100
   *   Migrate files with limit
   */
  public function migrate($options = ['limit' => 1000]) {
    $limit = (int) $options['limit'];

    try {
      $service = \Drupal::service('saho_media_migration.migrator');
      $stats = $service->getMigrationStats();

      $this->output()->writeln('');
      $this->output()->writeln('=== SAHO Media Migration ===');
      $this->output()->writeln("ℹ️  Files needing migration: {$stats['files_without_media']}");
      $this->output()->writeln('');

      if ($stats['files_without_media'] === 0) {
        $this->output()->writeln('✅ All files already have media entities!');
        return;
      }

      $files = $service->getFilesNeedingMigration($limit);
      $count = count($files);

      // Simple confirmation with y/n prompt.
      $this->output()->writeln("Migrate {$count} files? [y/n]");
      $answer = trim(fgets(STDIN));
      if (strtolower($answer) !== 'y') {
        return;
      }

      // Process the files directly instead of using batch API which can have
      // compatibility issues in CLI context.
      $this->output()->writeln("Starting migration of {$count} files...");
      $this->output()->writeln('');

      // Get the operations from the batch and process them directly.
      $processed = 0;

      foreach ($files as $file) {
        try {
          $media = $service->createMediaEntity($file);
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
    catch (\Exception $e) {
      $this->output()->writeln('❌ Migration failed: ' . $e->getMessage());
    }
  }

  /**
   * Validate SAHO migration.
   *
   * @command saho:validate
   * @aliases sval
   * @usage saho:validate
   *   Validate migration integrity
   */
  public function validate() {
    try {
      $service = \Drupal::service('saho_media_migration.migrator');
      $results = $service->validateMigration();

      foreach ($results as $result) {
        $icon = $result['status'] === 'pass' ? '✅' : ($result['status'] === 'warning' ? '⚠️' : '❌');
        $this->output()->writeln("{$icon} {$result['message']}");
      }

    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Validation failed: ' . $e->getMessage());
    }
  }

  /**
   * Generate CSV mapping.
   *
   * @command saho:generate-csv
   * @aliases sgc,saho:csv,scsv
   * @usage saho:generate-csv
   *   Generate CSV mapping file
   */
  public function generateCsv() {
    $this->output()->writeln('');
    $this->output()->writeln('=== GENERATING CSV MAPPING FILE ===');
    $this->output()->writeln('');

    try {
      $service = \Drupal::service('saho_media_migration.migrator');
      $filename = $service->generateCsvMapping();
      $this->output()->writeln("✅ CSV file generated: {$filename}");

      $stats = $service->getMigrationStats();
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

}
