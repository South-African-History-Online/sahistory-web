<?php

namespace Drupal\saho_media_migration\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;

/**
 * Service for building file mappings and automated path resolution.
 */
class FileMappingService {

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * The file system service.
   */
  protected FileSystemInterface $fileSystem;


  /**
   * Archive directories to search.
   */
  protected array $archiveDirectories = [
    'archive-files',
    'archive-files2',
    'archive-files3',
    'archive-files4',
    'archive-files5',
    'archive_files',
  ];

  public function __construct(
    Connection $database,
    FileSystemInterface $file_system,
  ) {
    $this->database = $database;
    $this->fileSystem = $file_system;
  }

  /**
   * Build comprehensive file mapping database.
   */
  public function buildFileMapping(): array {
    $mapping = [];
    $sites_path = DRUPAL_ROOT . '/sites/default/files';

    // Scan all archive directories.
    foreach ($this->archiveDirectories as $archive_dir) {
      $archive_path = $sites_path . '/' . $archive_dir;
      if (is_dir($archive_path)) {
        $this->scanDirectory($archive_path, $archive_dir, $mapping);
      }
    }

    // Store mapping in database for quick lookups.
    $this->storeMappingInDatabase($mapping);

    return $mapping;
  }

  /**
   * Process article images with "file uploads" pattern - all variations.
   */
  public function fixArticleImagePaths($dry_run = FALSE, $limit = 100): array {
    $results = ['processed' => 0, 'fixed' => 0, 'errors' => []];

    // Find content with ANY file uploads pattern variations.
    $query = $this->database->select('node__body', 'nb');
    $query->fields('nb', ['entity_id', 'body_value']);
    $or = $query->orConditionGroup();
    $or->condition('body_value', '%file%20uploads%', 'LIKE');
    $or->condition('body_value', '%file uploads%', 'LIKE');
    $or->condition('body_value', '%file_uploads%', 'LIKE');
    // The failing case with extra space.
    $or->condition('body_value', '%file%20uploads%20/%', 'LIKE');
    $query->condition($or);
    $query->range(0, $limit);

    $nodes = $query->execute();

    foreach ($nodes as $node) {
      $original_body = $node->body_value;
      $updated_body = $this->processFileUploadsInContent($original_body);

      if ($original_body !== $updated_body) {
        if (!$dry_run) {
          $this->database->update('node__body')
            ->fields(['body_value' => $updated_body])
            ->condition('entity_id', $node->entity_id)
            ->execute();
        }
        $results['fixed']++;
      }

      $results['processed']++;
    }

    return $results;
  }

  /**
   * Fix all broken file references comprehensively.
   */
  public function fixAllBrokenReferences($dry_run = FALSE, $limit = 100, $offset = 0): array {
    $results = ['processed' => 0, 'fixed' => 0, 'errors' => [], 'samples' => []];

    // Find ALL content with sites/default/files references.
    $query = $this->database->select('node__body', 'nb');
    $query->fields('nb', ['entity_id', 'body_value']);
    $query->condition('body_value', '%sites/default/files/%', 'LIKE');
    $query->range($offset, $limit);
    // Consistent ordering.
    $query->orderBy('entity_id', 'ASC');

    $nodes = $query->execute();

    foreach ($nodes as $node) {
      $original_body = $node->body_value;
      $updated_body = $this->fixAllFileReferencesInContent($original_body);

      // Collect samples of what we're trying to fix.
      if (count($results['samples']) < 5) {
        preg_match_all('#sites/default/files/([^"\s)>]+)#i', $original_body, $matches);
        if (!empty($matches[0])) {
          $results['samples'][] = [
            'node_id' => $node->entity_id,
          // First 3 references.
            'found_refs' => array_slice($matches[0], 0, 3),
          ];
        }
      }

      if ($original_body !== $updated_body) {
        if (!$dry_run) {
          $this->database->update('node__body')
            ->fields(['body_value' => $updated_body])
            ->condition('entity_id', $node->entity_id)
            ->execute();
        }
        $results['fixed']++;
      }

      $results['processed']++;
    }

    return $results;
  }

