<?php

namespace Drupal\saho_media_migration\Commands;

use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Simple SAHO commands that work.
 */
class SahoCommands extends DrushCommands {

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
    } catch (\Exception $e) {
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
      
      $this->io()->table(['Metric', 'Count'], [
        ['Total Files', number_format($stats['total_files'])],
        ['Files with Media', number_format($stats['files_with_media'])],
        ['Files Needing Migration', number_format($stats['files_without_media'])],
        ['Migration Progress', $stats['migration_progress'] . '%'],
      ]);
      
      if ($stats['migration_progress'] < 100) {
        $this->io()->warning("Migration incomplete. Run 'drush saho:migrate' to continue.");
      } else {
        $this->io()->success("Migration complete!");
      }
      
    } catch (\Exception $e) {
      $this->io()->error('Error: ' . $e->getMessage());
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
      
      $this->io()->title('SAHO Media Migration');
      $this->io()->note("Files needing migration: {$stats['files_without_media']}");
      
      if ($stats['files_without_media'] === 0) {
        $this->io()->success('All files already have media entities!');
        return;
      }

      $files = $service->getFilesNeedingMigration($limit);
      $count = count($files);
      
      if (!$this->io()->confirm("Migrate {$count} files?")) {
        return;
      }

      $batch = $service->createMigrationBatch($files);
      batch_set($batch);
      drush_backend_batch_process();
      
    } catch (\Exception $e) {
      $this->io()->error('Migration failed: ' . $e->getMessage());
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
      
    } catch (\Exception $e) {
      $this->io()->error('Validation failed: ' . $e->getMessage());
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
      $this->io()->success("CSV generated: {$filename}");
    } catch (\Exception $e) {
      $this->io()->error('CSV generation failed: ' . $e->getMessage());
    }
  }

}