# CRITICAL: Shop Twig Template Fix

**Status:** 🚨 URGENT - Fixes 500 errors on all product pages
**Branch:** `shop-twig-error-fix`
**Error:** `Object of type Drupal\commerce_product\Entity\Product cannot be printed`

---

## 🐛 The Problem

### What's Happening
- **All product pages return 500 errors** on production
- Error occurs in `commerce-product--full.html.twig` line 78
- Twig cannot print the entire `Product` entity object

### Root Cause
The template was using `product.field_name.value` which sometimes returns:
- ✅ String value (for simple fields) - works fine
- ❌ Entity object (for references) - causes fatal error
- ❌ Field object - causes fatal error

**Example of problematic code:**
```twig
{{ product.field_publisher }}  {# ❌ Might return entity object #}
{{ product.field_author.value }} {# ❌ Sometimes not a simple value #}
```

---

## ✅ The Solution

### Changed Approach
Use Drupal's **`content` array** instead of `product` entity:

```twig
{# BEFORE (Broken) #}
{{ product.field_author.value }}
{{ product.field_publisher }}

{# AFTER (Fixed) #}
{{ content.field_author }}
{{ content.field_publisher }}
```

### Why This Works
- `content` = pre-rendered field output from Drupal's render pipeline
- Already formatted, safe to print
- Handles all field types correctly (text, references, dates, etc.)
- **This is the Drupal-standard approach** used in core templates

---

## 🚀 Production Deployment

### Quick Deploy (3 commands)

```bash
# 1. Pull the fix
git fetch
git checkout shop-twig-error-fix
git pull origin shop-twig-error-fix

# 2. Force clear Twig cache
rm -rf sites/shop.sahistory.org.za/files/php/twig/*

# 3. Clear Drupal cache
drush --uri=https://shop.sahistory.org.za cr
```

**Test immediately:**
```bash
curl -I https://shop.sahistory.org.za/product/kora
# Should return: HTTP/1.1 200 OK (not 500!)
```

---

## 🛠️ Automated Fix Script

For thorough cache clearing:

```bash
bash scripts/force-clear-twig-cache.sh
```

This script will:
1. ✅ Backup existing Twig cache
2. ✅ Delete all compiled templates
3. ✅ Disable Twig cache temporarily
4. ✅ Clear all Drupal caches
5. ✅ Test a product page
6. ✅ Re-enable Twig cache
7. ✅ Provide diagnostic output

---

## 📋 What Changed

### Modified File
`webroot/themes/custom/saho_shop/templates/commerce/commerce-product--full.html.twig`

### Field Changes (product → content)

All these fields now use `content.*` instead of `product.*`:

| Field | Before | After |
|-------|--------|-------|
| Author | `product.field_author.value` | `content.field_author` |
| Editor | `product.field_editor.value` | `content.field_editor` |
| Publisher | `product.field_publisher` | `content.field_publisher` |
| Publication Date | `product.field_publication_date` | `content.field_publication_date` |
| Year | `product.field_year.value` | `content.field_year` |
| ISBN | `product.field_isbn.value` | `content.field_isbn` |
| Pages | `product.field_pages.value` | `content.field_pages` |
| Format | `product.field_format` | `content.field_format` |
| Language | `product.field_language` | `content.field_language` |
| Categories | `product.field_categories` | `content.field_categories` |
| Body | `product.body` | `content.body` |

### What Stayed the Same

