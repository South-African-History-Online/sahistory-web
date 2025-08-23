<?php

/**
 * @file
 * Debug Specific File WebP Conversion.
 *
 * Diagnoses why a specific image file is not being converted to WebP.
 */

set_time_limit(0);
ini_set('memory_limit', '512M');

if ($argc < 2) {
  echo "Usage: php debug_specific_file.php <image_file_path>\n";
  echo "Example: php debug_specific_file.php sites/default/files/bio_pics/ReggieWilliams.jpg\n";
  exit(1);
}

$target_file = $argv[1];

echo "🔍 Debug Specific File WebP Conversion\n";
echo "======================================\n\n";

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

// Construct full path.
if (strpos($target_file, $files_dir) === 0) {
  $full_path = $target_file;
}
else {
  $full_path = $files_dir . '/' . ltrim($target_file, '/');
}

echo "🎯 Target file: $full_path\n\n";

// Check if file exists.
echo "📋 FILE DIAGNOSTICS:\n";
echo "====================\n";

if (!file_exists($full_path)) {
  echo "❌ File does not exist: $full_path\n";
  
  // Try to find similar files.
  $basename = basename($target_file);
  echo "🔍 Searching for files with similar name: $basename\n";
  
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($files_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
  );
  
  $found_similar = [];
  foreach ($iterator as $file) {
    if ($file->isFile() && $file->getFilename() === $basename) {
      $found_similar[] = $file->getRealPath();
    }
  }
  
  if (!empty($found_similar)) {
    echo "✅ Found " . count($found_similar) . " files with same name:\n";
    foreach ($found_similar as $similar_file) {
      echo "   • $similar_file\n";
    }
  }
  else {
    echo "❌ No files found with name: $basename\n";
  }
  
  exit(1);
}

echo "✅ File exists: $full_path\n";
echo "📏 File size: " . number_format(filesize($full_path)) . " bytes\n";
echo "🗓️  Modified: " . date('Y-m-d H:i:s', filemtime($full_path)) . "\n";

// Check file type.
$file_info = @getimagesize($full_path);
if ($file_info) {
  echo "🖼️  Image type: " . $file_info['mime'] . "\n";
  echo "📐 Dimensions: {$file_info[0]} x {$file_info[1]}\n";
}
else {
  echo "❌ Invalid image file or cannot read image info\n";
}

// Check if it's actually an image.
$file_type = mime_content_type($full_path);
echo "📄 MIME type: $file_type\n";

// Check first few bytes.
$handle = fopen($full_path, 'r');
if ($handle) {
  $first_bytes = fread($handle, 50);
  fclose($handle);
  echo "🔡 First bytes: " . substr(bin2hex($first_bytes), 0, 20) . "...\n";
  
  // Check if it's HTML.
  if (strpos($first_bytes, '<html') !== FALSE || strpos($first_bytes, '<!DOCTYPE') !== FALSE) {
    echo "⚠️  WARNING: This appears to be an HTML file, not an image!\n";
  }
}

// Check WebP path.
$webp_path = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $full_path);
echo "\n📋 WEBP CONVERSION:\n";
echo "===================\n";
echo "🎯 Expected WebP path: $webp_path\n";

if (file_exists($webp_path)) {
  echo "✅ WebP file EXISTS\n";
  echo "📏 WebP size: " . number_format(filesize($webp_path)) . " bytes\n";
  echo "🗓️  WebP modified: " . date('Y-m-d H:i:s', filemtime($webp_path)) . "\n";
  
  // Compare sizes.
  $original_size = filesize($full_path);
  $webp_size = filesize($webp_path);
  $savings = $original_size - $webp_size;
  $savings_percent = round(($savings / $original_size) * 100, 1);
  echo "💾 Space savings: " . number_format($savings) . " bytes ($savings_percent%)\n";
}
else {
  echo "❌ WebP file does NOT exist\n";
  
  // Try to convert now.
  echo "\n🔧 ATTEMPTING CONVERSION:\n";
  echo "=========================\n";
  
  if (!in_array($file_info['mime'] ?? '', ['image/jpeg', 'image/png'])) {
    echo "❌ Unsupported image type for WebP: " . ($file_info['mime'] ?? 'unknown') . "\n";
  }
  else {
    $source_image = NULL;
    
    switch ($file_info['mime']) {
      case 'image/jpeg':
        $source_image = @imagecreatefromjpeg($full_path);
        break;
        
      case 'image/png':
        $source_image = @imagecreatefrompng($full_path);
        if ($source_image !== FALSE) {
          imagealphablending($source_image, FALSE);
          imagesavealpha($source_image, TRUE);
        }
        break;
    }
    
    if ($source_image === FALSE) {
      echo "❌ Failed to create image resource (corrupt or invalid image)\n";
    }
    else {
      echo "✅ Successfully created image resource\n";
      
      $success = @imagewebp($source_image, $webp_path, 80);
      imagedestroy($source_image);
      
      if ($success) {
        echo "✅ WebP conversion SUCCESSFUL!\n";
        echo "📁 Created: $webp_path\n";
        echo "📏 Size: " . number_format(filesize($webp_path)) . " bytes\n";
        
        // Set permissions.
        @chmod($webp_path, 0644);
        echo "✅ Permissions set to 644\n";
      }
      else {
        echo "❌ WebP conversion FAILED (imagewebp returned false)\n";
        
        // Check if GD has WebP support.
        if (!function_exists('imagewebp')) {
          echo "❌ imagewebp() function not available\n";
        }
        elseif (!in_array('WEBP Support', gd_info())) {
          echo "❌ GD library does not have WebP support\n";
        }
      }
    }
  }
}

// Check .htaccess serving.
echo "\n📋 HTACCESS SERVING TEST:\n";
echo "=========================\n";
if (file_exists($webp_path)) {
  echo "🌐 To test WebP serving, use:\n";
  echo "   curl -H \"Accept: image/webp\" -I https://yourdomain.com/" . str_replace($files_dir . '/', '', $full_path) . "\n";
  echo "   (Should return Content-Type: image/webp)\n";
}
else {
  echo "❌ Cannot test serving - WebP file doesn't exist\n";
}

echo "\n🎯 SUMMARY:\n";
echo "===========\n";
if (file_exists($webp_path)) {
  echo "✅ File is properly converted to WebP\n";
}
else {
  echo "❌ File is NOT converted to WebP\n";
  echo "🔧 Manual conversion may be needed\n";
}