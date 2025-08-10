<?php

namespace Drupal\saho_media_migration\Commands;

use Drupal\Core\Database\Connection;
use Drupal\saho_media_migration\Service\FileMappingService;
use Drush\Commands\DrushCommands;

/**
 * Enhanced Drush commands for automated file path resolution.
 */
class FileMappingCommands extends DrushCommands {

  /**
   * The file mapping service.
   */
  protected FileMappingService $fileMappingService;

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * Constructs a FileMappingCommands object.
   */
  public function __construct(FileMappingService $file_mapping_service, Connection $database) {
    parent::__construct();
    $this->fileMappingService = $file_mapping_service;
    $this->database = $database;
  }

  /**
   * Build comprehensive file mapping database.
   *
   * @command saho:build-mapping
   * @aliases sbm
   * @usage saho:build-mapping
   *   Scan all archive directories and build file mapping database
   */
  public function buildMapping(): void {
    $this->output()->writeln('🔍 Building comprehensive file mapping database...');
    $this->output()->writeln('');

    $mapping = $this->fileMappingService->buildFileMapping();
    $total_files = array_sum(array_map('count', $mapping));

    $this->output()->writeln('✅ File mapping built successfully!');
    $this->output()->writeln("📁 Total files mapped: " . number_format($total_files));
    $this->output()->writeln("🗂️  Unique filenames: " . number_format(count($mapping)));
    $this->output()->writeln('');
    $this->output()->writeln('💡 You can now use other commands to fix file references.');
  }

