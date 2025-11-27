<?php

/**
 * @file
 * Safe WebP Generator.
 *
 * Generates WebP files without modifying originals.
 * Use this instead of automatic conversion on upload.
 */

set_time_limit(0);
ini_set('memory_limit', '1G');

echo "🛡️  Safe WebP Generator\n";
echo "======================\n\n";

// Find the correct files directory.
$possible_dirs = [
  'sites/default/files',
  'webroot/sites/default/files',
  '../sites/default/files',
];

$files_dir = NULL;
foreach ($possible_dirs as $dir) {
  if (is_dir($dir)) {
    $files_dir = $dir;
    break;
  }
}

if (!$files_dir) {
  echo "❌ Files directory not found!\n";
  exit(1);
}

echo "📁 Using files directory: $files_dir\n";

// Get optional parameters.
$batch_size = isset($argv[1]) ? (int)$argv[1] : 100;
$start_offset = isset($argv[2]) ? (int)$argv[2] : 0;

echo "📦 Batch size: $batch_size files\n";
echo "🎯 Start offset: $start_offset files\n\n";

// Statistics.
$stats = [
  'total_processed' => 0,
  'webp_created' => 0,
  'already_existed' => 0,
  'failed_invalid' => 0,
  'failed_conversion' => 0,
  'original_size' => 0,
  'webp_size' => 0,
];

/**
 * Safely convert image to WebP without modifying original.
 */
function safe_webp_convert($source_path, &$stats) {
  $stats['total_processed']++;
  
  // Create WebP path.
  $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);
  
  // Skip if WebP already exists.
  if (file_exists($webp_path)) {
    $stats['already_existed']++;
    return TRUE;
  }
  
  // Validate image before processing.
  $image_info = @getimagesize($source_path);
  if (!$image_info) {
    $stats['failed_invalid']++;
    return FALSE;
  }
  
  // Only process JPEG and PNG.
  if (!in_array($image_info['mime'], ['image/jpeg', 'image/png'])) {
    $stats['failed_invalid']++;
    return FALSE;
  }
  
  // Check for HTML content (fake images).
  $handle = fopen($source_path, 'r');
  if ($handle) {
    $first_bytes = fread($handle, 50);
    fclose($handle);
    
    if (strpos($first_bytes, '<html') !== FALSE || strpos($first_bytes, '<!DOCTYPE') !== FALSE) {
      $stats['failed_invalid']++;
      return FALSE;
    }
  }
  
  // Create image resource.
  $source_image = NULL;
  switch ($image_info['mime']) {
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
    $stats['failed_conversion']++;
    return FALSE;
  }
  
  // Convert to WebP with high quality.
  $success = @imagewebp($source_image, $webp_path, 85);
  imagedestroy($source_image);
  
  if ($success) {
    // Set correct permissions.
    @chmod($webp_path, 0644);
    
    // Track sizes.
    $stats['original_size'] += filesize($source_path);
    $stats['webp_size'] += filesize($webp_path);
    $stats['webp_created']++;
    
    return TRUE;
  }
  else {
    $stats['failed_conversion']++;
    return FALSE;
  }
}

// Find all image files.
echo "🔍 Finding image files...\n";

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($files_dir, RecursiveDirectoryIterator::SKIP_DOTS),
  RecursiveIteratorIterator::SELF_FIRST
);

$image_files = [];
foreach ($iterator as $file) {
  if ($file->isFile()) {
    $extension = strtolower($file->getExtension());
    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
      $image_files[] = $file->getRealPath();
    }
  }
}

$total_files = count($image_files);
echo "📊 Found " . number_format($total_files) . " image files\n";

// Process batch.
$batch_files = array_slice($image_files, $start_offset, $batch_size);
$batch_count = count($batch_files);

echo "📦 Processing batch: " . ($start_offset + 1) . " to " . ($start_offset + $batch_count) . "\n";
echo "📈 Batch size: $batch_count files\n\n";

foreach ($batch_files as $index => $image_file) {
  $progress = $index + 1;
  $percent = round(($progress / $batch_count) * 100, 1);
  
  if ($progress % 50 == 0 || $progress <= 10) {
    echo "📈 Progress: $progress/$batch_count ($percent%)\n";
  }
  
  safe_webp_convert($image_file, $stats);
}

// Calculate savings.
$savings = $stats['original_size'] - $stats['webp_size'];
$savings_percent = $stats['original_size'] > 0 ? 
  round(($savings / $stats['original_size']) * 100, 1) : 0;

echo "\n🎉 BATCH COMPLETE!\n";
echo "==================\n";
echo "Files processed: " . number_format($stats['total_processed']) . "\n";
echo "WebP created: " . number_format($stats['webp_created']) . "\n";
echo "Already existed: " . number_format($stats['already_existed']) . "\n";
echo "Failed (invalid): " . number_format($stats['failed_invalid']) . "\n";
echo "Failed (conversion): " . number_format($stats['failed_conversion']) . "\n";

if ($stats['original_size'] > 0) {
  echo "\n💾 STORAGE IMPACT:\n";
  echo "Original size: " . round($stats['original_size'] / 1024 / 1024, 2) . " MB\n";
  echo "WebP size: " . round($stats['webp_size'] / 1024 / 1024, 2) . " MB\n";
  echo "Space savings: " . round($savings / 1024 / 1024, 2) . " MB ($savings_percent%)\n";
}

// Show next batch command.
$next_offset = $start_offset + $batch_size;
if ($next_offset < $total_files) {
  $remaining = $total_files - $next_offset;
  echo "\n📦 NEXT BATCH:\n";
  echo "Run: php safe_webp_generator.php $batch_size $next_offset\n";
  echo "Remaining files: " . number_format($remaining) . "\n";
}
else {
  echo "\n✅ ALL FILES PROCESSED!\n";
}