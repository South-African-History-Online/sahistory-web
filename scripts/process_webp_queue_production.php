<?php

/**
 * @file
 * Process WebP Conversion Queue on Production.
 *
 * Manual queue processor for production environments.
 */

// Bootstrap Drupal.
use Drupal\Core\DrupalKernel;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'vendor/autoload.php';
$kernel = new DrupalKernel('prod', $autoloader);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$kernel->boot();

echo "🔄 Processing WebP Conversion Queue\n";
echo "===================================\n\n";

// Get queue service.
$queue = \Drupal::queue('saho_webp_conversion');
$queue_count = $queue->numberOfItems();

echo "📊 Queue items found: $queue_count\n";

if ($queue_count === 0) {
  echo "✅ No items in queue to process\n";
  exit(0);
}

// Get batch size from command line.
$batch_size = isset($argv[1]) ? (int)$argv[1] : 50;
echo "📦 Processing batch size: $batch_size\n\n";

$processed = 0;
$success = 0;
$errors = 0;

// Process queue items.
while ($processed < $batch_size && ($item = $queue->claimItem())) {
  $processed++;
  
  try {
    if (!isset($item->data['file_id'])) {
      throw new \Exception('Missing file_id in queue item');
    }
    
    $file_id = $item->data['file_id'];
    $file = File::load($file_id);
    
    if (!$file) {
      throw new \Exception("File not found: $file_id");
    }
    
    // Convert the file.
    $result = convertFileToWebp($file);
    
    if ($result === 'success') {
      $success++;
      echo "✅ Converted: " . $file->getFilename() . "\n";
    }
    else {
      $errors++;
      echo "❌ Failed: " . $file->getFilename() . " ($result)\n";
    }
    
    // Remove item from queue.
    $queue->deleteItem($item);
    
  }
  catch (\Exception $e) {
    $errors++;
    echo "❌ Error processing item: " . $e->getMessage() . "\n";
    
    // Release item back to queue for retry.
    $queue->releaseItem($item);
  }
  
  // Progress indicator.
  if ($processed % 10 === 0) {
    echo "📈 Progress: $processed processed\n";
  }
}

echo "\n🎉 BATCH COMPLETE!\n";
echo "==================\n";
echo "Items processed: $processed\n";
echo "Successful: $success\n";
echo "Errors: $errors\n";

$remaining = $queue->numberOfItems();
if ($remaining > 0) {
  echo "📦 Remaining in queue: $remaining\n";
  echo "🔄 Run again to process more: php process_webp_queue_production.php $batch_size\n";
}

/**
 * Convert a file to WebP format safely.
 */
function convertFileToWebp(File $file) {
  // Only process image files.
  $mime_type = $file->getMimeType();
  if (!in_array($mime_type, ['image/jpeg', 'image/png'])) {
    return 'unsupported_type';
  }

  // Get file path.
  $source_path = \Drupal::service('file_system')->realpath($file->getFileUri());
  if (!$source_path || !file_exists($source_path)) {
    return 'source_missing';
  }

  // Wait to ensure file is fully written.
  usleep(100000); // 0.1 seconds

  // Create WebP path.
  $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);

  // Skip if WebP already exists.
  if (file_exists($webp_path)) {
    return 'already_exists';
  }

  // Validate image file.
  $image_info = @getimagesize($source_path);
  if (!$image_info) {
    return 'invalid_image';
  }

  // Check for HTML content (fake images).
  $handle = fopen($source_path, 'r');
  if ($handle) {
    $first_bytes = fread($handle, 50);
    fclose($handle);

    if (strpos($first_bytes, '<html') !== FALSE || strpos($first_bytes, '<!DOCTYPE') !== FALSE) {
      return 'html_file';
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
      return 'create_resource_failed';
    }

    // Convert with 85% quality.
    $success = @imagewebp($source_image, $webp_path, 85);
    imagedestroy($source_image);

    if ($success) {
      // Set permissions to match source.
      @chmod($webp_path, fileperms($source_path));

      // Log successful conversion.
      \Drupal::logger('saho_webp')->info('Queue converted @source to WebP', [
        '@source' => basename($source_path),
      ]);

      return 'success';
    }
    else {
      return 'webp_conversion_failed';
    }
  }
  catch (\Exception $e) {
    \Drupal::logger('saho_webp')->error('Queue conversion failed for @source: @error', [
      '@source' => basename($source_path),
      '@error' => $e->getMessage(),
    ]);
    return 'exception: ' . $e->getMessage();
  }
}