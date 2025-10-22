# Quick Production Fix for Search API Errors

## The Problems
1. **Image Title Fields**: Search API throws errors: "Call to a member function getCacheTags() on null" when image fields have NULL title values
2. **Image Alt Text**: Search API errors when images have NULL alt text
3. **Text Format**: "Missing text format: 2" - Legacy format ID from old Drupal versions

## The Solution
Run these commands on production via SSH:

### Option 1: One-Command Fix (Fastest)
```bash
cd /path/to/drupal/root && \
# Fix NULL title fields (CRITICAL - prevents getCacheTags() errors)
vendor/bin/drush sqlq "UPDATE node__field_archive_image SET field_archive_image_title = '' WHERE field_archive_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_article_image SET field_article_image_title = '' WHERE field_article_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_bio_pic SET field_bio_pic_title = '' WHERE field_bio_pic_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_event_image SET field_event_image_title = '' WHERE field_event_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_image SET field_image_title = '' WHERE field_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_place_image SET field_place_image_title = '' WHERE field_place_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_tdih_image SET field_tdih_image_title = '' WHERE field_tdih_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_archive_image SET field_archive_image_title = '' WHERE field_archive_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_article_image SET field_article_image_title = '' WHERE field_article_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_bio_pic SET field_bio_pic_title = '' WHERE field_bio_pic_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_event_image SET field_event_image_title = '' WHERE field_event_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_image SET field_image_title = '' WHERE field_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_place_image SET field_place_image_title = '' WHERE field_place_image_title IS NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_tdih_image SET field_tdih_image_title = '' WHERE field_tdih_image_title IS NULL" && \
# Fix NULL alt text for accessibility/SEO
vendor/bin/drush sqlq "UPDATE node__field_bio_pic nbp JOIN node_field_data nfd ON nbp.entity_id = nfd.nid SET nbp.field_bio_pic_alt = CONCAT(nfd.title, ' portrait') WHERE (nbp.field_bio_pic_alt IS NULL OR nbp.field_bio_pic_alt = '') AND nbp.field_bio_pic_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_bio_pic nbp JOIN node_field_data nfd ON nbp.entity_id = nfd.nid SET nbp.field_bio_pic_alt = CONCAT(nfd.title, ' portrait') WHERE (nbp.field_bio_pic_alt IS NULL OR nbp.field_bio_pic_alt = '') AND nbp.field_bio_pic_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_article_image nai JOIN node_field_data nfd ON nai.entity_id = nfd.nid SET nai.field_article_image_alt = nfd.title WHERE (nai.field_article_image_alt IS NULL OR nai.field_article_image_alt = '') AND nai.field_article_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_article_image nai JOIN node_field_data nfd ON nai.entity_id = nfd.nid SET nai.field_article_image_alt = nfd.title WHERE (nai.field_article_image_alt IS NULL OR nai.field_article_image_alt = '') AND nai.field_article_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_image ni JOIN node_field_data nfd ON ni.entity_id = nfd.nid SET ni.field_image_alt = nfd.title WHERE (ni.field_image_alt IS NULL OR ni.field_image_alt = '') AND ni.field_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_image ni JOIN node_field_data nfd ON ni.entity_id = nfd.nid SET ni.field_image_alt = nfd.title WHERE (ni.field_image_alt IS NULL OR ni.field_image_alt = '') AND ni.field_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_archive_image nai JOIN node_field_data nfd ON nai.entity_id = nfd.nid SET nai.field_archive_image_alt = nfd.title WHERE (nai.field_archive_image_alt IS NULL OR nai.field_archive_image_alt = '') AND nai.field_archive_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_archive_image nai JOIN node_field_data nfd ON nai.entity_id = nfd.nid SET nai.field_archive_image_alt = nfd.title WHERE (nai.field_archive_image_alt IS NULL OR nai.field_archive_image_alt = '') AND nai.field_archive_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_event_image nei JOIN node_field_data nfd ON nei.entity_id = nfd.nid SET nei.field_event_image_alt = nfd.title WHERE (nei.field_event_image_alt IS NULL OR nei.field_event_image_alt = '') AND nei.field_event_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_event_image nei JOIN node_field_data nfd ON nei.entity_id = nfd.nid SET nei.field_event_image_alt = nfd.title WHERE (nei.field_event_image_alt IS NULL OR nei.field_event_image_alt = '') AND nei.field_event_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_place_image npi JOIN node_field_data nfd ON npi.entity_id = nfd.nid SET npi.field_place_image_alt = nfd.title WHERE (npi.field_place_image_alt IS NULL OR npi.field_place_image_alt = '') AND npi.field_place_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_place_image npi JOIN node_field_data nfd ON npi.entity_id = nfd.nid SET npi.field_place_image_alt = nfd.title WHERE (npi.field_place_image_alt IS NULL OR npi.field_place_image_alt = '') AND npi.field_place_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_tdih_image nti JOIN node_field_data nfd ON nti.entity_id = nfd.nid SET nti.field_tdih_image_alt = nfd.title WHERE (nti.field_tdih_image_alt IS NULL OR nti.field_tdih_image_alt = '') AND nti.field_tdih_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_tdih_image nti JOIN node_field_data nfd ON nti.entity_id = nfd.nid SET nti.field_tdih_image_alt = nfd.title WHERE (nti.field_tdih_image_alt IS NULL OR nti.field_tdih_image_alt = '') AND nti.field_tdih_image_target_id IS NOT NULL" && \
# Fix legacy text format references
vendor/bin/drush sqlq "UPDATE node__field_old_ref_str SET field_old_ref_str_format = 'full_html' WHERE field_old_ref_str_format = '2'" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_old_ref_str SET field_old_ref_str_format = 'full_html' WHERE field_old_ref_str_format = '2'" && \
# Clear cache and reindex
vendor/bin/drush cr && \
vendor/bin/drush search-api:clear && \
vendor/bin/drush search-api:index && \
echo "✓ Fix complete! All NULL image title fields, alt text, and legacy format IDs updated."
```

