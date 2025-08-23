<?php
/**
 * Fix Missing WebP Conversions
 * 
 * Specifically targets the 1,556 files that failed conversion
 * with enhanced error handling and retry logic
 */

set_time_limit(0);
ini_set('memory_limit', '1G');

echo "🔧 Fix Missing WebP Conversions\n";
echo "===============================\n\n";

// Find the correct files directory
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

// Statistics
$stats = [
    'total_missing' => 0,
    'converted' => 0,
    'skipped_exists' => 0,
    'failed_corrupt' => 0,
    'failed_permissions' => 0,
    'failed_memory' => 0,
    'failed_other' => 0
];

$problem_files = [];

// Function to attempt WebP conversion with detailed error reporting
function convertToWebP($source_path, $webp_path, &$stats, &$problem_files) {
    // Skip if WebP already exists
    if (file_exists($webp_path)) {
        $stats['skipped_exists']++;
        return true;
    }
    
    // Check if source file exists and is readable
    if (!file_exists($source_path) || !is_readable($source_path)) {
        $stats['failed_permissions']++;
        $problem_files[] = [
            'file' => $source_path,
            'error' => 'File not found or not readable',
            'type' => 'permissions'
        ];
        return false;
    }
    
    // Check file size (skip very large files that might cause memory issues)
    $file_size = filesize($source_path);
    if ($file_size > 50 * 1024 * 1024) { // 50MB limit
        $stats['failed_memory']++;
        $problem_files[] = [
            'file' => $source_path,
            'error' => 'File too large: ' . round($file_size/1024/1024, 2) . 'MB',
            'type' => 'memory'
        ];
        return false;
    }
    
    // Try to get image info first
    $image_info = @getimagesize($source_path);
    if (!$image_info) {
        $stats['failed_corrupt']++;
        $problem_files[] = [
            'file' => $source_path,
            'error' => 'Invalid or corrupt image file',
            'type' => 'corrupt'
        ];
        return false;
    }
    
    // Check if dimensions are reasonable
    if ($image_info[0] > 10000 || $image_info[1] > 10000) {
        $stats['failed_memory']++;
        $problem_files[] = [
            'file' => $source_path,
            'error' => 'Image too large: ' . $image_info[0] . 'x' . $image_info[1],
            'type' => 'memory'
        ];
        return false;
    }
    
    // Determine MIME type and create source image
    $source_image = null;
    switch ($image_info['mime']) {
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
            $stats['failed_other']++;
            $problem_files[] = [
                'file' => $source_path,
                'error' => 'Unsupported MIME type: ' . $image_info['mime'],
                'type' => 'unsupported'
            ];
            return false;
    }
    
    if ($source_image === false) {
        $stats['failed_corrupt']++;
        $problem_files[] = [
            'file' => $source_path,
            'error' => 'Failed to create image resource (corrupt file)',
            'type' => 'corrupt'
        ];
        return false;
    }
    
    // Try WebP conversion
    $success = @imagewebp($source_image, $webp_path, 80);
    imagedestroy($source_image);
    
    if ($success) {
        // Set correct permissions
        @chmod($webp_path, 0644);
        $stats['converted']++;
        echo "✅ Converted: " . basename($source_path) . "\n";
        return true;
    } else {
        $stats['failed_other']++;
        $problem_files[] = [
            'file' => $source_path,
            'error' => 'WebP conversion failed (imagewebp returned false)',
            'type' => 'conversion'
        ];
        return false;
    }
}

// Find all missing WebP files
echo "🔍 Scanning for missing WebP files...\n";

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($files_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$missing_files = [];

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $extension = strtolower($file->getExtension());
        
        // Check for double extension files first
        if (preg_match('/\.(jpg|jpeg|png)\.webp$/i', $file->getFilename())) {
            continue; // Skip double extension files
        }
        
        // Process image files
        if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            $filepath = $file->getRealPath();
            $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filepath);
            
            if (!file_exists($webp_path)) {
                $missing_files[] = $filepath;
                $stats['total_missing']++;
            }
        }
    }
}

echo "📊 Found " . number_format($stats['total_missing']) . " files missing WebP versions\n\n";

if (empty($missing_files)) {
    echo "✅ No missing WebP files found!\n";
    exit(0);
}

// Process missing files
echo "🚀 Processing missing files...\n";
$processed = 0;

foreach ($missing_files as $source_path) {
    $processed++;
    $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $source_path);
    
    // Show progress every 100 files
    if ($processed % 100 == 0) {
        $percent = round(($processed / count($missing_files)) * 100, 1);
        echo "📈 Progress: $processed/" . count($missing_files) . " ($percent%)\n";
    }
    
    convertToWebP($source_path, $webp_path, $stats, $problem_files);
    
    // Brief pause to prevent server overload
    if ($processed % 50 == 0) {
        usleep(100000); // 0.1 second pause
    }
}

echo "\n🎉 CONVERSION COMPLETE!\n";
echo "======================\n";
echo "Total missing files found: " . number_format($stats['total_missing']) . "\n";
echo "Successfully converted: " . number_format($stats['converted']) . "\n";
echo "Already existed: " . number_format($stats['skipped_exists']) . "\n";
echo "Failed - corrupt/invalid: " . number_format($stats['failed_corrupt']) . "\n";
echo "Failed - permissions: " . number_format($stats['failed_permissions']) . "\n";
echo "Failed - too large: " . number_format($stats['failed_memory']) . "\n";
echo "Failed - other reasons: " . number_format($stats['failed_other']) . "\n";

if (!empty($problem_files)) {
    echo "\n⚠️  PROBLEM FILES REPORT:\n";
    echo "========================\n";
    
    // Group by error type
    $grouped_problems = [];
    foreach ($problem_files as $problem) {
        $grouped_problems[$problem['type']][] = $problem;
    }
    
    foreach ($grouped_problems as $type => $files) {
        echo "\n" . strtoupper($type) . " ISSUES (" . count($files) . " files):\n";
        echo str_repeat("-", 40) . "\n";
        
        $shown = 0;
        foreach ($files as $file) {
            if ($shown < 10) { // Show first 10 of each type
                echo "• " . basename($file['file']) . " - " . $file['error'] . "\n";
                $shown++;
            }
        }
        
        if (count($files) > 10) {
            echo "... and " . (count($files) - 10) . " more " . $type . " files\n";
        }
    }
}

// Final status
$final_success_rate = $stats['total_missing'] > 0 ? 
    round((($stats['converted'] + $stats['skipped_exists']) / $stats['total_missing']) * 100, 1) : 100;

echo "\n📊 FINAL RESULT: $final_success_rate% of missing files processed successfully\n";

if ($stats['converted'] > 0) {
    echo "🎯 Run comprehensive status check to see updated conversion rate:\n";
    echo "   php comprehensive_webp_status.php\n";
}