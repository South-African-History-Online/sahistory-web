# Quick Start: Place Image Border Removal

## The Problem
Editor uses Snipping Tool → Red borders automatically added → Images look bad on site

## The Solution
Run this script to automatically remove borders from all Place images.

## Usage

### First Time (or after long break)

```bash
# 1. Test first (doesn't change anything)
./trim_place_images.sh --dry-run

# 2. Review the output - check for errors

# 3. Run for real
./trim_place_images.sh

# 4. Clear Drupal caches
ddev drush image-flush --all
ddev drush cr
```

### Regular Maintenance (Weekly/Monthly)

```bash
# Quick check for new uploads
./trim_place_images.sh --dry-run

# If new images found, process them
./trim_place_images.sh
ddev drush image-flush --all && ddev drush cr
```

## Important Notes

- **Safe**: Always creates backups before modifying images
- **Smart**: Only processes images that actually have borders
- **Targeted**: Only processes Place content type (where editor uploads)
- **Fast**: ~1 second per image

## If Something Goes Wrong

```bash
# Find your backup
ls -lt webroot/sites/default/image_backups/

# Restore from backup
./trim_place_images.sh --restore webroot/sites/default/image_backups/[TIMESTAMP]
```

## Options

```bash
--dry-run           # Test without making changes (ALWAYS USE FIRST)
--fuzz 10           # More aggressive border detection
--fuzz 2            # More conservative border detection
--restore [DIR]     # Restore from backup
--help              # Show help
```

## Where to Find Logs

```bash
# View latest log
ls -lt webroot/sites/default/image_processing_logs/
cat webroot/sites/default/image_processing_logs/trim_places_*.log
```

## Full Documentation

See `PLACE_IMAGE_BORDER_REMOVAL.md` for complete documentation.
