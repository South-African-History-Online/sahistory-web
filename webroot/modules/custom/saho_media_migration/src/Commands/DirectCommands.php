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

      $batch = $service->createMigrationBatch($files);
      batch_set($batch);
      // Use Drupal's batch processor instead of deprecated Drush function.
      \Drupal::service('batch.processor')->process();

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
   * @command saho:csv
   * @aliases scsv
   * @usage saho:csv
   *   Generate CSV mapping file
   */
  public function generateCsv() {
    try {
      $service = \Drupal::service('saho_media_migration.migrator');
      $filename = $service->generateCsvMapping();
      $this->output()->writeln("✅ CSV generated: {$filename}");
    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ CSV generation failed: ' . $e->getMessage());
    }
  }

}
