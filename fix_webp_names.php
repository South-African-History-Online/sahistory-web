<?php
/**
 * Fix WebP file naming issue
 * Renames files from file.jpg.webp to file.webp
 */

echo "🔧 Fixing WebP file names\n";
echo "========================\n\n";

$fixed = 0;
$already_correct = 0;
$errors = 0;

// Find the correct files directory
$files_dir = 'webroot/sites/default/files';
if (!is_dir($files_dir)) {
    $files_dir = '/var/www/html/webroot/sites/default/files';
    if (!is_dir($files_dir)) {
        $files_dir = '/var/www/html/sites/default/files';
        if (!is_dir($files_dir)) {
            echo "Files directory not found!\n";
            exit(1);
        }
    }
}

echo "Using files directory: $files_dir\n";

// Find all WebP files with double extensions
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($files_dir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $filename = $file->getFilename();
        $filepath = $file->getRealPath();
        
        // Check if it has double extension (.jpg.webp, .jpeg.webp, .png.webp)
        if (preg_match('/\.(jpg|jpeg|png|JPG|JPEG|PNG)\.webp$/i', $filename)) {
            // Get the correct name (remove the original extension)
            $new_filename = preg_replace('/\.(jpg|jpeg|png|JPG|JPEG|PNG)\.webp$/i', '.webp', $filename);
            $new_filepath = dirname($filepath) . '/' . $new_filename;
            
            // Check if correct file already exists
            if (file_exists($new_filepath)) {
                // Compare file sizes to see if they're the same
                if (filesize($filepath) == filesize($new_filepath)) {
                    // Same file, remove the duplicate
                    unlink($filepath);
                    echo "🗑️  Removed duplicate: $filename\n";
                    $already_correct++;
                } else {
                    // Different files, skip
                    echo "⚠️  Conflict (keeping both): $filename\n";
                    $errors++;
                }
            } else {
                // Rename the file
                if (rename($filepath, $new_filepath)) {
                    echo "✅ Renamed: $filename → $new_filename\n";
                    $fixed++;
                } else {
                    echo "❌ Failed to rename: $filename\n";
                    $errors++;
                }
            }
        }
    }
}

echo "\n📊 Summary:\n";
echo "===========\n";
echo "✅ Fixed: $fixed files\n";
echo "🗑️  Removed duplicates: $already_correct files\n";
echo "⚠️  Errors/Conflicts: $errors files\n";
echo "\nWebP files now have correct naming!";