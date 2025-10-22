#!/usr/bin/env php
<?php

/**
 * @file
 * Convert a single image file to WebP format.
 *
 * Usage:
 *   php scripts/convert_single_webp.php bio_pics/ReggieWilliams_1.jpg
 *   php scripts/convert_single_webp.php /sites/default/files/bio_pics/ReggieWilliams_1.jpg
 *
 * @codingStandardsIgnoreFile
 */

// Error reporting.
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Determine files directory based on directory structure.
// Check if we're in project root or public_html.
if (file_exists(__DIR__ . '/../webroot/sites/default/files/')) {
  $files_dir = __DIR__ . '/../webroot/sites/default/files/';
}
elseif (file_exists(__DIR__ . '/../public_html/sites/default/files/')) {
  $files_dir = __DIR__ . '/../public_html/sites/default/files/';
}
else {
  echo "ERROR: Cannot locate files directory\n";
  echo "Tried:\n";
  echo "  " . __DIR__ . "/../webroot/sites/default/files/\n";
  echo "  " . __DIR__ . "/../public_html/sites/default/files/\n";
  exit(1);
}

$source_path = $files_dir . $relative_path;

echo "Debug: Files directory: $files_dir\n";
echo "Debug: Looking for: $source_path\n";

// Check if file exists.
if (!file_exists($source_path)) {
  echo "ERROR: File not found: $source_path\n";
  exit(1);
}

echo "Debug: File exists!\n";

// Check WebP support.
if (!function_exists('imagewebp')) {
  echo "ERROR: PHP GD library does not have WebP support\n";
  echo "imagewebp() function is not available\n";
  exit(1);
}

echo "Debug: WebP support available\n";

// First, check if the file is actually already WebP (misnamed).
$file_header = file_get_contents($source_path, FALSE, NULL, 0, 16);
$is_webp = (substr($file_header, 0, 4) === 'RIFF' && substr($file_header, 8, 4) === 'WEBP');

if ($is_webp) {
  echo "DEBUG: File is already WebP (starts with RIFF...WEBP)\n";
  echo "The file has a .jpg/.png extension but contains WebP data.\n";

  // Generate WebP path.
  $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);

  if (file_exists($webp_path)) {
    echo "WebP file already exists at: $webp_path\n";
    exit(0);
  }

  // Copy the misnamed WebP to correct .webp extension.
  if (copy($source_path, $webp_path)) {
    chmod($webp_path, fileperms($source_path));

    $file_size = filesize($source_path);
    echo "✓ WebP file created (copied from misnamed source)!\n";
    echo "  File size: " . formatBytes($file_size) . "\n";
    echo "  Path: $webp_path\n";
    echo "\nNote: The original .jpg file is actually WebP format.\n";
    echo "Consider fixing the source file naming.\n";
    exit(0);
  }
  else {
    echo "ERROR: Failed to copy file to .webp extension\n";
    exit(1);
  }
}

// Check if it's an image - use multiple methods for compatibility.
$mime_type = NULL;

// Try mime_content_type if available.
if (function_exists('mime_content_type')) {
  $mime_type = mime_content_type($source_path);
}
// Fallback to finfo.
elseif (function_exists('finfo_open')) {
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime_type = finfo_file($finfo, $source_path);
  finfo_close($finfo);
}
// Fallback to exif_imagetype + manual mapping.
elseif (function_exists('exif_imagetype')) {
  $image_type = exif_imagetype($source_path);
  $mime_map = [
    IMAGETYPE_JPEG => 'image/jpeg',
    IMAGETYPE_PNG => 'image/png',
  ];
  $mime_type = isset($mime_map[$image_type]) ? $mime_map[$image_type] : NULL;
}
// Last resort - check file extension.
else {
  $ext = strtolower(pathinfo($source_path, PATHINFO_EXTENSION));
  if (in_array($ext, ['jpg', 'jpeg'])) {
    $mime_type = 'image/jpeg';
  }
  elseif ($ext === 'png') {
    $mime_type = 'image/png';
  }
}

echo "Debug: MIME type: $mime_type\n";

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

// Check memory limit and file size.
$file_size = filesize($source_path);
echo "Debug: File size: " . formatBytes($file_size) . "\n";

$memory_limit = ini_get('memory_limit');
echo "Debug: Memory limit: $memory_limit\n";

// Convert to WebP.
echo "Debug: Attempting to create image resource...\n";

// Enable error capture.
$old_error_handler = set_error_handler(function($errno, $errstr) {
  echo "Debug: PHP Error: $errstr\n";
});

$source_image = NULL;

switch ($mime_type) {
  case 'image/jpeg':
    echo "Debug: Loading JPEG...\n";
    $source_image = imagecreatefromjpeg($source_path);
    if ($source_image === FALSE) {
      echo "Debug: imagecreatefromjpeg() returned FALSE\n";
      $error = error_get_last();
      if ($error) {
        echo "Debug: Last error: " . $error['message'] . "\n";
      }
    }
    else {
      echo "Debug: JPEG loaded successfully\n";
    }
    break;

  case 'image/png':
    echo "Debug: Loading PNG...\n";
    $source_image = imagecreatefrompng($source_path);
    if ($source_image === FALSE) {
      echo "Debug: imagecreatefrompng() returned FALSE\n";
    }
    else {
      echo "Debug: PNG loaded successfully\n";
      imagealphablending($source_image, FALSE);
      imagesavealpha($source_image, TRUE);
    }
    break;
}

// Restore error handler.
if ($old_error_handler) {
  set_error_handler($old_error_handler);
}

// Check if image was created successfully (PHP 8+ returns GdImage object, PHP 7 returns resource).
$is_valid_image = $source_image && ($source_image instanceof \GdImage || is_resource($source_image));

if ($is_valid_image) {
  echo "Debug: Image resource created successfully\n";
  echo "Debug: Converting to WebP...\n";
  $success = imagewebp($source_image, $webp_path, 80);

  if ($success === FALSE) {
    echo "Debug: imagewebp() returned FALSE\n";
    $error = error_get_last();
    if ($error) {
      echo "Debug: Last error: " . $error['message'] . "\n";
    }
  }

  imagedestroy($source_image);

  if ($success) {
    echo "Debug: WebP file created\n";

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
  echo "Debug: source_image = " . var_export($source_image, TRUE) . "\n";
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
