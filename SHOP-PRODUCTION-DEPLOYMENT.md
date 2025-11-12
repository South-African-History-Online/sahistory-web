# Shop Production Deployment Guide

**Last Updated:** November 12, 2025
**Branch:** main (shop-phase-2 merged)
**Status:** Ready for production deployment

---

## 🐛 Issues Fixed

### 1. Twig Template Error (500 Error)
**Error:** `Object of type Drupal\commerce_product\Entity\Product cannot be printed`

**Cause:** Template referenced fields that don't exist on publication products:
- `field_sku` (SKU is on variation, not product)
- `field_stock_status`
- `field_additional_info`
- `field_related_products`

**Fix:** Updated `commerce-product--full.html.twig` to only use existing fields:
- ✅ body, field_author, field_editor, field_subtitle
- ✅ field_publisher, field_publication_date, field_year
- ✅ field_isbn, field_pages, field_format, field_language
- ✅ field_categories, field_images, field_featured

### 2. Missing Frontend Assets (Broken Styling)
**Problem:** Production server has no Node.js/npm, can't build assets

**Solution:** Compiled CSS/JS now committed to git
- `webroot/themes/custom/saho_shop/css/commerce.css` (21.5KB)
- `webroot/themes/custom/saho_shop/css/global.css` (7.3KB)
- `webroot/themes/custom/saho_shop/js/commerce.js` (2.4KB)
- `webroot/themes/custom/saho_shop/js/global.js` (2.1KB)

---

## 🚀 Deployment Steps

### Step 1: Pull Latest Code

```bash
# On production server
cd /path/to/drupal
git pull origin main
```

### Step 2: Clear Drupal Caches (Critical!)

```bash
# Clear Twig cache to load new template
drush --uri=https://shop.sahistory.org.za cache:clear

# Or clear all caches
drush --uri=https://shop.sahistory.org.za cr

# Verify Twig cache is cleared
drush --uri=https://shop.sahistory.org.za cache:rebuild
```

### Step 3: Verify Assets Are Present

```bash
# Check compiled CSS exists
ls -lh webroot/themes/custom/saho_shop/css/

# Should show:
# commerce.css (21.5KB)
# global.css (7.3KB)

# Check compiled JS exists
ls -lh webroot/themes/custom/saho_shop/js/

# Should show:
# commerce.js (2.4KB)
# global.js (2.1KB)
```

### Step 4: Test the Shop Site

```bash
# Visit shop front page
curl -I https://shop.sahistory.org.za

# Visit a product page
curl -I https://shop.sahistory.org.za/product/kora

# Should return 200 OK, not 500 error
```

### Step 5: Verify Styling Works

Open in browser:
1. Visit https://shop.sahistory.org.za
2. Check CSS is loaded (inspect with browser DevTools)
3. Check product pages display correctly
4. Verify images and layout are correct

---

## 🔧 Troubleshooting

### "Still getting 500 error on product pages"

**Solution:** Clear Twig cache multiple times

```bash
# Method 1: Drush
drush --uri=https://shop.sahistory.org.za twig:debug off
drush --uri=https://shop.sahistory.org.za cr

# Method 2: Delete compiled Twig files
rm -rf webroot/sites/shop.sahistory.org.za/files/php/twig/*
drush --uri=https://shop.sahistory.org.za cr

# Method 3: Nuclear option
drush --uri=https://shop.sahistory.org.za sql:query "TRUNCATE cache_bootstrap;"
drush --uri=https://shop.sahistory.org.za sql:query "TRUNCATE cache_render;"
drush --uri=https://shop.sahistory.org.za cr
```

### "CSS/JS not loading"

**Check 1: Files exist**
```bash
ls -lh webroot/themes/custom/saho_shop/css/
ls -lh webroot/themes/custom/saho_shop/js/
```

**Check 2: File permissions**
```bash
chmod 644 webroot/themes/custom/saho_shop/css/*
chmod 644 webroot/themes/custom/saho_shop/js/*
```

