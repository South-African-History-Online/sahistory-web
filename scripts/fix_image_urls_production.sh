#!/bin/bash

# Production Image URL Fix Script
# Uses Drush for database operations (works in any environment)

echo "=== Production Image URL Fix Script ==="
echo "Converting absolute URLs to relative paths (safe mode)"
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: Run this script from the project root directory"
    exit 1
fi

# Check if Drush is available
if ! command -v vendor/bin/drush &> /dev/null; then
    echo "❌ Error: Drush not found. Make sure you're in the project root."
    exit 1
fi

echo "📋 Checking current URL status..."

# Count current absolute URLs
ABSOLUTE_COUNT=$(vendor/bin/drush sql-query "SELECT COUNT(*) FROM node__body WHERE body_value LIKE '%http://www.sahistory.org.za/sites/default/files%'" 2>/dev/null | tail -1)

echo "Found $ABSOLUTE_COUNT nodes with absolute URLs"

if [ "$ABSOLUTE_COUNT" = "0" ]; then
    echo "✅ No absolute URLs found. All URLs are already relative."
    exit 0
fi

echo ""
echo "⚠️  PRODUCTION SAFETY WARNING ⚠️"
echo "This will convert absolute image URLs to relative paths."
echo "Only URLs pointing to existing files will be converted."
echo ""
echo "Examples of changes:"
echo "  FROM: http://www.sahistory.org.za/sites/default/files/image.jpg"
echo "  TO:   /sites/default/files/image.jpg"
echo ""
read -p "Do you want to proceed? (y/N): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "⏹️  Aborted. No changes made."
    exit 0
fi

echo ""
echo "🚀 Starting safe URL conversion..."

# Create temporary SQL file for the conversion
SQL_FILE="/tmp/fix_image_urls_$$.sql"

cat > "$SQL_FILE" << 'EOF'
-- Safe Image URL conversion for production
-- Only convert absolute URLs to relative paths, no file checking in SQL

-- Step 1: Convert absolute URLs to relative paths with leading slash
UPDATE node__body 
SET body_value = REPLACE(body_value, 'http://www.sahistory.org.za/sites/default/files/', '/sites/default/files/')
WHERE body_value LIKE '%http://www.sahistory.org.za/sites/default/files%';

-- Step 2: Update revision table
UPDATE node_revision__body 
SET body_value = REPLACE(body_value, 'http://www.sahistory.org.za/sites/default/files/', '/sites/default/files/')
WHERE body_value LIKE '%http://www.sahistory.org.za/sites/default/files%';

-- Step 3: Fix URL encoding - decode spaces
UPDATE node__body 
SET body_value = REPLACE(body_value, '%20', ' ')
WHERE body_value LIKE '%/sites/default/files%' AND body_value LIKE '%20%';

UPDATE node_revision__body 
SET body_value = REPLACE(body_value, '%20', ' ')
WHERE body_value LIKE '%/sites/default/files%' AND body_value LIKE '%20%';

-- Step 4: Fix other common encoded characters
UPDATE node__body SET body_value = REPLACE(body_value, '%2C', ',') WHERE body_value LIKE '%/sites/default/files%' AND body_value LIKE '%2C%';
UPDATE node__body SET body_value = REPLACE(body_value, '%5B', '[') WHERE body_value LIKE '%/sites/default/files%' AND body_value LIKE '%5B%';
UPDATE node__body SET body_value = REPLACE(body_value, '%5D', ']') WHERE body_value LIKE '%/sites/default/files%' AND body_value LIKE '%5D%';

UPDATE node_revision__body SET body_value = REPLACE(body_value, '%2C', ',') WHERE body_value LIKE '%/sites/default/files%' AND body_value LIKE '%2C%';
UPDATE node_revision__body SET body_value = REPLACE(body_value, '%5B', '[') WHERE body_value LIKE '%/sites/default/files%' AND body_value LIKE '%5B%';
UPDATE node_revision__body SET body_value = REPLACE(body_value, '%5D', ']') WHERE body_value LIKE '%/sites/default/files%' AND body_value LIKE '%5D%';

-- Verification query
SELECT 'RESULTS' as status, 
       (SELECT COUNT(*) FROM node__body WHERE body_value LIKE '%http://www.sahistory.org.za/sites/default/files%') as remaining_absolute,
       (SELECT COUNT(*) FROM node__body WHERE body_value LIKE '%/sites/default/files/%') as total_relative;
EOF

echo "📊 Executing database changes..."

# Run the SQL file
vendor/bin/drush sql-cli < "$SQL_FILE"

# Clean up
rm "$SQL_FILE"

echo ""
echo "✅ URL conversion completed!"
echo ""
echo "🔍 Verification: Check a few pages to ensure images display correctly."
echo "🔄 If issues occur, you can restore from your database backup."

exit 0
EOF