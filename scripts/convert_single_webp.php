#!/usr/bin/env php
<?php

/**
 * @file
 * Convert a single image file to WebP format.
 *
 * Usage:
 *   php scripts/convert_single_webp.php bio_pics/ReggieWilliams_1.jpg
 *   php scripts/convert_single_webp.php /sites/default/files/bio_pics/ReggieWilliams_1.jpg
 */

// Check if path argument is provided.
if ($argc < 2) {
  echo "Usage: php scripts/convert_single_webp.php <path-to-image>\n";
  echo "Example: php scripts/convert_single_webp.php bio_pics/ReggieWilliams_1.jpg\n";
  exit(1);
}

$relative_path = $argv[1];

// Normalize path - remove leading slashes and 'sites/default/files/'.
$relative_path = trim($relative_path, '/');
$relative_path = preg_replace('#^sites/default/files/#', '', $relative_path);

// Build full path.
$files_dir = __DIR__ . '/../webroot/sites/default/files/';
$source_path = $files_dir . $relative_path;

// Check if file exists.
if (!file_exists($source_path)) {
  echo "ERROR: File not found: $source_path\n";
  exit(1);
}

// Check if it's an image.
$mime_type = mime_content_type($source_path);
if (!in_array($mime_type, ['image/jpeg', 'image/png'])) {
  echo "ERROR: File is not a JPEG or PNG: $mime_type\n";
  exit(1);
}

// Generate WebP path.
$webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);

// Check if WebP already exists.
if (file_exists($webp_path)) {
  $webp_size = filesize($webp_path);
  $original_size = filesize($source_path);
  $savings = round((1 - $webp_size / $original_size) * 100, 1);

  echo "WebP already exists:\n";
  echo "  Original: " . formatBytes($original_size) . "\n";
  echo "  WebP: " . formatBytes($webp_size) . " ({$savings}% savings)\n";
  echo "  Path: $webp_path\n";
  exit(0);
}

echo "Converting: $relative_path\n";

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

  if ($source_image) {
    $success = @imagewebp($source_image, $webp_path, 80);
    imagedestroy($source_image);

    if ($success) {
      // Set same permissions as original.
      chmod($webp_path, fileperms($source_path));

      $webp_size = filesize($webp_path);
      $original_size = filesize($source_path);
      $savings = round((1 - $webp_size / $original_size) * 100, 1);

      echo "✓ Conversion successful!\n";
      echo "  Original: " . formatBytes($original_size) . "\n";
      echo "  WebP: " . formatBytes($webp_size) . " ({$savings}% savings)\n";
      echo "  Path: $webp_path\n";
      exit(0);
    }
    else {
      echo "ERROR: Failed to create WebP file\n";
      exit(1);
    }
  }
  else {
    echo "ERROR: Failed to create image resource from source file\n";
    exit(1);
  }
}
catch (Exception $e) {
  echo "ERROR: " . $e->getMessage() . "\n";
  exit(1);
}

/**
 * Format bytes to human-readable format.
 */
function formatBytes($bytes) {
  if ($bytes >= 1048576) {
    return round($bytes / 1048576, 2) . ' MB';
  }
  elseif ($bytes >= 1024) {
    return round($bytes / 1024, 2) . ' KB';
  }
  return $bytes . ' B';
}
