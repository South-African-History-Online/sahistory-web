# Place Image Border Removal Documentation

## Problem Statement

The editor responsible for maintaining Place content uses the Windows Snipping Tool for screenshots, which automatically adds red borders around captured images. This creates visual inconsistency on the website and requires manual editing.

## Solution

Automated script (`trim_place_images.sh`) that:
- Identifies all images in Place content type (fields: `field_feature_banner`, `field_place_image`)
- Automatically removes red borders using ImageMagick
- Only processes Place content (editor only uploads to this content type)
- Provides safety features like dry-run, backups, and restore capability

## Prerequisites

### Required Software
- **ImageMagick 7+**: For image processing
  ```bash
  # Check if installed
  magick --version

  # Install if needed
  sudo apt-get install imagemagick  # Debian/Ubuntu
  brew install imagemagick          # macOS
  ```

- **Drupal/Drush**: Already installed in your environment

### Permissions
Script must be run from the project root with appropriate file system permissions.

## Usage

### Quick Start

1. **Always start with a dry-run** to see what would be changed:
   ```bash
   ./trim_place_images.sh --dry-run
   ```

2. **Review the dry-run output**, then process for real:
   ```bash
   ./trim_place_images.sh
   ```

3. **Clear Drupal caches** to regenerate image styles:
   ```bash
   ddev drush image-flush --all
   ddev drush cr
   ```

### Command Options

```bash
# Test run without making changes (RECOMMENDED FIRST)
./trim_place_images.sh --dry-run

# Production run (processes images)
./trim_place_images.sh

# Adjust fuzz tolerance (default: 5%)
./trim_place_images.sh --fuzz 10

# Restore from backup
./trim_place_images.sh --restore /path/to/backup

# Show help
./trim_place_images.sh --help
```

### Understanding Fuzz Tolerance

The `--fuzz` parameter controls how close a color needs to be to red to be considered "border":
- **Lower values (1-3%)**: Only exact red shades are trimmed (more conservative)
- **Default (5%)**: Handles slight color variations from compression
- **Higher values (10-15%)**: More aggressive, may trim reddish content

Start with default (5%). Only adjust if you notice:
- Borders not being removed → increase fuzz
- Content being trimmed incorrectly → decrease fuzz

## Workflow Examples

### Initial Processing (All Existing Images)

```bash
# 1. Dry-run to see what will be processed
./trim_place_images.sh --dry-run

# Review output:
# - How many images will be processed?
# - Are the file sizes changing significantly?
# - Any errors reported?

# 2. Process all images
./trim_place_images.sh

# 3. Review a few random places on the site

# 4. If satisfied, clear caches
ddev drush image-flush --all
ddev drush cr
```

### Regular Maintenance (New Uploads)

Run periodically (weekly/monthly) to catch new uploads:

```bash
# Quick check for new images
./trim_place_images.sh --dry-run

# If new images found, process them
./trim_place_images.sh
ddev drush image-flush --all
ddev drush cr
```

### Emergency Rollback

If something goes wrong:

```bash
# Find your backup directory
ls -lt webroot/sites/default/image_backups/

# Restore from most recent backup
./trim_place_images.sh --restore webroot/sites/default/image_backups/20250128_143022

# Clear caches
ddev drush image-flush --all
ddev drush cr
```

## Output and Logging

### Console Output
Real-time progress with:
- Current file being processed
- Progress counter (e.g., [15/47])
- Size reduction for each image
- Summary statistics

### Log Files
Detailed logs saved to `webroot/sites/default/image_processing_logs/`:
- Timestamped filename: `trim_places_YYYYMMDD_HHMMSS.log`
- Complete record of all operations
- Useful for auditing and troubleshooting

Example log entry:
```
[2025-01-28 14:30:15] [15/47] Processing: cape-town-harbor.png
[2025-01-28 14:30:15]   ✓ Trimmed: -45832 bytes (-12%)
```

### Backups
Automatic backups created at `webroot/sites/default/image_backups/TIMESTAMP/`:
- Preserves original directory structure
- Original files remain untouched in backup
- Can be restored using `--restore` flag

## Safety Features

### 1. Lock File
Prevents multiple simultaneous runs:
```bash
# If you see this error:
# "Another instance is already running (lock file exists)"

# And you're certain no other instance is running:
rm /tmp/trim_place_images.lock
```

### 2. Automatic Backups
Every production run creates a timestamped backup before making changes.

