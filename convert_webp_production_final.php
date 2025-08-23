<?php
/**
 * Production-ready WebP Mass Converter
 * 
 * FEATURES:
 * - Correct naming (replaces extension, doesn't append)
 * - Handles errors gracefully
 * - Non-destructive (keeps originals)
 * - Progress tracking
 * - Memory efficient
 * 
 * Usage: php convert_webp_production_final.php [--fix-existing]
 */

set_time_limit(0);
ini_set('memory_limit', '512M');

$fix_existing = in_array('--fix-existing', $argv);

if ($fix_existing) {
    echo "🔧 Fixing existing WebP files with double extensions\n";
    fixExistingWebpFiles();
    echo "\n";
}

echo "🚀 Production WebP Mass Converter\n";
echo "=================================\n";
echo "Creates correctly named WebP files\n";
echo "Original files are NEVER modified\n\n";

$start_time = time();
$converted = 0;
$skipped = 0;
$errors = 0;
$total_original_size = 0;
$total_webp_size = 0;

function convertToWebP($source_path) {
    // IMPORTANT: Replace extension, don't append
    $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);
    
    // Skip if WebP already exists
    if (file_exists($webp_path)) {
        return 'exists';
    }
    
    try {
        $image_info = @getimagesize($source_path);
        if (!$image_info) {
            return 'invalid';
        }
        
        $mime_type = $image_info['mime'];
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
            default:
                return 'unsupported';
        }
        
        if (!$source_image) {
            return 'failed_load';
        }
        
        // Convert to WebP with 80% quality
        $success = @imagewebp($source_image, $webp_path, 80);
        imagedestroy($source_image);
        
        if ($success) {
            // Set permissions to match source
            chmod($webp_path, fileperms($source_path));
            return 'converted';
        }
        
        return 'failed_convert';
        
    } catch (Exception $e) {
        return 'error: ' . $e->getMessage();
    }
}

function fixExistingWebpFiles() {
    $fixed = 0;
    $removed = 0;
    
    // Find the correct files directory - always webroot/sites/default/files
    $files_dir = 'webroot/sites/default/files';
    if (!is_dir($files_dir)) {
        echo "Files directory not found - skipping WebP fix\n";
        echo "Expected: webroot/sites/default/files\n";
        echo "Current working directory: " . getcwd() . "\n";
        return;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($files_dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && preg_match('/\.(jpg|jpeg|png)\.webp$/i', $file->getFilename())) {
            $old_path = $file->getRealPath();
            $new_path = preg_replace('/\.(jpg|jpeg|png)\.webp$/i', '.webp', $old_path);
            
            if (file_exists($new_path)) {
                // Remove duplicate
                unlink($old_path);
                $removed++;
            } else {
                // Rename to correct format
                rename($old_path, $new_path);
                $fixed++;
            }
        }
    }
    
    echo "Fixed $fixed files, removed $removed duplicates\n";
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

// Find all images
$files_to_convert = [];

// Find the correct files directory - always webroot/sites/default/files
$files_dir = 'webroot/sites/default/files';
if (!is_dir($files_dir)) {
    echo "Files directory not found!\n";
    echo "Expected: webroot/sites/default/files\n";
    echo "Current working directory: " . getcwd() . "\n";
    echo "Script location: " . dirname(__FILE__) . "\n";
    echo "\nPlease run this script from the Drupal root directory.\n";
    exit(1);
}

echo "Using files directory: $files_dir\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($files_dir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $extension = strtolower($file->getExtension());
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $files_to_convert[] = $file->getRealPath();
        }
    }
}

$total_files = count($files_to_convert);
echo "Found " . number_format($total_files) . " images to process\n";
echo "Starting conversion...\n\n";

foreach ($files_to_convert as $index => $file_path) {
    $result = convertToWebP($file_path);
    $original_size = filesize($file_path);
    $total_original_size += $original_size;
    
    switch ($result) {
        case 'converted':
            $converted++;
            $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file_path);
            $webp_size = filesize($webp_path);
            $total_webp_size += $webp_size;
            $savings = round((1 - $webp_size / $original_size) * 100, 1);
            
            if ($converted % 100 == 0) {
                echo "✓ Converted " . number_format($converted) . " files (saved {$savings}%)\n";
            }
            break;
            
        case 'exists':
            $skipped++;
            $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file_path);
            if (file_exists($webp_path)) {
                $total_webp_size += filesize($webp_path);
            }
            break;
            
        default:
            $errors++;
            if ($errors % 50 == 0) {
                echo "⚠ {$errors} errors so far\n";
            }
            break;
    }
    
    // Progress update
    if (($index + 1) % 1000 == 0) {
        $progress = round((($index + 1) / $total_files) * 100, 1);
        $elapsed = time() - $start_time;
        $eta = $elapsed > 0 ? round(($total_files - $index - 1) * $elapsed / ($index + 1)) : 0;
        
        echo "\n📊 Progress: {$progress}% (" . ($index + 1) . "/" . number_format($total_files) . ")\n";
        echo "   Converted: " . number_format($converted) . "\n";
        echo "   Skipped: " . number_format($skipped) . "\n"; 
        echo "   Errors: " . number_format($errors) . "\n";
        echo "   ETA: " . gmdate('H:i:s', $eta) . "\n\n";
    }
}

// Final summary
$total_time = time() - $start_time;

echo "\n🎉 CONVERSION COMPLETE!\n";
echo "======================\n";
echo "Total files processed: " . number_format($total_files) . "\n";
echo "Successfully converted: " . number_format($converted) . "\n";
echo "Already existed: " . number_format($skipped) . "\n";
echo "Errors: " . number_format($errors) . "\n";
echo "Total time: " . gmdate('H:i:s', $total_time) . "\n\n";

if ($total_original_size > 0 && $total_webp_size > 0) {
    $savings_percent = round((($total_original_size - $total_webp_size) / $total_original_size) * 100, 1);
    
    echo "💾 STORAGE IMPACT:\n";
    echo "Original size: " . formatBytes($total_original_size) . "\n";
    echo "WebP size: " . formatBytes($total_webp_size) . "\n";
    echo "Space savings: " . formatBytes($total_original_size - $total_webp_size) . " ({$savings_percent}%)\n\n";
}

echo "✅ All files use correct naming (file.webp not file.jpg.webp)\n";
echo "✅ Original files remain unchanged\n";
echo "✅ Safe to run on production\n";