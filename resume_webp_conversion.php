<?php
/**
 * Resume WebP conversion from where it left off
 * 
 * Automatically detects progress and continues conversion
 */

set_time_limit(0);
ini_set('memory_limit', '1G');

echo "🔄 WebP Conversion Resume Tool\n";
echo "==============================\n";

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

// Count total images and existing WebP files
$total_images = 0;
$webp_count = 0;
$last_processed = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($files_dir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$all_images = [];
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $extension = strtolower($file->getExtension());
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $all_images[] = $file->getRealPath();
            $total_images++;
            
            // Check if WebP exists
            $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file->getRealPath());
            if (file_exists($webp_path)) {
                $webp_count++;
                $last_processed++;
            }
        } elseif ($extension === 'webp') {
            $webp_count++;
        }
    }
}

$completion_rate = $total_images > 0 ? round(($last_processed / $total_images) * 100, 1) : 0;
$remaining = $total_images - $last_processed;

echo "📊 Conversion Status:\n";
echo "Total images: " . number_format($total_images) . "\n";
echo "Already converted: " . number_format($last_processed) . "\n";
echo "Remaining: " . number_format($remaining) . "\n";
echo "Completion: {$completion_rate}%\n\n";

if ($remaining == 0) {
    echo "✅ Conversion already complete!\n";
    exit(0);
}

// Suggest optimal batch size based on remaining files
$suggested_batch = min(5000, max(1000, $remaining));

echo "🚀 RESUME CONVERSION:\n";
echo "Run: php convert_webp_chunked.php {$suggested_batch} {$last_processed}\n";
echo "\nThis will process the remaining " . number_format($remaining) . " files.\n";
echo "Estimated batches needed: " . ceil($remaining / $suggested_batch) . "\n";