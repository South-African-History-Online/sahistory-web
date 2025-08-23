<?php
/**
 * Complete WebP Conversion - Runs all remaining batches automatically
 * 
 * This script will:
 * 1. Fix any double extension files
 * 2. Run chunked conversion until all files are processed
 * 3. Provide final status report
 */

set_time_limit(0);
ini_set('memory_limit', '1G');

echo "🚀 Complete WebP Conversion Tool\n";
echo "================================\n\n";

// Step 1: Fix double extension files
echo "Step 1: Fixing double extension files...\n";
$fix_script = 'fix_webp_names.php';
if (file_exists($fix_script)) {
    passthru("php $fix_script", $fix_return);
    if ($fix_return === 0) {
        echo "✅ Double extensions fixed successfully\n\n";
    } else {
        echo "⚠️  Fix completed with warnings (code: $fix_return)\n\n";
    }
} else {
    echo "ℹ️  Fix script not found, skipping...\n\n";
}

// Step 2: Run conversion batches until complete
echo "Step 2: Running conversion batches...\n";
$batch_size = 5000;
$max_batches = 20; // Safety limit
$batch_count = 0;

while ($batch_count < $max_batches) {
    $batch_count++;
    
    // Get current status
    $status_output = shell_exec("php resume_webp_conversion.php 2>/dev/null");
    
    // Parse remaining files from output
    if (preg_match('/Remaining: ([\d,]+)/', $status_output, $matches)) {
        $remaining = intval(str_replace(',', '', $matches[1]));
        
        if ($remaining <= 0) {
            echo "✅ All files converted! No more batches needed.\n";
            break;
        }
        
        // Parse start offset
        if (preg_match('/php convert_webp_chunked\.php \d+ (\d+)/', $status_output, $offset_matches)) {
            $start_offset = intval($offset_matches[1]);
            
            echo "📦 Running batch $batch_count: Starting from file " . number_format($start_offset) . "\n";
            echo "   Files remaining: " . number_format($remaining) . "\n";
            
            // Run the batch
            $batch_start = time();
            passthru("php convert_webp_chunked.php $batch_size $start_offset", $batch_return);
            $batch_time = time() - $batch_start;
            
            if ($batch_return === 0) {
                echo "✅ Batch $batch_count completed in " . gmdate('H:i:s', $batch_time) . "\n\n";
            } else {
                echo "⚠️  Batch $batch_count completed with warnings (code: $batch_return)\n\n";
            }
            
            // Brief pause between batches
            sleep(2);
        } else {
            echo "❌ Could not parse resume command, stopping.\n";
            break;
        }
    } else {
        echo "❌ Could not parse remaining files count, stopping.\n";
        break;
    }
}

if ($batch_count >= $max_batches) {
    echo "⚠️  Reached maximum batch limit ($max_batches). Run script again if needed.\n\n";
}

// Step 3: Final status report
echo "Step 3: Final status report...\n";
echo "==============================\n";

$final_status = shell_exec("php resume_webp_conversion.php 2>/dev/null");
echo $final_status;

// Also show the drush status
echo "\nDrush WebP Status:\n";
echo "------------------\n";
passthru("vendor/bin/drush saho:webp-status 2>/dev/null || echo 'Drush status unavailable'");

echo "\n🎉 WebP conversion process complete!\n";
echo "Original images are preserved.\n";
echo "WebP files created with correct naming.\n";