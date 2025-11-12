<?php

/**
 * @file
 * Bulk import product images for publications.
 *
 * This script imports cover images from a directory and attaches them
 * to Commerce products based on the product_image_mapping.csv file.
 *
 * Prerequisites:
 * 1. Copy all cover images to: webroot/sites/shop.sahistory.org.za/files/product-covers/
 * 2. Ensure product_image_mapping.csv exists in project root
 *
 * Usage: ddev drush -l shop.ddev.site php:script scripts/import_product_images.php
 */

use Drupal\commerce_product\Entity\Product;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

echo "Publication Cover Image Import\n";
echo str_repeat('=', 70) . "\n\n";

// Configuration
$csv_file = '/var/www/html/product_image_mapping.csv';
$source_dir = '/var/www/html/covers/';  // Where you copy your images
$dest_dir = 'public://product-covers/';  // Where Drupal will store them

// Check if CSV exists
if (!file_exists($csv_file)) {
  echo "ERROR: Mapping file not found: $csv_file\n";
  echo "Please ensure product_image_mapping.csv is in the project root.\n";
  exit(1);
}

// Check if source directory exists
if (!is_dir($source_dir)) {
  echo "ERROR: Source directory not found: $source_dir\n";
  echo "Please create it and copy your cover images there.\n";
  echo "\nRun this command:\n";
  echo "  mkdir -p covers/\n";
  echo "  cp /path/to/your/covers/* covers/\n";
  exit(1);
}

// Create destination directory if it doesn't exist
$file_system = \Drupal::service('file_system');
$dest_path = $file_system->realpath($dest_dir);
if (!$dest_path) {
  mkdir(\Drupal::service('file_system')->realpath('public://') . '/product-covers', 0775, TRUE);
  echo "✓ Created destination directory: $dest_dir\n\n";
}

// Load CSV mapping
echo "Loading image mapping from CSV...\n";
$handle = fopen($csv_file, 'r');
$header = fgetcsv($handle);  // Skip header row
$mapping = [];

while (($row = fgetcsv($handle)) !== FALSE) {
  $mapping[] = [
    'old_nid' => $row[0],
    'title' => $row[1],
    'sku' => $row[2],
    'price' => $row[3],
    'image_fid' => $row[4],
    'filename' => $row[5],
    'path' => $row[6],
    'filesize' => $row[7],
  ];
}
fclose($handle);

echo "✓ Loaded " . count($mapping) . " product mappings\n\n";

// Statistics
$imported = 0;
$skipped = 0;
$errors = 0;

echo "Starting image import...\n";
echo str_repeat('-', 70) . "\n\n";

foreach ($mapping as $item) {
  // Skip if no filename
  if (empty($item['filename']) || $item['filename'] == 'NULL') {
    echo "⊘ Skipping '{$item['title']}' (SKU: {$item['sku']}) - No image in old database\n";
    $skipped++;
    continue;
  }

  try {
    // Find product by SKU
    $variation_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
    $variations = $variation_storage->loadByProperties(['sku' => $item['sku']]);

    if (empty($variations)) {
      echo "⊘ No product found with SKU: {$item['sku']} ({$item['title']})\n";
      $skipped++;
      continue;
    }

    $variation = reset($variations);
    $product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
    $products = $product_storage->loadByProperties(['variations' => $variation->id()]);

    if (empty($products)) {
      echo "⊘ No product found for variation SKU: {$item['sku']}\n";
      $skipped++;
      continue;
    }

    $product = reset($products);

    // Check if product already has images
    if ($product->hasField('field_images') && !$product->get('field_images')->isEmpty()) {
      echo "⊘ Skipping '{$item['title']}' - Already has images\n";
      $skipped++;
      continue;
    }

    // Check if source file exists
    $source_file = $source_dir . $item['filename'];
    if (!file_exists($source_file)) {
      echo "✗ Image file not found: {$item['filename']} for '{$item['title']}'\n";
      $errors++;
      continue;
    }

    // Copy file to Drupal's managed files
    $destination = $dest_dir . $item['filename'];
    $file_data = file_get_contents($source_file);

    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $file = $file_repository->writeData($file_data, $destination, \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);

    if ($file) {
      // Attach to product
      $product->set('field_images', [
        'target_id' => $file->id(),
        'alt' => $product->getTitle() . ' cover',
        'title' => $product->getTitle(),
      ]);
      $product->save();

      echo "✓ Imported: {$item['filename']} → '{$item['title']}' (SKU: {$item['sku']})\n";
      $imported++;
    }
    else {
      echo "✗ Failed to save file: {$item['filename']}\n";
      $errors++;
    }

  }
  catch (\Exception $e) {
    echo "✗ Error processing '{$item['title']}': " . $e->getMessage() . "\n";
    $errors++;
  }
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "Image Import Complete!\n";
echo str_repeat('=', 70) . "\n\n";

echo "Results:\n";
echo "  ✓ Imported: $imported images\n";
echo "  ⊘ Skipped:  $skipped products\n";
echo "  ✗ Errors:   $errors\n\n";

if ($imported > 0) {
  echo "Successfully imported images for $imported products!\n";
  echo "View your products: https://shop.ddev.site/admin/commerce/products\n\n";
}

if ($errors > 0) {
  echo "⚠ There were $errors errors. Please review the output above.\n\n";
}

if ($skipped > 0) {
  echo "ℹ Skipped $skipped products (no image or already has image)\n\n";
}

echo "Next steps:\n";
echo "  1. Review products: /admin/commerce/products\n";
echo "  2. Configure image display: /admin/commerce/config/product-types/publication/edit/display\n";
echo "  3. Clear cache: ddev drush cr\n";
