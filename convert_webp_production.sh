#!/bin/bash
# Production WebP Conversion Script
# Safe batch processing for production environment

echo "🚀 Production WebP Converter"
echo "=========================="
echo "Processing images in batches to prevent server overload"
echo ""

# Set PHP memory limit and execution time
export PHP_MEMORY_LIMIT=512M
export PHP_MAX_EXECUTION_TIME=0

# Process directories one by one
DIRECTORIES=(
    "webroot/sites/default/files/styles"
    "webroot/sites/default/files/biography_pics"
    "webroot/sites/default/files/bio_pics"
    "webroot/sites/default/files/article_images"
    "webroot/sites/default/files/images"
    "webroot/sites/default/files/media"
    "webroot/sites/default/files/gallery"
    "webroot/sites/default/files"
)

for DIR in "${DIRECTORIES[@]}"; do
    if [ -d "$DIR" ]; then
        echo "Processing: $DIR"
        find "$DIR" -maxdepth 1 \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \) -type f | while read -r file; do
            webp_file="${file}.webp"
            if [ ! -f "$webp_file" ]; then
                # Convert to WebP with 80% quality
                cwebp -q 80 "$file" -o "$webp_file" 2>/dev/null
                if [ $? -eq 0 ]; then
                    echo "✓ Converted: $(basename "$file")"
                else
                    echo "✗ Failed: $(basename "$file")"
                fi
            fi
        done
        echo "Completed: $DIR"
        echo "Sleeping 2 seconds to reduce server load..."
        sleep 2
    fi
done

echo ""
echo "✅ Conversion complete!"
echo "Original files remain unchanged"