### 3. Dry-Run Mode
Test everything without modifying files:
- Shows what would be changed
- Reports size reductions
- Identifies potential errors
- No backup needed (nothing is changed)

### 4. Smart Detection
Only processes images that actually have borders:
- Checks if trimming would save >100 bytes
- Skips images without significant borders
- Avoids unnecessary processing

### 5. Error Handling
- Validates prerequisites (ImageMagick, Drush)
- Checks file existence before processing
- Reports errors without stopping entire batch
- Comprehensive error logging

## Understanding the Output

### Dry-Run Output
```
[15/47] Processing: screenshot-2025-01-15.png
  ℹ Would trim: -45832 bytes (-12%)
```
- **Would trim**: This image has a border, will be processed
- **Bytes/percentage**: How much smaller the file will become

```
[16/47] Processing: photo-without-border.jpg
  ℹ No significant border detected
```
- Image doesn't have a border, will be skipped

### Production Run Output
```
[15/47] Processing: screenshot-2025-01-15.png
  ✓ Trimmed: -45832 bytes (-12%)
```
- **Trimmed**: Successfully processed and saved

```
[16/47] Processing: photo-without-border.jpg
  ○ No significant border detected, left unchanged
```
- Image analyzed but not modified (no border found)

### Summary Statistics
```
========================================
Processing Complete!
========================================
Total images found: 47
Successfully processed: 23
No change needed: 22
Skipped (not found): 2
Errors: 0
========================================
```

## Technical Details

### What Images Are Processed?

The script queries the Drupal database for images that meet ALL these criteria:
1. Attached to nodes with content type = "place"
2. In either of these fields:
   - `field_feature_banner` (banner images)
   - `field_place_image` (place images)
3. File extensions: `.png`, `.jpg`, `.jpeg`, `.webp`

### How Border Removal Works

Uses ImageMagick's trim function:
```bash
magick input.png -fuzz 5% -trim +repage output.png
```

- **-fuzz 5%**: Colors within 5% of border edge color are considered "border"
- **-trim**: Removes borders of uniform color from all edges
- **+repage**: Updates image metadata to new dimensions

### Database Query

The script uses this SQL query to find images:
```sql
SELECT DISTINCT fm.uri, fm.filename, fm.filesize
FROM file_managed fm
INNER JOIN node__field_feature_banner nfb
  ON fm.fid = nfb.field_feature_banner_target_id
WHERE nfb.bundle = 'place'
AND (fm.uri LIKE '%.png' OR fm.uri LIKE '%.jpg'
     OR fm.uri LIKE '%.jpeg' OR fm.uri LIKE '%.webp')
UNION
SELECT DISTINCT fm.uri, fm.filename, fm.filesize
FROM file_managed fm
INNER JOIN node__field_place_image npi
  ON fm.fid = npi.field_place_image_target_id
WHERE npi.bundle = 'place'
AND (fm.uri LIKE '%.png' OR fm.uri LIKE '%.jpg'
     OR fm.uri LIKE '%.jpeg' OR fm.uri LIKE '%.webp');
```

## Troubleshooting

### "ImageMagick 'magick' command not found"
**Solution**: Install ImageMagick
```bash
# Debian/Ubuntu
sudo apt-get install imagemagick

# macOS
brew install imagemagick

# Verify installation
magick --version
```

### "Another instance is already running"
**Cause**: Lock file exists from previous run

**Solution**:
```bash
# Check if process is actually running
ps aux | grep trim_place_images

# If not running, remove lock file
rm /tmp/trim_place_images.lock
```

### Some Images Not Processing
**Check**:
1. Does the file exist on disk?
   ```bash
   ls -la webroot/sites/default/files/[path-from-error]
   ```

2. Are file permissions correct?
   ```bash
   # Should be readable/writable
   chmod 664 webroot/sites/default/files/path/to/image.png
   ```

3. Is the image corrupted?
   ```bash
   magick identify webroot/sites/default/files/path/to/image.png
   ```

### Images Processed but Still Look Wrong
**Possible causes**:
1. **Cached image styles**: Clear Drupal image cache
   ```bash
   ddev drush image-flush --all
   ddev drush cr
   ```

2. **Browser cache**: Hard refresh (Ctrl+Shift+R)

3. **CDN cache**: If using a CDN, you may need to purge its cache

### Borders Not Being Removed
**Try adjusting fuzz tolerance**:
```bash
# More aggressive border detection
./trim_place_images.sh --fuzz 10 --dry-run

# If results look good, run for real
./trim_place_images.sh --fuzz 10
```

