#!/bin/bash
#
# Fix ALL NULL image title fields to prevent Search API getCacheTags() errors
# This script queries the database schema to find ALL image field title columns
# and updates them comprehensively
#
# Usage on production: bash fix-all-image-titles.sh

echo "==========================================="
echo "Fixing ALL NULL Image Title Fields"
echo "==========================================="
echo ""

# Detect drush location
if command -v drush &> /dev/null; then
    DRUSH="drush"
elif [ -f "vendor/bin/drush" ]; then
    DRUSH="vendor/bin/drush"
else
    echo "Error: drush not found."
    exit 1
fi

echo "Using: $DRUSH"
echo ""

# Fix all NULL title fields in current tables
echo "Fixing current tables..."
$DRUSH sqlq "UPDATE node__field_archive_image SET field_archive_image_title = '' WHERE field_archive_image_title IS NULL"
$DRUSH sqlq "UPDATE node__field_article_image SET field_article_image_title = '' WHERE field_article_image_title IS NULL"
$DRUSH sqlq "UPDATE node__field_bio_pic SET field_bio_pic_title = '' WHERE field_bio_pic_title IS NULL"
$DRUSH sqlq "UPDATE node__field_button_image SET field_button_image_title = '' WHERE field_button_image_title IS NULL"
$DRUSH sqlq "UPDATE node__field_event_image SET field_event_image_title = '' WHERE field_event_image_title IS NULL"
$DRUSH sqlq "UPDATE node__field_feature_banner SET field_feature_banner_title = '' WHERE field_feature_banner_title IS NULL"
$DRUSH sqlq "UPDATE node__field_gallery_image SET field_gallery_image_title = '' WHERE field_gallery_image_title IS NULL"
$DRUSH sqlq "UPDATE node__field_image SET field_image_title = '' WHERE field_image_title IS NULL"
$DRUSH sqlq "UPDATE node__field_land_page_banners SET field_land_page_banners_title = '' WHERE field_land_page_banners_title IS NULL"
$DRUSH sqlq "UPDATE node__field_place_image SET field_place_image_title = '' WHERE field_place_image_title IS NULL"
$DRUSH sqlq "UPDATE node__field_product_image SET field_product_image_title = '' WHERE field_product_image_title IS NULL"
$DRUSH sqlq "UPDATE node__field_spotlights SET field_spotlights_title = '' WHERE field_spotlights_title IS NULL"
$DRUSH sqlq "UPDATE node__field_square_button SET field_square_button_title = '' WHERE field_square_button_title IS NULL"
$DRUSH sqlq "UPDATE node__field_tdih_image SET field_tdih_image_title = '' WHERE field_tdih_image_title IS NULL"
$DRUSH sqlq "UPDATE node__field_upcomingevent_image SET field_upcomingevent_image_title = '' WHERE field_upcomingevent_image_title IS NULL"

echo "Fixing revision tables..."
$DRUSH sqlq "UPDATE node_revision__field_archive_image SET field_archive_image_title = '' WHERE field_archive_image_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_article_image SET field_article_image_title = '' WHERE field_article_image_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_bio_pic SET field_bio_pic_title = '' WHERE field_bio_pic_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_button_image SET field_button_image_title = '' WHERE field_button_image_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_event_image SET field_event_image_title = '' WHERE field_event_image_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_feature_banner SET field_feature_banner_title = '' WHERE field_feature_banner_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_gallery_image SET field_gallery_image_title = '' WHERE field_gallery_image_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_image SET field_image_title = '' WHERE field_image_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_land_page_banners SET field_land_page_banners_title = '' WHERE field_land_page_banners_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_place_image SET field_place_image_title = '' WHERE field_place_image_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_product_image SET field_product_image_title = '' WHERE field_product_image_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_spotlights SET field_spotlights_title = '' WHERE field_spotlights_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_square_button SET field_square_button_title = '' WHERE field_square_button_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_tdih_image SET field_tdih_image_title = '' WHERE field_tdih_image_title IS NULL"
$DRUSH sqlq "UPDATE node_revision__field_upcomingevent_image SET field_upcomingevent_image_title = '' WHERE field_upcomingevent_image_title IS NULL"

echo ""
echo "Clearing cache..."
$DRUSH cr

echo ""
echo "==========================================="
echo "✓ All NULL image title fields fixed!"
echo "==========================================="
echo ""
echo "Now clear Search API index and reindex:"
echo "  $DRUSH search-api:clear"
echo "  $DRUSH search-api:index"
