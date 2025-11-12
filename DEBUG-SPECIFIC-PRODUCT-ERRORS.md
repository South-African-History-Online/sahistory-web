# Debug: Specific Product 500 Errors

**Problem:** Some products work, others still return 500 errors
**CI Status:** ✅ Passing
**Template:** Fixed and deployed

---

## 🔍 Diagnostic Questions

To diagnose why only certain products fail, we need to identify the pattern:

### 1. Which Products Are Failing?

Run this on production to get a list of all products:

```bash
drush --uri=https://shop.sahistory.org.za sqlq "
SELECT product_id, title, created, changed
FROM commerce_product_field_data
WHERE type = 'publication'
ORDER BY product_id;
"
```

Then test each one:
```bash
# Test each product URL
curl -I https://shop.sahistory.org.za/product/1
curl -I https://shop.sahistory.org.za/product/2
# etc...
```

**Document which ones fail (return 500) vs work (return 200)**

---

## 🎯 Likely Causes

### Cause 1: Multiple Twig Templates

There might be multiple product templates, and only one was fixed.

**Check for other templates:**
```bash
find webroot/themes -name "*commerce-product*.twig" 2>/dev/null
```

**Expected templates:**
- `commerce-product--full.html.twig` ✅ (we fixed this)
- `commerce-product--teaser.html.twig` (might exist)
- `commerce-product--publication.html.twig` (product type specific)
- `commerce-product--publication--full.html.twig` (most specific)

**If more specific templates exist**, they override the generic one!

**Fix:** Apply the same fix to ALL product templates.

---

### Cause 2: Field Data Differences

Products failing might have:
- Entity references that render as objects (publisher, categories)
- Missing or malformed field data
- Different field configurations

**Check what's different about failing products:**
```bash
# Get field data for a WORKING product (e.g., product 5)
drush --uri=https://shop.sahistory.org.za ev "
  \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load(5);
  var_dump(\$product->field_publisher->entity);
"

# Get field data for a FAILING product (e.g., product 27)
drush --uri=https://shop.sahistory.org.za ev "
  \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load(27);
  var_dump(\$product->field_publisher->entity);
"
```

**Look for differences in:**
- Which fields have data
- Entity references vs. text values
- NULL vs. empty vs. populated

---

### Cause 3: View Mode Differences

Products might be using different view modes:
- `full` (we fixed this)
- `default`
- `teaser`
- `search_result`

**Check which view mode is failing:**
```bash
# Check product display configuration
drush --uri=https://shop.sahistory.org.za config:get core.entity_view_display.commerce_product.publication.full
drush --uri=https://shop.sahistory.org.za config:get core.entity_view_display.commerce_product.publication.default
```

**If products use `default` view mode**, there might be a separate template!

---

### Cause 4: Twig Cache Per Node

Twig compiles separate cache files for different scenarios.

**Force regenerate ALL Twig cache:**
```bash
# Delete ALL Twig cache
rm -rf sites/shop.sahistory.org.za/files/php/twig/*

# Truncate cache tables
drush --uri=https://shop.sahistory.org.za sqlq "TRUNCATE cache_render;"
drush --uri=https://shop.sahistory.org.za sqlq "TRUNCATE cache_dynamic_page_cache;"

# Disable cache temporarily
drush --uri=https://shop.sahistory.org.za config:set system.performance twig.config cache false
drush --uri=https://shop.sahistory.org.za cr

# Test failing products
curl -I https://shop.sahistory.org.za/product/FAILING_ID

# Re-enable cache
drush --uri=https://shop.sahistory.org.za config:set system.performance twig.config cache true
drush --uri=https://shop.sahistory.org.za cr
```

---

## 🛠️ Debug Commands

### Get Exact Error for Failing Product

```bash
# Replace 27 with the failing product ID
drush --uri=https://shop.sahistory.org.za ev "
  try {
    \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load(27);
    \$view_builder = \Drupal::entityTypeManager()->getViewBuilder('commerce_product');
    \$build = \$view_builder->view(\$product, 'full');
    \$renderer = \Drupal::service('renderer');
    \$html = \$renderer->renderRoot(\$build);
    echo 'Success!';
  } catch (\Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . PHP_EOL;
    echo 'Line: ' . \$e->getLine() . PHP_EOL;
    echo 'File: ' . \$e->getFile() . PHP_EOL;
  }
"
```

This will show the **exact error** and which template/field is causing it.

---

### Check Which Template Is Being Used

```bash
# Enable Twig debugging
drush --uri=https://shop.sahistory.org.za config:set system.performance twig.config debug true

# Visit failing product page
# View page source - look for comments like:
# <!-- FILE NAME SUGGESTIONS: commerce-product--publication--full.html.twig -->
```

This tells you **which template file** Drupal is actually using!

---

### Compare Working vs Failing Products

```bash
# Get all field values for a working product
drush --uri=https://shop.sahistory.org.za ev "
  \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load(5);
  foreach (\$product->getFields() as \$field_name => \$field) {
    echo \$field_name . ': ' . (\$field->isEmpty() ? 'EMPTY' : 'HAS_DATA') . PHP_EOL;
  }
"

# Get all field values for a failing product
drush --uri=https://shop.sahistory.org.za ev "
  \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load(27);
  foreach (\$product->getFields() as \$field_name => \$field) {
    echo \$field_name . ': ' . (\$field->isEmpty() ? 'EMPTY' : 'HAS_DATA') . PHP_EOL;
  }
"
```

**Look for fields that exist on failing products but not working ones.**

---

## 💡 Most Likely Scenario

**Theory:** There's a more specific template that we didn't fix.

Drupal template precedence:
1. `commerce-product--publication--27--full.html.twig` (most specific - unlikely)
2. `commerce-product--publication--full.html.twig` (we might not have this!)
3. `commerce-product--full.html.twig` (we fixed this)
4. `commerce-product.html.twig` (fallback)

**If `commerce-product--publication--full.html.twig` exists**, it overrides our fix!

**Check:**
```bash
ls -la webroot/themes/custom/saho_shop/templates/commerce/ | grep publication
```

**If it exists, apply the same fix to it!**

---

## 🚀 Emergency Fix Script

If you need to quickly fix ALL product templates:

```bash
# Find all commerce-product templates
cd webroot/themes/custom/saho_shop/templates/commerce

# Replace product. with content. in ALL templates
for file in commerce-product*.twig; do
  if [ -f "$file" ]; then
    echo "Fixing: $file"
    # Backup
    cp "$file" "$file.backup"
    # Replace (for fields only, not for product.title, etc)
    sed -i 's/product\.field_/content.field_/g' "$file"
    sed -i 's/product\.body/content.body/g' "$file"
  fi
done

# Clear cache
rm -rf ../../../sites/shop.sahistory.org.za/files/php/twig/*
drush --uri=https://shop.sahistory.org.za cr
```

---

## 📞 Next Steps

1. **Identify which products fail** (run curl tests)
2. **Get exact error message** (run drush ev debug command)
3. **Check for more specific templates** (ls command)
4. **Report back with findings**

Then we can create a targeted fix!
