<?php

namespace Drupal\saho_webp\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\Entity\File;

/**
 * Processes WebP conversion queue items.
 *
 * @QueueWorker(
 *   id = "saho_webp_conversion",
 *   title = @Translation("SAHO WebP Conversion"),
 *   cron = {"time" = 60}
 * )
 */
class WebpConversionWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!isset($data['file_id'])) {
      return;
    }

    $file = File::load($data['file_id']);
    if (!$file) {
      return;
    }

    $this->convertFileToWebp($file);
  }

  /**
   * Convert a file to WebP format safely.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file entity to convert.
   */
  protected function convertFileToWebp(File $file) {
    // Only process image files.
    $mime_type = $file->getMimeType();
    if (!in_array($mime_type, ['image/jpeg', 'image/png'])) {
      return;
    }

    // Get file path.
    $source_path = \Drupal::service('file_system')->realpath($file->getFileUri());
    if (!$source_path || !file_exists($source_path)) {
      \Drupal::logger('saho_webp')->warning('Source file not found: @path', [
        '@path' => $file->getFileUri(),
      ]);
      return;
    }

    // Wait a moment to ensure file is fully written.
    // 0.5 seconds.
    usleep(500000);

    // Create WebP path.
    $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);

    // Skip if WebP already exists.
    if (file_exists($webp_path)) {
      return;
    }

    // Validate image file.
    $image_info = @getimagesize($source_path);
    if (!$image_info) {
      \Drupal::logger('saho_webp')->warning('Invalid image file: @file', [
        '@file' => basename($source_path),
      ]);
      return;
    }

    // Check for HTML content (fake images).
    $handle = fopen($source_path, 'r');
    if ($handle) {
      $first_bytes = fread($handle, 50);
      fclose($handle);

      if (strpos($first_bytes, '<html') !== FALSE || strpos($first_bytes, '<!DOCTYPE') !== FALSE) {
        \Drupal::logger('saho_webp')->warning('HTML file with image extension: @file', [
          '@file' => basename($source_path),
        ]);
        return;
      }
    }

    // Convert to WebP.
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

      if ($source_image === FALSE) {
        throw new \Exception('Failed to create image resource');
      }

      // Convert with 85% quality for better results.
      $success = @imagewebp($source_image, $webp_path, 85);
      imagedestroy($source_image);

      if ($success) {
        // Set permissions to match source.
        chmod($webp_path, fileperms($source_path));

        // Log successful conversion.
        \Drupal::logger('saho_webp')->info('Converted @source to WebP', [
          '@source' => basename($source_path),
        ]);
      }
      else {
        throw new \Exception('imagewebp() returned FALSE');
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('saho_webp')->error('Failed to convert @source: @error', [
        '@source' => basename($source_path),
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
