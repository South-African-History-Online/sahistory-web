#!/bin/bash

# Product Image Import Readiness Check
# Usage: ./scripts/check_image_readiness.sh

echo "================================================="
echo "Product Image Import Readiness Check"
echo "================================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check 1: CSV file exists
echo -n "1. Checking mapping CSV... "
if [ -f "product_image_mapping.csv" ]; then
    COUNT=$(wc -l < product_image_mapping.csv)
    echo -e "${GREEN}✓ Found${NC} (${COUNT} rows)"
else
    echo -e "${RED}✗ Missing${NC}"
    echo "   Run: ddev drush -l shop.ddev.site php:script scripts/generate_image_mapping.php"
    exit 1
fi

# Check 2: Covers directory exists
echo -n "2. Checking covers directory... "
if [ -d "covers" ]; then
    echo -e "${GREEN}✓ Exists${NC}"
else
    echo -e "${YELLOW}! Missing${NC}"
    mkdir -p covers
    echo "   Created covers/ directory"
fi

# Check 3: Count images in covers directory
echo -n "3. Checking for images... "
IMAGE_COUNT=$(find covers -type f \( -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" \) 2>/dev/null | wc -l)
if [ "$IMAGE_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓ Found ${IMAGE_COUNT} images${NC}"
else
    echo -e "${YELLOW}! No images found${NC}"
    echo "   Copy your cover images to the covers/ directory"
fi

# Check 4: List expected vs actual images
if [ "$IMAGE_COUNT" -gt 0 ]; then
    echo ""
    echo "4. Matching images to products:"
    echo "   Expected: 33 images"
    echo "   Found: ${IMAGE_COUNT} images"
    echo ""

    # Extract expected filenames from CSV
    tail -n +2 product_image_mapping.csv | cut -d',' -f6 | sort > /tmp/expected_files.txt

    # List actual files
    find covers -type f -name "*.jpg" -o -name "*.jpeg" -o -name "*.png" | xargs -n1 basename | sort > /tmp/actual_files.txt

    # Show matches and mismatches
    echo "   Matched images:"
    comm -12 /tmp/expected_files.txt /tmp/actual_files.txt | head -10 | sed 's/^/     ✓ /'

    MISSING_COUNT=$(comm -23 /tmp/expected_files.txt /tmp/actual_files.txt | wc -l)
    if [ "$MISSING_COUNT" -gt 0 ]; then
        echo ""
        echo -e "   ${YELLOW}Missing ${MISSING_COUNT} images:${NC}"
        comm -23 /tmp/expected_files.txt /tmp/actual_files.txt | head -5 | sed 's/^/     - /'
        if [ "$MISSING_COUNT" -gt 5 ]; then
            echo "     ... and $((MISSING_COUNT - 5)) more"
        fi
    fi

    EXTRA_COUNT=$(comm -13 /tmp/expected_files.txt /tmp/actual_files.txt | wc -l)
    if [ "$EXTRA_COUNT" -gt 0 ]; then
        echo ""
        echo -e "   ${YELLOW}Unexpected files (not in CSV):${NC}"
        comm -13 /tmp/expected_files.txt /tmp/actual_files.txt | head -5 | sed 's/^/     ? /'
    fi

    rm -f /tmp/expected_files.txt /tmp/actual_files.txt
fi

echo ""
echo "================================================="
echo "Status Summary"
echo "================================================="

READY=true

if [ ! -f "product_image_mapping.csv" ]; then
    echo -e "${RED}✗ CSV mapping file missing${NC}"
    READY=false
fi

if [ ! -d "covers" ]; then
    echo -e "${RED}✗ Covers directory missing${NC}"
    READY=false
fi

if [ "$IMAGE_COUNT" -eq 0 ]; then
    echo -e "${YELLOW}⚠ No images in covers/ directory${NC}"
    READY=false
fi

if [ "$READY" = true ] && [ "$IMAGE_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓ Ready to import!${NC}"
    echo ""
    echo "Run import command:"
    echo "  ddev drush -l shop.ddev.site php:script scripts/import_product_images.php"
else
    echo ""
    echo "Next steps:"
    echo "  1. Copy cover images to: covers/"
    echo "  2. Ensure filenames match the CSV (see product_image_mapping.csv)"
    echo "  3. Run this check again: ./scripts/check_image_readiness.sh"
    echo ""
    echo "See PRODUCT-IMAGE-IMPORT-GUIDE.md for detailed instructions"
fi

echo ""
