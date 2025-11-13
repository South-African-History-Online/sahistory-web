#!/bin/bash
#
# Debug Product Display Issues
# Checks why metadata and images aren't showing on production
#
# Usage: bash debug-product-display.sh [product_id]
#

SHOP_URI="https://shop.sahistory.org.za"
PRODUCT_ID=${1:-2}  # Default to product 2 if not specified

# Detect if we're in DDEV
if command -v ddev &> /dev/null && [ -f .ddev/config.yaml ]; then
    DRUSH="ddev drush"
else
    DRUSH="drush"
fi

echo "=========================================="
echo "Product Display Diagnostic"
echo "Product ID: $PRODUCT_ID"
echo "Using: $DRUSH"
echo "=========================================="
echo ""

echo "Step 1: Check if product exists and loads..."
$DRUSH --uri=$SHOP_URI ev "
  \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load($PRODUCT_ID);
  if (!\$product) {
    echo 'Product not found!' . PHP_EOL;
    exit(1);
  }
  echo 'Product exists: ' . \$product->label() . PHP_EOL;
  echo 'Type: ' . \$product->bundle() . PHP_EOL;
  echo '' . PHP_EOL;
"

echo "Step 2: Check field display configuration for 'full' view mode..."
$DRUSH --uri=$SHOP_URI config:get core.entity_view_display.commerce_product.publication.full | grep -A 2 "field_"

echo ""
echo "Step 3: Check what's in the content array when rendering..."
$DRUSH --uri=$SHOP_URI ev "
  \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load($PRODUCT_ID);
  \$view_builder = \Drupal::entityTypeManager()->getViewBuilder('commerce_product');
  \$build = \$view_builder->view(\$product, 'full');

  echo 'Fields in content array:' . PHP_EOL;
  echo '---' . PHP_EOL;

  foreach (\$build as \$key => \$value) {
    if (strpos(\$key, 'field_') === 0 || \$key === 'body') {
      if (is_array(\$value) && isset(\$value['#field_name'])) {
        \$empty = empty(\$value['#items']) || \$value['#items']->isEmpty();
        echo sprintf('%-30s %s', \$key . ':', \$empty ? 'EMPTY' : 'HAS_DATA') . PHP_EOL;

        // Show first value for debugging
        if (!\$empty && isset(\$value[0])) {
          if (isset(\$value[0]['#markup'])) {
            echo '  Value: ' . substr(strip_tags(\$value[0]['#markup']), 0, 50) . '...' . PHP_EOL;
          } elseif (isset(\$value[0]['#context']['value'])) {
            echo '  Value: ' . substr(\$value[0]['#context']['value'], 0, 50) . '...' . PHP_EOL;
          }
        }
      }
    }
  }

  echo '' . PHP_EOL;
  echo 'Image field details:' . PHP_EOL;
  if (isset(\$build['field_images'])) {
    echo 'field_images exists in build' . PHP_EOL;
    if (isset(\$build['field_images']['#items'])) {
      echo 'Items count: ' . count(\$build['field_images']['#items']) . PHP_EOL;
    }
    if (isset(\$build['field_images'][0])) {
      echo 'First image render array exists' . PHP_EOL;
    }
  } else {
    echo 'field_images NOT in build array!' . PHP_EOL;
  }
"

echo ""
echo "Step 4: Check field visibility settings..."
$DRUSH --uri=$SHOP_URI ev "
  \$display = \Drupal::entityTypeManager()
    ->getStorage('entity_view_display')
    ->load('commerce_product.publication.full');

  if (!\$display) {
    echo 'Display configuration not found!' . PHP_EOL;
    exit(1);
  }

  echo 'Field display settings:' . PHP_EOL;
  \$components = \$display->getComponents();

  foreach (\$components as \$field_name => \$settings) {
    if (strpos(\$field_name, 'field_') === 0 || \$field_name === 'body') {
      echo sprintf('%-30s ', \$field_name . ':');
      echo 'Type: ' . (\$settings['type'] ?? 'default');
      echo ', Region: ' . (\$settings['region'] ?? 'content');
      echo ', Weight: ' . (\$settings['weight'] ?? 0);
      echo PHP_EOL;
    }
  }

  echo '' . PHP_EOL;
  echo 'Hidden fields:' . PHP_EOL;
  \$hidden = [];
  foreach (['field_author', 'field_editor', 'field_publisher', 'field_images', 'body'] as \$field) {
    if (!isset(\$components[\$field])) {
      \$hidden[] = \$field;
    }
  }
  if (empty(\$hidden)) {
    echo 'No important fields are hidden' . PHP_EOL;
  } else {
    echo 'HIDDEN: ' . implode(', ', \$hidden) . PHP_EOL;
  }
