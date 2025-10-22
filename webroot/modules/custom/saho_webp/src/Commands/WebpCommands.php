<?php

namespace Drupal\saho_webp\Commands;

use Drush\Commands\DrushCommands;
use Drupal\file\Entity\File;

/**
 * Drush commands for WebP management.
 */
class WebpCommands extends DrushCommands {

  /**
   * Convert a single image file to WebP by path.
   *
   * @param string $path
   *   Relative path from files directory
   *   (e.g., "bio_pics/ReggieWilliams_1.jpg").
   *
   * @command saho:webp-file
   * @aliases swf-single
   *
   * @usage saho:webp-file bio_pics/ReggieWilliams_1.jpg
   *   Convert a single file to WebP format.
   */
  public function convertSingleFile($path) {
    return $this->convertSingleFileByPath($path);
  }

  /**
   * Convert all existing images to WebP.
   *
   * @command saho:webp-convert-all
   * @aliases webp-all
   * @usage saho:webp-convert-all
   *   Convert all existing images to WebP format.
   */
  public function convertAll() {
    // Convert all files.
    $this->output()->writeln('Converting all images to WebP...');

    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties([
        'filemime' => ['image/jpeg', 'image/png'],
      ]);

    $converted = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($files as $file) {
      $result = $this->convertFileEntity($file);

      switch ($result) {
        case 'converted':
          $converted++;
          break;

        case 'exists':
          $skipped++;
          break;

        default:
          $errors++;
          break;
      }

      if (($converted + $skipped + $errors) % 100 == 0) {
        $this->output()->writeln("Processed: " . ($converted + $skipped + $errors) . " files");
      }
    }

    $this->output()->writeln("Conversion complete!");
    $this->output()->writeln("Converted: $converted");
    $this->output()->writeln("Skipped: $skipped");
    $this->output()->writeln("Errors: $errors");
  }

  /**
   * Fix WebP file naming (remove double extensions).
   *
   * @command saho:webp-fix-names
   * @aliases webp-fix
   * @usage saho:webp-fix-names
   *   Fix WebP files with double extensions.
   */
  public function fixNames() {
    $this->output()->writeln('Fixing WebP file names...');

    $file_system = \Drupal::service('file_system');
    $files_dir = $file_system->realpath('public://');

    $fixed = 0;
    $removed = 0;

    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($files_dir, \RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
      if ($file->isFile() && preg_match('/\.(jpg|jpeg|png)\.webp$/i', $file->getFilename())) {
        $old_path = $file->getRealPath();
        $new_path = preg_replace('/\.(jpg|jpeg|png)\.webp$/i', '.webp', $old_path);

        if (file_exists($new_path)) {
          unlink($old_path);
          $removed++;
        }
        else {
          rename($old_path, $new_path);
          $fixed++;
        }
      }
    }

    $this->output()->writeln("Fixed: $fixed files");
    $this->output()->writeln("Removed duplicates: $removed files");
  }

  /**
   * Convert a single file entity to WebP (internal method).
   */
  private function convertFileEntity(File $file) {
    $source_path = \Drupal::service('file_system')->realpath($file->getFileUri());
    if (!$source_path || !file_exists($source_path)) {
      return 'missing';
    }

    $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);

    if (file_exists($webp_path)) {
      return 'exists';
    }

    try {
      $mime_type = $file->getMimeType();
      $source_image = NULL;

      switch ($mime_type) {
        case 'image/jpeg':
          $source_image = @imagecreatefromjpeg($source_path);
          break;

        case 'image/png':
          $source_image = @imagecreatefrompng($source_path);
          if ($source_image !== FALSE) {
            imagealphablending($source_image, FALSE);
            imagesavealpha($source_image, TRUE);
          }
          break;
      }

      if ($source_image) {
        $success = @imagewebp($source_image, $webp_path, 80);
        imagedestroy($source_image);

        if ($success) {
          chmod($webp_path, fileperms($source_path));
          return 'converted';
        }
      }
    }
    catch (\Exception $e) {
      // Log error.
    }