These still use `product.*` (safe, don't return entities):
- `product.title.value` - Simple text
- `product.bundle` - Machine name string
- `product.field_images` - Media field (renders correctly)
- `product.variations` - Special Commerce render array

---

## 🔍 Why Production Failed But Local Worked

### Possible Reasons

1. **Twig Cache Not Cleared**
   - Production was using OLD compiled template
   - File: `sites/shop.*/files/php/twig/6914e13d5834d_commerce-product--full.ht_*/mpMJo1sqQ...php`
   - Even after git pull, old cache persisted

2. **Different Data**
   - Some fields might have entity references on production
   - Local dev might have simpler data that didn't trigger the bug

3. **Cache Layers**
   - Opcache, page cache, render cache all storing old output
   - Required aggressive cache clearing

---

## ⚠️ Critical: Must Clear Twig Cache!

### Why Normal Cache Clear Isn't Enough

```bash
drush cr  # ❌ Clears Drupal cache, but NOT compiled Twig files
```

Compiled Twig templates are PHP files stored in:
```
sites/shop.sahistory.org.za/files/php/twig/
```

These must be **physically deleted** for new templates to take effect!

### The Right Way

```bash
# Method 1: Manual (fastest)
rm -rf sites/shop.sahistory.org.za/files/php/twig/*
drush --uri=https://shop.sahistory.org.za cr

# Method 2: Automated script (thorough)
bash scripts/force-clear-twig-cache.sh
```

---

## ✅ Success Verification

### 1. Check Product Page Loads

```bash
curl -I https://shop.sahistory.org.za/product/kora
```

**Expected:** `HTTP/1.1 200 OK`
**Before Fix:** `HTTP/1.1 500 Internal Server Error`

### 2. Check Drupal Logs (Should be clean)

```bash
drush --uri=https://shop.sahistory.org.za watchdog:show --severity=Error --count=5
```

**Expected:** No recent "cannot be printed" errors

### 3. Browse Products

Visit these URLs - all should work:
- https://shop.sahistory.org.za/product/kora
- https://shop.sahistory.org.za/product/africa-todays-world
- https://shop.sahistory.org.za/product/my-life
- https://shop.sahistory.org.za/product/african-national-congress-and-regeneration-political-power

**Expected:**
- ✅ Page loads successfully
- ✅ Product title displays
- ✅ Product metadata shows (author, publisher, etc.)
- ✅ Images display (if available)
- ✅ No errors in browser console

---

## 🎯 Post-Deployment Steps

### 1. Test All Product Pages

```bash
# Get list of all products
drush --uri=https://shop.sahistory.org.za sqlq "SELECT product_id, title FROM commerce_product_field_data WHERE type='publication';"

# Test each product URL
# https://shop.sahistory.org.za/product/{product-id}
```

### 2. Verify Images Display

- Check product grid: https://shop.sahistory.org.za/products
- Verify thumbnails show
- Click products to see full images

### 3. Re-enable Production Caching

```bash
# Ensure Twig cache is ON for production
drush --uri=https://shop.sahistory.org.za config:get system.performance twig.config

# Should show:
#   cache: true
#   debug: false
#   auto_reload: false
```

---

## 📊 Comparison: Before vs After

### Before Fix (Broken)

```twig
{# ❌ Trying to print entity object #}
{% if product.field_publisher|render %}
  <span class="value">{{ product.field_publisher }}</span>
{% endif %}
```

**Result:** 💥 Fatal error - "Object cannot be printed"

### After Fix (Working)

```twig
{# ✅ Printing pre-rendered content #}
{% if content.field_publisher|render %}
  <span class="value">{{ content.field_publisher }}</span>
{% endif %}
```

**Result:** ✅ Publisher name displays correctly

---

## 🔄 Merge to Main

After testing on production:

```bash
# If fix works, merge to main
git checkout main
git merge shop-twig-error-fix
git push origin main

# Delete fix branch (optional)
git branch -d shop-twig-error-fix
git push origin --delete shop-twig-error-fix
```

---

## 📞 Troubleshooting

### Still Getting 500 Error After Deploy?

**1. Verify code is actually updated:**
```bash
grep "content.field_author" webroot/themes/custom/saho_shop/templates/commerce/commerce-product--full.html.twig
# Should find the line (if not, code wasn't pulled)
```

**2. Force delete Twig cache again:**
```bash
rm -rf sites/shop.sahistory.org.za/files/php/twig/*
drush --uri=https://shop.sahistory.org.za cr
```

**3. Check file permissions:**
```bash
ls -la sites/shop.sahistory.org.za/files/php/
# Directory should be writable by www-data
```

**4. Disable Twig cache temporarily to debug:**
```bash
drush --uri=https://shop.sahistory.org.za config:set system.performance twig.config cache false
drush --uri=https://shop.sahistory.org.za config:set system.performance twig.config debug true
drush --uri=https://shop.sahistory.org.za cr
```

**5. Check for other template files:**
```bash
find webroot/themes/custom/saho_shop/templates -name "*commerce-product*"
# Make sure no other templates have the same bug
```

---

## 📝 Lessons Learned

### ✅ Best Practices

1. **Always use `content.*` in entity templates**
   - Not `entity.field_name`
   - This is how Drupal core does it

2. **Test with real data that has entity references**
   - Simple text fields might work with `entity.field_name.value`
   - But entity references will break

3. **Clear Twig cache after template changes**
   - `drush cr` is not enough!
   - Must delete compiled PHP files

4. **Keep templates defensive**
   - Use `|render` to check if field has content
   - Always wrap in `{% if %}`

---

## 🎉 Expected Outcome

After applying this fix:

- ✅ All product pages load successfully (200 OK)
- ✅ No more "cannot be printed" errors
- ✅ Product metadata displays correctly
- ✅ Shop is functional and ready for customers

---

**Branch:** `shop-twig-error-fix`
**Commit:** `2c3c42d8`
**Files Changed:** 2 (template + cache clear script)
**Lines Changed:** +168 -22
