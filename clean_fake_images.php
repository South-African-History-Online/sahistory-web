<?php

/**
 * @file
 * Clean Fake Image Files.
 *
 * Removes HTML 404 error pages that were saved as image files.
 * These are not actually corrupt - they are error pages with image extensions.
 */

set_time_limit(0);
ini_set('memory_limit', '1G');

echo "🧹 Clean Fake Image Files Tool\n";
echo "===============================\n\n";

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

// Statistics.
$stats = [
  'total_checked' => 0,
  'fake_images_found' => 0,
  'fake_images_removed' => 0,
  'removal_errors' => 0,
  'actual_images' => 0,
];

$fake_files = [];

/**
 * Check if a file is a fake image (HTML error page with image extension).
 */
function is_fake_image($filepath) {
  if (!file_exists($filepath) || !is_readable($filepath)) {
    return FALSE;
  }

  // Read first 100 bytes to check for HTML content.
  $handle = fopen($filepath, 'r');
  if (!$handle) {
    return FALSE;
  }

  $first_bytes = fread($handle, 100);
  fclose($handle);

  // Check for HTML indicators.
  $html_indicators = [
    '<!DOCTYPE html',
    '<html',
    '<HTML',
    '<!doctype html',
    '<head>',
    '<HEAD>',
    '<title>404',
    '<title>Not Found',
  ];

  foreach ($html_indicators as $indicator) {
    if (strpos($first_bytes, $indicator) !== FALSE) {
      return TRUE;
    }
  }

  return FALSE;
}

// Scan all image files.
echo "🔍 Scanning for fake image files...\n";

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($files_dir, RecursiveDirectoryIterator::SKIP_DOTS),
  RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
  if ($file->isFile()) {
    $extension = strtolower($file->getExtension());

    // Check image files.
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
      $stats['total_checked']++;
      $filepath = $file->getRealPath();

      if (is_fake_image($filepath)) {
        $stats['fake_images_found']++;
        $fake_files[] = [
          'path' => $filepath,
          'relative_path' => str_replace($files_dir . '/', '', $filepath),
          'size' => filesize($filepath),
        ];
      }
      else {
        $stats['actual_images']++;
      }

      // Progress indicator.
      if ($stats['total_checked'] % 1000 == 0) {
        echo "📈 Checked: " . number_format($stats['total_checked']) . " files\n";
      }
    }
  }
}

echo "\n📊 SCAN RESULTS:\n";
echo "================\n";
echo "Total image files checked: " . number_format($stats['total_checked']) . "\n";
echo "Fake images found: " . number_format($stats['fake_images_found']) . "\n";
echo "Actual images found: " . number_format($stats['actual_images']) . "\n";

if (empty($fake_files)) {
  echo "\n✅ No fake image files found!\n";
  exit(0);
}

// Show sample of fake files.
echo "\n🔍 SAMPLE FAKE FILES:\n";
echo "====================\n";
$shown = 0;
foreach ($fake_files as $fake_file) {
  if ($shown < 10) {
    echo "• " . $fake_file['relative_path'] . " (" . number_format($fake_file['size']) . " bytes)\n";
    $shown++;
  }
}
if (count($fake_files) > 10) {
  echo "... and " . (count($fake_files) - 10) . " more fake files\n";
}

// Ask for confirmation.
echo "\n⚠️  WARNING: This will PERMANENTLY DELETE " . count($fake_files) . " fake image files!\n";
echo "These are HTML error pages, not actual images, so it's safe to remove them.\n";
echo "Continue? [y/N]: ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
  echo "❌ Operation cancelled.\n";
  exit(0);
}

// Remove fake files.
echo "\n🗑️  Removing fake image files...\n";

foreach ($fake_files as $fake_file) {
  if (unlink($fake_file['path'])) {
    $stats['fake_images_removed']++;
    if ($stats['fake_images_removed'] % 100 == 0) {
      echo "📈 Removed: " . number_format($stats['fake_images_removed']) . " files\n";
    }
  }
  else {
    $stats['removal_errors']++;
    echo "❌ Failed to remove: " . $fake_file['relative_path'] . "\n";
  }
}

echo "\n🎉 CLEANUP COMPLETE!\n";
echo "====================\n";
echo "Fake images removed: " . number_format($stats['fake_images_removed']) . "\n";
echo "Removal errors: " . number_format($stats['removal_errors']) . "\n";
echo "Remaining actual images: " . number_format($stats['actual_images']) . "\n";

if ($stats['fake_images_removed'] > 0) {
  echo "\n✅ Your image collection is now clean of fake HTML files!\n";
  echo "🎯 Run comprehensive WebP status check to see improved results:\n";
  echo "   php comprehensive_webp_status.php\n";
}