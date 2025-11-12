# Quick Fix: Shop Images Not Showing

**Problem:** Images are on production but not displaying like they do locally

---

## 🔍 Likely Causes

1. **File permissions** - Web server can't read files
2. **Missing image derivatives** - Drupal image styles not generated
3. **Wrong file paths** - Files uploaded to wrong location
4. **Cache issues** - Drupal cached broken image URLs

---

## ⚡ Quick Fix Commands

Run these commands on production server:

### 1. Fix File Permissions (Most Common Issue)

```bash
# Set correct ownership (adjust www-data if using different user)
sudo chown -R www-data:www-data sites/shop.sahistory.org.za/files

# Set correct permissions
sudo find sites/shop.sahistory.org.za/files -type d -exec chmod 775 {} \;
sudo find sites/shop.sahistory.org.za/files -type f -exec chmod 664 {} \;
```

### 2. Verify Images Are Actually There

```bash
# Check images exist
ls -lh sites/shop.sahistory.org.za/files/product-covers/

# Should show about 18-20 .jpg files
# If empty, images weren't uploaded! See "Upload Images" below
```

### 3. Flush Image Styles & Clear Cache

```bash
# Regenerate image style derivatives
drush --uri=https://shop.sahistory.org.za image:flush --all

# Clear all caches
drush --uri=https://shop.sahistory.org.za cr
```

### 4. Test an Image URL

```bash
# Get a sample image from database
drush --uri=https://shop.sahistory.org.za sqlq "SELECT uri FROM file_managed WHERE uri LIKE '%product-covers%' LIMIT 1;"

# Should output something like: public://product-covers/cover_africa_in_todays_world.jpg

# Test if file exists
ls -lh sites/shop.sahistory.org.za/files/product-covers/cover_africa_in_todays_world.jpg

# Test HTTP access
curl -I https://shop.sahistory.org.za/sites/shop.sahistory.org.za/files/product-covers/cover_africa_in_todays_world.jpg

# Should return: HTTP/1.1 200 OK
```

---

## 📤 Upload Images (If Missing)

If `ls sites/shop.sahistory.org.za/files/product-covers/` is empty, upload them:

### From Your Local Machine

```bash
# Option 1: Rsync (fastest, preserves everything)
rsync -avz \
  webroot/sites/shop.sahistory.org.za/files/product-covers/ \
  user@production:/path/to/drupal/sites/shop.sahistory.org.za/files/product-covers/

# Option 2: SCP (simpler)
scp webroot/sites/shop.sahistory.org.za/files/product-covers/*.jpg \
  user@production:/path/to/drupal/sites/shop.sahistory.org.za/files/product-covers/

# Then on production, fix permissions
sudo chown -R www-data:www-data sites/shop.sahistory.org.za/files/product-covers
sudo chmod 664 sites/shop.sahistory.org.za/files/product-covers/*.jpg
```

---

## 🔍 Diagnostic Checklist

Run through this checklist to find the issue:

### ✅ Files Exist?

```bash
ls -lh sites/shop.sahistory.org.za/files/product-covers/
```
**Expected:** 18-20 .jpg files, varying sizes

### ✅ Permissions Correct?

```bash
ls -la sites/shop.sahistory.org.za/files/product-covers/ | head -5
```
**Expected:**
- Owner: `www-data:www-data` (or your web server user)
- Permissions: `-rw-rw-r--` (664) for files

### ✅ Drupal Knows About Images?

```bash
drush --uri=https://shop.sahistory.org.za sqlq "
SELECT COUNT(*) as product_images
FROM commerce_product__field_images;
"
```
**Expected:** 21 (number of product-image relationships)

### ✅ Files Table Has References?

```bash
drush --uri=https://shop.sahistory.org.za sqlq "
SELECT fid, uri, filesize
FROM file_managed
WHERE uri LIKE '%product-covers%'
LIMIT 5;
"
```
**Expected:** Shows 5 images with URIs like `public://product-covers/cover_*.jpg`

### ✅ Image Styles Directory Exists?

```bash
ls -ld sites/shop.sahistory.org.za/files/styles/
```
**Expected:** Directory exists, owned by www-data

If missing:
```bash
mkdir -p sites/shop.sahistory.org.za/files/styles
sudo chown www-data:www-data sites/shop.sahistory.org.za/files/styles
chmod 775 sites/shop.sahistory.org.za/files/styles
```

