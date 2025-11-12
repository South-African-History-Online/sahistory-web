#!/bin/bash
#
# Force Clear Twig Cache on Production
# Use this when Twig templates are not updating after code deployment
#
# Usage: bash force-clear-twig-cache.sh
#

set -e

SHOP_URI="https://shop.sahistory.org.za"
TWIG_CACHE="sites/shop.sahistory.org.za/files/php/twig"

echo "=========================================="
echo "Force Clear Twig Cache"
echo "=========================================="
echo ""

# Step 1: Check if Twig cache directory exists
echo "Step 1: Checking Twig cache directory..."
if [ -d "$TWIG_CACHE" ]; then
    SIZE=$(du -sh "$TWIG_CACHE" | cut -f1)
    COUNT=$(find "$TWIG_CACHE" -type f | wc -l)
    echo "✓ Twig cache exists: $TWIG_CACHE"
    echo "  Size: $SIZE"
    echo "  Files: $COUNT compiled templates"
else
    echo "⚠ Twig cache directory doesn't exist"
    echo "  (This is OK if Twig hasn't compiled any templates yet)"
fi
echo ""

# Step 2: Backup old cache (just in case)
echo "Step 2: Creating backup of current cache..."
if [ -d "$TWIG_CACHE" ]; then
    BACKUP_DIR="${TWIG_CACHE}_backup_$(date +%Y%m%d_%H%M%S)"
    echo "Backing up to: $BACKUP_DIR"
    cp -r "$TWIG_CACHE" "$BACKUP_DIR"
    echo "✓ Backup created"
else
    echo "Skipped (no cache to backup)"
fi
echo ""

# Step 3: Delete ALL compiled Twig files
echo "Step 3: Deleting ALL compiled Twig files..."
read -p "Continue with deletion? [y/N] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if [ -d "$TWIG_CACHE" ]; then
        echo "Deleting: $TWIG_CACHE/*"
        rm -rf "${TWIG_CACHE}/"*
        echo "✓ All Twig cache files deleted"
    else
        echo "Nothing to delete"
    fi
else
    echo "Cancelled"
    exit 0
fi
echo ""

# Step 4: Disable Twig cache temporarily
echo "Step 4: Temporarily disabling Twig cache..."
drush --uri=$SHOP_URI config:set system.performance twig.config debug true --yes
drush --uri=$SHOP_URI config:set system.performance twig.config auto_reload true --yes
drush --uri=$SHOP_URI config:set system.performance twig.config cache false --yes
echo "✓ Twig caching disabled"
echo ""

# Step 5: Clear Drupal caches
echo "Step 5: Clearing all Drupal caches..."
drush --uri=$SHOP_URI cache:rebuild
echo "✓ Drupal caches cleared"
echo ""

# Step 6: Truncate cache tables
echo "Step 6: Truncating cache tables in database..."
read -p "Truncate cache tables? (Nuclear option) [y/N] " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    drush --uri=$SHOP_URI sqlq "TRUNCATE cache_bootstrap;"
    drush --uri=$SHOP_URI sqlq "TRUNCATE cache_render;"
    drush --uri=$SHOP_URI sqlq "TRUNCATE cache_page;"
    drush --uri=$SHOP_URI sqlq "TRUNCATE cache_dynamic_page_cache;"
    echo "✓ Cache tables truncated"
else
    echo "Skipped cache table truncation"
fi
echo ""

# Step 7: Test a product page
echo "Step 7: Testing product page..."
echo "Testing: $SHOP_URI/product/kora"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$SHOP_URI/product/kora")

if [ "$HTTP_CODE" = "200" ]; then
    echo "✓ Product page returns 200 OK"
    echo "  New Twig templates will be compiled on this request"
elif [ "$HTTP_CODE" = "500" ]; then
    echo "✗ Product page still returns 500 error!"
    echo "  Check Drupal logs for details:"
    echo "  drush --uri=$SHOP_URI watchdog:show --severity=Error --count=5"
else
    echo "⚠ Product page returns HTTP $HTTP_CODE"
fi
echo ""

# Step 8: Re-enable Twig cache for production
echo "Step 8: Re-enabling Twig cache for production..."
read -p "Re-enable Twig cache now? [Y/n] " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    drush --uri=$SHOP_URI config:set system.performance twig.config debug false --yes
    drush --uri=$SHOP_URI config:set system.performance twig.config auto_reload false --yes
    drush --uri=$SHOP_URI config:set system.performance twig.config cache true --yes
    drush --uri=$SHOP_URI cache:rebuild
    echo "✓ Twig caching re-enabled"
else
    echo "⚠ Twig cache still disabled!"
    echo "  Remember to re-enable for production performance"
fi
echo ""

# Summary
echo "=========================================="
echo "Cache Clear Complete"
echo "=========================================="
echo ""
echo "What happened:"
echo "1. ✓ Old Twig cache backed up"
echo "2. ✓ All compiled Twig files deleted"
echo "3. ✓ Twig cache temporarily disabled"
echo "4. ✓ Drupal caches cleared"
echo "5. ✓ Twig cache re-enabled"
echo ""
echo "Next steps:"
echo "1. Visit: $SHOP_URI/product/kora"
echo "2. Check if page loads without 500 error"
echo "3. If still failing, check logs:"
echo "   drush --uri=$SHOP_URI watchdog:show --count=20"
echo ""
echo "If product page loads successfully:"
echo "- New Twig template is now in use ✓"
echo "- Visit other product pages to compile their caches"
echo ""
