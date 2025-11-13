# Deploy: Product Display Configuration Fix

## Problem Solved
This fixes metadata and images not displaying on production product pages.

**Root Causes** (3 issues):
1. Missing `core.entity_view_mode.commerce_product.full` view mode configuration
2. Missing `core.entity_view_display.commerce_product.publication.full` display configuration
3. Template `commerce-product--publication.html.twig` was using incorrect field access pattern

**Branch**: `bugfix/shop-product-images`
**Commit**: `23f52ecc`

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

## What These Fixes Do

### 1. View Mode Configuration (`core.entity_view_mode.commerce_product.full.yml`)
Defines the "full" view mode for commerce products. Without this, Drupal can't use "full" as a valid display mode.

### 2. Display Configuration (`core.entity_view_display.commerce_product.publication.full.yml`)
The `full.yml` view mode display configuration tells Drupal:

#### Fields to Display in Content Array:
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

#### Fields Explicitly Hidden:
- `created` - Creation date
- `field_featured` - Featured flag
- `stores` - Store assignment
- `title` - Handled separately in template
- `uid` - Author user

### 3. Template Fix (`commerce-product--publication.html.twig`)
The product-specific template needed two fixes:

**Before (Broken)**:
```twig
{{ product.field_images }}
{{ product.field_author }}
{{ product.field_publisher }}
```
This tried to print entity objects directly, which causes errors or shows nothing.

**After (Fixed)**:
```twig
{% if product.field_images|render %}
  {{ product.field_images }}
{% endif %}
{% if product.field_author|render %}
  <div class="meta-item"><strong>Author:</strong> {{ product.field_author }}</div>
{% endif %}
```

The fix:
- Added conditional checks using `|render` filter to test if field has content
- Wrapped fields in proper HTML markup with labels
- Prevents rendering empty fields

**Why This Template Takes Priority**:
Drupal template precedence means `commerce-product--publication.html.twig` (product type specific) overrides `commerce-product--full.html.twig` (view mode specific), so both needed to be fixed.

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