### ✅ Can Access via HTTP?

```bash
curl -I https://shop.sahistory.org.za/sites/shop.sahistory.org.za/files/product-covers/cover_africa_in_todays_world.jpg
```
**Expected:** `HTTP/1.1 200 OK`

**If 403 Forbidden:** Permission problem
**If 404 Not Found:** File doesn't exist or wrong path

---

## 🎯 Most Common Fixes

### Issue: Broken Image Icon (Alt Text Shows)

**Cause:** File permissions or file doesn't exist

**Fix:**
```bash
# Check if file exists
ls sites/shop.sahistory.org.za/files/product-covers/

# If empty, upload images
# If files exist, fix permissions
sudo chown -R www-data:www-data sites/shop.sahistory.org.za/files
sudo find sites/shop.sahistory.org.za/files -type f -exec chmod 664 {} \;
```

### Issue: Some Images Show, Some Don't

**Cause:** Image style derivatives not generated

**Fix:**
```bash
drush --uri=https://shop.sahistory.org.za image:flush --all
drush --uri=https://shop.sahistory.org.za cr

# Then visit product pages to trigger derivative generation
```

### Issue: Images Very Large / No Styling

**Cause:** Original images being served instead of derivatives

**Fix:**
```bash
# Check if image styles are configured
drush --uri=https://shop.sahistory.org.za config:get image.style.medium

# Flush and regenerate
drush --uri=https://shop.sahistory.org.za image:flush --all
drush --uri=https://shop.sahistory.org.za cr
```

---

## 🔧 Advanced Diagnostic Script

For comprehensive diagnostics, run:

```bash
bash scripts/fix-shop-images-production.sh
```

This script will:
- ✅ Check directory structure
- ✅ Verify file permissions
- ✅ Check database references
- ✅ Test HTTP accessibility
- ✅ Offer to fix permissions
- ✅ Flush image styles
- ✅ Clear caches

---

## 📊 Expected Image Configuration

### Image Storage
- **Location:** `sites/shop.sahistory.org.za/files/product-covers/`
- **Count:** 20 images
- **Format:** JPG
- **Sizes:** Vary from 15KB to 9.4MB
- **URI in DB:** `public://product-covers/filename.jpg`

### Image Derivatives (Auto-Generated)
- **Location:** `sites/shop.sahistory.org.za/files/styles/*/public/product-covers/`
- **Styles:** medium, large, thumbnail, product_teaser, etc.
- **Generated:** On-demand when image is requested

### Products With Images (20 products)
1. Africa in Today's World
2. Amulets & Dreams
3. Better to die on one's feet
4. Bonani Africa 2010 Catalogue
5. Cape Flats Details
6. Collected Poems (Gwala & Qabula)
7. Community Based Public Works Programme
8. Culture in the New South Africa
9. Imperial Ghetto
10. Kali Pani
11. Lover of his People
12. My Life
13. One Hundred Years of the ANC
14. Social Identities in the New South Africa
15. The ANC and Regeneration of Political Power
16. The Final Prize
17. The I of the beholder
18. The People's Paper
19. The True Confessions
20. Imperial Ghetto (another edition)

---

## ✅ Success Test

After applying fixes, verify:

1. **Visit shop homepage:** https://shop.sahistory.org.za
2. **Check product listing** - Images should show in grid
3. **Click a product** - Full image should display
4. **Right-click image** → "Open in new tab" - Should load image directly
5. **Check browser console** - No 404 errors for images

---

## 📞 Still Not Working?

If images still don't show after all fixes:

1. **Check web server error logs:**
   ```bash
   tail -f /var/log/apache2/error.log
   # or
   tail -f /var/log/nginx/error.log
   ```

2. **Check Drupal logs:**
   ```bash
   drush --uri=https://shop.sahistory.org.za watchdog:show --severity=Error --count=20
   ```

3. **Verify file system settings:**
   ```bash
   drush --uri=https://shop.sahistory.org.za config:get system.file
   ```

4. **Check if SELinux is blocking (if on RedHat/CentOS):**
   ```bash
   sudo setenforce 0  # Temporarily disable to test
   # If this fixes it, configure SELinux properly
   ```

---

**Quick Reference:**
- Images location: `sites/shop.sahistory.org.za/files/product-covers/`
- Permissions: `664` files, `775` directories
- Owner: `www-data:www-data`
- Count: 20 images
- Total size: ~53MB
