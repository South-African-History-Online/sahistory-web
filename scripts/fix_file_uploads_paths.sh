#!/bin/bash
# Fix broken file paths in node body fields
# Issue: HTML contains '/sites/default/files/file uploads /' (with trailing space)
# Should be: '/sites/default/files/archive-files/' for archive nodes
#            '/sites/default/files/file uploads/' for place/article nodes
#
# Affected: 109 nodes total
#   - 70 archive nodes
#   - 35 place nodes
#   - 4 article nodes
#
# Date: 2025-10-21
# Created by: Claude Code

set -e

# Detect environment and set drush command
if command -v ddev &> /dev/null && [ -f ".ddev/config.yaml" ]; then
    DRUSH="ddev drush"
    ENV="local (DDEV)"
elif [ -f "vendor/bin/drush" ]; then
    DRUSH="./vendor/bin/drush"
    ENV="staging/production"
else
    echo "Error: Cannot find drush. Please run from project root."
    exit 1
fi

echo "========================================"
echo "Fix File Uploads Path Issue"
echo "========================================"
echo "Environment: $ENV"
echo "Drush command: $DRUSH"
echo ""

# Check current state
echo "Step 1: Checking current broken paths..."
BROKEN_COUNT=$($DRUSH sqlq "SELECT COUNT(*) FROM node__body WHERE body_value LIKE '%/sites/default/files/file uploads /%'" 2>/dev/null | grep -o '[0-9]\+' | head -1)
echo "Found $BROKEN_COUNT nodes with broken paths"
echo ""

if [ -z "$BROKEN_COUNT" ] || [ "$BROKEN_COUNT" -eq 0 ]; then
    echo "No broken paths found. Nothing to fix!"
    exit 0
fi

# Show breakdown by content type
echo "Step 2: Breakdown by content type..."
$DRUSH sqlq "SELECT bundle, COUNT(*) as count FROM node__body WHERE body_value LIKE '%/sites/default/files/file uploads /%' GROUP BY bundle"
echo ""

# Ask for confirmation
read -p "Do you want to create a backup before proceeding? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Step 3: Creating backup tables..."
    $DRUSH sqlq "DROP TABLE IF EXISTS node__body_backup_20251021"
    $DRUSH sqlq "CREATE TABLE node__body_backup_20251021 AS SELECT * FROM node__body WHERE body_value LIKE '%/sites/default/files/file uploads /%'" 2>&1 | grep -v "^$"

    $DRUSH sqlq "DROP TABLE IF EXISTS node_revision__body_backup_20251021" 2>&1 | grep -v "^$"
    $DRUSH sqlq "CREATE TABLE node_revision__body_backup_20251021 AS SELECT * FROM node_revision__body WHERE body_value LIKE '%/sites/default/files/file uploads /%'" 2>&1 | grep -v "^$"

    BACKUP_COUNT=$($DRUSH sqlq "SELECT COUNT(*) FROM node__body_backup_20251021" 2>/dev/null | grep -o '[0-9]\+' | head -1)
    echo "Backed up $BACKUP_COUNT rows"
    echo ""
fi

read -p "Proceed with fixing the paths? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 1
fi

echo "Step 4: Fixing paths..."

# Fix archive nodes: replace 'file uploads ' with 'archive-files'
echo "  - Fixing archive nodes..."
$DRUSH sqlq "UPDATE node__body SET body_value = REPLACE(body_value, '/sites/default/files/file uploads /', '/sites/default/files/archive-files/') WHERE bundle = 'archive' AND body_value LIKE '%/sites/default/files/file uploads /%'"

$DRUSH sqlq "UPDATE node_revision__body SET body_value = REPLACE(body_value, '/sites/default/files/file uploads /', '/sites/default/files/archive-files/') WHERE bundle = 'archive' AND body_value LIKE '%/sites/default/files/file uploads /%'"

