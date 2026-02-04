#  SAHO Website Scripts

This directory contains scripts and documentation for WebP image optimization and content management on the SAHO website.

## 📁 Directory Structure

### PHP Scripts

#### WebP Optimization
- **`clean_fake_images.php`** - Removes HTML 404 error pages saved with image extensions
- **`comprehensive_webp_status.php`** - Detailed WebP conversion status across all subdirectories
- **`complete_webp_conversion.php`** - Automated complete conversion system
- **`convert_webp_chunked.php`** - Chunked batch conversion for large datasets
- **`convert_webp_production_final.php`** - Production-safe conversion script
- **`debug_specific_file.php`** - Debug why specific images aren't converting
- **`fix_missing_webp.php`** - Targeted fix for problematic files
- **`production_webp_audit.php`** - Production audit and conversion tool
- **`process_webp_queue_production.php`** - Manual queue processing for production
- **`resume_webp_conversion.php`** - Resume interrupted conversions
- **`safe_webp_generator.php`** - Safe manual WebP generation

#### Content Management
- **`fix_image_urls_safe.php`** - Safely convert absolute image URLs to relative paths

### Shell Scripts
- **`webp.sh`** - Main script runner with simple commands
- **`deploy.sh`** - Deployment script for production
- **`restore-htaccess.sh`** - Restore .htaccess backups

### Documentation
- **`WEBP_OPTIMIZATION.md`** - Main project documentation (start here!)
- **`FIXED_WEBP_DEPLOYMENT.md`** - Fixed auto-conversion deployment guide
- **`PRODUCTION_COMMANDS.md`** - Quick reference for production commands
- **`PRODUCTION_QUICK_FIX.md`** - Immediate fixes for production issues
- **`PRODUCTION_WEBP_DEPLOYMENT.md`** - Complete production deployment guide
- **`IMAGE_URL_FIX.md`** - Safe image URL conversion guide

##  **Quick Start Commands**

All scripts should be run from the **project root directory**:

```bash
# From project root (where composer.json is)
cd /path/to/project/root

# Simple commands using webp.sh
scripts/webp.sh status     # Check conversion status
scripts/webp.sh clean      # Clean fake HTML error pages
scripts/webp.sh convert    # Complete conversion
scripts/webp.sh audit      # Production audit

# Or direct PHP script calls
php scripts/comprehensive_webp_status.php
php scripts/clean_fake_images.php
php scripts/complete_webp_conversion.php
php scripts/production_webp_audit.php
```

##  **Most Common Commands**

### WebP Development
```bash
scripts/webp.sh status
scripts/webp.sh clean
scripts/webp.sh convert
```

### WebP Production
```bash
scripts/webp.sh clean
scripts/webp.sh audit
vendor/bin/drush saho:webp-convert
```

### Content Management
```bash
# Fix absolute image URLs (safe - checks file existence)
ddev exec php scripts/fix_image_urls_safe.php
```

## 📋 **Path Requirements**

- **Run from project root** (where composer.json exists)
- Scripts automatically detect `sites/default/files` vs `webroot/sites/default/files`
- Works in both DDEV and production environments

## 🚨 **Important Notes**

- **Original images are NEVER modified** - only WebP copies are created
- **Always backup before running** on production
- **Scripts handle path detection automatically**
- **Check documentation files** for detailed instructions

## 📊 **Expected Results**

After running all scripts:
- **95%+ WebP conversion rate**
- **Significant bandwidth savings** (typically 60-70%)
- **Improved PageSpeed Insights scores**
- **Automatic WebP serving** to compatible browsers