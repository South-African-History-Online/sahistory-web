#!/bin/bash
# SAHO .htaccess Restoration Script
# Run this after composer install to restore custom configurations

echo "🔧 Restoring SAHO custom .htaccess configurations..."

# Check if webroot/.htaccess exists
if [ ! -f "webroot/.htaccess" ]; then
    echo "❌ webroot/.htaccess not found! Make sure you're in the project root."
    exit 1
fi

# Check if custom config exists
if [ ! -f ".htaccess.custom" ]; then
    echo "❌ .htaccess.custom not found! Run from project root."
    exit 1
fi

# Backup current .htaccess
cp webroot/.htaccess webroot/.htaccess.drupal.backup
echo "✅ Backed up default Drupal .htaccess"

# Find the insertion point (after the webp AddType line)
if grep -q "AddType image/webp .webp" webroot/.htaccess; then
    echo "✅ Found WebP AddType line - inserting custom rules"
    
    # Create temporary file with custom rules inserted
    awk '
    /^# Add correct encoding for webp\./ { print; getline; print; print ""; getline ".htaccess.custom" {print} close(".htaccess.custom"); next }
    { print }
    ' webroot/.htaccess > webroot/.htaccess.temp
    
    # Simple approach: append custom rules after the webp line
    sed '/AddType image\/webp \.webp/r .htaccess.custom' webroot/.htaccess > webroot/.htaccess.temp
    mv webroot/.htaccess.temp webroot/.htaccess
    
    echo "✅ Merged custom .htaccess configurations"
else
    echo "⚠️  WebP AddType line not found - appending custom rules to end"
    echo "" >> webroot/.htaccess
    echo "# SAHO Custom Configurations" >> webroot/.htaccess
    cat .htaccess.custom >> webroot/.htaccess
    echo "✅ Appended custom .htaccess configurations"
fi

echo ""
echo "🎉 .htaccess restoration complete!"
echo ""
echo "📝 What was restored:"
echo "   - WebP auto-serving rules"
echo "   - Enhanced cache headers"
echo "   - Custom redirects for legacy URLs"
echo "   - Performance optimizations"
echo "   - Compression settings"
echo ""
echo "💡 To make this automatic, add to composer.json scripts:"
echo '   "post-install-cmd": ["./restore-htaccess.sh"]'