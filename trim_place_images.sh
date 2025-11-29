#!/bin/bash

# Script to remove red borders from place content type images
# Processes images from field_feature_banner and field_place_image (PNG, JPG, JPEG, WebP)
#
# BACKGROUND:
#   Editor uses Snipping Tool which adds red borders to screenshots.
#   This script automatically removes those borders using ImageMagick.
#
# USAGE:
#   Dry-run (recommended first):
#     ./trim_place_images.sh --dry-run
#
#   Production run:
#     ./trim_place_images.sh
#
#   Restore from backup:
#     ./trim_place_images.sh --restore /path/to/backup
#
# OPTIONS:
#   --dry-run      Test run without making changes
#   --restore DIR  Restore files from backup directory
#   --fuzz N       Set fuzz tolerance (default: 5)
#   --help         Show this help message

set -euo pipefail

# Default configuration
FUZZ_TOLERANCE=5
DRY_RUN=false
RESTORE_MODE=false
RESTORE_DIR=""

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --restore)
            RESTORE_MODE=true
            RESTORE_DIR="$2"
            shift 2
            ;;
        --fuzz)
            FUZZ_TOLERANCE="$2"
            shift 2
            ;;
        --help)
            head -n 25 "$0" | grep "^#" | sed 's/^# \?//'
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Run with --help for usage information"
            exit 1
            ;;
    esac
done

# Use vendor/bin/drush universally (works in all environments)
DRUSH="vendor/bin/drush"
if [ ! -f "$DRUSH" ]; then
    echo "❌ Error: Drush not found at vendor/bin/drush"
    echo "Run 'composer install' first"
    exit 1
fi

# Check for ImageMagick (try 'magick' first, fall back to 'convert')
if command -v magick &> /dev/null; then
    IMAGEMAGICK_CMD="magick"
elif command -v convert &> /dev/null; then
    IMAGEMAGICK_CMD="convert"
else
    echo "❌ Error: ImageMagick not found (neither 'magick' nor 'convert')"
    echo "Install with: sudo apt-get install imagemagick (or equivalent)"
    exit 1
fi
echo "Using ImageMagick command: $IMAGEMAGICK_CMD"

# Get the Drupal root and files directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DRUPAL_ROOT="$SCRIPT_DIR/webroot"
FILES_DIR="$DRUPAL_ROOT/sites/default/files"

# Change to files directory
cd "$FILES_DIR"

# Create lock file to prevent concurrent runs
LOCK_FILE="/tmp/trim_place_images.lock"
if [ -f "$LOCK_FILE" ]; then
    echo "❌ Error: Another instance is already running (lock file exists)"
    echo "If you're sure no other instance is running, remove: $LOCK_FILE"
    exit 1
fi
trap "rm -f $LOCK_FILE" EXIT
touch "$LOCK_FILE"

# Setup logging
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_DIR="../image_processing_logs"
mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/trim_places_$TIMESTAMP.log"

# Function to log with timestamp
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

# Restore mode
if [ "$RESTORE_MODE" = true ]; then
    if [ ! -d "$RESTORE_DIR" ]; then
        echo "❌ Error: Restore directory not found: $RESTORE_DIR"
        exit 1
    fi

    log "========================================="
    log "RESTORE MODE: Restoring from backup"
    log "Source: $RESTORE_DIR"
    log "========================================="

    RESTORED=0
    find "$RESTORE_DIR" -type f | while read -r backup_file; do
        # Get relative path
        rel_path="${backup_file#$RESTORE_DIR/}"
        target_file="$FILES_DIR/$rel_path"

        if cp "$backup_file" "$target_file"; then
            log "✓ Restored: $rel_path"
            RESTORED=$((RESTORED + 1))
        else
            log "✗ Failed to restore: $rel_path"
        fi
    done

    log "Restored $RESTORED files"
    log "Running cache clear..."
    $DRUSH image-flush --all
    $DRUSH cr
    exit 0
fi

# Regular processing mode
BACKUP_DIR="../image_backups/$TIMESTAMP"
mkdir -p "$BACKUP_DIR"

log "========================================="
log "Place Image Border Removal Script"
log "========================================="
log "Dry Run: $DRY_RUN"
log "Fuzz Tolerance: $FUZZ_TOLERANCE%"
log "Backup Directory: $BACKUP_DIR"
log "Log File: $LOG_FILE"
log "========================================="
log ""

# Counters
PROCESSED=0
SKIPPED=0
ERRORS=0
NO_CHANGE=0