  /**
   * Process DISA PDF paths with DC structure.
   */
  public function fixDisaPdfPaths($create_symlinks = FALSE, $dry_run = FALSE, $limit = 100): array {
    $results = ['processed' => 0, 'fixed' => 0, 'symlinks_created' => 0, 'errors' => []];

    if ($create_symlinks) {
      $results['symlinks_created'] = $this->createDisaSymlinks();
      // If creating symlinks, no content changes needed.
      return $results;
    }

    // Find content with DISA DC pattern.
    $query = $this->database->select('node__body', 'nb');
    $query->fields('nb', ['entity_id', 'body_value']);
    $query->condition('body_value', '%/DC/%', 'LIKE');
    $query->condition('body_value', '%.pdf%', 'LIKE');
    $query->range(0, $limit);

    $nodes = $query->execute();

    foreach ($nodes as $node) {
      $original_body = $node->body_value;
      $updated_body = $this->processDisaPdfsInContent($original_body);

      if ($original_body !== $updated_body) {
        if (!$dry_run) {
          $this->database->update('node__body')
            ->fields(['body_value' => $updated_body])
            ->condition('entity_id', $node->entity_id)
            ->execute();
        }
        $results['fixed']++;
      }

      $results['processed']++;
    }

    return $results;
  }

  /**
   * Generate comprehensive audit report.
   */
  public function generateAuditReport(): array {
    $report = [
      'broken_references' => $this->findBrokenReferences(),
      'missing_files' => $this->findMissingFiles(),
      'orphaned_patterns' => $this->findOrphanedPatterns(),
      'fixable_patterns' => $this->identifyFixablePatterns(),
    ];

    return $report;
  }

  /**
   * Process file uploads patterns in content - handles ALL variations.
   */
  private function processFileUploadsInContent(string $content): string {
    // Handle multiple file uploads patterns.
    $patterns = [
      // Pattern 1: file%20uploads%20/ (extra space - the failing case)
      '#sites/default/files/file%20uploads%20/([^"\s)]+)#i',
      // Pattern 2: file%20uploads/ (standard)
      '#sites/default/files/file%20uploads/([^"\s)]+)#i',
      // Pattern 3: file uploads / (unencoded with space)
      '#sites/default/files/file\s+uploads\s*/([^"\s)]+)#i',
      // Pattern 4: file_uploads/ (underscore)
      '#sites/default/files/file_uploads/([^"\s)]+)#i',
    ];

    foreach ($patterns as $pattern) {
      $content = preg_replace_callback(
        $pattern,
        function ($matches) {
          $filename = urldecode($matches[1]);
          return $this->findReplacementPath($filename, 'file_uploads') ?? $matches[0];
        },
        $content
      );
    }

    return $content;
  }

  /**
   * Process DISA PDFs in content.
   */
  private function processDisaPdfsInContent(string $content): string {
    return preg_replace_callback(
      '#sites/default/files/DC/([^/]+)/\1\.pdf#i',
      function ($matches) {
        $filename = $matches[1] . '.pdf';
        return $this->findReplacementPath($filename, 'disa_pdf') ?? $matches[0];
      },
      $content
    );
  }

  /**
   * Fix all file references in content using comprehensive pattern matching.
   */
  private function fixAllFileReferencesInContent(string $content): string {
    // Pattern to match any sites/default/files reference.
    return preg_replace_callback(
      '#sites/default/files/([^"\s)>]+)#i',
      function ($matches) {
        // Full match: sites/default/files/path/file.ext.
        $full_path = $matches[0];
        // Just the path/file.ext part.
        $file_path = $matches[1];

        // Skip if this is already an archive-files reference.
        if (strpos($file_path, 'archive-files') !== FALSE) {
          return $full_path;
        }

        // Skip if this is already a DC reference (handled by symlinks)
        if (strpos($file_path, '/DC/') !== FALSE) {
          return $full_path;
        }

        // Handle specific patterns that we know need fixing.
        // 1. File uploads patterns (including the %20 space issue)
        if (preg_match('#file(?:%20|\s|_)*uploads(?:%20|\s)*/?(.+)#i', $file_path, $upload_matches)) {
          $filename = urldecode($upload_matches[1]);
          $replacement = $this->findReplacementPath($filename, 'file_uploads');
          return $replacement ?? $full_path;
        }

        // 2. Files in subdirectories that might be in archive
        if (strpos($file_path, '/') !== FALSE) {
          $filename = basename(urldecode($file_path));
          $replacement = $this->findReplacementPath($filename, 'comprehensive');
          // Only replace if found in archive AND not in special subdir.
          if ($replacement && !preg_match('#^(images|css|js|styles)/#', $file_path)) {
            return $replacement;
          }
        }

        // 3. Root level files that might be in archive
        else {
          $filename = urldecode($file_path);
          $replacement = $this->findReplacementPath($filename, 'comprehensive');
          return $replacement ?? $full_path;
        }

        return $full_path;
      },
      $content
    );
  }

