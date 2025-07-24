<?php

namespace Drupal\saho_media_migration\Commands;

use Drupal\saho_media_migration\Service\MediaMigrationService;
use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Component\Utility\Bytes;

/**
 * Generate test content using REAL SAHO files from the database.
 */
class SahoRealContentCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The media migration service.
   *
   * @var \Drupal\saho_media_migration\Service\MediaMigrationService
   */
  protected $migrationService;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new SahoRealContentCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\saho_media_migration\Service\MediaMigrationService $migration_service
   *   The media migration service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    MediaMigrationService $migration_service,
    FileSystemInterface $file_system,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->migrationService = $migration_service;
    $this->fileSystem = $file_system;
  }

  /**
   * Generate test content using REAL SAHO biographical images and documents.
   *
   * @param int $count
   *   Number of nodes to create.
   * @param array $options
   *   Generation options.
   *
   * @option content-type Content type machine name (default: article)
   * @option use-broken Use files without media entities (shows broken state)
   * @option use-fixed Use files with media entities (shows fixed state)
   * @option bio-pics-only Only use biographical pictures from public://bio_pics/
   * @option documents-only Only use PDF and document files
   * @option batch-size Number of nodes to create per batch
   *
   * @command saho:generate-real-test-content
   * @aliases sgrt
   * @usage saho:generate-real-test-content 100 --bio-pics-only --use-broken
   *   Generate 100 nodes with real SAHO bio pics showing broken state
   * @usage saho:generate-real-test-content 100 --bio-pics-only --use-fixed
   *   Generate 100 nodes with real SAHO bio pics showing fixed state  
   * @usage saho:generate-real-test-content 50 --documents-only --use-broken
   *   Generate 50 nodes with real SAHO documents showing broken state
   */
  public function generateRealTestContent(
    $count = 100,
    array $options = [
      'content-type' => 'article',
      'use-broken' => FALSE,
      'use-fixed' => FALSE,
      'bio-pics-only' => FALSE,
      'documents-only' => FALSE,
      'batch-size' => 25,
    ],
  ) {
    $content_type = $options['content-type'];
    $use_broken = $options['use-broken'];
    $use_fixed = $options['use-fixed'];
    $bio_pics_only = $options['bio-pics-only'];
    $documents_only = $options['documents-only'];
    $batch_size = (int) $options['batch-size'];

    $this->io()->title("Generating {$count} test nodes using REAL SAHO content");

    // Get actual SAHO files from database.
    $real_files = $this->getRealSahoFiles($use_broken, $use_fixed, $bio_pics_only, $documents_only);

    if (empty($real_files)) {
      $this->io()->error('No suitable SAHO files found. Check your filter options.');
      return;
    }

    $this->io()->note(sprintf('Found %d real SAHO files to use', count($real_files)));
    $this->showFileSample($real_files);

    // Generate realistic test nodes.
    $created = 0;
    $failed = 0;
    $batches = ceil($count / $batch_size);

    for ($batch = 1; $batch <= $batches; $batch++) {
      $batch_count = min($batch_size, $count - $created);
      
      $this->io()->note(sprintf('Processing batch %d/%d (%d nodes)', $batch, $batches, $batch_count));

      for ($i = 0; $i < $batch_count; $i++) {
        try {
          // Create realistic SAHO content
          $node_data = $this->generateRealisticSahoNode(
            $content_type,
            $real_files,
            $use_broken,
            $bio_pics_only,
            $documents_only
          );

          $node = $this->createTestNode($node_data);
          
          if ($node) {
            $created++;
            if ($this->output()->isVerbose()) {
              $this->output()->writeln("  ✅ Created: {$node->label()} (NID: {$node->id()})");
            }
          } else {
            $failed++;
          }
        } catch (\Exception $e) {
          $failed++;
          $this->output()->writeln("  ❌ Failed: {$e->getMessage()}");
        }
      }

      // Progress update
      $percent = round(($created / $count) * 100, 1);
      $this->io()->note("Progress: {$created}/{$count} ({$percent}%)");
    }

    // Final summary
    $this->io()->success("Real SAHO test content generation completed!");
    $this->io()->definitionList([
      'Nodes created' => number_format($created),
      'Nodes failed' => number_format($failed),
      'Content type' => $content_type,
      'File source' => $this->getFileSourceDescription($bio_pics_only, $documents_only),
      'Migration state' => $use_broken ? 'Broken (before migration)' : ($use_fixed ? 'Fixed (after migration)' : 'Mixed'),
    ]);

    if ($created > 0) {
      $this->showCreatedExamples($content_type, $created);
    }
  }

  /**
   * Create before/after demonstration using real SAHO content.
   *
   * @param array $options
   *   Demo options
   *
   * @option nodes-per-state Number of nodes per state (broken/fixed)
   * @option focus-type Type of content to focus on: 'bio-pics', 'documents', 'mixed'
   *
   * @command saho:create-real-migration-demo
   * @aliases scrmd
   * @usage saho:create-real-migration-demo --focus-type=bio-pics
   *   Create demo with real biographical pictures
   * @usage saho:create-real-migration-demo --focus-type=documents --nodes-per-state=25
   *   Create demo with real historical documents
   */
  public function createRealMigrationDemo(
    array $options = [
      'nodes-per-state' => 50,
      'focus-type' => 'bio-pics',
    ],
  ) {
    $nodes_per_state = (int) $options['nodes-per-state'];
    $focus_type = $options['focus-type'];

    $this->io()->title('Creating Migration Demo with REAL SAHO Content');

    $bio_pics_only = ($focus_type === 'bio-pics');
    $documents_only = ($focus_type === 'documents');

    // 1. Create nodes showing BROKEN state (before migration)
    $this->io()->section('Creating nodes with BROKEN media (using real SAHO files)');
    $this->generateRealTestContent($nodes_per_state, [
      'content-type' => 'article',
      'use-broken' => TRUE,
      'bio-pics-only' => $bio_pics_only,
      'documents-only' => $documents_only,
      'batch-size' => 25,
    ]);

    // 2. Create nodes showing FIXED state (after migration)  
    $this->io()->section('Creating nodes with FIXED media (using real SAHO files)');
    $this->generateRealTestContent($nodes_per_state, [
      'content-type' => 'article',
      'use-fixed' => TRUE,
      'bio-pics-only' => $bio_pics_only,
      'documents-only' => $documents_only,
      'batch-size' => 25,
    ]);

    // 3. Provide testing instructions
    $this->io()->section('Testing Instructions');
    $this->io()->listing([
      "Visit /admin/content and filter by 'BROKEN Real SAHO' to see broken references",
      "Visit /admin/content and filter by 'FIXED Real SAHO' to see working references",
      "Compare how the same real SAHO files display differently",
      "Run migration: drush saho:migrate --priority=high",
      "Verify that broken nodes now work like the fixed examples",
    ]);

    $this->generateRealContentReport($focus_type);
  }

  /**
   * Show real SAHO files that will be affected by migration.
   *
   * @param array $options
   *   Analysis options
   *
   * @option type File type to analyze: 'bio-pics', 'documents', 'all'
   * @option limit Number of files to show
   * @option show-usage Show which files are actually used in content
   *
   * @command saho:analyze-real-files
   * @aliases sarf
   * @usage saho:analyze-real-files --type=bio-pics --show-usage
   *   Analyze real biographical pictures and their usage
   * @usage saho:analyze-real-files --type=documents --limit=20
   *   Show 20 real document files
   */
  public function analyzeRealFiles(
    array $options = [
      'type' => 'all',
      'limit' => 50,
      'show-usage' => FALSE,
    ],
  ) {
    $type = $options['type'];
    $limit = (int) $options['limit'];
    $show_usage = $options['show-usage'];

    $this->io()->title('Analysis of Real SAHO Files');

    // Get file statistics
    $stats = $this->getRealSahoFileStats($type);
    $this->displayRealFileStats($stats);

    // Show sample files
    $sample_files = $this->getRealSahoFiles(FALSE, FALSE,
      ($type === 'bio-pics'),
      ($type === 'documents'),
      $limit
    );

    if (!empty($sample_files)) {
      $this->displayRealFileSample($sample_files, $show_usage);
    }

    // Show migration impact
    $this->showMigrationImpact($type);
  }

  /**
   * Get real SAHO files from the database based on criteria.
   */
  protected function getRealSahoFiles($use_broken = FALSE, $use_fixed = FALSE, $bio_pics_only = FALSE, $documents_only = FALSE, $limit = 1000) {
    $query = $this->database->select('file_managed', 'f');
    $query->fields('f', ['fid', 'filename', 'uri', 'filemime', 'filesize', 'created']);

    // Filter by migration state
    if ($use_broken) {
      $query->leftJoin('media_field_data', 'mfd', 'f.fid = mfd.thumbnail__target_id');
      $query->isNull('mfd.thumbnail__target_id');
    } elseif ($use_fixed) {
      $query->innerJoin('media_field_data', 'mfd', 'f.fid = mfd.thumbnail__target_id');
    }

    // Filter by file type and include DC directory
    if ($bio_pics_only) {
      $uri_group = $query->orConditionGroup()
        ->condition('f.uri', 'public://bio_pics/%', 'LIKE')
        ->condition('f.uri', 'public://DC/%', 'LIKE');
      $query->condition($uri_group);
      $query->condition('f.filemime', 'image/%', 'LIKE');
    } elseif ($documents_only) {
      $mime_group = $query->orConditionGroup()
        ->condition('f.filemime', 'application/pdf')
        ->condition('f.filemime', 'application/msword')
        ->condition('f.filemime', 'application/vnd.openxmlformats-officedocument%', 'LIKE')
        ->condition('f.filemime', 'text/%', 'LIKE');
      $query->condition($mime_group);
      
      // Include both bio_pics and DC directories
      $uri_group = $query->orConditionGroup()
        ->condition('f.uri', 'public://bio_pics/%', 'LIKE')
        ->condition('f.uri', 'public://DC/%', 'LIKE');
      $query->condition($uri_group);
    } else {
      // For 'all' type, include both directories
      $uri_group = $query->orConditionGroup()
        ->condition('f.uri', 'public://bio_pics/%', 'LIKE')
        ->condition('f.uri', 'public://DC/%', 'LIKE');
      $query->condition($uri_group);
    }

    // Add file usage info if available
    $query->leftJoin('file_usage', 'fu', 'f.fid = fu.fid');
    $query->addField('fu', 'count', 'usage_count');

    // Order by usage and size
    $query->orderBy('fu.count', 'DESC');
    $query->orderBy('f.filesize', 'ASC');
    $query->range(0, $limit);

    return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Generate realistic SAHO node content.
   */
  protected function generateRealisticSahoNode($content_type, $real_files, $use_broken, $bio_pics_only, $documents_only) {
    // Select random files for this node
    $selected_files = $this->selectFilesForNode($real_files, $bio_pics_only, $documents_only);
    
    // Generate appropriate title and content
    $title = $this->generateRealisticTitle($selected_files, $use_broken);
    $body_content = $this->generateRealisticBodyContent($selected_files, $use_broken);

    $data = [
      'type' => $content_type,
      'title' => $title,
      'status' => 1,
      'uid' => 1,
      'body' => [
        'value' => $body_content,
        'format' => 'full_html',
      ],
    ];

    // Add image fields
    if ($bio_pics_only || !$documents_only) {
      $image_files = array_filter($selected_files, function ($file) {
        return strpos($file['filemime'], 'image/') === 0;
      });

      if (!empty($image_files)) {
        $data['field_image'] = [];
        foreach ($image_files as $file) {
          $alt_text = $this->generateRealisticAltText($file['filename']);
          $data['field_image'][] = [
            'target_id' => $file['fid'],
            'alt' => $alt_text,
            'title' => $this->generateImageTitle($file['filename']),
          ];
        }
      }
    }

    // Add document fields
    if ($documents_only || (!$bio_pics_only && !$documents_only)) {
      $doc_files = array_filter($selected_files, function ($file) {
        return strpos($file['filemime'], 'application/') === 0 || strpos($file['filemime'], 'text/') === 0;
      });

      if (!empty($doc_files)) {
        $data['field_files'] = [];
        foreach ($doc_files as $file) {
          $data['field_files'][] = [
            'target_id' => $file['fid'],
            'description' => $this->generateDocumentDescription($file['filename']),
          ];
        }
      }
    }

    return $data;
  }

  /**
   * Select appropriate files for a single node.
   */
  protected function selectFilesForNode($available_files, $bio_pics_only, $documents_only) {
    $selected = [];
    $max_files = $bio_pics_only ? 2 : ($documents_only ? 3 : 4);
    
    // Randomly select files, but ensure we get a good mix
    $file_count = min($max_files, count($available_files));
    $random_keys = array_rand($available_files, $file_count);
    
    if (!is_array($random_keys)) {
      $random_keys = [$random_keys];
    }
    
    foreach ($random_keys as $key) {
      $selected[] = $available_files[$key];
    }
    
    return $selected;
  }

  /**
   * Generate realistic titles based on actual SAHO file names.
   */
  protected function generateRealisticTitle($files, $use_broken) {
    $state_prefix = $use_broken ? 'BROKEN Real SAHO' : 'FIXED Real SAHO';
    
    // Extract person names from bio pic filenames
    $names = [];
    foreach ($files as $file) {
      if (strpos($file['uri'], 'bio_pics/') !== FALSE) {
        $filename = pathinfo($file['filename'], PATHINFO_FILENAME);
        // Clean up filename to get person name
        $name = str_replace(['_', '-', '.jpg', '.jpeg', '.png'], ' ', $filename);
        $name = ucwords(trim($name));
        if (strlen($name) > 3) {
          $names[] = $name;
        }
      }
    }
    
    if (!empty($names)) {
      $primary_name = $names[0];
      return "{$state_prefix}: Biography of {$primary_name}";
    }
    
    // For documents or other files
    $doc_files = array_filter($files, function ($f) {
      return strpos($f['filemime'], 'application/') === 0;
    });
    
    if (!empty($doc_files)) {
      return "{$state_prefix}: Historical Document Analysis";
    }
    
    return "{$state_prefix}: South African History Content";
  }

  /**
   * Generate realistic body content based on files.
   */
  protected function generateRealisticBodyContent($files, $use_broken) {
    $content = [];
    
    if ($use_broken) {
      $content[] = "<p><strong>⚠️ DEMONSTRATION: This content shows BROKEN media references (before migration)</strong></p>";
      $content[] = "<p>The images and documents attached to this article represent real SAHO historical content, but the media entities were never properly created during the Drupal 7 to 8 migration. This means:</p>";
      $content[] = "<ul>";
      $content[] = "<li>Images may not display correctly</li>";
      $content[] = "<li>Alt text and captions may be missing</li>";
      $content[] = "<li>File downloads may not work properly</li>";
      $content[] = "<li>Media library management is broken</li>";
      $content[] = "</ul>";
    } else {
      $content[] = "<p><strong>✅ DEMONSTRATION: This content shows FIXED media references (after migration)</strong></p>";
      $content[] = "<p>The images and documents attached to this article represent real SAHO historical content with properly created media entities. This demonstrates:</p>";
      $content[] = "<ul>";
      $content[] = "<li>Images display correctly with proper alt text</li>";
      $content[] = "<li>File downloads work seamlessly</li>";
      $content[] = "<li>Media library management is fully functional</li>";
      $content[] = "<li>Content editing workflows are restored</li>";
      $content[] = "</ul>";
    }
    
    // Add context about the actual files
    $bio_pics = array_filter($files, function ($f) {
      return strpos($f['uri'], 'bio_pics/') !== FALSE;
    });
    
    if (!empty($bio_pics)) {
      $content[] = "<h3>Biographical Images</h3>";
      $content[] = "<p>This article includes historical photographs from SAHO's biographical collection:</p>";
      foreach ($bio_pics as $file) {
        $filename = pathinfo($file['filename'], PATHINFO_FILENAME);
        $clean_name = ucwords(str_replace(['_', '-'], ' ', $filename));
        $content[] = "<li>Historical photograph: {$clean_name} (" . \Drupal\Core\File\FileSystem::formatSize($file['filesize']) . ")</li>";
      }
    }
    
    $docs = array_filter($files, function($f) {
      return strpos($f['filemime'], 'application/') === 0;
    });
    
    if (!empty($docs)) {
      $content[] = "<h3>Historical Documents</h3>";
      $content[] = "<p>Supporting historical documentation:</p>";
      foreach ($docs as $file) {
        $content[] = "<li>Document: {$file['filename']} (" . \Drupal\Core\File\FileSystem::formatSize($file['filesize']) . ")</li>";
      }
    }
    
    $content[] = "<p><em>Generated for SAHO media migration testing using real historical content.</em></p>";
    
    return implode("\n", $content);
  }

  /**
   * Generate realistic alt text from filenames.
   */
  protected function generateRealisticAltText($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $clean_name = ucwords(str_replace(['_', '-', '.'], ' ', $name));
    return "Historical photograph of {$clean_name} from SAHO archives";
  }

  /**
   * Generate image titles.
   */
  protected function generateImageTitle($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $clean_name = ucwords(str_replace(['_', '-', '.'], ' ', $name));
    return "SAHO Biography: {$clean_name}";
  }

  /**
   * Generate document descriptions.
   */
  protected function generateDocumentDescription($filename) {
    return "Historical document: {$filename} - Part of SAHO's digital archive collection";
  }

  /**
   * Create a test node with error handling.
   */
  protected function createTestNode($data) {
    try {
      $node_storage = $this->entityTypeManager->getStorage('node');
      $node = $node_storage->create($data);
      $node->save();
      return $node;
    } catch (\Exception $e) {
      $this->logger()->error('Failed to create SAHO test node: ' . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Show sample of files that will be used.
   */
  protected function showFileSample($files, $limit = 10) {
    $this->io()->section('Sample SAHO files to be used:');
    
    $sample = array_slice($files, 0, $limit);
    $rows = [];
    
    foreach ($sample as $file) {
      $path_parts = explode('/', $file['uri']);
      $directory = count($path_parts) > 2 ? $path_parts[1] : 'root';
      
      $rows[] = [
        'fid' => $file['fid'],
        'filename' => substr($file['filename'], 0, 30) . (strlen($file['filename']) > 30 ? '...' : ''),
        'type' => $this->getFileTypeLabel($file['filemime']),
        'size' => \Drupal\Core\File\FileSystem::formatSize($file['filesize']),
        'directory' => $directory,
        'usage' => $file['usage_count'] ?? 'N/A',
      ];
    }
    
    $this->io()->table(['FID', 'Filename', 'Type', 'Size', 'Directory', 'Usage'], $rows);
    
    if (count($files) > $limit) {
      $this->io()->note('... and ' . (count($files) - $limit) . ' more files');
    }
  }

  /**
   * Get file type label for display.
   */
  protected function getFileTypeLabel($mime_type) {
    if (strpos($mime_type, 'image/') === 0) {
      return 'Image';
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
    return 'Other';
  }

  /**
   * Get file source description.
   */
  protected function getFileSourceDescription($bio_pics_only, $documents_only) {
    if ($bio_pics_only) {
      return 'Real SAHO biographical pictures';
    }
    if ($documents_only) {
      return 'Real SAHO documents and PDFs';
    }
    return 'Mixed real SAHO content';
  }

  /**
   * Show examples of created content.
   */
  protected function showCreatedExamples($content_type, $created_count) {
    $this->io()->section('Testing your new content:');
    
    $this->io()->listing([
      "Visit /admin/content to see the {$created_count} new test nodes",
      "Filter by titles containing 'BROKEN Real SAHO' or 'FIXED Real SAHO'",
      "Click on individual nodes to see how real SAHO files display",
      "Compare broken vs fixed states side-by-side",
      "Run migration and verify the broken content gets fixed",
    ]);

    // Show direct links to some examples
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', $content_type)
      ->condition('title', '%Real SAHO%', 'LIKE')
      ->sort('created', 'DESC')
      ->range(0, 5);

    $nids = $query->execute();
    
    if (!empty($nids)) {
      $this->io()->note('Direct links to test content:');
      foreach ($nids as $nid) {
        $this->output()->writeln("  - /node/{$nid}");
      }
    }
  }

  /**
   * Additional helper methods for file statistics and reporting...
   */
  protected function getRealSahoFileStats($type) {
    $stats = [
      'total_files' => 0,
      'total_size' => 0,
      'by_type' => [],
      'by_directory' => [],
      'usage' => [
        'used' => 0,
        'unused' => 0,
      ],
    ];

    // Get all files matching the type
    $files = $this->getRealSahoFiles(FALSE, FALSE,
      ($type === 'bio-pics'),
      ($type === 'documents'),
      1000
    );

    foreach ($files as $file) {
      $stats['total_files']++;
      $stats['total_size'] += $file['filesize'];

      // Track by MIME type
      $mime_type = $this->getFileTypeLabel($file['filemime']);
      if (!isset($stats['by_type'][$mime_type])) {
        $stats['by_type'][$mime_type] = ['count' => 0, 'size' => 0];
      }
      $stats['by_type'][$mime_type]['count']++;
      $stats['by_type'][$mime_type]['size'] += $file['filesize'];

      // Track by directory
      $path_parts = explode('/', $file['uri']);
      $directory = count($path_parts) > 2 ? $path_parts[1] : 'root';
      if (!isset($stats['by_directory'][$directory])) {
        $stats['by_directory'][$directory] = ['count' => 0, 'size' => 0];
      }
      $stats['by_directory'][$directory]['count']++;
      $stats['by_directory'][$directory]['size'] += $file['filesize'];

      // Track usage
      if (!empty($file['usage_count'])) {
        $stats['usage']['used']++;
      } else {
        $stats['usage']['unused']++;
      }
    }

    return $stats;
  }

  /**
   * Display file statistics.
   *
   * @param array $stats
   *   The statistics to display.
   */
  protected function displayRealFileStats($stats) {
    if (empty($stats)) {
      return;
    }

    $this->io()->section('File Statistics');

    // Overall stats
    $this->io()->definitionList([
      'Total Files' => number_format($stats['total_files']),
      'Total Size' => \Drupal\Core\File\FileSystem::formatSize($stats['total_size']),
      'Files in Use' => number_format($stats['usage']['used']),
      'Unused Files' => number_format($stats['usage']['unused']),
    ]);

    // Stats by type
    $this->io()->section('Files by Type');
    $type_rows = [];
    foreach ($stats['by_type'] as $type => $data) {
      $type_rows[] = [
        $type,
        number_format($data['count']),
        \Drupal\Core\File\FileSystem::formatSize($data['size']),
        round(($data['count'] / $stats['total_files']) * 100, 1) . '%',
      ];
    }
    $this->io()->table(
      ['Type', 'Count', 'Total Size', 'Percentage'],
      $type_rows
    );

    // Stats by directory
    $this->io()->section('Files by Directory');
    $dir_rows = [];
    foreach ($stats['by_directory'] as $dir => $data) {
      $dir_rows[] = [
        $dir,
        number_format($data['count']),
        \Drupal\Core\File\FileSystem::formatSize($data['size']),
        round(($data['count'] / $stats['total_files']) * 100, 1) . '%',
      ];
    }
    $this->io()->table(
      ['Directory', 'Count', 'Total Size', 'Percentage'],
      $dir_rows
    );
  }

  /**
   * Display a sample of files.
   *
   * @param array $files
   *   The files to display.
   * @param bool $show_usage
   *   Whether to show usage information.
   */
  protected function displayRealFileSample($files, $show_usage) {
    if (empty($files)) {
      return;
    }

    $this->io()->section('File Sample Details');

    $rows = [];
    foreach ($files as $file) {
      $path_parts = explode('/', $file['uri']);
      $directory = count($path_parts) > 2 ? $path_parts[1] : 'root';

      $row = [
        'fid' => $file['fid'],
        'filename' => substr($file['filename'], 0, 30) . (strlen($file['filename']) > 30 ? '...' : ''),
        'type' => $this->getFileTypeLabel($file['filemime']),
        'size' => \Drupal\Core\File\FileSystem::formatSize($file['filesize']),
        'directory' => $directory,
      ];

      if ($show_usage) {
        $row['usage'] = $file['usage_count'] ?? 'N/A';
        $row['created'] = date('Y-m-d', $file['created']);
      }

      $rows[] = $row;
    }

    $headers = ['FID', 'Filename', 'Type', 'Size', 'Directory'];
    if ($show_usage) {
      $headers[] = 'Usage';
      $headers[] = 'Created';
    }

    $this->io()->table($headers, $rows);
  }

  /**
   * Show the impact of migration on files.
   *
   * @param string $type
   *   The type of files to analyze.
   */
  protected function showMigrationImpact($type) {
    $this->io()->section('Migration Impact Analysis');

    // Get files in both states
    $broken_files = $this->getRealSahoFiles(TRUE, FALSE,
      ($type === 'bio-pics'),
      ($type === 'documents'),
      1000
    );

    $fixed_files = $this->getRealSahoFiles(FALSE, TRUE,
      ($type === 'bio-pics'),
      ($type === 'documents'),
      1000
    );

    $total_files = count($broken_files) + count($fixed_files);
    if ($total_files === 0) {
      $this->io()->warning('No files found matching the specified criteria.');
      return;
    }

    $broken_size = array_reduce($broken_files, function ($carry, $file) {
      return $carry + $file['filesize'];
    }, 0);

    $fixed_size = array_reduce($fixed_files, function ($carry, $file) {
      return $carry + $file['filesize'];
    }, 0);

    // Display impact stats
    $this->io()->definitionList([
      'Files Needing Migration' => number_format(count($broken_files)),
      'Files Already Migrated' => number_format(count($fixed_files)),
      'Migration Progress' => round((count($fixed_files) / $total_files) * 100, 1) . '%',
      'Storage to Process' => \Drupal\Core\File\FileSystem::formatSize($broken_size),
      'Storage Already Processed' => \Drupal\Core\File\FileSystem::formatSize($fixed_size),
    ]);

    // Show recommendations
    $this->io()->section('Migration Recommendations');
    $this->io()->listing([
      sprintf('%d files need to be migrated', count($broken_files)),
      sprintf('Approximately %s of data will be processed', \Drupal\Core\File\FileSystem::formatSize($broken_size)),
      'Run migration using: drush saho:migrate --priority=high',
      'Monitor progress in the Drupal admin interface',
      'Verify migrated files in the Media Library',
    ]);
  }

  /**
   * Generate a report about the real content demo.
   *
   * @param string $focus_type
   *   The type of content being demonstrated.
   */
  protected function generateRealContentReport($focus_type) {
    $this->io()->section('Real Content Demo Report');
    
    $this->io()->definitionList([
      'Demo type' => ucfirst($focus_type) . ' focused demonstration',
      'Real files used' => 'Actual SAHO historical content from database',
      'Purpose' => 'Show before/after migration states with authentic content',
      'Testing approach' => 'Compare broken vs fixed nodes side-by-side',
    ]);
  }

}