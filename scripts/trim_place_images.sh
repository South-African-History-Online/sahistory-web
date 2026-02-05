#!/bin/bash

# Script to remove red borders from place content type images
# Processes images from field_feature_banner and field_place_image (PNG, JPG, JPEG, WebP)
#
# BACKGROUND:
#   Editor uses Snipping Tool which adds red borders to screenshots.
#   This script detects red pixels along edges and crops them precisely.
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
#   --help         Show this help message

set -euo pipefail

# Default configuration
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

# Get the script directory first (needed for absolute paths)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Use vendor/bin/drush with absolute path (works after cd to files directory)
DRUSH="$SCRIPT_DIR/vendor/bin/drush"
if [ ! -f "$DRUSH" ]; then
    echo "Error: Drush not found at $DRUSH"
    echo "Run 'composer install' first"
    exit 1
fi

# Check for ImageMagick (try 'magick' first, fall back to 'convert')
if command -v magick &> /dev/null; then
    MAGICK_CMD="magick"
elif command -v convert &> /dev/null; then
    MAGICK_CMD="convert"
else
    echo "Error: ImageMagick not found (neither 'magick' nor 'convert')"
    echo "Install with: sudo apt-get install imagemagick (or equivalent)"
    exit 1
fi
echo "Using ImageMagick command: $MAGICK_CMD"

# Get the Drupal root and files directory
DRUPAL_ROOT="$SCRIPT_DIR/webroot"
FILES_DIR="$DRUPAL_ROOT/sites/default/files"

# Change to files directory
cd "$FILES_DIR"

# Create lock file to prevent concurrent runs
LOCK_FILE="/tmp/trim_place_images.lock"
if [ -f "$LOCK_FILE" ]; then
    echo "Error: Another instance is already running (lock file exists)"
    echo "If you're sure no other instance is running, remove: $LOCK_FILE"
    exit 1
fi
trap "rm -f $LOCK_FILE" EXIT
touch "$LOCK_FILE"

# Setup logging
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_DIR="../image_processing_logs"
if ! mkdir -p "$LOG_DIR" 2>/dev/null; then
    LOG_DIR="/tmp/image_processing_logs"
    mkdir -p "$LOG_DIR"
fi
LOG_FILE="$LOG_DIR/trim_places_$TIMESTAMP.log"

# Function to log with timestamp
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"
}

# Function to check if a pixel is "Snipping Tool red" (R > 200, G < 80, B < 80)
# Returns 0 (true) if red, 1 (false) if not
is_red_pixel() {
    local image="$1"
    local x="$2"
    local y="$3"

    local pixel
    if [ "$MAGICK_CMD" = "magick" ]; then
        pixel=$($MAGICK_CMD "$image" -format "%[pixel:p{$x,$y}]" info: 2>/dev/null)
    else
        pixel=$($MAGICK_CMD "$image" -format "%[pixel:p{$x,$y}]" info: 2>/dev/null)
    fi

    # Extract RGB values from srgb(r,g,b) or srgba(r,g,b,a) format
    local r g b
    r=$(echo "$pixel" | sed -n 's/.*(\([0-9]*\),.*/\1/p')
    g=$(echo "$pixel" | sed -n 's/.*,\([0-9]*\),.*/\1/p')
    b=$(echo "$pixel" | sed -n 's/.*,\([0-9]*\)[,)].*/\1/p')

    # Check for pure red: R > 200, G < 80, B < 80
    if [ -n "$r" ] && [ -n "$g" ] && [ -n "$b" ]; then
        if [ "$r" -gt 200 ] && [ "$g" -lt 80 ] && [ "$b" -lt 80 ]; then
            return 0  # Is red
        fi
    fi
    return 1  # Not red
}

# Function to detect red border thickness on one edge
# Returns the number of pixels of red border (0-20)
# Max depth of 20px should cover most screenshot borders
detect_border_thickness() {
    local image="$1"
    local edge="$2"  # top, bottom, left, right
    local width="$3"
    local height="$4"
    local max_depth=20

    for depth in $(seq 0 $((max_depth - 1))); do
        local red_count=0
        local total_samples=5

        case $edge in
            top)
                for i in $(seq 1 $total_samples); do
                    local x=$((width * i / (total_samples + 1)))
                    if is_red_pixel "$image" "$x" "$depth"; then
                        red_count=$((red_count + 1))
                    fi
                done
                ;;
            bottom)
                for i in $(seq 1 $total_samples); do
                    local x=$((width * i / (total_samples + 1)))
                    local y=$((height - 1 - depth))
                    if is_red_pixel "$image" "$x" "$y"; then
                        red_count=$((red_count + 1))
                    fi
                done
                ;;
            left)
                for i in $(seq 1 $total_samples); do
                    local y=$((height * i / (total_samples + 1)))
                    if is_red_pixel "$image" "$depth" "$y"; then
                        red_count=$((red_count + 1))
                    fi
                done
                ;;
            right)
                for i in $(seq 1 $total_samples); do
                    local y=$((height * i / (total_samples + 1)))
                    local x=$((width - 1 - depth))
                    if is_red_pixel "$image" "$x" "$y"; then
                        red_count=$((red_count + 1))
                    fi
                done
                ;;
        esac

        # If less than 3 out of 5 samples are red at this depth, border ends here
        if [ $red_count -lt 3 ]; then
            echo $depth
            return
        fi
    done

    echo $max_depth
}