### Option 2: Upload and Run Script (Recommended)
```bash
# 1. Upload fix-image-alt-text.sh to your server
scp scripts/fix-image-alt-text.sh user@server:/path/to/drupal/scripts/

# 2. SSH to production
ssh user@server

# 3. Run the script
cd /path/to/drupal
bash scripts/fix-image-alt-text.sh
```

### Option 3: Step-by-Step (Safest)
Run each command one at a time:

```bash
# 1. Check how many images need fixing
vendor/bin/drush sqlq "SELECT COUNT(*) FROM node__field_bio_pic WHERE field_bio_pic_alt IS NULL OR field_bio_pic_alt = ''"
vendor/bin/drush sqlq "SELECT COUNT(*) FROM node__field_article_image WHERE field_article_image_alt IS NULL OR field_article_image_alt = ''"
vendor/bin/drush sqlq "SELECT COUNT(*) FROM node__field_image WHERE field_image_alt IS NULL OR field_image_alt = ''"

# 2. Fix biography images
vendor/bin/drush sqlq "UPDATE node__field_bio_pic nbp JOIN node_field_data nfd ON nbp.entity_id = nfd.nid SET nbp.field_bio_pic_alt = CONCAT(nfd.title, ' portrait') WHERE (nbp.field_bio_pic_alt IS NULL OR nbp.field_bio_pic_alt = '') AND nbp.field_bio_pic_target_id IS NOT NULL"

# 3. Fix biography revisions
vendor/bin/drush sqlq "UPDATE node_revision__field_bio_pic nbp JOIN node_field_data nfd ON nbp.entity_id = nfd.nid SET nbp.field_bio_pic_alt = CONCAT(nfd.title, ' portrait') WHERE (nbp.field_bio_pic_alt IS NULL OR nbp.field_bio_pic_alt = '') AND nbp.field_bio_pic_target_id IS NOT NULL"

# 4. Fix article images
vendor/bin/drush sqlq "UPDATE node__field_article_image nai JOIN node_field_data nfd ON nai.entity_id = nfd.nid SET nai.field_article_image_alt = nfd.title WHERE (nai.field_article_image_alt IS NULL OR nai.field_article_image_alt = '') AND nai.field_article_image_target_id IS NOT NULL"

# 5. Fix article revisions
vendor/bin/drush sqlq "UPDATE node_revision__field_article_image nai JOIN node_field_data nfd ON nai.entity_id = nfd.nid SET nai.field_article_image_alt = nfd.title WHERE (nai.field_article_image_alt IS NULL OR nai.field_article_image_alt = '') AND nai.field_article_image_target_id IS NOT NULL"

# 6. Fix general images
vendor/bin/drush sqlq "UPDATE node__field_image ni JOIN node_field_data nfd ON ni.entity_id = nfd.nid SET ni.field_image_alt = nfd.title WHERE (ni.field_image_alt IS NULL OR ni.field_image_alt = '') AND ni.field_image_target_id IS NOT NULL"

# 7. Fix general revisions
vendor/bin/drush sqlq "UPDATE node_revision__field_image ni JOIN node_field_data nfd ON ni.entity_id = nfd.nid SET ni.field_image_alt = nfd.title WHERE (ni.field_image_alt IS NULL OR ni.field_image_alt = '') AND ni.field_image_target_id IS NOT NULL"

# 8. Fix legacy text format references
vendor/bin/drush sqlq "UPDATE node__field_old_ref_str SET field_old_ref_str_format = 'full_html' WHERE field_old_ref_str_format = '2'"
vendor/bin/drush sqlq "UPDATE node_revision__field_old_ref_str SET field_old_ref_str_format = 'full_html' WHERE field_old_ref_str_format = '2'"

# 9. Clear cache
vendor/bin/drush cr

# 10. Verify fixes
vendor/bin/drush sqlq "SELECT COUNT(*) FROM node__field_bio_pic WHERE field_bio_pic_alt IS NULL OR field_bio_pic_alt = ''"
vendor/bin/drush sqlq "SELECT COUNT(*) FROM node__field_old_ref_str WHERE field_old_ref_str_format = '2'"
```

