#!/bin/bash
# Production fix for Search API getCacheTags() errors
# Upload this file to production and run it

cd /home/sahistrg878/public_html/sahistory.org.za || exit 1

echo "Fixing NULL image title fields..."

vendor/bin/drush sqlq "UPDATE node__field_archive_image SET field_archive_image_title = '' WHERE field_archive_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_article_image SET field_article_image_title = '' WHERE field_article_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_bio_pic SET field_bio_pic_title = '' WHERE field_bio_pic_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_button_image SET field_button_image_title = '' WHERE field_button_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_event_image SET field_event_image_title = '' WHERE field_event_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_feature_banner SET field_feature_banner_title = '' WHERE field_feature_banner_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_gallery_image SET field_gallery_image_title = '' WHERE field_gallery_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_image SET field_image_title = '' WHERE field_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_land_page_banners SET field_land_page_banners_title = '' WHERE field_land_page_banners_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_place_image SET field_place_image_title = '' WHERE field_place_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_product_image SET field_product_image_title = '' WHERE field_product_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_spotlights SET field_spotlights_title = '' WHERE field_spotlights_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_square_button SET field_square_button_title = '' WHERE field_square_button_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_tdih_image SET field_tdih_image_title = '' WHERE field_tdih_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node__field_upcomingevent_image SET field_upcomingevent_image_title = '' WHERE field_upcomingevent_image_title IS NULL"

vendor/bin/drush sqlq "UPDATE node_revision__field_archive_image SET field_archive_image_title = '' WHERE field_archive_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_article_image SET field_article_image_title = '' WHERE field_article_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_bio_pic SET field_bio_pic_title = '' WHERE field_bio_pic_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_button_image SET field_button_image_title = '' WHERE field_button_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_event_image SET field_event_image_title = '' WHERE field_event_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_feature_banner SET field_feature_banner_title = '' WHERE field_feature_banner_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_gallery_image SET field_gallery_image_title = '' WHERE field_gallery_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_image SET field_image_title = '' WHERE field_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_land_page_banners SET field_land_page_banners_title = '' WHERE field_land_page_banners_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_place_image SET field_place_image_title = '' WHERE field_place_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_product_image SET field_product_image_title = '' WHERE field_product_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_spotlights SET field_spotlights_title = '' WHERE field_spotlights_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_square_button SET field_square_button_title = '' WHERE field_square_button_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_tdih_image SET field_tdih_image_title = '' WHERE field_tdih_image_title IS NULL"
vendor/bin/drush sqlq "UPDATE node_revision__field_upcomingevent_image SET field_upcomingevent_image_title = '' WHERE field_upcomingevent_image_title IS NULL"

echo "Clearing cache..."
vendor/bin/drush cr

echo "Clearing Search API index..."
vendor/bin/drush search-api:clear

echo ""
echo "✓ Complete! Now run: vendor/bin/drush search-api:index"
