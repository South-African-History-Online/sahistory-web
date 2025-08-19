<?php

namespace Drupal\saho_webp\Commands;

use Drush\Commands\DrushCommands;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;

/**
 * Drush commands for WebP management.
 */
class WebpCommands extends DrushCommands {

  /**
   * Convert all existing images to WebP.
   *
   * @command saho:webp-convert-all
   * @aliases webp-all
   * @usage saho:webp-convert-all
   *   Convert all existing images to WebP format.
   */
  public function convertAll() {
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
      $result = $this->convertFile($file);
      
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
        } else {
          rename($old_path, $new_path);
          $fixed++;
        }
      }
    }
    
    $this->output()->writeln("Fixed: $fixed files");
    $this->output()->writeln("Removed duplicates: $removed files");
  }

  /**
   * Convert a single file to WebP.
   */
  private function convertFile(File $file) {
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
      $source_image = null;
      
      switch ($mime_type) {
        case 'image/jpeg':
          $source_image = @imagecreatefromjpeg($source_path);
          break;
        case 'image/png':
          $source_image = @imagecreatefrompng($source_path);
          if ($source_image !== false) {
            imagealphablending($source_image, false);
            imagesavealpha($source_image, true);
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
      // Log error
    }
    
    return 'error';
  }
}