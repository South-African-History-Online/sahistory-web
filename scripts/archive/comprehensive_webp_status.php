<?php
/**
 * Comprehensive WebP Status Checker
 * 
 * Scans ALL subdirectories within sites/default/files/
 * and provides detailed conversion statistics
 */

set_time_limit(0);
ini_set('memory_limit', '512M');

echo "🔍 Comprehensive WebP Status Check\n";
echo "==================================\n\n";

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

echo "📁 Scanning directory: $files_dir\n\n";

// Statistics tracking
$stats = [
    'total_images' => 0,
    'webp_exists' => 0,
    'webp_missing' => 0,
    'directories' => [],
    'large_unconverted' => [],
    'double_extensions' => 0,
    'conversion_rate' => 0
];

$missing_files = [];

// Recursively scan all directories
function scanDirectory($dir, &$stats, &$missing_files, $base_dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filename = $file->getFilename();
            $filepath = $file->getRealPath();
            $extension = strtolower($file->getExtension());
            
            // Get relative directory for reporting
            $relative_dir = str_replace($base_dir . '/', '', dirname($filepath));
            
            // Initialize directory stats if not exists
            if (!isset($stats['directories'][$relative_dir])) {
                $stats['directories'][$relative_dir] = [
                    'images' => 0,
                    'webp_exists' => 0,
                    'webp_missing' => 0
                ];
            }
            
            // Check for double extension WebP files
            if (preg_match('/\.(jpg|jpeg|png)\\.webp$/i', $filename)) {
                $stats['double_extensions']++;
                continue;
            }
            
            // Process image files
            if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                $stats['total_images']++;
                $stats['directories'][$relative_dir]['images']++;
                
                // Check if WebP version exists
                $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filepath);
                
                if (file_exists($webp_path)) {
                    $stats['webp_exists']++;
                    $stats['directories'][$relative_dir]['webp_exists']++;
                } else {
                    $stats['webp_missing']++;
                    $stats['directories'][$relative_dir]['webp_missing']++;
                    
                    // Track missing files with size info
                    $size = filesize($filepath);
                    $missing_files[] = [
                        'path' => $relative_dir . '/' . $filename,
                        'size' => $size,
                        'size_mb' => round($size / 1024 / 1024, 2)
                    ];
                    
                    // Track large unconverted files (> 100KB)
                    if ($size > 102400) {
                        $stats['large_unconverted'][] = [
                            'path' => $relative_dir . '/' . $filename,
                            'size_mb' => round($size / 1024 / 1024, 2)
                        ];
                    }
                }
            }
        }
    }
}

// Scan the directory
scanDirectory($files_dir, $stats, $missing_files, $files_dir);

// Calculate conversion rate
if ($stats['total_images'] > 0) {
    $stats['conversion_rate'] = round(($stats['webp_exists'] / $stats['total_images']) * 100, 1);
}

// Display overall statistics
echo "📊 OVERALL STATISTICS\n";
echo "====================\n";
echo "Total images found: " . number_format($stats['total_images']) . "\n";
echo "WebP files exist: " . number_format($stats['webp_exists']) . "\n";
echo "Missing WebP: " . number_format($stats['webp_missing']) . "\n";
echo "Conversion rate: " . $stats['conversion_rate'] . "%\n";

if ($stats['double_extensions'] > 0) {
    echo "⚠️  Double extension files: " . $stats['double_extensions'] . "\n";
}

echo "\n";

// Display directory breakdown
echo "📂 DIRECTORY BREAKDOWN\n";
echo "======================\n";
// Sort directories by missing count (highest first)
uasort($stats['directories'], function($a, $b) {
    return $b['webp_missing'] - $a['webp_missing'];
});

foreach ($stats['directories'] as $dir => $dir_stats) {
    if ($dir_stats['images'] > 0) {
        $dir_rate = $dir_stats['images'] > 0 ? round(($dir_stats['webp_exists'] / $dir_stats['images']) * 100, 1) : 0;
        $status_icon = $dir_stats['webp_missing'] > 0 ? '❌' : '✅';
        
        echo sprintf(
            "%s %s: %d images, %d converted (%s%%), %d missing\n",
            $status_icon,
            $dir,
            $dir_stats['images'],
            $dir_stats['webp_exists'],
            $dir_rate,
            $dir_stats['webp_missing']
        );
    }
}

echo "\n";

// Show largest unconverted files
if (!empty($stats['large_unconverted'])) {
    echo "🔍 LARGEST UNCONVERTED FILES (>100KB)\n";
    echo "====================================\n";
    
    // Sort by size (largest first)
    usort($stats['large_unconverted'], function($a, $b) {
        return $b['size_mb'] <=> $a['size_mb'];
    });
    
    $shown = 0;
    foreach ($stats['large_unconverted'] as $file) {
        if ($shown < 20) { // Show top 20
            echo sprintf("%.2f MB - %s\n", $file['size_mb'], $file['path']);
            $shown++;
        }
    }
    
    if (count($stats['large_unconverted']) > 20) {
        $remaining = count($stats['large_unconverted']) - 20;
        echo "... and $remaining more large files\n";
    }
    echo "\n";
}

// Recommendations
echo "💡 RECOMMENDATIONS\n";
echo "==================\n";

if ($stats['webp_missing'] > 0) {
    echo "🔧 Run conversion for missing files:\n";
    echo "   php convert_webp_chunked.php 5000 0\n\n";
    
    if (count($stats['large_unconverted']) > 0) {
        echo "🎯 Focus on large files first for biggest impact:\n";
        echo "   These " . count($stats['large_unconverted']) . " large files will give the most bandwidth savings\n\n";
    }
}

if ($stats['double_extensions'] > 0) {
    echo "🔧 Fix double extension files:\n";
    echo "   php fix_webp_names.php\n\n";
}

if ($stats['conversion_rate'] >= 95) {
    echo "✅ Conversion rate is excellent! (" . $stats['conversion_rate'] . "%)\n";
} elseif ($stats['conversion_rate'] >= 85) {
    echo "✅ Conversion rate is good (" . $stats['conversion_rate'] . "%), just a few missing\n";
} else {
    echo "⚠️  Conversion rate needs improvement (" . $stats['conversion_rate'] . "%)\n";
}

echo "\n📋 SUMMARY: " . number_format($stats['webp_missing']) . " images still need WebP conversion\n";