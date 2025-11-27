#!/bin/bash
#
# Diagnose Which Products Are Failing
# Tests all products and identifies patterns
#
# Usage: bash diagnose-failing-products.sh
#

set -e

SHOP_URI="https://shop.sahistory.org.za"

echo "=========================================="
echo "Product Error Diagnostic"
echo "=========================================="
echo ""

# Get list of all product IDs and titles
echo "Getting product list..."
PRODUCTS=$(drush --uri=$SHOP_URI sqlq "
SELECT product_id, title
FROM commerce_product_field_data
WHERE type = 'publication' AND status = 1
ORDER BY product_id;
")

echo "Found products:"
echo "$PRODUCTS"
echo ""
echo "=========================================="
echo "Testing Each Product..."
echo "=========================================="
echo ""

# Arrays to track results
WORKING_PRODUCTS=()
FAILING_PRODUCTS=()

# Test each product
while IFS=$'\t' read -r product_id title; do
    if [ ! -z "$product_id" ] && [ "$product_id" != "product_id" ]; then
        echo -n "Testing Product $product_id: ${title:0:50}... "

        # Test the product URL
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$SHOP_URI/product/$product_id" 2>/dev/null || echo "000")

        if [ "$HTTP_CODE" = "200" ]; then
            echo "✓ OK ($HTTP_CODE)"
            WORKING_PRODUCTS+=("$product_id|$title")
        elif [ "$HTTP_CODE" = "500" ]; then
            echo "✗ FAIL ($HTTP_CODE)"
            FAILING_PRODUCTS+=("$product_id|$title")
        else
            echo "? UNKNOWN ($HTTP_CODE)"
        fi
    fi
done <<< "$PRODUCTS"

echo ""
echo "=========================================="
echo "Results Summary"
echo "=========================================="
echo ""

WORKING_COUNT=${#WORKING_PRODUCTS[@]}
FAILING_COUNT=${#FAILING_PRODUCTS[@]}
TOTAL=$((WORKING_COUNT + FAILING_COUNT))

echo "Working: $WORKING_COUNT / $TOTAL"
echo "Failing: $FAILING_COUNT / $TOTAL"
echo ""

if [ $FAILING_COUNT -gt 0 ]; then
    echo "=========================================="
    echo "FAILING PRODUCTS:"
    echo "=========================================="
    for product in "${FAILING_PRODUCTS[@]}"; do
        IFS='|' read -r id title <<< "$product"
        echo "  Product $id: $title"
        echo "    URL: $SHOP_URI/product/$id"
    done
    echo ""

    echo "=========================================="
    echo "Analyzing First Failing Product..."
    echo "=========================================="

    # Get first failing product
    FIRST_FAIL="${FAILING_PRODUCTS[0]}"
    IFS='|' read -r fail_id fail_title <<< "$FIRST_FAIL"

    echo "Product ID: $fail_id"
    echo "Title: $fail_title"
    echo ""

    echo "Getting detailed error..."
    drush --uri=$SHOP_URI ev "
      try {
        \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load($fail_id);
        if (!\$product) {
          echo 'Product not found!' . PHP_EOL;
          exit;
        }
        echo 'Product loaded successfully' . PHP_EOL;
        echo 'Title: ' . \$product->label() . PHP_EOL;
        echo 'Type: ' . \$product->bundle() . PHP_EOL;
        echo '' . PHP_EOL;

        echo 'Attempting to render...' . PHP_EOL;
        \$view_builder = \Drupal::entityTypeManager()->getViewBuilder('commerce_product');
        \$build = \$view_builder->view(\$product, 'full');
        \$renderer = \Drupal::service('renderer');
        \$html = \$renderer->renderRoot(\$build);
        echo 'SUCCESS! Product renders correctly.' . PHP_EOL;
      } catch (\Exception \$e) {
        echo 'ERROR FOUND:' . PHP_EOL;
        echo 'Message: ' . \$e->getMessage() . PHP_EOL;
        echo 'Line: ' . \$e->getLine() . PHP_EOL;
        echo 'File: ' . \$e->getFile() . PHP_EOL;
        echo '' . PHP_EOL;
        echo 'Stack trace:' . PHP_EOL;
        echo \$e->getTraceAsString() . PHP_EOL;
      }
    " 2>&1

    echo ""
    echo "=========================================="
    echo "Checking Field Data..."
    echo "=========================================="

    echo "Fields on failing product $fail_id:"
    drush --uri=$SHOP_URI ev "
      \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load($fail_id);
      if (\$product) {
        foreach (\$product->getFields() as \$field_name => \$field) {
          if (strpos(\$field_name, 'field_') === 0) {
            \$value = 'EMPTY';
            if (!\$field->isEmpty()) {
              \$value = 'HAS_DATA';
              // Check if it's an entity reference
              if (method_exists(\$field, 'entity')) {
                \$entity = \$field->entity;
                if (\$entity) {
                  \$value .= ' (Entity: ' . get_class(\$entity) . ')';
                }
              }
            }
            echo sprintf('%-30s %s', \$field_name . ':', \$value) . PHP_EOL;
          }
        }
      }
    "

    echo ""
    echo "=========================================="
    echo "Comparing with Working Product..."
    echo "=========================================="

    if [ $WORKING_COUNT -gt 0 ]; then
        FIRST_WORK="${WORKING_PRODUCTS[0]}"
        IFS='|' read -r work_id work_title <<< "$FIRST_WORK"

        echo "Working product $work_id for comparison:"
        drush --uri=$SHOP_URI ev "
          \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load($work_id);
          if (\$product) {
            foreach (\$product->getFields() as \$field_name => \$field) {
              if (strpos(\$field_name, 'field_') === 0) {
                \$value = 'EMPTY';
                if (!\$field->isEmpty()) {
                  \$value = 'HAS_DATA';
                  if (method_exists(\$field, 'entity')) {
                    \$entity = \$field->entity;
                    if (\$entity) {
                      \$value .= ' (Entity: ' . get_class(\$entity) . ')';
                    }
                  }
                }
                echo sprintf('%-30s %s', \$field_name . ':', \$value) . PHP_EOL;
              }
            }
          }
        "
    fi

else
    echo "🎉 All products working!"
fi

echo ""
echo "=========================================="
echo "Recommendations"
echo "=========================================="
echo ""

if [ $FAILING_COUNT -gt 0 ]; then
    echo "1. Check the detailed error above"
    echo "2. Look for differences in field data between working and failing"
    echo "3. Check if Twig cache was properly cleared:"
    echo "   ls sites/shop.sahistory.org.za/files/php/twig/"
    echo "4. Enable Twig debug to see which template is used:"
    echo "   drush --uri=$SHOP_URI config:set system.performance twig.config debug true"
    echo "5. Check Drupal logs:"
    echo "   drush --uri=$SHOP_URI watchdog:show --severity=Error --count=10"
    echo ""
    echo "Quick fixes to try:"
    echo "  rm -rf sites/shop.sahistory.org.za/files/php/twig/*"
    echo "  drush --uri=$SHOP_URI cr"
else
    echo "All products are working correctly!"
    echo "If you're still seeing errors:"
    echo "1. Hard refresh browser (Ctrl+Shift+R)"
    echo "2. Clear browser cache"
    echo "3. Check if you're testing the right URL"
fi

echo ""
