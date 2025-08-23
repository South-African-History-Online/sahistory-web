<?php

namespace Drupal\saho_media_migration\Commands;

use Drush\Commands\DrushCommands;

/**
 * Simple SAHO commands that work.
 */
class DirectCommands extends DrushCommands {

  /**
   * Convert all images to WebP format.
   *
   * @command saho:webp-convert
   * @aliases swc
   * @usage saho:webp-convert
   *   Convert all existing images to WebP format
   */
  public function webpConvert() {
    $this->output()->writeln('Converting images to WebP...');

    // Use the PHP script for conversion.
    $script_path = DRUPAL_ROOT . '/../convert_webp_production_final.php';
    if (file_exists($script_path)) {
      $this->output()->writeln('Running WebP conversion script...');
      passthru("php $script_path --fix-existing", $return_code);

      if ($return_code === 0) {
        $this->output()->writeln('WebP conversion completed successfully!');
      }
      else {
        $this->output()->writeln('WebP conversion failed with code: ' . $return_code);
      }
    }
    else {
      $this->output()->writeln('Conversion script not found at: ' . $script_path);
    }
  }

  /**
   * Fix WebP file naming (remove double extensions).
   *
   * @command saho:webp-fix
   * @aliases swf
   * @usage saho:webp-fix
   *   Fix WebP files with double extensions
   */
  public function webpFix() {
    $this->output()->writeln('Fixing WebP file names...');

    $script_path = DRUPAL_ROOT . '/../fix_webp_names.php';
    if (file_exists($script_path)) {
      passthru("php $script_path", $return_code);

      if ($return_code === 0) {
        $this->output()->writeln('WebP naming fix completed!');
      }
      else {
        $this->output()->writeln('WebP fix failed with code: ' . $return_code);
      }
    }
    else {
      $this->output()->writeln('Fix script not found at: ' . $script_path);
    }
  }

  /**
   * Show WebP conversion status.
   *
   * @command saho:webp-status
   * @aliases sws
   * @usage saho:webp-status
   *   Show current WebP conversion status
   */
  public function webpStatus() {
    $this->output()->writeln('WebP Conversion Status');
    $this->output()->writeln('=====================');

    // Find the correct files directory.
    $files_dir = DRUPAL_ROOT . '/sites/default/files';
    if (!is_dir($files_dir)) {
      $files_dir = DRUPAL_ROOT . '/../webroot/sites/default/files';
      if (!is_dir($files_dir)) {
        $this->output()->writeln('Files directory not found.');
        return;
      }
    }

    $total_images = 0;
    $webp_files = 0;
    $double_ext = 0;

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($files_dir, \RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $filename = $file->getFilename();
        if (preg_match('/\.(jpg|jpeg|png)$/i', $filename)) {
          $total_images++;
        }
        if (preg_match('/\.webp$/i', $filename)) {
          $webp_files++;
          if (preg_match('/\.(jpg|jpeg|png)\.webp$/i', $filename)) {
            $double_ext++;
          }
        }
      }
    }

    $this->output()->writeln("Total images (JPG/PNG): " . number_format($total_images));
    $this->output()->writeln("WebP files created: " . number_format($webp_files));
    $this->output()->writeln("Double extension files: " . number_format($double_ext));

    if ($total_images > 0) {
      $percentage = round(($webp_files / $total_images) * 100, 1);
      $this->output()->writeln("Conversion rate: {$percentage}%");
    }

