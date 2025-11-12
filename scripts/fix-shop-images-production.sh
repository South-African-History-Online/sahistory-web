#!/bin/bash
#
# Fix Shop Product Images on Production
# Diagnoses and fixes image display issues
#
# Usage: bash fix-shop-images-production.sh
#

set -e

SHOP_URI="https://shop.sahistory.org.za"
FILES_DIR="sites/shop.sahistory.org.za/files"
COVERS_DIR="$FILES_DIR/product-covers"

echo "=========================================="
echo "Shop Product Images Diagnostic & Fix"
echo "=========================================="
echo ""

# Step 1: Check if images directory exists
echo "Step 1: Checking image directory..."
if [ -d "$COVERS_DIR" ]; then
    echo "✓ Directory exists: $COVERS_DIR"
    FILE_COUNT=$(ls -1 $COVERS_DIR/*.jpg 2>/dev/null | wc -l)
    echo "✓ Found $FILE_COUNT image files"
else
    echo "✗ Directory missing: $COVERS_DIR"
    echo "  Creating directory..."
    mkdir -p $COVERS_DIR
fi
echo ""

# Step 2: Check file permissions
echo "Step 2: Checking file permissions..."
OWNER=$(stat -c '%U:%G' $FILES_DIR 2>/dev/null || stat -f '%Su:%Sg' $FILES_DIR 2>/dev/null)
echo "Current owner: $OWNER"
echo "Directory permissions:"
ls -ld $FILES_DIR
ls -ld $COVERS_DIR 2>/dev/null || echo "Covers dir not found"
echo ""

# Step 3: Fix permissions
echo "Step 3: Fixing file permissions..."
read -p "Set correct permissions? (requires sudo) [y/N] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Setting ownership to www-data:www-data..."
    sudo chown -R www-data:www-data $FILES_DIR

    echo "Setting directory permissions to 775..."
    sudo find $FILES_DIR -type d -exec chmod 775 {} \;

    echo "Setting file permissions to 664..."
    sudo find $FILES_DIR -type f -exec chmod 664 {} \;

    echo "✓ Permissions fixed"
else
    echo "Skipped permission fix"
fi
echo ""

# Step 4: Check Drupal file_managed table
echo "Step 4: Checking Drupal file records..."
drush --uri=$SHOP_URI sqlq "
SELECT
    COUNT(*) as total_files,
    SUM(CASE WHEN uri LIKE '%product-covers%' THEN 1 ELSE 0 END) as product_images
FROM file_managed;
"
echo ""

# Step 5: Check image field references
echo "Step 5: Checking product image field references..."
drush --uri=$SHOP_URI sqlq "
SELECT
    COUNT(*) as products_with_images
FROM commerce_product__field_images;
"
echo ""

# Step 6: List products and their images
echo "Step 6: Sample product images..."
drush --uri=$SHOP_URI sqlq "
SELECT
    p.product_id,
    p.title,
    f.uri
FROM commerce_product_field_data p
LEFT JOIN commerce_product__field_images i ON p.product_id = i.entity_id
LEFT JOIN file_managed f ON i.field_images_target_id = f.fid
WHERE p.type = 'publication'
LIMIT 5;
"
echo ""

# Step 7: Check image styles
echo "Step 7: Checking image styles directory..."
STYLES_DIR="$FILES_DIR/styles"
if [ -d "$STYLES_DIR" ]; then
    echo "✓ Image styles directory exists"
    STYLE_COUNT=$(find $STYLES_DIR -name "*.jpg" 2>/dev/null | wc -l)
    echo "  Derivative images: $STYLE_COUNT"
else
    echo "✗ Image styles directory missing"
    echo "  Creating: $STYLES_DIR"
    mkdir -p $STYLES_DIR
    chmod 775 $STYLES_DIR
    chown www-data:www-data $STYLES_DIR 2>/dev/null || echo "  (Run with sudo to set owner)"
fi
echo ""

# Step 8: Flush image styles (regenerate derivatives)
echo "Step 8: Flushing image styles..."
read -p "Regenerate all image derivatives? [y/N] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Flushing image style derivatives..."
    drush --uri=$SHOP_URI image:flush --all
    echo "✓ Image styles flushed - derivatives will regenerate on demand"
else
    echo "Skipped image flush"
fi
echo ""

# Step 9: Clear Drupal caches
echo "Step 9: Clearing Drupal caches..."
drush --uri=$SHOP_URI cr
echo "✓ Cache cleared"
echo ""

# Step 10: Check .htaccess
echo "Step 10: Checking .htaccess in files directory..."
HTACCESS="$FILES_DIR/.htaccess"
if [ -f "$HTACCESS" ]; then
    echo "✓ .htaccess exists"
    if grep -q "SetHandler Drupal_Security_Do_Not_Remove_See_SA_2006_006" "$HTACCESS"; then
        echo "✓ .htaccess has correct rules"
    else
        echo "⚠ .htaccess might be incorrect"
    fi
else
    echo "✗ .htaccess missing in files directory"
    echo "  This might cause issues. Drupal should create it automatically."
fi
echo ""

# Step 11: Test image URLs
echo "Step 11: Testing image URL accessibility..."
TEST_IMAGE=$(drush --uri=$SHOP_URI sqlq "SELECT uri FROM file_managed WHERE uri LIKE '%product-covers%' LIMIT 1;" | tail -1)
if [ ! -z "$TEST_IMAGE" ]; then
    # Convert public:// to actual path
    IMAGE_PATH=$(echo $TEST_IMAGE | sed "s|public://|$FILES_DIR/|")
    echo "Testing image: $IMAGE_PATH"

    if [ -f "$IMAGE_PATH" ]; then
        echo "✓ Image file exists"
        FILE_SIZE=$(du -h "$IMAGE_PATH" | cut -f1)
        echo "  Size: $FILE_SIZE"

        # Test HTTP access
        IMAGE_URL="$SHOP_URI/sites/shop.sahistory.org.za/files/${IMAGE_PATH#*files/}"
        echo "  Testing URL: $IMAGE_URL"
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$IMAGE_URL")

        if [ "$HTTP_CODE" = "200" ]; then
            echo "✓ Image is accessible via HTTP (200 OK)"
        else
            echo "✗ Image returned HTTP $HTTP_CODE"
            echo "  This indicates a problem!"
        fi
    else
        echo "✗ Image file not found: $IMAGE_PATH"
    fi
else
    echo "No product images found in database"
fi
echo ""

# Summary
echo "=========================================="
echo "Diagnostic Complete"
echo "=========================================="
echo ""
echo "Common Issues & Solutions:"
echo ""
echo "1. Images not visible (broken image icon):"
echo "   - Check file permissions (664 for files, 775 for dirs)"
echo "   - Check ownership (www-data:www-data)"
echo "   - Clear Drupal cache"
echo ""
echo "2. Images show on some products but not others:"
echo "   - Flush image styles: drush image:flush --all"
echo "   - Check field_images references in database"
echo ""
echo "3. Image URLs 404:"
echo "   - Check .htaccess in files directory"
echo "   - Check web server configuration"
echo "   - Verify files were uploaded to production"
echo ""
echo "4. Images too large / slow to load:"
echo "   - Image styles should generate smaller derivatives"
echo "   - Check styles directory has images"
echo "   - Manually trigger: visit product pages to generate"
echo ""
echo "Next steps:"
echo "1. Visit: $SHOP_URI"
echo "2. Check a product page"
echo "3. Inspect element to see image URL"
echo "4. Check if URL returns 200 or 404"
echo ""
