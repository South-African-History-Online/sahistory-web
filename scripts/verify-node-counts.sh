#!/bin/bash
#
# Node Count Verification Script
# Compares node counts before and after content type deletion
#
# Usage:
#   ./scripts/verify-node-counts.sh [path-to-production-backup.sql]
#
# Example:
#   ./scripts/verify-node-counts.sh sahistrg878_production_2026_february.sql
#

set -e

PROD_BACKUP="$1"
REPORT_DIR="./node-count-reports"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=========================================="
echo "Node Count Verification Script"
echo "=========================================="
echo ""

# Create report directory
mkdir -p "$REPORT_DIR"

# Function to get node counts
get_node_counts() {
    local label="$1"
    local output_file="$2"

    echo "Getting node counts: $label"

    ddev drush sqlq "
SELECT
  type as 'Content Type',
  COUNT(DISTINCT nid) as 'Total',
  SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as 'Published',
  SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as 'Unpublished'
FROM node_field_data
GROUP BY type
ORDER BY type
" > "$output_file"

    echo "  Saved to: $output_file"
}

# Step 1: Get CURRENT state (after deletion locally)
echo ""
echo "Step 1: Capturing CURRENT state (after deletion)"
echo "------------------------------------------------"
CURRENT_FILE="$REPORT_DIR/current-state-${TIMESTAMP}.txt"
get_node_counts "Current State" "$CURRENT_FILE"

# Step 2: Import production backup if provided
if [ -n "$PROD_BACKUP" ]; then
    if [ ! -f "$PROD_BACKUP" ]; then
        echo -e "${RED}ERROR: Production backup not found: $PROD_BACKUP${NC}"
        exit 1
    fi

    echo ""
    echo "Step 2: Importing production backup"
    echo "------------------------------------"
    echo "  File: $PROD_BACKUP"
    echo ""
    echo -e "${YELLOW}WARNING: This will replace your current database!${NC}"
    echo -n "Continue? (yes/no): "
    read -r confirm

    if [ "$confirm" != "yes" ]; then
        echo "Aborted."
        exit 0
    fi

    echo ""
    echo "Importing database... (this may take a few minutes)"

    # Import based on file extension
    if [[ "$PROD_BACKUP" == *.gz ]]; then
        gunzip -c "$PROD_BACKUP" | ddev drush sqlc
    elif [[ "$PROD_BACKUP" == *.sql ]]; then
        ddev drush sqlc < "$PROD_BACKUP"
    else
        echo -e "${RED}ERROR: Unsupported file format. Use .sql or .sql.gz${NC}"
        exit 1
    fi

    echo "Database imported successfully"

    # Clear cache after import
    echo "Clearing cache..."
    ddev drush cr

    # Step 3: Get BEFORE counts
    echo ""
    echo "Step 3: Capturing PRODUCTION state (before deletion)"
    echo "-----------------------------------------------------"
    BEFORE_FILE="$REPORT_DIR/before-deletion-${TIMESTAMP}.txt"
    get_node_counts "Before Deletion" "$BEFORE_FILE"

    # Step 4: Generate comparison report
    echo ""
    echo "Step 4: Generating comparison report"
    echo "-------------------------------------"

    COMPARISON_FILE="$REPORT_DIR/comparison-report-${TIMESTAMP}.txt"

    {
        echo "=========================================="
        echo "NODE COUNT COMPARISON REPORT"
        echo "=========================================="
        echo "Generated: $(date)"
        echo ""
        echo "PRODUCTION STATE (BEFORE DELETION):"
        echo "=========================================="
        cat "$BEFORE_FILE"
        echo ""
        echo ""
        echo "CURRENT STATE (AFTER DELETION):"
        echo "=========================================="
        cat "$CURRENT_FILE"
        echo ""
        echo ""
        echo "CHANGES:"
        echo "=========================================="

        # Calculate differences for specific types
        BLOG_BEFORE=$(grep "^blog" "$BEFORE_FILE" | awk '{print $2}' || echo "0")
        BLOG_AFTER=$(grep "^blog" "$CURRENT_FILE" | awk '{print $2}' || echo "0")
        BLOG_DELETED=$((BLOG_BEFORE - BLOG_AFTER))

        FPC_BEFORE=$(grep "^frontpagecustom" "$BEFORE_FILE" | awk '{print $2}' || echo "0")
        FPC_AFTER=$(grep "^frontpagecustom" "$CURRENT_FILE" | awk '{print $2}' || echo "0")
        FPC_DELETED=$((FPC_BEFORE - FPC_AFTER))

        echo "blog:"
        echo "  Before: $BLOG_BEFORE nodes"
        echo "  After:  $BLOG_AFTER nodes"
        echo "  Deleted: $BLOG_DELETED nodes"
        echo ""
        echo "frontpagecustom:"
        echo "  Before: $FPC_BEFORE nodes"
        echo "  After:  $FPC_AFTER nodes"
        echo "  Deleted: $FPC_DELETED nodes"
        echo ""
        echo "TOTAL DELETED: $((BLOG_DELETED + FPC_DELETED)) nodes"

    } > "$COMPARISON_FILE"

    echo ""
    echo "=========================================="
    echo "REPORT GENERATED"
    echo "=========================================="
    echo ""
    cat "$COMPARISON_FILE"
    echo ""
    echo "Full report saved to: $COMPARISON_FILE"

else
    echo ""
    echo "Step 2: SKIPPED (no production backup provided)"
    echo "------------------------------------------------"
    echo ""
    echo "To compare with production data, run:"
    echo "  ./scripts/verify-node-counts.sh path/to/production-backup.sql"
fi

echo ""
echo "=========================================="
echo "VERIFICATION COMPLETE"
echo "=========================================="
echo ""
echo "Reports saved to: $REPORT_DIR/"
ls -lh "$REPORT_DIR/"
echo ""
