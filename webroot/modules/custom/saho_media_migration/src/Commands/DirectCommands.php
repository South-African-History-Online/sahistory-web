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

  /**
   * Build comprehensive file mapping database.
   *
   * @command saho:build-mapping
   * @aliases sbm
   * @usage saho:build-mapping
   *   Scan all archive directories and build file mapping database
   */
  public function buildMapping() {
    $this->output()->writeln('🔍 Building comprehensive file mapping database...');
    $this->output()->writeln('');
    
    try {
      $service = \Drupal::service('saho_media_migration.file_mapping');
      $mapping = $service->buildFileMapping();
      $total_files = array_sum(array_map('count', $mapping));
      
      $this->output()->writeln('✅ File mapping built successfully!');
      $this->output()->writeln("📁 Total files mapped: " . number_format($total_files));
      $this->output()->writeln("🗂️  Unique filenames: " . number_format(count($mapping)));
      $this->output()->writeln('');
      $this->output()->writeln('💡 You can now use other commands to fix file references.');
    } catch (\Exception $e) {
      $this->output()->writeln('❌ Error: ' . $e->getMessage());
    }
  }

  /**
   * Fix article images with "file uploads" pattern.
   *
   * @command saho:fix-article-images
   * @aliases sfai
   * @option dry-run Show what would be changed without making changes
   * @option limit Maximum number of nodes to process
   * @usage saho:fix-article-images --dry-run
   *   Preview changes without applying them
   */
  public function fixArticleImages($options = ['dry-run' => FALSE, 'limit' => 100]) {
    $dry_run = $options['dry-run'];
    $limit = (int) $options['limit'];
    
    $this->output()->writeln('🖼️  ' . ($dry_run ? 'PREVIEWING' : 'FIXING') . ' article image references...');
    $this->output()->writeln('');
    
    try {
      $service = \Drupal::service('saho_media_migration.file_mapping');
      $results = $service->fixArticleImagePaths($dry_run, $limit);
      
      $this->output()->writeln('📊 Results:');
      $this->output()->writeln("   Processed: {$results['processed']} nodes");
      $this->output()->writeln("   Fixed: {$results['fixed']} nodes");
      
      if ($dry_run && $results['fixed'] > 0) {
        $this->output()->writeln('');
        $this->output()->writeln('💡 Run without --dry-run to apply these changes.');
      } elseif ($results['fixed'] > 0) {
        $this->output()->writeln('');
        $this->output()->writeln('✅ Article image references updated!');
        $this->output()->writeln('💡 Clear caches: drush cache:rebuild');
      }
    } catch (\Exception $e) {
      $this->output()->writeln('❌ Error: ' . $e->getMessage());
    }
  }

  /**
   * Fix DISA PDF references with symlinks.
   *
   * @command saho:fix-disa-pdfs
   * @aliases sfdp
   * @option create-symlinks Create symbolic links instead of updating content
   * @usage saho:fix-disa-pdfs --create-symlinks
   *   Create symbolic links to maintain original URL structure
   */
  public function fixDisaPdfs($options = ['create-symlinks' => TRUE]) {
    $create_symlinks = $options['create-symlinks'];
    
    $this->output()->writeln('🔗 Creating symbolic links for DISA PDFs...');
    $this->output()->writeln('');
    
    try {
      $service = \Drupal::service('saho_media_migration.file_mapping');
      $results = $service->fixDisaPdfPaths($create_symlinks, FALSE, 100);
      
      $this->output()->writeln('📊 Results:');
      $this->output()->writeln("   Symlinks created: {$results['symlinks_created']}");
      
      if ($results['symlinks_created'] > 0) {
        $this->output()->writeln('✅ DISA symlinks created successfully!');
        $this->output()->writeln('💡 Original URLs will now work without content changes.');
      } else {
        $this->output()->writeln('ℹ️  No new symlinks needed (may already exist).');
      }
    } catch (\Exception $e) {
      $this->output()->writeln('❌ Error: ' . $e->getMessage());
    }
  }

  /**
   * Generate comprehensive audit report.
   *
   * @command saho:audit-files
   * @aliases saf
   * @usage saho:audit-files
   *   Generate comprehensive file audit report
   */
  public function auditFiles() {
    $this->output()->writeln('🔍 Generating comprehensive file audit report...');
    $this->output()->writeln('');
    
    try {
      $service = \Drupal::service('saho_media_migration.file_mapping');
      $report = $service->generateAuditReport();
      
      // Display broken references
      if (!empty($report['broken_references'])) {
        $this->output()->writeln('❌ BROKEN REFERENCES: ' . count($report['broken_references']));
        $this->output()->writeln('');
      }
      
      // Display fixable patterns
      if (!empty($report['fixable_patterns'])) {
        $this->output()->writeln('✅ AUTOMATICALLY FIXABLE:');
        $this->output()->writeln('-------------------------');
        
        foreach ($report['fixable_patterns'] as $pattern => $info) {
          $confidence_icon = $info['confidence'] === 'high' ? '🎯' : '⚠️';
          $this->output()->writeln("   {$confidence_icon} {$info['description']}: {$info['count']} occurrences");
          
          // Suggest specific commands
          if ($pattern === 'file_uploads') {
            $this->output()->writeln('      💡 Fix with: drush saho:fix-article-images');
          } elseif ($pattern === 'disa_pdfs') {
            $this->output()->writeln('      💡 Fix with: drush saho:fix-disa-pdfs');
          }
        }
        $this->output()->writeln('');
      }
      
      // Summary
      $total_issues = count($report['broken_references']) + count($report['missing_files'] ?? []);
      $fixable_issues = array_sum(array_column($report['fixable_patterns'], 'count'));
      
      $this->output()->writeln('📋 SUMMARY:');
      $this->output()->writeln('-----------');
      $this->output()->writeln("Total issues found: {$total_issues}");
      $this->output()->writeln("Automatically fixable: {$fixable_issues}");
      
      if ($fixable_issues > 0) {
        $this->output()->writeln('');
        $this->output()->writeln('🚀 RECOMMENDED ACTIONS:');
        $this->output()->writeln('1. drush saho:build-mapping');
        $this->output()->writeln('2. drush saho:fix-article-images --dry-run');
        $this->output()->writeln('3. drush saho:fix-disa-pdfs');
      }
    } catch (\Exception $e) {
      $this->output()->writeln('❌ Error: ' . $e->getMessage());
    }
  }

}