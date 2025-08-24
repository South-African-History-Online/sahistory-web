# Production WebP Commands Quick Reference

## 🚀 Complete Production Setup

Copy the scripts directory to your production root:
```bash
# Copy entire scripts directory
scp -r scripts/ user@production:/path/to/drupal/

# Copy WebP module
scp -r webroot/modules/custom/saho_webp/ user@production:/path/to/drupal/webroot/modules/custom/
```

## 📋 Step-by-Step Production Workflow

### 1. Initial Audit
```bash
# Check current WebP conversion status
php scripts/comprehensive_webp_status.php
```

### 2. Clean Fake Images
```bash
# Remove HTML 404 error pages saved as images
php scripts/clean_fake_images.php
```

### 3. URGENT: Clear Cache After Module Fix
```bash
# Clear Drupal cache to disable auto-conversion hooks
vendor/bin/drush cr
```

### 4. Run Safe Conversion
```bash
# Safe manual conversion (doesn't modify originals)
php scripts/safe_webp_generator.php 500 0
```

### 5. Targeted Audit & Fix
```bash
# Audit missing files and attempt conversion
php scripts/production_webp_audit.php
```

### 6. Debug Specific Files
```bash
# Debug why a specific file isn't converting
php scripts/debug_specific_file.php sites/default/files/bio_pics/ReggieWilliams.jpg
```

### 7. Fix Double Extensions
```bash
# Clean up any .jpg.webp files
php scripts/fix_webp_names.php
```

### 8. Final Status Check
```bash
# Verify final conversion rate
php scripts/comprehensive_webp_status.php
```

## 🎯 For Your Specific Issue

Since `ReggieWilliams.jpg` is not converting, run this:

```bash
# Navigate to your Drupal root on production
cd /home/yourusername/public_html/sahistory.org.za

# Debug the specific file
php scripts/debug_specific_file.php sites/default/files/bio_pics/ReggieWilliams.jpg
```

This will show you:
- ✅ If the file exists and its properties
- 🔍 Whether it's a real image or HTML error page
- 🔧 Attempt conversion and show detailed error messages
- 📊 File size and type information

## 🔧 Common Issues & Solutions

### Issue: File not found
- **Solution**: The file path might be different on production
- **Command**: `find sites/default/files -name "ReggieWilliams.jpg" -type f`

### Issue: HTML error pages as images
- **Solution**: Clean fake images first
- **Command**: `php scripts/clean_fake_images.php`

### Issue: WebP conversion fails
- **Causes**: No GD WebP support, corrupt images, wrong permissions
- **Check**: `php -m | grep gd` and `php -i | grep -i webp`

### Issue: Files exist but not being served
- **Solution**: Check .htaccess WebP serving rules
- **Test**: `curl -H "Accept: image/webp" -I https://yourdomain.com/sites/default/files/bio_pics/ReggieWilliams.jpg`

## 📈 Expected Results

After running all commands, you should see:
- **99%+ conversion rate**
- **WebP files created alongside originals**
- **Automatic WebP serving for compatible browsers**
- **Significant bandwidth savings**

## ⚠️ Important Notes

- **Always run from Drupal root directory** (where composer.json exists)
- **Never run from webroot subdirectory** on production
- **Scripts automatically detect correct path structure**
- **Original images are never modified** (WebP files created alongside)

## 🎉 Success Indicators

You'll know it's working when:
1. `php scripts/comprehensive_webp_status.php` shows 99%+ conversion rate
2. Browser dev tools show WebP files being served
3. PageSpeed Insights scores improve significantly
4. Bandwidth usage decreases