**Check 3: Libraries are registered**
```bash
# Check theme libraries
drush --uri=https://shop.sahistory.org.za config:get saho_shop.info.yml

# Clear cache
drush --uri=https://shop.sahistory.org.za cr
```

### "Product page still shows error"

**Verify template is updated:**
```bash
# Check template has new code
grep "About this publication" webroot/themes/custom/saho_shop/templates/commerce/commerce-product--full.html.twig

# Should find the line with the new heading
```

**Force template reload:**
```bash
# Delete compiled templates
rm -rf webroot/sites/shop.sahistory.org.za/files/php/twig/*

# Disable Twig cache temporarily
drush --uri=https://shop.sahistory.org.za config:set system.performance twig_cache 0
drush --uri=https://shop.sahistory.org.za cr

# Test, then re-enable
drush --uri=https://shop.sahistory.org.za config:set system.performance twig_cache 1
drush --uri=https://shop.sahistory.org.za cr
```

---

## 📊 Verification Checklist

After deployment, verify:

- [ ] Shop homepage loads: https://shop.sahistory.org.za
- [ ] CSS is loaded and styling looks correct
- [ ] Navigation menu displays properly
- [ ] Product listing page works: https://shop.sahistory.org.za/products
- [ ] Individual product pages load without errors
- [ ] Product images display correctly
- [ ] Product metadata shows (author, publisher, etc.)
- [ ] Add to cart button is visible
- [ ] No 500 errors in browser console
- [ ] No PHP errors in Drupal logs

**Test these specific products:**
- https://shop.sahistory.org.za/product/kora (previously caused 500 error)
- https://shop.sahistory.org.za/product/africa-todays-world
- https://shop.sahistory.org.za/product/my-life

---

## 🔄 Future Updates

### When Making CSS/JS Changes

If you update SCSS or JS source files:

```bash
# On your local development machine
cd webroot/themes/custom/saho_shop
npm run production

# Commit the compiled assets
git add css/ js/ mix-manifest.json
git commit -m "Update shop frontend styles"
git push

# Then on production
git pull
drush --uri=https://shop.sahistory.org.za cr
```

### When Making Template Changes

If you update Twig templates:

```bash
# On your local development machine
git add webroot/themes/custom/saho_shop/templates/
git commit -m "Update shop template"
git push

# Then on production
git pull
rm -rf webroot/sites/shop.sahistory.org.za/files/php/twig/*
drush --uri=https://shop.sahistory.org.za cr
```

---

## 🤖 CI/CD (GitHub Actions)

A GitHub Actions workflow now runs on every push to:

1. **Check Drupal Coding Standards**
   - Runs PHPCS on shop theme
   - Ensures code follows Drupal standards

2. **Lint Frontend Code**
   - Runs Biome on JavaScript files
   - Checks code quality and formatting

3. **Verify Build**
   - Compiles assets in CI
   - Ensures production build works

**View workflow:** `.github/workflows/shop-ci.yml`
**View results:** GitHub Actions tab in repository

---

## 📞 Support

If issues persist after following this guide:

1. **Check Drupal logs:**
   ```bash
   drush --uri=https://shop.sahistory.org.za watchdog:show --count=20
   ```

2. **Check PHP error logs:**
   ```bash
   tail -f /var/log/php-fpm/error.log
   # or
   tail -f /var/log/apache2/error.log
   ```

3. **Enable verbose error reporting temporarily:**
   ```bash
   drush --uri=https://shop.sahistory.org.za config:set system.logging error_level verbose
   # Test, then disable:
   drush --uri=https://shop.sahistory.org.za config:set system.logging error_level hide
   ```

---

## ✅ Success!

If everything works:
- Shop site loads ✅
- Styling looks good ✅
- Product pages work ✅
- No 500 errors ✅

**Shop is ready for customers!** 🎉

---

**Previous Issues Reference:**
- Twig Error: `/home/mno/Downloads/message (1).txt`
- Config Import: `/home/mno/Downloads/message.txt`
- Database Export: `.ddev/backups/shop-phase2-secure-20251112-193613.sql.gz`
