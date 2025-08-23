<?php
/**
 * Chunked WebP Mass Converter for Production
 * 
 * Processes images in batches to avoid memory/timeout issues
 * 
 * Usage: php convert_webp_chunked.php [batch_size] [start_offset]
 * Example: php convert_webp_chunked.php 5000 0
 */

set_time_limit(0);
ini_set('memory_limit', '1G');

$batch_size = isset($argv[1]) ? (int)$argv[1] : 5000;
$start_offset = isset($argv[2]) ? (int)$argv[2] : 0;

echo "🚀 Chunked WebP Converter for Production\n";
echo "=======================================\n";
echo "Batch size: " . number_format($batch_size) . " files\n";
echo "Start offset: " . number_format($start_offset) . " files\n";
echo "Original files are NEVER modified\n\n";

$start_time = time();
$converted = 0;
$skipped = 0;
$errors = 0;
$total_original_size = 0;
$total_webp_size = 0;

function convertToWebP($source_path) {
    $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);
    
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
        
        $success = @imagewebp($source_image, $webp_path, 80);
        imagedestroy($source_image);
        
        if ($success) {
            chmod($webp_path, fileperms($source_path));
            return 'converted';
        }
        
        return 'failed_convert';
        
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

// Find files directory
$possible_dirs = [
    'sites/default/files',
    'webroot/sites/default/files',
    '../sites/default/files',
];

$files_dir = null;
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

// Collect all image files
$all_files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($files_dir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $extension = strtolower($file->getExtension());
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $all_files[] = $file->getRealPath();
        }
    }
}

$total_files = count($all_files);
echo "📊 Total images found: " . number_format($total_files) . "\n";

// Calculate batch range
$end_offset = min($start_offset + $batch_size, $total_files);
$batch_files = array_slice($all_files, $start_offset, $batch_size);
$batch_count = count($batch_files);

echo "🎯 Processing batch: " . number_format($start_offset + 1) . " to " . number_format($end_offset) . "\n";
echo "📦 Batch size: " . number_format($batch_count) . " files\n\n";

if ($batch_count == 0) {
    echo "✅ No more files to process!\n";
    exit(0);
}

// Process the batch
foreach ($batch_files as $index => $file_path) {
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
                echo "⚠ {$errors} errors in this batch\n";
            }
            break;
    }
    
    // Progress within batch
    if (($index + 1) % 500 == 0) {
        $batch_progress = round((($index + 1) / $batch_count) * 100, 1);
        $overall_progress = round((($start_offset + $index + 1) / $total_files) * 100, 1);
        echo "📈 Batch progress: {$batch_progress}% | Overall: {$overall_progress}%\n";
    }
    
    // Memory cleanup every 1000 files
    if (($index + 1) % 1000 == 0) {
        gc_collect_cycles();
    }
}

// Batch summary
$total_time = time() - $start_time;
$next_offset = $start_offset + $batch_count;

echo "\n🎉 BATCH COMPLETE!\n";
echo "==================\n";
echo "Files processed in batch: " . number_format($batch_count) . "\n";
echo "Converted: " . number_format($converted) . "\n";
echo "Skipped: " . number_format($skipped) . "\n";
echo "Errors: " . number_format($errors) . "\n";
echo "Batch time: " . gmdate('H:i:s', $total_time) . "\n\n";

if ($total_original_size > 0 && $total_webp_size > 0) {
    $savings_percent = round((($total_original_size - $total_webp_size) / $total_original_size) * 100, 1);
    
    echo "💾 BATCH STORAGE IMPACT:\n";
    echo "Original size: " . formatBytes($total_original_size) . "\n";
    echo "WebP size: " . formatBytes($total_webp_size) . "\n";
    echo "Space savings: " . formatBytes($total_original_size - $total_webp_size) . " ({$savings_percent}%)\n\n";
}

// Next steps
if ($next_offset < $total_files) {
    $remaining = $total_files - $next_offset;
    echo "🚀 CONTINUE PROCESSING:\n";
    echo "php convert_webp_chunked.php {$batch_size} {$next_offset}\n";
    echo "Remaining files: " . number_format($remaining) . "\n";
} else {
    echo "✅ ALL FILES PROCESSED!\n";
    echo "Ready for production use.\n";
}