  /**
   * Fix article images with "file uploads" pattern.
   *
   * @command saho:fix-article-images
   * @aliases sfai
   * @option dry-run Show what would be changed without making changes
   * @option limit Maximum number of nodes to process
   * @usage saho:fix-article-images
   *   Fix article image references with file uploads pattern
   * @usage saho:fix-article-images --dry-run
   *   Preview changes without applying them
   * @usage saho:fix-article-images --limit=50
   *   Process only 50 nodes
   */
  public function fixArticleImages(array $options = ['dry-run' => FALSE, 'limit' => 100]): void {
    $dry_run = $options['dry-run'];
    $limit = (int) $options['limit'];

    $this->output()->writeln('🖼️  ' . ($dry_run ? 'PREVIEWING' : 'FIXING') . ' article image references...');
    $this->output()->writeln('');

    if (!$dry_run && !$this->confirm('This will modify node content. Continue?')) {
      return;
    }

    $results = $this->fileMappingService->fixArticleImagePaths($dry_run, $limit);

    $this->output()->writeln('📊 Results:');
    $this->output()->writeln("   Processed: {$results['processed']} nodes");
    $this->output()->writeln("   Fixed: {$results['fixed']} nodes");

    if (!empty($results['errors'])) {
      $this->output()->writeln('❌ Errors:');
      foreach ($results['errors'] as $error) {
        $this->output()->writeln("   - {$error}");
      }
    }

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

  /**
   * Fix DISA PDF references.
   *
   * @command saho:fix-disa-pdfs
   * @aliases sfdp
   * @option create-symlinks Create symbolic links instead of updating content
   * @option dry-run Show what would be changed without making changes
   * @option limit Maximum number of nodes to process
   * @usage saho:fix-disa-pdfs
   *   Fix DISA PDF file references by updating content
   * @usage saho:fix-disa-pdfs --create-symlinks
   *   Create symbolic links to maintain original URL structure
   * @usage saho:fix-disa-pdfs --dry-run
   *   Preview changes without applying them
   */
  public function fixDisaPdfs(array $options = ['create-symlinks' => FALSE, 'dry-run' => FALSE, 'limit' => 100]): void {
    $create_symlinks = $options['create-symlinks'];
    $dry_run = $options['dry-run'];
    $limit = (int) $options['limit'];

    if ($create_symlinks) {
      $this->output()->writeln('🔗 Creating symbolic links for DISA PDFs...');
      $this->output()->writeln('');

      if (!$dry_run && !$this->confirm('This will create symlinks in the DC directory. Continue?')) {
        return;
      }

      $results = $this->fileMappingService->fixDisaPdfPaths(TRUE, $dry_run, $limit);

      $this->output()->writeln('📊 Results:');
      $this->output()->writeln("   Symlinks created: {$results['symlinks_created']}");

      if ($results['symlinks_created'] > 0) {
        $this->output()->writeln('✅ DISA symlinks created successfully!');
        $this->output()->writeln('💡 Original URLs will now work without content changes.');
      }
    }
    else {
      $this->output()->writeln('📄 ' . ($dry_run ? 'PREVIEWING' : 'FIXING') . ' DISA PDF references...');
      $this->output()->writeln('');

      if (!$dry_run && !$this->confirm('This will modify node content. Continue?')) {
        return;
      }

      $results = $this->fileMappingService->fixDisaPdfPaths(FALSE, $dry_run, $limit);

      $this->output()->writeln('📊 Results:');
      $this->output()->writeln("   Processed: {$results['processed']} nodes");
      $this->output()->writeln("   Fixed: {$results['fixed']} nodes");

      if ($dry_run && $results['fixed'] > 0) {
        $this->output()->writeln('');
        $this->output()->writeln('💡 Run without --dry-run to apply these changes.');
      }
      elseif ($results['fixed'] > 0) {
        $this->output()->writeln('');
        $this->output()->writeln('✅ DISA PDF references updated!');
      }
    }
  }

  /**
   * Generate comprehensive audit report.
   *
   * @command saho:audit-files
   * @aliases saf
   * @option format Output format (table, csv, json)
   * @usage saho:audit-files
   *   Generate comprehensive file audit report
   * @usage saho:audit-files --format=csv
   *   Generate CSV audit report
   */
  public function auditFiles(array $options = ['format' => 'table']): void {
    $format = $options['format'];

    $this->output()->writeln('🔍 Generating comprehensive file audit report...');
    $this->output()->writeln('');

    $report = $this->fileMappingService->generateAuditReport();

    // Display broken references.
    if (!empty($report['broken_references'])) {
      $this->output()->writeln('❌ BROKEN REFERENCES (' . count($report['broken_references']) . '):');
      $this->output()->writeln('--------------------------------------------');

      if ($format === 'table') {
        $rows = [];
        foreach (array_slice($report['broken_references'], 0, 10) as $broken) {
          $rows[] = [
            $broken['node_id'],
            substr($broken['path'], 0, 60) . (strlen($broken['path']) > 60 ? '...' : ''),
          ];
        }

        $this->displayTable(['Node ID', 'Broken Path'], $rows);

        if (count($report['broken_references']) > 10) {
          $this->output()->writeln('... and ' . (count($report['broken_references']) - 10) . ' more');
        }
      }
      $this->output()->writeln('');
    }

    // Display missing files.
    if (!empty($report['missing_files'])) {
      $this->output()->writeln('🗃️  MISSING FILES (' . count($report['missing_files']) . '):');
      $this->output()->writeln('----------------------------------');

      foreach (array_slice($report['missing_files'], 0, 5) as $missing) {
        $this->output()->writeln("   FID {$missing['fid']}: {$missing['filename']}");
      }

      if (count($report['missing_files']) > 5) {
        $this->output()->writeln('   ... and ' . (count($report['missing_files']) - 5) . ' more');
      }
      $this->output()->writeln('');
    }

    // Display orphaned patterns.
    if (!empty($report['orphaned_patterns'])) {
      $this->output()->writeln('🔍 PROBLEMATIC PATTERNS:');
      $this->output()->writeln('------------------------');

      foreach ($report['orphaned_patterns'] as $pattern => $count) {
        $this->output()->writeln("   {$pattern}: {$count} occurrences");
      }
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
          $this->output()->writeln('      💡 Fix with: drush saho:fix-disa-pdfs --create-symlinks');
        }
      }
      $this->output()->writeln('');
    }

    // Summary.
    $total_issues = count($report['broken_references']) + count($report['missing_files']);
    $fixable_issues = array_sum(array_column($report['fixable_patterns'], 'count'));

    $this->output()->writeln('📋 SUMMARY:');
    $this->output()->writeln('-----------');
    $this->output()->writeln("Total issues found: {$total_issues}");
    $this->output()->writeln("Automatically fixable: {$fixable_issues}");