    if ($double_ext > 0) {
      $this->output()->writeln("⚠️  Warning: {$double_ext} files have double extensions and need fixing");
    }
    else {
      $this->output()->writeln("✅ All WebP files have correct naming");
    }
  }

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
    }
    catch (\Exception $e) {
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
      }
      elseif ($results['fixed'] > 0) {
        $this->output()->writeln('');
        $this->output()->writeln('✅ Article image references updated!');
        $this->output()->writeln('💡 Clear caches: drush cache:rebuild');
      }
    }
    catch (\Exception $e) {
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
      }
      else {
        $this->output()->writeln('ℹ️  No new symlinks needed (may already exist).');
      }
    }
    catch (\Exception $e) {
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

      // Display broken references.
      if (!empty($report['broken_references'])) {
        $this->output()->writeln('❌ BROKEN REFERENCES: ' . count($report['broken_references']));
        $this->output()->writeln('');
      }

      // Display fixable patterns.
      if (!empty($report['fixable_patterns'])) {
        $this->output()->writeln('✅ AUTOMATICALLY FIXABLE:');
        $this->output()->writeln('-------------------------');

        foreach ($report['fixable_patterns'] as $pattern => $info) {
          $confidence_icon = $info['confidence'] === 'high' ? '🎯' : '⚠️';
          $this->output()->writeln("   {$confidence_icon} {$info['description']}: {$info['count']} occurrences");

          // Suggest specific commands.
          if ($pattern === 'file_uploads') {
            $this->output()->writeln('      💡 Fix with: drush saho:fix-article-images');
          }
          elseif ($pattern === 'disa_pdfs') {
            $this->output()->writeln('      💡 Fix with: drush saho:fix-disa-pdfs');
          }
        }
        $this->output()->writeln('');
      }

      // Summary.
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
    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Error: ' . $e->getMessage());
    }
  }

  /**
   * Search for file reference patterns in content.
   *
   * @command saho:find-patterns
   * @aliases sfp
   * @usage saho:find-patterns
   *   Search for different file reference patterns
   */
  public function findPatterns() {
    $this->output()->writeln('🔍 Searching for file reference patterns...');
    $this->output()->writeln('');

    try {
      $db = \Drupal::database();

      $patterns = [
        'file%20uploads' => '%file%20uploads%',
        'file uploads' => '%file uploads%',
        'file_uploads' => '%file_uploads%',
        'broken images' => '%<img%src%sites/default/files/%',
        'broken links' => '%<a%href%sites/default/files/%',
        'archive-files refs' => '%archive-files%',
        'DC refs' => '%/DC/%',
        'any file refs' => '%sites/default/files/%',
      ];

      foreach ($patterns as $name => $pattern) {
        $query = $db->select('node__body', 'nb');
        $query->addExpression('COUNT(DISTINCT entity_id)', 'nodes');
        $query->addExpression('COUNT(*)', 'total_refs');
        $query->condition('body_value', $pattern, 'LIKE');
        $result = $query->execute()->fetchAssoc();

        if ($result['nodes'] > 0) {
          $this->output()->writeln("📄 {$name}: {$result['nodes']} nodes, {$result['total_refs']} references");
        }
      }

    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Error: ' . $e->getMessage());
    }
  }

  /**
   * Fix ALL broken file references comprehensively.
   *
   * @command saho:fix-all-files
   * @aliases sfaf
   * @option dry-run Show what would be changed without making changes
   * @option limit Maximum number of nodes to process
   * @option offset Skip this many nodes (for continuing processing)
   * @usage saho:fix-all-files --dry-run --limit=50
   *   Preview comprehensive file fixes
   */
  public function fixAllBrokenFiles($options = ['dry-run' => FALSE, 'limit' => 100, 'offset' => 0]) {
    $dry_run = $options['dry-run'];
    $limit = (int) $options['limit'];
    $offset = (int) $options['offset'];

    $this->output()->writeln('🔧 ' . ($dry_run ? 'PREVIEWING' : 'FIXING') . ' ALL broken file references...');
    if ($offset > 0) {
      $this->output()->writeln("📍 Starting from offset: {$offset}");
    }
    $this->output()->writeln('');

    try {
      $service = \Drupal::service('saho_media_migration.file_mapping');
      $results = $service->fixAllBrokenReferences($dry_run, $limit, $offset);

      $this->output()->writeln('📊 Results:');
      $this->output()->writeln("   Processed: {$results['processed']} nodes");
      $this->output()->writeln("   Fixed: {$results['fixed']} nodes");

      // Show sample references being processed.
      if (!empty($results['samples'])) {
        $this->output()->writeln('');
        $this->output()->writeln('🔍 Sample file references found:');
        foreach ($results['samples'] as $sample) {
          $this->output()->writeln("   Node {$sample['node_id']}:");
          foreach ($sample['found_refs'] as $ref) {
            $this->output()->writeln("     - {$ref}");
          }
        }
      }

      if ($results['processed'] > 0) {
        $next_offset = $offset + $results['processed'];
        $this->output()->writeln('');
        $this->output()->writeln("💡 To continue: drush saho:fix-all-files --offset={$next_offset} --limit={$limit}");
      }

      if ($dry_run && $results['fixed'] > 0) {
        $this->output()->writeln('');
        $this->output()->writeln('💡 Run without --dry-run to apply these changes.');
      }
      elseif ($results['fixed'] > 0) {
        $this->output()->writeln('');
        $this->output()->writeln('✅ File references updated!');
      }
    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Error: ' . $e->getMessage());
    }
  }

  /**
   * Fix specific URL encoding variations in file uploads.
   *
   * @command saho:fix-file-uploads-encoding
   * @aliases sfue
   * @option dry-run Show what would be changed without making changes
   * @usage saho:fix-file-uploads-encoding --dry-run
   *   Fix the file%20uploads%20/ vs file%20uploads/ encoding issue
   */
  public function fixFileUploadsEncoding($options = ['dry-run' => FALSE]) {
    $dry_run = $options['dry-run'];

    $this->output()->writeln('🔧 ' . ($dry_run ? 'PREVIEWING' : 'FIXING') . ' file uploads URL encoding variations...');
    $this->output()->writeln('');

    try {
      // Target specifically the problematic pattern with extra %20.
      $db = \Drupal::database();
      $query = $db->select('node__body', 'nb');
      $query->fields('nb', ['entity_id', 'body_value']);
      $query->condition('body_value', '%file%20uploads%20/%', 'LIKE');

      $results = $query->execute();
      $fixed = 0;
      $processed = 0;

      foreach ($results as $node) {
        $original = $node->body_value;
        // Fix encoding issue: file%20uploads%20/ -> file%20uploads/.
        $updated = str_replace('file%20uploads%20/', 'file%20uploads/', $original);

        if ($original !== $updated) {
          if (!$dry_run) {
            $db->update('node__body')
              ->fields(['body_value' => $updated])
              ->condition('entity_id', $node->entity_id)
              ->execute();
          }
          $fixed++;
          $this->output()->writeln("  ✅ Node {$node->entity_id}: Fixed encoding issue");
        }
        $processed++;
      }

      $this->output()->writeln('');
      $this->output()->writeln("📊 Results: Processed {$processed}, Fixed {$fixed}");

      if ($dry_run && $fixed > 0) {
        $this->output()->writeln('💡 Run without --dry-run to apply changes.');
      }

    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Error: ' . $e->getMessage());
    }
  }

  /**
   * Fix remaining file upload patterns with enhanced query.
   *
   * @command saho:fix-remaining-uploads
   * @aliases sfru
   * @option dry-run Show what would be changed without making changes
   * @usage saho:fix-remaining-uploads --dry-run
   *   Fix remaining file upload patterns
   */
  public function fixRemainingUploads($options = ['dry-run' => FALSE]) {
    $dry_run = $options['dry-run'];

    $this->output()->writeln('🔧 ' . ($dry_run ? 'PREVIEWING' : 'FIXING') . ' remaining file upload patterns...');
    $this->output()->writeln('');

    try {
      $db = \Drupal::database();

      // Find ALL file upload variations.
      $query = $db->select('node__body', 'nb');
      $query->fields('nb', ['entity_id', 'body_value']);
      $or = $query->orConditionGroup();
      $or->condition('body_value', '%file%20uploads/%', 'LIKE');
      $or->condition('body_value', '%file uploads/%', 'LIKE');
      $or->condition('body_value', '%file_uploads/%', 'LIKE');
      $query->condition($or);

      $results = $query->execute();
      $fixed = 0;
      $processed = 0;

      foreach ($results as $node) {
        $original = $node->body_value;

        // Try multiple replacement patterns.
        $updated = preg_replace_callback(
          '#sites/default/files/file(?:%20|\s|_)+uploads/?/([^"\s)]+)#i',
          function ($matches) {
            $filename = urldecode($matches[1]);

            // Search archive directories.
            $sites_path = DRUPAL_ROOT . '/sites/default/files';
            $dirs = ['archive-files', 'archive-files3', 'archive-files2'];

            foreach ($dirs as $dir) {
              $path = $sites_path . '/' . $dir . '/' . $filename;
              if (file_exists($path)) {
                return "sites/default/files/{$dir}/{$filename}";
              }
            }

            // Keep original if not found.
            return $matches[0];
          },
          $original
        );

        if ($original !== $updated) {
          if (!$dry_run) {
            $db->update('node__body')
              ->fields(['body_value' => $updated])
              ->condition('entity_id', $node->entity_id)
              ->execute();
          }
          $fixed++;
          $this->output()->writeln("  ✅ Node {$node->entity_id}: Fixed file upload patterns");
        }
        $processed++;
      }

      $this->output()->writeln('');
      $this->output()->writeln("📊 Results: Processed {$processed}, Fixed {$fixed}");

    }
    catch (\Exception $e) {
      $this->output()->writeln('❌ Error: ' . $e->getMessage());
    }
  }

}