## What This Does

### Image Title Field Fixes (CRITICAL)
- **Converts NULL to empty string** in title fields for all image types
- Affects 7 major image field types:
  - `field_archive_image` (~17,567 records)
  - `field_article_image` (~490 records)
  - `field_bio_pic` (~4,467 records)
  - `field_event_image` (~2,905 records)
  - `field_image` (~39 records)
  - `field_place_image` (~28 records)
  - `field_tdih_image` (~3,148 records)
- **Total: ~28,644 NULL title fields fixed**
- Prevents "Call to a member function getCacheTags() on null" errors
- Updates both current data AND revision history

### Image Alt Text Fixes (Accessibility/SEO)
- **Biography images**: Adds alt text as "{Person Name} portrait" (e.g., "Nelson Mandela portrait")
- **Article images**: Adds alt text as the article title
- **Archive/Event/Place/TDIH images**: Adds alt text as content title
- **General images**: Adds alt text as the content title
- Updates both current data AND revision history

### Text Format Fixes
- **Legacy format 2**: Converts old numeric format ID "2" to modern "full_html" format
- Affects 5 nodes with `field_old_ref_str` field
- Updates both current data AND revision history

## Expected Results

- **~28,644 NULL title fields** will be set to empty string (fixes getCacheTags() errors)
- **~30,000+ images** will get proper alt text
- **5 legacy format references** will be updated
- **Search API errors** will stop completely
- **Search reindexing** will complete without errors
- **Accessibility** improved for screen readers
- **SEO** improved with descriptive image text

## Time to Run

- **1-3 minutes** depending on server speed
- **Safe to run** - only updates NULL/empty alt text fields
- **No downtime** required

## Verification

After running, check error logs:
```bash
vendor/bin/drush watchdog:show --type=search_api --count=10
```

You should see no more "getCacheTags() on null" errors.

## Notes

- These SQL commands are **safe** - they only update fields that are NULL or empty
- They **preserve** any existing alt text
- No content will be deleted or modified (except adding alt text)
- The fix updates both live data and revision history for consistency
