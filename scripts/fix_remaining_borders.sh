#!/bin/bash

# Quick script to manually fix remaining place images with stubborn borders
# Uses -shave instead of -trim for uniform border removal

set -euo pipefail

# Files to fix (add more as you find them)
IMAGES_TO_FIX=(
    "place images/cape winelands airport.jpg"
    "button/field_feature_banner/WONDERBOOMPOORT.png"
)

cd webroot/sites/default/files

echo "Fixing remaining images with red borders..."
echo ""

for img in "${IMAGES_TO_FIX[@]}"; do
    if [ -f "$img" ]; then
        echo "Processing: $img"

        # Backup if not already backed up
        if [ ! -f "${img}.backup" ]; then
            cp "$img" "${img}.backup"
            echo "  ✓ Backed up"
        fi

        # Remove 5px border from all sides
        magick "$img" -shave 5x5 "$img"
        echo "  ✓ Removed 5px border from all edges"
    else
        echo "  ✗ Not found: $img"
    fi
    echo ""
done

echo "Done! Now run:"
echo "  vendor/bin/drush image-flush --all"
echo "  vendor/bin/drush cr"
