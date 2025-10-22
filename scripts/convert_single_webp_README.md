# Convert Single Image to WebP

## Purpose

This script converts a single JPEG or PNG image to WebP format. It's useful for:
- Converting newly uploaded images that weren't included in bulk conversions
- Testing WebP conversion on specific files
- Quick ad-hoc WebP generation without running full site conversions

## Usage

### Basic Usage

```bash
# From project root
php scripts/convert_single_webp.php <path-to-image>
```

### Examples

```bash
# Relative path from files directory
php scripts/convert_single_webp.php bio_pics/ReggieWilliams_1.jpg

# Full path (will be normalized)
php scripts/convert_single_webp.php sites/default/files/bio_pics/ReggieWilliams_1.jpg

# Leading slash (will be normalized)
php scripts/convert_single_webp.php /bio_pics/ReggieWilliams_1.jpg
```

## Output

### Success
```
Converting: bio_pics/ReggieWilliams_1.jpg
✓ Conversion successful!
  Original: 88.46 KB
  WebP: 45.23 KB (48.9% savings)
  Path: /var/www/html/webroot/sites/default/files/bio_pics/ReggieWilliams_1.webp
```

### Already Exists
```
WebP already exists:
  Original: 88.46 KB
  WebP: 45.23 KB (48.9% savings)
  Path: /var/www/html/webroot/sites/default/files/bio_pics/ReggieWilliams_1.webp
```

### Error Cases
- `ERROR: File not found:` - The source image doesn't exist
- `ERROR: File is not a JPEG or PNG:` - File is not a supported image format
- `ERROR: Failed to create image resource` - Corrupted or invalid image file
- `ERROR: Failed to create WebP file` - GD library couldn't create WebP

## Features

- **Path normalization**: Handles various path formats automatically
- **Duplicate detection**: Skips conversion if WebP already exists
- **File size comparison**: Shows original vs WebP size and savings percentage
- **Permissions preservation**: WebP file gets same permissions as original
- **Quality**: Uses 80% quality setting (good balance of size/quality)
- **PNG transparency**: Preserves alpha channel for PNG images

## Requirements

- PHP 7.4+ with GD library
- GD library compiled with WebP support
- Read access to source images
- Write access to files directory

## Use Cases

### 1. Convert Recently Uploaded File

When a new image is uploaded to the site:

```bash
php scripts/convert_single_webp.php bio_pics/NewPerson_1.jpg
```

### 2. Fix Missing WebP

If you discover a file serving JPEG instead of WebP:

```bash
# Check if WebP exists
ls webroot/sites/default/files/bio_pics/ReggieWilliams_1.webp

# If not, convert it
php scripts/convert_single_webp.php bio_pics/ReggieWilliams_1.jpg
```

### 3. Batch Convert Specific Directory

Convert all images in a specific directory:

```bash
# Find all JPGs in bio_pics without WebP versions
for file in webroot/sites/default/files/bio_pics/*.jpg; do
  basename=$(basename "$file")
  webp="${file%.jpg}.webp"
  if [ ! -f "$webp" ]; then
    echo "Converting: $basename"
    php scripts/convert_single_webp.php "bio_pics/$basename"
  fi
done
```

## Integration with .htaccess

Once the WebP file is created, Apache's `.htaccess` rules will automatically serve it to browsers that support WebP:

```apache
# From .htaccess.custom lines 28-33
RewriteCond %{HTTP_ACCEPT} image/webp
RewriteCond %{REQUEST_URI} \.(jpe?g|png)$ [NC]
RewriteCond %{REQUEST_FILENAME} ^(.+)\.(jpe?g|png)$
RewriteCond %1.webp -f
RewriteRule ^(.+)\.(jpe?g|png)$ $1.webp [T=image/webp,E=accept:1,L]
```

## Troubleshooting

### WebP Not Being Served After Conversion

1. **Verify WebP exists:**
   ```bash
   ls -lh webroot/sites/default/files/bio_pics/ReggieWilliams_1.webp
   ```

2. **Check permissions:**
   ```bash
   # Should be readable by web server
   chmod 644 webroot/sites/default/files/bio_pics/ReggieWilliams_1.webp
   ```

3. **Test with curl:**
   ```bash
   curl -I -H "Accept: image/webp,*/*" "https://sahistory.org.za/sites/default/files/bio_pics/ReggieWilliams_1.jpg"
   # Should return: content-type: image/webp
   ```

4. **Check browser DevTools:**
   - Network tab should show `.jpg` request returning `webp` content-type
   - Response Headers should show `content-type: image/webp`

### Script Fails with GD Error

Ensure GD library has WebP support:

```bash
php -r "var_dump(function_exists('imagewebp'));"
# Should output: bool(true)
```

If false, PHP GD needs to be recompiled with `--with-webp` flag.

## Related Commands

### Bulk WebP Conversion
```bash
# Convert all images in file_managed table
ddev drush saho:webp-convert
# Or on production:
./vendor/bin/drush saho:webp-convert
```

### Check WebP Status
```bash
ddev drush saho:webp-status
```

### Fix WebP File Naming
```bash
# Remove double extensions like .jpg.webp
ddev drush saho:webp-fix
```

## Production Deployment

To use on production/staging:

```bash
# SSH into server
ssh production

# Navigate to project root
cd /var/www/html/sahistory-web

# Run script
php scripts/convert_single_webp.php bio_pics/ReggieWilliams_1.jpg
```

## Exit Codes

- `0` - Success (converted or already exists)
- `1` - Error (file not found, conversion failed, etc.)

This makes it easy to use in scripts:

```bash
if php scripts/convert_single_webp.php bio_pics/test.jpg; then
  echo "WebP ready to serve"
else
  echo "Conversion failed"
fi
```

## Performance

- **Speed**: ~0.1-0.5 seconds per image
- **Memory**: Depends on image size (typically 20-50MB for large images)
- **Quality**: 80% produces good visual quality with 40-60% file size savings

## Notes

- The script is idempotent - safe to run multiple times
- Original images are never modified
- WebP files are created alongside originals (e.g., `image.jpg` → `image.webp`)
- Compatible with existing `.htaccess` WebP serving rules