"

echo ""
echo "Step 5: Check actual product field data..."
$DRUSH --uri=$SHOP_URI ev "
  \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load($PRODUCT_ID);

  echo 'Product fields with data:' . PHP_EOL;

  \$fields_to_check = [
    'field_images', 'field_author', 'field_editor', 'field_publisher',
    'field_subtitle', 'field_isbn', 'field_pages', 'field_year',
    'field_categories', 'body'
  ];

  foreach (\$fields_to_check as \$field_name) {
    if (\$product->hasField(\$field_name)) {
      \$field = \$product->get(\$field_name);
      if (!\$field->isEmpty()) {
        echo sprintf('%-30s ', \$field_name . ':');

        if (\$field_name === 'field_images') {
          echo 'HAS ' . count(\$field) . ' image(s)';
          if (!empty(\$field->target_id)) {
            echo ' (File ID: ' . \$field->target_id . ')';
          }
        } elseif (\$field_name === 'body') {
          echo substr(strip_tags(\$field->value), 0, 50) . '...';
        } else {
          echo \$field->value ?? 'Entity reference';
        }
        echo PHP_EOL;
      } else {
        echo sprintf('%-30s EMPTY', \$field_name . ':') . PHP_EOL;
      }
    }
  }
"

echo ""
echo "Step 6: Check image file accessibility..."
$DRUSH --uri=$SHOP_URI ev "
  \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load($PRODUCT_ID);

  if (\$product->hasField('field_images') && !\$product->get('field_images')->isEmpty()) {
    \$images = \$product->get('field_images');
    \$file = \$images->entity;

    if (\$file) {
      echo 'Image file details:' . PHP_EOL;
      echo 'File ID: ' . \$file->id() . PHP_EOL;
      echo 'URI: ' . \$file->getFileUri() . PHP_EOL;
      echo 'URL: ' . \$file->createFileUrl() . PHP_EOL;

      \$path = \Drupal::service('file_system')->realpath(\$file->getFileUri());
      echo 'Physical path: ' . \$path . PHP_EOL;

      if (file_exists(\$path)) {
        echo 'File exists: YES' . PHP_EOL;
        echo 'File size: ' . filesize(\$path) . ' bytes' . PHP_EOL;
        echo 'Permissions: ' . substr(sprintf('%o', fileperms(\$path)), -4) . PHP_EOL;
      } else {
        echo 'File exists: NO - FILE MISSING!' . PHP_EOL;
      }
    } else {
      echo 'Image entity not loaded!' . PHP_EOL;
    }
  } else {
    echo 'Product has no images' . PHP_EOL;
  }
"

echo ""
echo "Step 7: Test rendering with error catching..."
$DRUSH --uri=$SHOP_URI ev "
  try {
    \$product = \Drupal::entityTypeManager()->getStorage('commerce_product')->load($PRODUCT_ID);
    \$view_builder = \Drupal::entityTypeManager()->getViewBuilder('commerce_product');
    \$build = \$view_builder->view(\$product, 'full');
    \$renderer = \Drupal::service('renderer');

    // Try to render
    \$html = \$renderer->renderRoot(\$build);

    echo 'Rendering: SUCCESS' . PHP_EOL;
    echo 'HTML length: ' . strlen(\$html) . ' characters' . PHP_EOL;

    // Check if metadata appears in HTML
    if (strpos(\$html, 'product-meta') !== false) {
      echo 'Metadata section: FOUND' . PHP_EOL;
    } else {
      echo 'Metadata section: MISSING FROM HTML!' . PHP_EOL;
    }

    if (strpos(\$html, 'commerce-product__gallery') !== false) {
      echo 'Gallery section: FOUND' . PHP_EOL;
    } else {
      echo 'Gallery section: MISSING FROM HTML!' . PHP_EOL;
    }

  } catch (\Exception \$e) {
    echo 'Rendering FAILED: ' . \$e->getMessage() . PHP_EOL;
  }
"

echo ""
echo "=========================================="
echo "Diagnostic Complete"
echo "=========================================="
