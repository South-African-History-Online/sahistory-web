# Quick Production Fix for Image Alt Text Errors

## The Problem
Search API throws errors: "Call to a member function getCacheTags() on null" when images have NULL alt text.

## The Solution
Run these commands on production via SSH:

### Option 1: One-Command Fix (Fastest)
```bash
cd /path/to/drupal/root && \
vendor/bin/drush sqlq "UPDATE node__field_bio_pic nbp JOIN node_field_data nfd ON nbp.entity_id = nfd.nid SET nbp.field_bio_pic_alt = CONCAT(nfd.title, ' portrait') WHERE (nbp.field_bio_pic_alt IS NULL OR nbp.field_bio_pic_alt = '') AND nbp.field_bio_pic_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_bio_pic nbp JOIN node_field_data nfd ON nbp.entity_id = nfd.nid SET nbp.field_bio_pic_alt = CONCAT(nfd.title, ' portrait') WHERE (nbp.field_bio_pic_alt IS NULL OR nbp.field_bio_pic_alt = '') AND nbp.field_bio_pic_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_article_image nai JOIN node_field_data nfd ON nai.entity_id = nfd.nid SET nai.field_article_image_alt = nfd.title WHERE (nai.field_article_image_alt IS NULL OR nai.field_article_image_alt = '') AND nai.field_article_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_article_image nai JOIN node_field_data nfd ON nai.entity_id = nfd.nid SET nai.field_article_image_alt = nfd.title WHERE (nai.field_article_image_alt IS NULL OR nai.field_article_image_alt = '') AND nai.field_article_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node__field_image ni JOIN node_field_data nfd ON ni.entity_id = nfd.nid SET ni.field_image_alt = nfd.title WHERE (ni.field_image_alt IS NULL OR ni.field_image_alt = '') AND ni.field_image_target_id IS NOT NULL" && \
vendor/bin/drush sqlq "UPDATE node_revision__field_image ni JOIN node_field_data nfd ON ni.entity_id = nfd.nid SET ni.field_image_alt = nfd.title WHERE (ni.field_image_alt IS NULL OR ni.field_image_alt = '') AND ni.field_image_target_id IS NOT NULL" && \
vendor/bin/drush cr && \
echo "✓ Fix complete! All NULL image alt text has been updated."
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

# 8. Clear cache
vendor/bin/drush cr

# 9. Verify fix
vendor/bin/drush sqlq "SELECT COUNT(*) FROM node__field_bio_pic WHERE field_bio_pic_alt IS NULL OR field_bio_pic_alt = ''"
```

## What This Does

- **Biography images**: Adds alt text as "{Person Name} portrait" (e.g., "Nelson Mandela portrait")
- **Article images**: Adds alt text as the article title
- **General images**: Adds alt text as the content title
- Updates both current data AND revision history

## Expected Results

- **~22,000 images** will get proper alt text
- **Search API errors** will stop
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
