#!/bin/bash
#
# Fix NULL image alt text across the site
# This resolves Search API ImageFormatter errors
#
# Usage from project root: bash scripts/fix-image-alt-text.sh
# Usage from webroot: bash ../scripts/fix-image-alt-text.sh
# On production: cd webroot && bash ../scripts/fix-image-alt-text.sh

# Change to webroot if we're in project root
if [ -d "webroot" ] && [ ! -f "index.php" ]; then
    echo "Changing to webroot directory..."
    cd webroot || exit 1
fi

# Detect drush location
if command -v drush &> /dev/null; then
    DRUSH="drush"
elif [ -f "vendor/bin/drush" ]; then
    DRUSH="vendor/bin/drush"
elif [ -f "../vendor/bin/drush" ]; then
    DRUSH="../vendor/bin/drush"
else
    echo "Error: drush not found."
    echo "Please run from Drupal webroot directory or project root."
    exit 1
fi

echo "=========================================="
echo "Fixing NULL Image Alt Text"
echo "=========================================="
echo "Using: $DRUSH"
echo ""

# Count issues before fix
echo "Checking current issues..."
BIO_COUNT=$($DRUSH sqlq "SELECT COUNT(*) FROM node__field_bio_pic WHERE field_bio_pic_alt IS NULL OR field_bio_pic_alt = ''")
ARTICLE_COUNT=$($DRUSH sqlq "SELECT COUNT(*) FROM node__field_article_image WHERE field_article_image_alt IS NULL OR field_article_image_alt = ''")
IMAGE_COUNT=$($DRUSH sqlq "SELECT COUNT(*) FROM node__field_image WHERE field_image_alt IS NULL OR field_image_alt = ''")

echo "Found issues:"
echo "  - Biography images: $BIO_COUNT"
echo "  - Article images: $ARTICLE_COUNT"
echo "  - General images: $IMAGE_COUNT"
echo ""

TOTAL=$((BIO_COUNT + ARTICLE_COUNT + IMAGE_COUNT))
if [ "$TOTAL" -eq 0 ]; then
    echo "No issues found! All images have alt text."
    exit 0
fi

echo "Total images to fix: $TOTAL"
echo ""
read -p "Continue with fix? (y/n) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 1
fi

echo ""
echo "Fixing biography images..."
$DRUSH sqlq "UPDATE node__field_bio_pic nbp JOIN node_field_data nfd ON nbp.entity_id = nfd.nid SET nbp.field_bio_pic_alt = CONCAT(nfd.title, ' portrait') WHERE (nbp.field_bio_pic_alt IS NULL OR nbp.field_bio_pic_alt = '') AND nbp.field_bio_pic_target_id IS NOT NULL"

echo "Fixing biography image revisions..."
$DRUSH sqlq "UPDATE node_revision__field_bio_pic nbp JOIN node_field_data nfd ON nbp.entity_id = nfd.nid SET nbp.field_bio_pic_alt = CONCAT(nfd.title, ' portrait') WHERE (nbp.field_bio_pic_alt IS NULL OR nbp.field_bio_pic_alt = '') AND nbp.field_bio_pic_target_id IS NOT NULL"

echo "Fixing article images..."
$DRUSH sqlq "UPDATE node__field_article_image nai JOIN node_field_data nfd ON nai.entity_id = nfd.nid SET nai.field_article_image_alt = nfd.title WHERE (nai.field_article_image_alt IS NULL OR nai.field_article_image_alt = '') AND nai.field_article_image_target_id IS NOT NULL"

echo "Fixing article image revisions..."
$DRUSH sqlq "UPDATE node_revision__field_article_image nai JOIN node_field_data nfd ON nai.entity_id = nfd.nid SET nai.field_article_image_alt = nfd.title WHERE (nai.field_article_image_alt IS NULL OR nai.field_article_image_alt = '') AND nai.field_article_image_target_id IS NOT NULL"

echo "Fixing general images..."
$DRUSH sqlq "UPDATE node__field_image ni JOIN node_field_data nfd ON ni.entity_id = nfd.nid SET ni.field_image_alt = nfd.title WHERE (ni.field_image_alt IS NULL OR ni.field_image_alt = '') AND ni.field_image_target_id IS NOT NULL"

echo "Fixing general image revisions..."
$DRUSH sqlq "UPDATE node_revision__field_image ni JOIN node_field_data nfd ON ni.entity_id = nfd.nid SET ni.field_image_alt = nfd.title WHERE (ni.field_image_alt IS NULL OR ni.field_image_alt = '') AND ni.field_image_target_id IS NOT NULL"

echo ""
echo "Verifying fixes..."
BIO_REMAINING=$($DRUSH sqlq "SELECT COUNT(*) FROM node__field_bio_pic WHERE field_bio_pic_alt IS NULL OR field_bio_pic_alt = ''")
ARTICLE_REMAINING=$($DRUSH sqlq "SELECT COUNT(*) FROM node__field_article_image WHERE field_article_image_alt IS NULL OR field_article_image_alt = ''")
IMAGE_REMAINING=$($DRUSH sqlq "SELECT COUNT(*) FROM node__field_image WHERE field_image_alt IS NULL OR field_image_alt = ''")

echo "Remaining issues:"
echo "  - Biography images: $BIO_REMAINING"
echo "  - Article images: $ARTICLE_REMAINING"
echo "  - General images: $IMAGE_REMAINING"
echo ""

echo "Clearing cache..."
$DRUSH cr

echo ""
echo "=========================================="
echo "Fix Complete!"
echo "=========================================="
echo ""
echo "Fixed $TOTAL images with NULL alt text"
echo ""
echo "Benefits:"
echo "  ✓ Search API errors resolved"
echo "  ✓ Improved accessibility"
echo "  ✓ Better SEO"
echo ""