  /**
   * Find replacement path for a file.
   */
  private function findReplacementPath(string $filename, string $context = 'general'): ?string {
    $sites_path = DRUPAL_ROOT . '/sites/default/files';

    // Context-specific directory priorities.
    $search_order = match ($context) {
      'file_uploads' => ['archive-files', 'archive-files3', 'archive-files2'],
      'disa_pdf' => ['archive-files', 'archive-files2', 'archive-files3'],
      'comprehensive' => [
        'archive-files',
        'archive-files2',
        'archive-files3',
        'archive-files4',
        'archive-files5',
        'archive_files',
      ],
      default => $this->archiveDirectories,
    };

    foreach ($search_order as $dir) {
      $potential_path = $sites_path . '/' . $dir . '/' . $filename;
      if (file_exists($potential_path)) {
        return "sites/default/files/{$dir}/{$filename}";
      }
    }

    return NULL;
  }

  /**
   * Create symlinks for DISA files to maintain URL structure.
   */
  private function createDisaSymlinks(): int {
    $created = 0;
    $sites_path = DRUPAL_ROOT . '/sites/default/files';
    $dc_base_path = $sites_path . '/DC';

    // Ensure DC directory exists.
    if (!is_dir($dc_base_path)) {
      mkdir($dc_base_path, 0755, TRUE);
    }

    // Search for DISA files in archive directories.
    foreach (['archive-files', 'archive-files2'] as $archive_dir) {
      $archive_path = $sites_path . '/' . $archive_dir;

      if (!is_dir($archive_path)) {
        continue;
      }

      $iterator = new \DirectoryIterator($archive_path);

      foreach ($iterator as $file) {
        if ($file->isFile()) {
          $filename = $file->getFilename();
          $base_name = '';

          // Handle both PDFs and thumbnails.
          if (str_ends_with($filename, '.pdf')) {
            $base_name = str_replace('.pdf', '', $filename);
          }
          elseif (str_ends_with($filename, '.thumb.gif')) {
            $base_name = str_replace('.thumb.gif', '', $filename);
          }
          else {
            // Skip files that aren't PDFs or thumbnails.
            continue;
          }

          // Create nested directory structure: DC/basename/.
          $nested_dir = $dc_base_path . '/' . $base_name;
          if (!is_dir($nested_dir)) {
            mkdir($nested_dir, 0755, TRUE);
          }

          // Create symlink.
          $symlink_path = $nested_dir . '/' . $filename;
          $target_path = $file->getPathname();

          if (!file_exists($symlink_path)) {
            if (symlink($target_path, $symlink_path)) {
              $created++;
            }
          }
        }
      }
    }

    return $created;
  }