# Get all images from place content type
log "Querying database for place images..."
IMAGE_QUERY="SELECT DISTINCT fm.uri, fm.filename, fm.filesize
FROM file_managed fm
INNER JOIN node__field_feature_banner nfb ON fm.fid = nfb.field_feature_banner_target_id
WHERE nfb.bundle = 'place'
AND (fm.uri LIKE '%.png' OR fm.uri LIKE '%.jpg' OR fm.uri LIKE '%.jpeg' OR fm.uri LIKE '%.webp')
UNION
SELECT DISTINCT fm.uri, fm.filename, fm.filesize
FROM file_managed fm
INNER JOIN node__field_place_image npi ON fm.fid = npi.field_place_image_target_id
WHERE npi.bundle = 'place'
AND (fm.uri LIKE '%.png' OR fm.uri LIKE '%.jpg' OR fm.uri LIKE '%.jpeg' OR fm.uri LIKE '%.webp');"

TOTAL_IMAGES=$($DRUSH sqlq "$IMAGE_QUERY" | grep -c "^public://" || true)
log "Found $TOTAL_IMAGES images to process"
log ""

CURRENT=0

while IFS=$'\t' read -r uri filename filesize; do
    # Skip header or empty lines
    [[ "$uri" == "uri" ]] && continue
    [[ -z "$uri" ]] && continue

    CURRENT=$((CURRENT + 1))

    # Convert public:// to actual path
    file_path="${uri#public://}"

    log "[$CURRENT/$TOTAL_IMAGES] Processing: $filename"

    if [ ! -f "$file_path" ]; then
        log "  ✗ File not found: $file_path"
        SKIPPED=$((SKIPPED + 1))
        continue
    fi

    # Get original file size
    original_size=$(stat -f%z "$file_path" 2>/dev/null || stat -c%s "$file_path" 2>/dev/null)

    if [ "$DRY_RUN" = true ]; then
        # In dry-run, just check what would be trimmed
        test_output="/tmp/trim_test_$$_${CURRENT}.tmp"
        if $IMAGEMAGICK_CMD "$file_path" -fuzz ${FUZZ_TOLERANCE}% -trim +repage "$test_output" 2>/dev/null; then
            new_size=$(stat -f%z "$test_output" 2>/dev/null || stat -c%s "$test_output" 2>/dev/null)
            size_diff=$((original_size - new_size))

            if [ $size_diff -gt 100 ]; then
                percentage=$((size_diff * 100 / original_size))
                log "  ℹ Would trim: -${size_diff} bytes (-${percentage}%)"
                PROCESSED=$((PROCESSED + 1))
            else
                log "  ℹ No significant border detected"
                NO_CHANGE=$((NO_CHANGE + 1))
            fi
            rm -f "$test_output"
        else
            log "  ✗ Error testing image"
            ERRORS=$((ERRORS + 1))
        fi
    else
        # Backup original
        backup_path="$BACKUP_DIR/$file_path"
        mkdir -p "$(dirname "$backup_path")"
        if ! cp "$file_path" "$backup_path"; then
            log "  ✗ Backup failed, skipping"
            ERRORS=$((ERRORS + 1))
            continue
        fi

        # Trim the border
        temp_output="${file_path}.tmp"
        if $IMAGEMAGICK_CMD "$file_path" -fuzz ${FUZZ_TOLERANCE}% -trim +repage "$temp_output" 2>/dev/null; then
            new_size=$(stat -f%z "$temp_output" 2>/dev/null || stat -c%s "$temp_output" 2>/dev/null)
            size_diff=$((original_size - new_size))

            if [ $size_diff -gt 100 ]; then
                mv "$temp_output" "$file_path"
                percentage=$((size_diff * 100 / original_size))
                log "  ✓ Trimmed: -${size_diff} bytes (-${percentage}%)"
                PROCESSED=$((PROCESSED + 1))
            else
                rm -f "$temp_output"
                log "  ○ No significant border detected, left unchanged"
                NO_CHANGE=$((NO_CHANGE + 1))
            fi
        else
            rm -f "$temp_output"
            log "  ✗ Error processing image"
            ERRORS=$((ERRORS + 1))
        fi
    fi
done < <($DRUSH sqlq "$IMAGE_QUERY")

log ""
log "========================================="
log "Processing Complete!"
log "========================================="
log "Total images found: $TOTAL_IMAGES"
log "Successfully processed: $PROCESSED"
log "No change needed: $NO_CHANGE"
log "Skipped (not found): $SKIPPED"
log "Errors: $ERRORS"
log "========================================="

if [ "$DRY_RUN" = false ] && [ $PROCESSED -gt 0 ]; then
    log ""
    log "Backups saved to: $BACKUP_DIR"
    log ""
    log "Next steps:"
    log "1. Verify results look correct"
    log "2. Run: $DRUSH image-flush --all"
    log "3. Run: $DRUSH cr"
    log "4. Test the website"
    log ""
    log "To restore from backup if needed:"
    log "  ./trim_place_images.sh --restore $BACKUP_DIR"
elif [ "$DRY_RUN" = true ]; then
    log ""
    log "This was a dry-run. No files were modified."
    log "To process images, run without --dry-run flag:"
    log "  ./trim_place_images.sh"
fi

log ""
log "Full log saved to: $LOG_FILE"
