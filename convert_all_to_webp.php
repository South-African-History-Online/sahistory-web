<?php
/**
 * Mass WebP Converter for SAHO
 * 
 * SAFE: Only creates .webp files alongside originals
 * Run: php convert_all_to_webp.php
 */

set_time_limit(0); // Remove time limit
ini_set('memory_limit', '512M'); // Increase memory

$start_time = time();
$converted = 0;
$skipped = 0;
$errors = 0;
$total_original_size = 0;
$total_webp_size = 0;

function convertToWebP($source_path) {
    $webp_path = $source_path . '.webp';
    
    // Skip if WebP already exists
    if (file_exists($webp_path)) {
        return 'exists';
    }
    
    try {
        $image_info = getimagesize($source_path);
        if (!$image_info) {
            return 'invalid';
        }
        
        $mime_type = $image_info['mime'];
        $source_image = null;
        
        switch ($mime_type) {
            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $source_image = imagecreatefrompng($source_path);
                imagealphablending($source_image, false);
                imagesavealpha($source_image, true);
                break;
            default:
                return 'unsupported';
        }
        
        if (!$source_image) {
            return 'failed_load';
        }
        
        // Convert to WebP with 80% quality
        $success = imagewebp($source_image, $webp_path, 80);
        imagedestroy($source_image);
        
        return $success ? 'converted' : 'failed_convert';
        
    } catch (Exception $e) {
        return 'error: ' . $e->getMessage();
    }
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

echo "🚀 SAHO WebP Mass Converter\n";
echo "==========================\n";
echo "SAFE: Creates .webp files alongside originals\n";
echo "Original files are NEVER modified or deleted\n\n";

// Find all images
$files_to_convert = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('webroot/sites/default/files', RecursiveDirectoryIterator::SKIP_DOTS)
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
            $webp_size = filesize($file_path . '.webp');
            $total_webp_size += $webp_size;
            $savings = round((1 - $webp_size / $original_size) * 100, 1);
            
            if ($converted % 100 == 0) {
                echo "✓ Converted " . number_format($converted) . " files (current: -{$savings}%)\n";
            }
            break;
            
        case 'exists':
            $skipped++;
            if (file_exists($file_path . '.webp')) {
                $total_webp_size += filesize($file_path . '.webp');
            }
            break;
            
        default:
            $errors++;
            if ($errors % 50 == 0) {
                echo "⚠ {$errors} errors so far\n";
            }
            break;
    }
    
    // Progress update every 1000 files
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
$original_size_mb = $total_original_size / 1024 / 1024;
$webp_size_mb = $total_webp_size / 1024 / 1024;
$savings_mb = $original_size_mb - $webp_size_mb;
$savings_percent = $total_original_size > 0 ? round(($savings_mb / $original_size_mb) * 100, 1) : 0;

echo "\n🎉 CONVERSION COMPLETE!\n";
echo "======================\n";
echo "Total files processed: " . number_format($total_files) . "\n";
echo "Successfully converted: " . number_format($converted) . "\n";
echo "Already existed: " . number_format($skipped) . "\n";
echo "Errors: " . number_format($errors) . "\n";
echo "Total time: " . gmdate('H:i:s', $total_time) . "\n\n";

echo "💾 STORAGE IMPACT:\n";
echo "Original size: " . formatBytes($total_original_size) . "\n";
echo "WebP size: " . formatBytes($total_webp_size) . "\n";
echo "Space savings: " . formatBytes($total_original_size - $total_webp_size) . " ({$savings_percent}%)\n\n";

echo "🌐 BANDWIDTH SAVINGS:\n";
echo "For every 1000 visitors, you'll save: " . formatBytes(($total_original_size - $total_webp_size) * 1000) . "\n";
echo "Monthly savings (10k visitors): " . formatBytes(($total_original_size - $total_webp_size) * 10000) . "\n\n";

echo "✅ All original files remain unchanged!\n";
echo "✅ WebP files created alongside originals\n";
echo "✅ Safe to delete .webp files anytime\n";