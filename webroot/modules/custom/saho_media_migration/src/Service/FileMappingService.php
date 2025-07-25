<?php

namespace Drupal\saho_media_migration\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for building comprehensive file mappings and automated path resolution.
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
   * The logger factory.
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

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
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->database = $database;
    $this->fileSystem = $file_system;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Build comprehensive file mapping database.
   */
  public function buildFileMapping(): array {
    $mapping = [];
    $sites_path = DRUPAL_ROOT . '/sites/default/files';
    
    $this->loggerFactory->get('saho_file_mapping')->info('Starting file mapping build...');
    
    // Scan all archive directories
    foreach ($this->archiveDirectories as $archive_dir) {
      $archive_path = $sites_path . '/' . $archive_dir;
      if (is_dir($archive_path)) {
        $this->loggerFactory->get('saho_file_mapping')->info('Scanning directory: @dir', ['@dir' => $archive_dir]);
        $this->scanDirectory($archive_path, $archive_dir, $mapping);
      }
    }
    
    // Store mapping in database for quick lookups
    $this->storeMappingInDatabase($mapping);
    
    $this->loggerFactory->get('saho_file_mapping')->info('File mapping build complete. Total files: @count', 
      ['@count' => array_sum(array_map('count', $mapping))]);
    
    return $mapping;
  }

  /**
   * Process article images with "file uploads" pattern.
   */
  public function fixArticleImagePaths($dry_run = FALSE, $limit = 100): array {
    $results = ['processed' => 0, 'fixed' => 0, 'errors' => []];
    
    // Find content with "file uploads" or "file%20uploads" patterns
    $query = $this->database->select('node__body', 'nb');
    $query->fields('nb', ['entity_id', 'body_value']);
    $query->condition($this->database->orConditionGroup()
      ->condition('body_value', '%file%20uploads%', 'LIKE')
      ->condition('body_value', '%file uploads%', 'LIKE')
    );
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
   * Process DISA PDF paths with DC structure.
   */
  public function fixDisaPdfPaths($create_symlinks = FALSE, $dry_run = FALSE, $limit = 100): array {
    $results = ['processed' => 0, 'fixed' => 0, 'symlinks_created' => 0, 'errors' => []];
    
    if ($create_symlinks) {
      $results['symlinks_created'] = $this->createDisaSymlinks();
      return $results; // If creating symlinks, no content changes needed
    }
    
    // Find content with DISA DC pattern
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
   * Process file uploads patterns in content.
   */
  private function processFileUploadsInContent(string $content): string {
    return preg_replace_callback(
      '#sites/default/files/file(?:%20|\s)uploads(?:%20|\s)/([^"\s)]+)#i',
      function ($matches) {
        $filename = urldecode($matches[1]);
        return $this->findReplacementPath($filename, 'file_uploads') ?? $matches[0];
      },
      $content
    );
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
   * Find replacement path for a file.
   */
  private function findReplacementPath(string $filename, string $context = 'general'): ?string {
    $sites_path = DRUPAL_ROOT . '/sites/default/files';
    
    // Context-specific directory priorities
    $search_order = match ($context) {
      'file_uploads' => ['archive-files', 'archive-files3', 'archive-files2'],
      'disa_pdf' => ['archive-files', 'archive-files2', 'archive-files3'],
      default => $this->archiveDirectories,
    };
    
    foreach ($search_order as $dir) {
      $potential_path = $sites_path . '/' . $dir . '/' . $filename;
      if (file_exists($potential_path)) {
        return "sites/default/files/{$dir}/{$filename}";
      }
    }
    
    return null;
  }

  /**
   * Create symlinks for DISA files to maintain URL structure.
   */
  private function createDisaSymlinks(): int {
    $created = 0;
    $sites_path = DRUPAL_ROOT . '/sites/default/files';
    $dc_base_path = $sites_path . '/DC';
    
    // Ensure DC directory exists
    if (!is_dir($dc_base_path)) {
      mkdir($dc_base_path, 0755, TRUE);
    }
    
    // Search for DISA PDFs in archive directories
    foreach (['archive-files', 'archive-files2'] as $archive_dir) {
      $archive_path = $sites_path . '/' . $archive_dir;
      
      if (!is_dir($archive_path)) {
        continue;
      }
      
      $iterator = new \DirectoryIterator($archive_path);
      
      foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.pdf')) {
          $filename = $file->getFilename();
          $base_name = str_replace('.pdf', '', $filename);
          
          // Create nested directory structure: DC/basename/
          $nested_dir = $dc_base_path . '/' . $base_name;
          if (!is_dir($nested_dir)) {
            mkdir($nested_dir, 0755, TRUE);
          }
          
          // Create symlink
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
    } catch (\Exception $e) {
      $this->loggerFactory->get('saho_file_mapping')->error('Error scanning directory @dir: @error', [
        '@dir' => $path,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Store file mapping in database for performance.
   */
  private function storeMappingInDatabase(array $mapping): void {
    // Create table if it doesn't exist
    $schema = $this->database->schema();
    if (!$schema->tableExists('saho_file_mapping')) {
      $this->createMappingTable();
    }
    
    // Clear existing mappings
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
    
    // Look for file references that don't exist
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
    $query->range(0, 1000); // Limit for performance
    
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
    
    // Common problematic patterns
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
    
    // File uploads pattern
    $file_uploads_count = $this->countPattern('%file%20uploads%');
    if ($file_uploads_count > 0) {
      $fixable['file_uploads'] = [
        'count' => $file_uploads_count,
        'description' => 'Article images with "file uploads" pattern',
        'confidence' => 'high',
      ];
    }
    
    // DISA DC pattern
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