# Fix place nodes: replace 'file uploads ' with 'file uploads' (remove trailing space)
echo "  - Fixing place nodes..."
$DRUSH sqlq "UPDATE node__body SET body_value = REPLACE(body_value, '/sites/default/files/file uploads /', '/sites/default/files/file uploads/') WHERE bundle = 'place' AND body_value LIKE '%/sites/default/files/file uploads /%'"

$DRUSH sqlq "UPDATE node_revision__body SET body_value = REPLACE(body_value, '/sites/default/files/file uploads /', '/sites/default/files/file uploads/') WHERE bundle = 'place' AND body_value LIKE '%/sites/default/files/file uploads /%'"

# Fix article nodes: replace 'file uploads ' with 'file uploads' (remove trailing space)
echo "  - Fixing article nodes..."
$DRUSH sqlq "UPDATE node__body SET body_value = REPLACE(body_value, '/sites/default/files/file uploads /', '/sites/default/files/file uploads/') WHERE bundle = 'article' AND body_value LIKE '%/sites/default/files/file uploads /%'"

$DRUSH sqlq "UPDATE node_revision__body SET body_value = REPLACE(body_value, '/sites/default/files/file uploads /', '/sites/default/files/file uploads/') WHERE bundle = 'article' AND body_value LIKE '%/sites/default/files/file uploads /%'"

echo ""
echo "Step 5: Verifying fixes..."
echo ""

# Verify the fixes
echo "Archive nodes now using archive-files:"
$DRUSH sqlq "SELECT COUNT(DISTINCT entity_id) as count FROM node__body WHERE bundle = 'archive' AND body_value LIKE '%/sites/default/files/archive-files/%'"

echo ""
echo "Place nodes now using file uploads (no space):"
$DRUSH sqlq "SELECT COUNT(DISTINCT entity_id) as count FROM node__body WHERE bundle = 'place' AND body_value LIKE '%/sites/default/files/file uploads/%'"

echo ""
echo "Article nodes now using file uploads (no space):"
$DRUSH sqlq "SELECT COUNT(DISTINCT entity_id) as count FROM node__body WHERE bundle = 'article' AND body_value LIKE '%/sites/default/files/file uploads/%'"

echo ""
echo "Remaining broken paths (should be 0):"
REMAINING=$($DRUSH sqlq "SELECT COUNT(*) FROM node__body WHERE body_value LIKE '%/sites/default/files/file uploads /%'" 2>/dev/null | grep -o '[0-9]\+' | head -1)
echo "$REMAINING"

echo ""
if [ "$REMAINING" -eq 0 ]; then
    echo "✓ SUCCESS! All paths fixed."
else
    echo "✗ WARNING: $REMAINING broken paths remain."
fi

echo ""
echo "Step 6: Clearing cache..."
$DRUSH cr

echo ""
echo "========================================"
echo "COMPLETE!"
echo "========================================"
echo ""
echo "Test these nodes in your browser:"
echo "  - Archive: https://sahistory.org.za/node/124417"
echo "  - Place: https://sahistory.org.za/node/65696"
echo "  - Place: https://sahistory.org.za/node/65909"
echo ""
echo "To rollback (if needed):"
if [ "$ENV" = "local (DDEV)" ]; then
    echo "  ddev drush sqlq 'TRUNCATE node__body; INSERT INTO node__body SELECT * FROM node__body_backup_20251021;'"
    echo "  ddev drush sqlq 'TRUNCATE node_revision__body; INSERT INTO node_revision__body SELECT * FROM node_revision__body_backup_20251021;'"
    echo "  ddev drush cr"
else
    echo "  ./vendor/bin/drush sqlq 'TRUNCATE node__body; INSERT INTO node__body SELECT * FROM node__body_backup_20251021;'"
    echo "  ./vendor/bin/drush sqlq 'TRUNCATE node_revision__body; INSERT INTO node_revision__body SELECT * FROM node_revision__body_backup_20251021;'"
    echo "  ./vendor/bin/drush cr"
fi
echo ""