# Restore mode
if [ "$RESTORE_MODE" = true ]; then
    if [ ! -d "$RESTORE_DIR" ]; then
        echo "Error: Restore directory not found: $RESTORE_DIR"
        exit 1
    fi

    log "========================================="
    log "RESTORE MODE: Restoring from backup"
    log "Source: $RESTORE_DIR"
    log "========================================="

    RESTORED=0
    find "$RESTORE_DIR" -type f | while read -r backup_file; do
        rel_path="${backup_file#$RESTORE_DIR/}"
        target_file="$FILES_DIR/$rel_path"

        if cp "$backup_file" "$target_file"; then
            log "Restored: $rel_path"
            RESTORED=$((RESTORED + 1))
        else
            log "Failed to restore: $rel_path"
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
if ! mkdir -p "$BACKUP_DIR" 2>/dev/null; then
    BACKUP_DIR="/tmp/image_backups/$TIMESTAMP"
    mkdir -p "$BACKUP_DIR"
fi

log "========================================="
log "Place Image Red Border Removal Script"
log "========================================="
log "Dry Run: $DRY_RUN"
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
        log "  File not found: $file_path"
        SKIPPED=$((SKIPPED + 1))
        continue
    fi

    # Get original dimensions
    if [ "$MAGICK_CMD" = "magick" ]; then
        read width height <<< $($MAGICK_CMD identify -format "%w %h" "$file_path" 2>/dev/null)
    else
        read width height <<< $(identify -format "%w %h" "$file_path" 2>/dev/null)
    fi

    if [ -z "$width" ] || [ -z "$height" ]; then
        log "  Error reading image dimensions"
        ERRORS=$((ERRORS + 1))
        continue
    fi

    # Detect red border on each edge
    top_border=$(detect_border_thickness "$file_path" "top" "$width" "$height")
    bottom_border=$(detect_border_thickness "$file_path" "bottom" "$width" "$height")
    left_border=$(detect_border_thickness "$file_path" "left" "$width" "$height")
    right_border=$(detect_border_thickness "$file_path" "right" "$width" "$height")

    # Check if any border was detected
    if [ "$top_border" -eq 0 ] && [ "$bottom_border" -eq 0 ] && [ "$left_border" -eq 0 ] && [ "$right_border" -eq 0 ]; then
        log "  No red border detected"
        NO_CHANGE=$((NO_CHANGE + 1))
        continue
    fi

    # Calculate new dimensions
    new_width=$((width - left_border - right_border))
    new_height=$((height - top_border - bottom_border))

    # Safety check: don't make image too small
    if [ "$new_width" -lt 50 ] || [ "$new_height" -lt 50 ]; then
        log "  Skipped: result would be too small (${new_width}x${new_height})"
        NO_CHANGE=$((NO_CHANGE + 1))
        continue
    fi

    log "  Detected border: top=${top_border}px, bottom=${bottom_border}px, left=${left_border}px, right=${right_border}px"

    if [ "$DRY_RUN" = true ]; then
        log "  Would crop: ${width}x${height} -> ${new_width}x${new_height}"
        PROCESSED=$((PROCESSED + 1))
    else
        # Backup original
        backup_path="$BACKUP_DIR/$file_path"
        mkdir -p "$(dirname "$backup_path")"
        if ! cp "$file_path" "$backup_path"; then
            log "  Backup failed, skipping"
            ERRORS=$((ERRORS + 1))
            continue
        fi

        # Crop the image
        temp_output="${file_path}.tmp"
        if $MAGICK_CMD "$file_path" -crop "${new_width}x${new_height}+${left_border}+${top_border}" +repage "$temp_output" 2>/dev/null; then
            mv "$temp_output" "$file_path"
            log "  Cropped: ${width}x${height} -> ${new_width}x${new_height}"
            PROCESSED=$((PROCESSED + 1))
        else
            rm -f "$temp_output"
            log "  Error cropping image"
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
