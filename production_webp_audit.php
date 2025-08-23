<?php

/**
 * @file
 * Production WebP Audit Tool.
 *
 * Comprehensive audit for production WebP conversion issues.
 * Identifies missing WebP files and diagnoses conversion problems.
 */

set_time_limit(0);
ini_set('memory_limit', '1G');

echo "🔍 Production WebP Audit Tool\n";
echo "=============================\n\n";

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

echo "📁 Using files directory: $files_dir\n\n";

// Statistics.
$stats = [
  'total_images' => 0,
  'webp_exists' => 0,
  'webp_missing' => 0,
  'conversion_attempts' => 0,
  'conversion_success' => 0,
  'conversion_failed' => 0,
];

$missing_files = [];
$failed_conversions = [];

echo "🔍 Scanning for missing WebP files...\n";

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($files_dir, RecursiveDirectoryIterator::SKIP_DOTS),
  RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
  if ($file->isFile()) {
    $extension = strtolower($file->getExtension());
    
    // Process image files.
    if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
      $stats['total_images']++;
      $filepath = $file->getRealPath();
      $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filepath);
      
      if (file_exists($webp_path)) {
        $stats['webp_exists']++;
      }
      else {
        $stats['webp_missing']++;
        $missing_files[] = [
          'path' => $filepath,
          'relative_path' => str_replace($files_dir . '/', '', $filepath),
          'size' => filesize($filepath),
          'modified' => filemtime($filepath),
        ];
      }
      
      // Progress indicator.
      if ($stats['total_images'] % 1000 == 0) {
        echo "📈 Scanned: " . number_format($stats['total_images']) . " images\n";
      }
    }
  }
}

echo "\n📊 SCAN RESULTS:\n";
echo "================\n";
echo "Total images: " . number_format($stats['total_images']) . "\n";
echo "WebP exists: " . number_format($stats['webp_exists']) . "\n";
echo "Missing WebP: " . number_format($stats['webp_missing']) . "\n";
$conversion_rate = $stats['total_images'] > 0 ? round(($stats['webp_exists'] / $stats['total_images']) * 100, 1) : 0;
echo "Conversion rate: $conversion_rate%\n\n";

if (empty($missing_files)) {
  echo "✅ All images have WebP versions!\n";
  exit(0);
}

// Sort missing files by size (largest first).
usort($missing_files, function ($a, $b) {
  return $b['size'] <=> $a['size'];
});

echo "🎯 TOP MISSING WEBP FILES (by size):\n";
echo "====================================\n";
$shown = 0;
foreach ($missing_files as $missing) {
  if ($shown < 20) {
    $size_mb = round($missing['size'] / 1024 / 1024, 2);
    $date = date('Y-m-d H:i', $missing['modified']);
    echo sprintf("%.2f MB - %s (modified: %s)\n", $size_mb, $missing['relative_path'], $date);
    $shown++;
  }
}

if (count($missing_files) > 20) {
  echo "... and " . (count($missing_files) - 20) . " more files\n";
}

echo "\n🔧 ATTEMPTING TO CONVERT MISSING FILES:\n";
echo "=======================================\n";

$batch_size = min(50, count($missing_files));
echo "Processing batch of $batch_size files...\n\n";

foreach (array_slice($missing_files, 0, $batch_size) as $missing) {
  $stats['conversion_attempts']++;
  $source_path = $missing['path'];
  $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);
  
  echo "🔧 Converting: " . $missing['relative_path'] . "\n";
  
  // Check if it's a valid image.
  $image_info = @getimagesize($source_path);
  if (!$image_info) {
    echo "   ❌ Invalid image file\n";
    $failed_conversions[] = [
      'file' => $missing['relative_path'],
      'error' => 'Invalid image file (getimagesize failed)',
    ];
    continue;
  }
  
  // Check MIME type.
  if (!in_array($image_info['mime'], ['image/jpeg', 'image/png'])) {
    echo "   ❌ Unsupported type: " . $image_info['mime'] . "\n";
    $failed_conversions[] = [
      'file' => $missing['relative_path'],
      'error' => 'Unsupported MIME type: ' . $image_info['mime'],
    ];
    continue;
  }
  
  // Check for HTML content (fake images).
  $handle = fopen($source_path, 'r');
  if ($handle) {
    $first_bytes = fread($handle, 100);
    fclose($handle);
    
    if (strpos($first_bytes, '<html') !== FALSE || strpos($first_bytes, '<!DOCTYPE') !== FALSE) {
      echo "   ❌ HTML file with image extension\n";
      $failed_conversions[] = [
        'file' => $missing['relative_path'],
        'error' => 'HTML file with image extension (fake image)',
      ];
      continue;
    }
  }
  
  // Attempt conversion.
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
    echo "   ❌ Failed to create image resource\n";
    $failed_conversions[] = [
      'file' => $missing['relative_path'],
      'error' => 'Failed to create image resource (corrupt image)',
    ];
    continue;
  }
  
  $success = @imagewebp($source_image, $webp_path, 80);
  imagedestroy($source_image);
  
  if ($success) {
    echo "   ✅ Converted successfully\n";
    @chmod($webp_path, 0644);
    $stats['conversion_success']++;
  }
  else {
    echo "   ❌ WebP conversion failed\n";
    $failed_conversions[] = [
      'file' => $missing['relative_path'],
      'error' => 'imagewebp() returned false',
    ];
    $stats['conversion_failed']++;
  }
}

echo "\n🎉 CONVERSION RESULTS:\n";
echo "======================\n";
echo "Attempted: " . $stats['conversion_attempts'] . "\n";
echo "Successful: " . $stats['conversion_success'] . "\n";
echo "Failed: " . $stats['conversion_failed'] . "\n";

if (!empty($failed_conversions)) {
  echo "\n⚠️  FAILED CONVERSIONS:\n";
  echo "=======================\n";
  foreach ($failed_conversions as $failed) {
    echo "• " . $failed['file'] . " - " . $failed['error'] . "\n";
  }
}

echo "\n💡 RECOMMENDATIONS:\n";
echo "===================\n";

if ($stats['conversion_success'] > 0) {
  echo "✅ Run comprehensive status check to see updated results:\n";
  echo "   php comprehensive_webp_status.php\n\n";
}

if ($stats['conversion_failed'] > 0) {
  echo "🔧 For failed conversions, consider:\n";
  echo "   1. Check if GD library has WebP support: php -m | grep gd\n";
  echo "   2. Remove fake HTML files: php clean_fake_images.php\n";
  echo "   3. Manual investigation of corrupt files\n\n";
}

if ($stats['webp_missing'] > $batch_size) {
  $remaining = $stats['webp_missing'] - $batch_size;
  echo "📦 $remaining files remain. Run script again or use:\n";
  echo "   php convert_webp_production_final.php\n";
}