    if ($fixable_issues > 0) {
      $this->output()->writeln('');
      $this->output()->writeln('🚀 RECOMMENDED ACTIONS:');
      $this->output()->writeln('1. drush saho:build-mapping  # Build file mapping database');
      $this->output()->writeln('2. drush saho:fix-article-images --dry-run  # Preview article fixes');
      $this->output()->writeln('3. drush saho:fix-disa-pdfs --create-symlinks  # Fix DISA PDFs');
    }
  }

  /**
   * Process all fixable patterns automatically.
   *
   * @command saho:auto-fix
   * @aliases saf-auto
   * @option dry-run Show what would be changed without making changes
   * @option limit Maximum number of items to process per pattern
   * @usage saho:auto-fix
   *   Automatically fix all known patterns
   * @usage saho:auto-fix --dry-run
   *   Preview all automatic fixes
   */
  public function autoFix(array $options = ['dry-run' => FALSE, 'limit' => 200]): void {
    $dry_run = $options['dry-run'];
    $limit = (int) $options['limit'];

    $this->output()->writeln('🤖 ' . ($dry_run ? 'PREVIEWING' : 'RUNNING') . ' automatic file path fixes...');
    $this->output()->writeln('');

    if (!$dry_run && !$this->confirm('This will automatically fix file references. Continue?')) {
      return;
    }

    $total_fixed = 0;

    // 1. Build mapping first
    $this->output()->writeln('1️⃣  Building file mapping...');
    $this->fileMappingService->buildFileMapping();
    $this->output()->writeln('   ✅ File mapping ready');
    $this->output()->writeln('');

    // 2. Fix article images
    $this->output()->writeln('2️⃣  Processing article images...');
    $article_results = $this->fileMappingService->fixArticleImagePaths($dry_run, $limit);
    $this->output()->writeln("   📊 Processed: {$article_results['processed']}, Fixed: {$article_results['fixed']}");
    $total_fixed += $article_results['fixed'];
    $this->output()->writeln('');

    // 3. Create DISA symlinks (preferred approach)
    $this->output()->writeln('3️⃣  Creating DISA PDF symlinks...');
    $disa_results = $this->fileMappingService->fixDisaPdfPaths(TRUE, $dry_run, $limit);
    $this->output()->writeln("   📊 Symlinks created: {$disa_results['symlinks_created']}");
    $total_fixed += $disa_results['symlinks_created'];
    $this->output()->writeln('');

    // Summary.
    $this->output()->writeln('🎉 AUTO-FIX COMPLETE!');
    $this->output()->writeln('====================');
    $this->output()->writeln("Total fixes applied: {$total_fixed}");

    if ($dry_run && $total_fixed > 0) {
      $this->output()->writeln('');
      $this->output()->writeln('💡 Run without --dry-run to apply these changes.');
    }
    elseif ($total_fixed > 0) {
      $this->output()->writeln('');
      $this->output()->writeln('✅ All automatic fixes applied successfully!');
      $this->output()->writeln('💡 Clear caches: drush cache:rebuild');
    }
    else {
      $this->output()->writeln('');
      $this->output()->writeln('ℹ️  No issues found that can be automatically fixed.');
    }
  }

  /**
   * Search for a specific file across all archive directories.
   *
   * @command saho:find-file
   * @aliases sff
   * @argument filename The filename to search for
   * @usage saho:find-file "chad_article_image_1.jpg"
   *   Find all locations of a specific file
   */
  public function findFile(string $filename): void {
    $this->output()->writeln("🔍 Searching for file: {$filename}");
    $this->output()->writeln('');

    // Check if file mapping exists.
    $query = $this->database->select('saho_file_mapping', 'sfm');
    $query->fields('sfm', ['actual_path', 'archive_dir', 'filesize', 'modified']);
    $query->condition('filename', $filename);

    $results = $query->execute()->fetchAll();

    if (empty($results)) {
      $this->output()->writeln('❌ File not found in mapping database.');
      $this->output()->writeln('💡 Try running: drush saho:build-mapping');
      return;
    }

    $this->output()->writeln("✅ Found {$filename} in " . count($results) . " location(s):");
    $this->output()->writeln('');

    foreach ($results as $result) {
      $size = $this->formatFileSize($result->filesize);
      $modified = date('Y-m-d H:i:s', $result->modified);

      $this->output()->writeln("📁 {$result->archive_dir}/");
      $this->output()->writeln("   Path: {$result->actual_path}");
      $this->output()->writeln("   Size: {$size}");
      $this->output()->writeln("   Modified: {$modified}");
      $this->output()->writeln("   Web path: sites/default/files/{$result->archive_dir}/{$filename}");
      $this->output()->writeln('');
    }
  }

  /**
   * Display a table (wrapper for Drush IO).
   */
  private function displayTable(array $headers, array $rows): void {
    // Use parent's table method directly.
    if (method_exists($this, 'output')) {
      $this->output()->writeln('');
      foreach ($rows as $row) {
        $this->output()->writeln(implode(' | ', $row));
      }
      $this->output()->writeln('');
    }
  }

  /**
   * Ask for confirmation (wrapper for Drush IO).
   */
  protected function confirm($question, $default = FALSE): bool {
    // Use parent's confirm method with proper signature.
    return parent::confirm($question, $default);
  }

  /**
   * Format file size in human-readable format.
   */
  private function formatFileSize(int $size): string {
    $units = ['B', 'KB', 'MB', 'GB'];

    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
      $size /= 1024;
    }

    return round($size, 2) . ' ' . $units[$i];
  }

}
