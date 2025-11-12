<?php

/**
 * @file
 * Generate product_image_mapping.csv for image import.
 *
 * Usage: ddev drush -l shop.ddev.site php:script scripts/generate_image_mapping.php
 */

use Drupal\commerce_product\Entity\Product;

echo "Generating Product Image Mapping CSV\n";
echo str_repeat('=', 70) . "\n\n";

$csv_file = '/var/www/html/product_image_mapping.csv';
$handle = fopen($csv_file, 'w');

// Write header
fputcsv($handle, [
  'old_nid',
  'title',
  'sku',
  'price',
  'image_fid',
  'filename',
  'path',
  'filesize'
]);

// Load all publication products
$product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
$query = $product_storage->getQuery()
  ->condition('type', 'publication')
  ->accessCheck(FALSE)
  ->sort('product_id', 'ASC');

$product_ids = $query->execute();
$products = $product_storage->loadMultiple($product_ids);

$count = 0;
foreach ($products as $product) {
  $title = $product->getTitle();
  $product_id = $product->id();

  // Get SKU and price from first variation
  $variations = $product->getVariations();
  if (empty($variations)) {
    continue;
  }

  $variation = reset($variations);
  $sku = $variation->getSku();
  $price = $variation->getPrice() ? $variation->getPrice()->getNumber() : 0;

  // Generate filename from title
  $filename = 'cover_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($title)) . '.jpg';
  $filename = preg_replace('/_+/', '_', $filename);
  $filename = trim($filename, '_');

  // Write row
  fputcsv($handle, [
    $product_id,
    $title,
    $sku,
    $price,
    'NULL',  // image_fid
    $filename,
    'NULL',  // path
    'NULL'   // filesize
  ]);

  $count++;
  echo "✓ {$count}. {$title} → {$filename}\n";
}

fclose($handle);

echo "\n" . str_repeat('=', 70) . "\n";
echo "CSV Generation Complete!\n";
echo str_repeat('=', 70) . "\n\n";
echo "Generated mapping for {$count} products\n";
echo "Output file: {$csv_file}\n\n";
echo "Next steps:\n";
echo "  1. Copy cover images to covers/ directory\n";
echo "  2. Match image filenames to the generated names\n";
echo "  3. Run: ddev drush -l shop.ddev.site php:script scripts/import_product_images.php\n\n";
