#!/bin/bash

# SAHO WebP Batch Converter
# Processes images in small batches to avoid timeouts
# 100% SAFE - only creates .webp files alongside originals

echo "🚀 SAHO WebP Batch Converter"
echo "Converting in batches of 500 images..."
echo "SAFE: Original files are never modified!"

BATCH_SIZE=500
CURRENT_BATCH=0
TOTAL_CONVERTED=0

# Create conversion function
convert_batch() {
    local skip_count=$((CURRENT_BATCH * BATCH_SIZE))
    echo ""
    echo "📦 Processing batch $((CURRENT_BATCH + 1))..."
    echo "   Skipping first $skip_count files..."
    
    # Use PHP to process this batch
    ddev drush php:eval "
    \$converted_in_batch = 0;
    \$skip_count = $skip_count;
    \$batch_size = $BATCH_SIZE;
    \$current_count = 0;
    
    function convertImageBatch(\$file_path) {
        \$webp_path = \$file_path . '.webp';
        if (file_exists(\$webp_path)) return false;
        
        try {
            \$image_info = getimagesize(\$file_path);
            if (!\$image_info) return false;
            
            \$source_image = null;
            switch (\$image_info['mime']) {
                case 'image/jpeg':
                    \$source_image = imagecreatefromjpeg(\$file_path);
                    break;
                case 'image/png':
                    \$source_image = imagecreatefrompng(\$file_path);
                    imagealphablending(\$source_image, false);
                    imagesavealpha(\$source_image, true);
                    break;
                default:
                    return false;
            }
            
            if (!\$source_image) return false;
            \$success = imagewebp(\$source_image, \$webp_path, 80);
            imagedestroy(\$source_image);
            return \$success;
        } catch (Exception \$e) {
            return false;
        }
    }
    
    \$iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('sites/default/files', RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach (\$iterator as \$file) {
        if (\$file->isFile()) {
            \$extension = strtolower(\$file->getExtension());
            if (in_array(\$extension, ['jpg', 'jpeg', 'png'])) {
                if (\$current_count < \$skip_count) {
                    \$current_count++;
                    continue;
                }
                
                if (\$converted_in_batch >= \$batch_size) {
                    break;
                }
                
                if (convertImageBatch(\$file->getRealPath())) {
                    \$converted_in_batch++;
                    if (\$converted_in_batch % 50 == 0) {
                        echo '✓ Converted ' . \$converted_in_batch . ' in this batch' . PHP_EOL;
                    }
                }
                \$current_count++;
            }
        }
    }
    
    echo 'Batch complete: ' . \$converted_in_batch . ' images converted' . PHP_EOL;
    "
    
    # Extract the number from the output
    BATCH_RESULT=$(ddev drush php:eval "
    \$converted_in_batch = 0;
    \$skip_count = $skip_count;
    \$batch_size = $BATCH_SIZE;
    \$current_count = 0;
    
    function convertImageBatch(\$file_path) {
        \$webp_path = \$file_path . '.webp';
        if (file_exists(\$webp_path)) return false;
        
        try {
            \$image_info = getimagesize(\$file_path);
            if (!\$image_info) return false;
            
            \$source_image = null;
            switch (\$image_info['mime']) {
                case 'image/jpeg':
                    \$source_image = imagecreatefromjpeg(\$file_path);
                    break;
                case 'image/png':
                    \$source_image = imagecreatefrompng(\$file_path);
                    imagealphablending(\$source_image, false);
                    imagesavealpha(\$source_image, true);
                    break;
                default:
                    return false;
            }
            
            if (!\$source_image) return false;
            \$success = imagewebp(\$source_image, \$webp_path, 80);
            imagedestroy(\$source_image);
            return \$success;
        } catch (Exception \$e) {
            return false;
        }
    }
    
    \$iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('sites/default/files', RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach (\$iterator as \$file) {
        if (\$file->isFile()) {
            \$extension = strtolower(\$file->getExtension());
            if (in_array(\$extension, ['jpg', 'jpeg', 'png'])) {
                if (\$current_count < \$skip_count) {
                    \$current_count++;
                    continue;
                }
                
                if (\$converted_in_batch >= \$batch_size) {
                    break;
                }
                
                if (convertImageBatch(\$file->getRealPath())) {
                    \$converted_in_batch++;
                }
                \$current_count++;
            }
        }
    }
    
    echo \$converted_in_batch;
    " 2>/dev/null | tail -1)
    
    TOTAL_CONVERTED=$((TOTAL_CONVERTED + BATCH_RESULT))
    echo "   ✅ Batch $((CURRENT_BATCH + 1)) complete: $BATCH_RESULT images converted"
    echo "   📊 Total converted so far: $TOTAL_CONVERTED"
    
    if [ "$BATCH_RESULT" -lt "$BATCH_SIZE" ]; then
        echo ""
        echo "🎉 All images processed!"
        echo "📊 Final total: $TOTAL_CONVERTED WebP files created"
        echo "✅ All original files remain unchanged"
        exit 0
    fi
}

# Main processing loop
while true; do
    convert_batch
    CURRENT_BATCH=$((CURRENT_BATCH + 1))
    
    echo ""
    echo "⏸ Pausing 5 seconds between batches..."
    sleep 5
done