# WebP System Production Deployment Guide

## 🚀 Components to Deploy

### 1. Custom Drupal Module
**Location:** `webroot/modules/custom/saho_webp/`
**Files:**
- `saho_webp.info.yml`
- `saho_webp.module`
- `src/Commands/WebpCommands.php`
- `README.md`

**Deployment:**
```bash
# Copy module to production
scp -r webroot/modules/custom/saho_webp/ user@production:/path/to/drupal/webroot/modules/custom/

# Enable module on production
vendor/bin/drush en saho_webp -y
```

### 2. Conversion Scripts
**Files to copy to production root:**
- `convert_webp_production_final.php`
- `fix_webp_names.php`
- `comprehensive_webp_status.php`
- `complete_webp_conversion.php`
- `resume_webp_conversion.php`
- `fix_missing_webp.php` (targeted fix for problem files)
- `clean_fake_images.php` (removes HTML 404 error pages saved as images)

**Deployment:**
```bash
# Copy scripts to production root
scp convert_webp_production_final.php user@production:/path/to/drupal/
scp fix_webp_names.php user@production:/path/to/drupal/
scp comprehensive_webp_status.php user@production:/path/to/drupal/
scp complete_webp_conversion.php user@production:/path/to/drupal/
scp resume_webp_conversion.php user@production:/path/to/drupal/
scp fix_missing_webp.php user@production:/path/to/drupal/
scp clean_fake_images.php user@production:/path/to/drupal/
```

### 3. .htaccess Rules
**Update production .htaccess** with WebP serving rules:

```apache
# WebP Auto-serving - serve WebP if browser supports it and file exists
RewriteCond %{HTTP_ACCEPT} image/webp
RewriteCond %{REQUEST_URI} \.(jpe?g|png)$ [NC]
RewriteCond %{REQUEST_FILENAME} ^(.+)\.(jpe?g|png)$
RewriteCond %1.webp -f
RewriteRule ^(.+)\.(jpe?g|png)$ $1.webp [T=image/webp,E=accept:1,L]

# Set Vary header for WebP
<IfModule mod_headers.c>
    <FilesMatch "\.(jpe?g|png)$">
        Header append Vary Accept
    </FilesMatch>
</IfModule>

# WebP MIME type
<IfModule mod_mime.c>
    AddType image/webp .webp
</IfModule>

# Compression for WebP
<IfModule mod_deflate.c>
    <FilesMatch "\.(webp)$">
        SetOutputFilter DEFLATE
    </FilesMatch>
</IfModule>

# Cache WebP files
<IfModule mod_expires.c>
    <FilesMatch "\.(webp)$">
        ExpiresActive on
        ExpiresDefault "access plus 1 year"
    </FilesMatch>
</IfModule>
```

## 📋 Step-by-Step Production Deployment

### Step 1: Deploy Files
```bash
# 1. Copy module
scp -r webroot/modules/custom/saho_webp/ user@production:/path/to/drupal/webroot/modules/custom/

# 2. Copy conversion scripts
scp *.php user@production:/path/to/drupal/

# 3. Update .htaccess (backup first!)
cp webroot/.htaccess webroot/.htaccess.backup
# Then add WebP rules to production .htaccess
```

### Step 2: Enable Module
```bash
# SSH into production
ssh user@production
cd /path/to/drupal

# Enable the module
vendor/bin/drush en saho_webp -y

# Verify module is enabled
vendor/bin/drush pml | grep saho_webp
```

### Step 3: Test System
```bash
# Test path detection
php comprehensive_webp_status.php

# Should show: "📁 Scanning directory: sites/default/files"
# NOT: "webroot/sites/default/files"
```

### Step 4: Run Initial Conversion
```bash
# Start with comprehensive status
php comprehensive_webp_status.php

# Run complete conversion (processes all remaining files)
php complete_webp_conversion.php

# Or run in batches manually:
php convert_webp_production_final.php
```

### Step 5: Verify WebP Serving
Test that WebP files are being served:
```bash
# Test with curl (should return WebP if available)
curl -H "Accept: image/webp" -I https://yourdomain.com/sites/default/files/some-image.jpg

# Should return: Content-Type: image/webp
```

## 🔧 Production Commands

### Check Status
```bash
# Comprehensive status across all directories
php comprehensive_webp_status.php

# Drush command status
vendor/bin/drush saho:webp-status

# Resume conversion status
php resume_webp_conversion.php
```

### Run Conversions
```bash
# Complete automated conversion
php complete_webp_conversion.php

# Manual chunked conversion
php convert_webp_production_final.php

# Fix double extension files
php fix_webp_names.php

# Clean fake HTML error pages saved as images
php clean_fake_images.php

# Targeted fix for remaining problem files  
php fix_missing_webp.php
```

### Monitor New Uploads
- New image uploads are automatically converted to WebP
- Check Drupal logs: `vendor/bin/drush ws --tail`
- Look for "saho_webp" entries

## ⚠️ Important Production Notes

### Path Detection
- Scripts automatically detect `sites/default/files` vs `webroot/sites/default/files`
- Run from Drupal root directory (where composer.json is located)
- Never run from webroot subdirectory

### Performance
- Conversion runs automatically on file upload
- Large batches may need chunking (scripts handle this)
- Monitor server resources during bulk conversion

### Backup Strategy
- Original images are NEVER modified
- WebP files are created alongside originals
- Safe to delete .webp files to regenerate

### Troubleshooting
1. **"Files directory not found"**: Ensure you're in Drupal root
2. **Module not working**: Check `vendor/bin/drush pml | grep saho_webp`
3. **WebP not served**: Verify .htaccess rules and mod_rewrite
4. **Conversion errors**: Check server logs and PHP GD/ImageMagick

## 📊 Expected Results

After deployment, you should achieve:
- **95%+ conversion rate** for existing images
- **Automatic conversion** for new uploads
- **Transparent WebP serving** to compatible browsers
- **Significant bandwidth savings** (typically 60-70%)
- **Improved PageSpeed scores**

## 🎯 Testing Checklist

- [ ] Module enabled and active
- [ ] Status scripts work without path errors
- [ ] New image uploads create WebP versions
- [ ] Browser requests return WebP when supported
- [ ] .htaccess rules don't break existing functionality
- [ ] Conversion scripts complete without errors
- [ ] PageSpeed Insights shows WebP serving