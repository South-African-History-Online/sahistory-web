# Deploy: Full View Mode Configuration Fix

## Problem Solved
This fixes metadata and images not displaying on production product pages.

**Root Cause**: Missing `core.entity_view_display.commerce_product.publication.full.yml` configuration file on production. Without this, Drupal doesn't populate the `content` array in the Twig template, so all fields appear empty even though the data exists.

**Branch**: `bugfix/shop-product-images`
**Commit**: `12d8092a`

---

## Production Deployment Steps

### 1. Pull Latest Changes

```bash
cd /path/to/shop/webroot
git pull origin bugfix/shop-product-images
```

### 2. Import Configuration

```bash
drush --uri=https://shop.sahistory.org.za config:import -y
```

This will import the new `core.entity_view_display.commerce_product.publication.full.yml` file which configures:
- All 15 publication fields
- Display formatters (image style, entity reference labels, etc.)
- Field weights and ordering
- Which fields to show vs hide

### 3. Clear All Caches

```bash
# Clear Drupal cache
drush --uri=https://shop.sahistory.org.za cr

# Clear compiled Twig templates
rm -rf sites/shop.sahistory.org.za/files/php/twig/*
```

### 4. Verify Fix

Test a product that previously had no metadata/images:

```bash
# Test product rendering via Drush
drush --uri=https://shop.sahistory.org.za ev "
  \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load(2);
  \$view_builder = \Drupal::entityTypeManager()->getViewBuilder('commerce_product');
  \$build = \$view_builder->view(\$product, 'full');

  echo 'Fields in content array:' . PHP_EOL;
  foreach (\$build as \$key => \$value) {
    if (strpos(\$key, 'field_') === 0 || \$key === 'body') {
      if (is_array(\$value) && isset(\$value['#field_name'])) {
        \$empty = empty(\$value['#items']) || \$value['#items']->isEmpty();
        echo sprintf('%-30s %s', \$key . ':', \$empty ? 'EMPTY' : 'HAS_DATA') . PHP_EOL;
      }
    }
  }
"
```

**Expected Output**: All fields should show "HAS_DATA" instead of not appearing at all.

### 5. Test in Browser

Visit any product page, e.g.:
- https://shop.sahistory.org.za/product/2
- https://shop.sahistory.org.za/product/5

**You should now see**:
- Product images in the gallery
- Author name
- Publisher
- Publication date
- ISBN
- Page count
- Format
- Language
- Categories
- Full description

---

## What This Configuration Does

The `full.yml` view mode configuration tells Drupal:

### Fields to Display in Content Array:
- `field_images` - Image gallery with sc600x600 style, lazy loading
- `field_subtitle` - Secondary title
- `body` - Full description
- `field_author` - Author name (string)
- `field_editor` - Editor name (string)
- `field_publisher` - Publisher entity reference (as link)
- `field_publication_date` - Date formatted as "Month Year"
- `field_year` - Publication year
- `field_isbn` - ISBN number
- `field_pages` - Page count
- `field_format` - Format (Paperback, Hardcover, etc.)
- `field_language` - Language
- `field_categories` - Category taxonomy terms (as links)
- `variations` - Add to cart form

### Fields Explicitly Hidden:
- `created` - Creation date
- `field_featured` - Featured flag
- `stores` - Store assignment
- `title` - Handled separately in template
- `uid` - Author user

---

## Why This Was Failing

### Before Fix:
```yaml
# Configuration file didn't exist
# Drupal used fallback "default" view mode
# No fields configured for "full" mode
# Template received empty content array
```

### After Fix:
```yaml
# Configuration exists
# All 15 fields properly configured
# Template receives populated content array
# Fields render with correct formatters
```

---

## Troubleshooting

### If metadata still doesn't appear:

**Check configuration was imported:**
```bash
drush --uri=https://shop.sahistory.org.za config:get core.entity_view_display.commerce_product.publication.full
```

Should show the full YAML structure with all fields configured.

**Check which view mode is being used:**
```bash
drush --uri=https://shop.sahistory.org.za ev "
  \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load(2);
  \$view_builder = \Drupal::entityTypeManager()->getViewBuilder('commerce_product');
  \$build = \$view_builder->view(\$product, 'full');
  echo 'View mode: ' . (\$build['#view_mode'] ?? 'not set') . PHP_EOL;
"
```

Should output: `View mode: full`

**Force clear everything:**
```bash
rm -rf sites/shop.sahistory.org.za/files/php/twig/*
drush --uri=https://shop.sahistory.org.za sqlq "TRUNCATE cache_render;"
drush --uri=https://shop.sahistory.org.za sqlq "TRUNCATE cache_dynamic_page_cache;"
drush --uri=https://shop.sahistory.org.za cr
```

---

## Success Criteria

✅ Product images display in gallery
✅ Author, editor, publisher appear
✅ Publication date, year, ISBN visible
✅ Page count, format, language shown
✅ Categories display as links
✅ Full description renders
✅ Add to cart form works
✅ No 500 errors on any product

---

## Next Steps After Deployment

Once verified working on production:
1. Merge `bugfix/shop-product-images` to `main`
2. Update `SHOP-SETUP-GUIDE.md` with this lesson learned
3. Delete temporary diagnostic scripts and docs

---

## Related Files

- **Configuration**: `config/shop/core.entity_view_display.commerce_product.publication.full.yml`
- **Template**: `webroot/themes/custom/saho_shop/templates/commerce/commerce-product--full.html.twig`
- **Diagnostic Script**: `scripts/debug-product-display.sh`