### Too Much Being Trimmed
**Try lower fuzz tolerance**:
```bash
# More conservative border detection
./trim_place_images.sh --fuzz 2 --dry-run

# If results look good, run for real
./trim_place_images.sh --fuzz 2
```

## Maintenance Schedule

### Recommended Schedule

**Weekly** (if editor is actively uploading):
```bash
# Quick check for new images
./trim_place_images.sh --dry-run

# Process if any found
./trim_place_images.sh
ddev drush image-flush --all && ddev drush cr
```

**Monthly** (for peace of mind):
```bash
# Full audit of all place images
./trim_place_images.sh --dry-run
# Review log to ensure all images are border-free
```

### Automation Options

#### Cron Job (Production Server)
```bash
# Add to crontab
# Process new images every Sunday at 2 AM
0 2 * * 0 cd /path/to/sahistory-web && ./trim_place_images.sh >> /var/log/trim_places.log 2>&1
```

#### Git Hook (Pre-deployment)
Run before deploying to production:
```bash
# .git/hooks/pre-push
#!/bin/bash
./trim_place_images.sh --dry-run
read -p "Found images to process. Run now? (y/n) " -n 1 -r
if [[ $REPLY =~ ^[Yy]$ ]]; then
    ./trim_place_images.sh
fi
```

## Best Practices

### For Developers

1. **Always dry-run first** in production
2. **Test on local/staging** before production
3. **Keep backups** for at least 30 days
4. **Review logs** after each run
5. **Document any issues** in GitHub

### For Editors

**Ideal workflow** (if editor is technical):
1. Upload images to Place content
2. Run script periodically
3. Borders automatically removed

**Alternative workflow** (non-technical editor):
1. Editor uploads images (with borders)
2. Developer runs script weekly
3. Borders cleaned up in batch

**Long-term solution**:
Consider providing the editor with:
- Alternative screenshot tool (without borders)
- Quick guide on removing borders before upload
- Automated upload processing hook

## Security Considerations

- Script only processes images in Place content type
- Automatic backups prevent data loss
- Lock file prevents race conditions
- Read-only operations in dry-run mode
- Comprehensive logging for audit trail

## Performance

### Processing Speed
- **~0.5-2 seconds per image** (depends on size)
- **Batch of 50 images**: ~1-2 minutes
- **Parallel processing**: Not implemented (safe for shared hosting)

### Resource Usage
- **Memory**: Moderate (ImageMagick processes one image at a time)
- **Disk I/O**: Moderate (reads, writes, backup copy)
- **CPU**: Brief spikes during image processing

### Optimization Tips
- Run during off-peak hours (low traffic)
- Process in batches if >100 images
- Consider running on staging first for large batches

## Integration with Drupal

### After Processing Images

The script modifies the source images, so you must regenerate image styles:

```bash
# Flush all image styles (forces regeneration)
ddev drush image-flush --all

# Clear all caches
ddev drush cr

# Optional: Rebuild image style for specific style
ddev drush image-flush [style-name]
```

### Image Fields Affected

1. **field_feature_banner**: Banner images for places
2. **field_place_image**: Main place images

### Content Type
- **Only "place" content type** is processed
- Other content types are not affected

## Support and Contributing

### Getting Help

1. **Check this documentation** first
2. **Review log files** in `webroot/sites/default/image_processing_logs/`
3. **Create GitHub issue** with:
   - Log file contents
   - Screenshots of the issue
   - Steps to reproduce

### Reporting Issues

Include in your report:
- Script version (check first few lines of script)
- Environment (DDEV/production)
- ImageMagick version: `magick --version`
- Sample log output
- Example images (if possible)

### Contributing

Improvements welcome:
- Better border detection algorithms
- Parallel processing for large batches
- Web UI for editors
- Automated scheduling
- Integration with media upload

## Future Enhancements

Potential improvements:
1. **Web UI**: Allow editors to trigger processing
2. **Automatic processing**: Hook into file upload events
3. **Preview mode**: Show before/after images
4. **Batch processing**: Process in chunks for very large datasets
5. **Email notifications**: Alert admin when processing completes
6. **Smart detection**: ML-based border detection
7. **Multi-color borders**: Handle borders beyond just red

## License and Credits

Part of the South African History Online (SAHO) project.

**Author**: Development team
**Maintained by**: SAHO development team
**Repository**: https://github.com/South-African-History-Online/sahistory-web