    return 'error';
  }

  /**
   * Display WebP conversion status.
   *
   * @command saho:webp-status
   * @usage saho:webp-status
   *   Show current WebP conversion status.
   */
  public function status() {
    $this->output()->writeln("WebP Conversion Status");
    $this->output()->writeln("======================");

    // Check queue status.
    $queue = \Drupal::queue('saho_webp_conversion');
    $queue_count = $queue->numberOfItems();

    if ($queue_count > 0) {
      $this->output()->writeln("Queue items pending: $queue_count");
    }
    else {
      $this->output()->writeln("No queue items pending");
    }

    $this->output()->writeln("Module is active and ready for conversions.");
  }

  /**
   * Convert a single image file to WebP by path (internal method).
   */
  private function convertSingleFileByPath($path) {
    $file_system = \Drupal::service('file_system');

    // Normalize the path - remove leading slashes and 'sites/default/files/'.
    $path = trim($path, '/');
    $path = preg_replace('#^sites/default/files/#', '', $path);

    // Build the full file path.
    $source_path = $file_system->realpath('public://') . '/' . $path;

    if (!file_exists($source_path)) {
      $this->output()->writeln("<error>File not found: $source_path</error>");
      return;
    }

    // Check if it's an image.
    $mime_type = mime_content_type($source_path);
    if (!in_array($mime_type, ['image/jpeg', 'image/png'])) {
      $this->output()->writeln("<error>File is not a JPEG or PNG: $mime_type</error>");
      return;
    }

    // Generate WebP path.
    $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);

    if (file_exists($webp_path)) {
      $webp_size = filesize($webp_path);
      $original_size = filesize($source_path);
      $savings = round((1 - $webp_size / $original_size) * 100, 1);

      $this->output()->writeln("<info>WebP already exists:</info>");
      $this->output()->writeln("  Original: " . $this->formatBytes($original_size));
      $this->output()->writeln("  WebP: " . $this->formatBytes($webp_size) . " ({$savings}% savings)");
      $this->output()->writeln("  Path: $webp_path");
      return;
    }

    // Convert to WebP.
    $this->output()->writeln("Converting: $path");

    try {
      $source_image = NULL;

      switch ($mime_type) {
        case 'image/jpeg':
          $source_image = @imagecreatefromjpeg($source_path);
          break;

        case 'image/png':
          $source_image = @imagecreatefrompng($source_path);
          if ($source_image !== FALSE) {
            imagealphablending($source_image, FALSE);
            imagesavealpha($source_image, TRUE);
          }
          break;
      }

      if ($source_image) {
        $success = @imagewebp($source_image, $webp_path, 80);
        imagedestroy($source_image);

        if ($success) {
          // Set same permissions as original.
          chmod($webp_path, fileperms($source_path));

          $webp_size = filesize($webp_path);
          $original_size = filesize($source_path);
          $savings = round((1 - $webp_size / $original_size) * 100, 1);

          $this->output()->writeln("<info>✓ Conversion successful!</info>");
          $this->output()->writeln("  Original: " . $this->formatBytes($original_size));
          $this->output()->writeln("  WebP: " . $this->formatBytes($webp_size) . " ({$savings}% savings)");
          $this->output()->writeln("  Path: $webp_path");
        }
        else {
          $this->output()->writeln("<error>Failed to create WebP file</error>");
        }
      }
      else {
        $this->output()->writeln("<error>Failed to create image resource from source file</error>");
      }
    }
    catch (\Exception $e) {
      $this->output()->writeln("<error>Error: " . $e->getMessage() . "</error>");
    }
  }

  /**
   * Format bytes to human-readable format.
   */
  private function formatBytes($bytes) {
    if ($bytes >= 1048576) {
      return round($bytes / 1048576, 2) . ' MB';
    }
    elseif ($bytes >= 1024) {
      return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
  }

  /**
   * Process WebP conversion queue.
   *
   * @command saho:webp-process-queue
   * @option limit Number of queue items to process
   * @usage saho:webp-process-queue --limit=50
   *   Process up to 50 queue items.
   */
  public function processQueue($options = ['limit' => 0]) {
    $queue = \Drupal::queue('saho_webp_conversion');
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('saho_webp_conversion');

    $limit = $options['limit'] ? (int) $options['limit'] : 0;
    $processed = 0;

    $this->output()->writeln("Processing WebP conversion queue...");

    while (($limit === 0 || $processed < $limit) && ($item = $queue->claimItem())) {
      // Ensure $item is an object with data property.
      if (!is_object($item) || !property_exists($item, 'data')) {
        continue;
      }

      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
        $processed++;

        if ($processed % 10 === 0) {
          $this->output()->writeln("Processed: $processed items");
        }
      }
      catch (\Exception $e) {
        $this->output()->writeln("Error processing item: " . $e->getMessage());
        $queue->releaseItem($item);
      }
    }

    $this->output()->writeln("Completed. Processed $processed items.");
  }

}