  /**
   * Recursively scan directory for files.
   */
  private function scanDirectory(string $path, string $archive_dir, array &$mapping): void {
    try {
      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::LEAVES_ONLY
      );

      foreach ($iterator as $file) {
        if ($file->isFile()) {
          $filename = $file->getFilename();
          $relative_path = str_replace($path . '/', '', $file->getPathname());

          $mapping[$filename][] = [
            'actual_path' => $file->getPathname(),
            'archive_dir' => $archive_dir,
            'relative_path' => $relative_path,
            'filesize' => $file->getSize(),
            'modified' => $file->getMTime(),
          ];
        }
      }
    }
    catch (\Exception $e) {
    }
  }

  /**
   * Store file mapping in database for performance.
   *
   * Uses a transaction to prevent race conditions where concurrent processes
   * could see empty results between truncate and insert operations.
   */
  private function storeMappingInDatabase(array $mapping): void {
    // Create table if it doesn't exist.
    $schema = $this->database->schema();
    if (!$schema->tableExists('saho_file_mapping')) {
      $this->createMappingTable();
    }

    // Use transaction to ensure atomic truncate + insert operation.
    $transaction = $this->database->startTransaction();

    try {
      // Clear existing mappings.
      $this->database->truncate('saho_file_mapping')->execute();

      $insert = $this->database->insert('saho_file_mapping')
        ->fields(['filename', 'actual_path', 'archive_dir', 'relative_path', 'filesize', 'modified', 'created']);

      $timestamp = time();

      foreach ($mapping as $filename => $locations) {
        foreach ($locations as $location) {
          $insert->values([
            'filename' => $filename,
            'actual_path' => $location['actual_path'],
            'archive_dir' => $location['archive_dir'],
            'relative_path' => $location['relative_path'],
            'filesize' => $location['filesize'] ?? 0,
            'modified' => $location['modified'] ?? 0,
            'created' => $timestamp,
          ]);
        }
      }

      $insert->execute();

      // Explicitly commit by destroying the transaction object.
      // Drupal's Transaction commits when the object goes out of scope.
      unset($transaction);
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * Create the file mapping table.
   */
  private function createMappingTable(): void {
    $schema = $this->database->schema();

    $table_schema = [
      'description' => 'Stores mapping between referenced file paths and actual file locations.',
      'fields' => [
        'id' => [
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique mapping ID.',
        ],
        'filename' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'description' => 'The filename being mapped.',
        ],
        'actual_path' => [
          'type' => 'varchar',
          'length' => 500,
          'not null' => TRUE,
          'description' => 'The actual filesystem path to the file.',
        ],
        'archive_dir' => [
          'type' => 'varchar',
          'length' => 100,
          'not null' => TRUE,
          'description' => 'The archive directory where file is located.',
        ],
        'relative_path' => [
          'type' => 'varchar',
          'length' => 500,
          'not null' => TRUE,
          'description' => 'The relative path within the archive directory.',
        ],
        'filesize' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'File size in bytes.',
        ],
        'modified' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'File modification timestamp.',
        ],
        'created' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'When this mapping was created.',
        ],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'filename' => ['filename'],
        'archive_dir' => ['archive_dir'],
        'filename_archive' => ['filename', 'archive_dir'],
      ],
    ];

    $schema->createTable('saho_file_mapping', $table_schema);
  }

  /**
   * Find broken file references in content.
   */
  private function findBrokenReferences(): array {
    $broken = [];

    // Look for file references that don't exist.
    $query = $this->database->select('node__body', 'nb');
    $query->fields('nb', ['entity_id', 'body_value']);
    $query->condition('body_value', '%sites/default/files/%', 'LIKE');

    $results = $query->execute();

    foreach ($results as $row) {
      preg_match_all('#sites/default/files/[^"\s)]+#', $row->body_value, $matches);

      foreach ($matches[0] as $path) {
        $full_path = DRUPAL_ROOT . '/' . $path;
        if (!file_exists($full_path)) {
          $broken[] = [
            'node_id' => $row->entity_id,
            'path' => $path,
            'full_path' => $full_path,
          ];
        }
      }
    }

    return $broken;
  }

  /**
   * Find missing physical files.
   */
  private function findMissingFiles(): array {
    $missing = [];

    $query = $this->database->select('file_managed', 'f');
    $query->fields('f', ['fid', 'uri', 'filename']);
    // Limit for performance.
    $query->range(0, 1000);

    $files = $query->execute();

    foreach ($files as $file) {
      if (!file_exists($file->uri)) {
        $missing[] = [
          'fid' => $file->fid,
          'uri' => $file->uri,
          'filename' => $file->filename,
        ];
      }
    }

    return $missing;
  }

  /**
   * Find orphaned file patterns.
   */
  private function findOrphanedPatterns(): array {
    $patterns = [];

    // Common problematic patterns.
    $pattern_queries = [
      'file_uploads' => '%file%20uploads%',
      'disa_dc' => '%/DC/%',
      'encoded_spaces' => '%20%',
      'double_slashes' => '%//%',
    ];

    foreach ($pattern_queries as $pattern_name => $like_pattern) {
      $query = $this->database->select('node__body', 'nb');
      $query->addExpression('COUNT(*)', 'count');
      $query->condition('body_value', $like_pattern, 'LIKE');

      $count = $query->execute()->fetchField();

      if ($count > 0) {
        $patterns[$pattern_name] = $count;
      }
    }

    return $patterns;
  }

  /**
   * Identify patterns that can be automatically fixed.
   */
  private function identifyFixablePatterns(): array {
    $fixable = [];

    // File uploads pattern.
    $file_uploads_count = $this->countPattern('%file%20uploads%');
    if ($file_uploads_count > 0) {
      $fixable['file_uploads'] = [
        'count' => $file_uploads_count,
        'description' => 'Article images with "file uploads" pattern',
        'confidence' => 'high',
      ];
    }

    // DISA DC pattern.
    $disa_count = $this->countPattern('%/DC/%.pdf%');
    if ($disa_count > 0) {
      $fixable['disa_pdfs'] = [
        'count' => $disa_count,
        'description' => 'DISA PDFs with nested DC structure',
        'confidence' => 'high',
      ];
    }

    return $fixable;
  }

  /**
   * Count occurrences of a pattern.
   */
  private function countPattern(string $pattern): int {
    $query = $this->database->select('node__body', 'nb');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('body_value', $pattern, 'LIKE');

    return (int) $query->execute()->fetchField();
  }

}
