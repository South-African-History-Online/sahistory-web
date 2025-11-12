<?php

/**
 * @file
 * Match existing cover images to products and update CSV mapping.
 *
 * Usage: ddev drush -l shop.ddev.site php:script scripts/match_and_rename_images.php
 */

echo "Matching Existing Cover Images to Products\n";
echo str_repeat('=', 70) . "\n\n";

$covers_dir = '/var/www/html/covers/';
$csv_file = '/var/www/html/product_image_mapping.csv';

// Get all existing cover images
$existing_images = [];
$files = scandir($covers_dir);
foreach ($files as $file) {
  if (preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
    $existing_images[] = $file;
  }
}

echo "Found " . count($existing_images) . " images in covers/ directory\n\n";

// Load products
$product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
$query = $product_storage->getQuery()
  ->condition('type', 'publication')
  ->accessCheck(FALSE)
  ->sort('product_id', 'ASC');

$product_ids = $query->execute();
$products = $product_storage->loadMultiple($product_ids);

// Create mapping
$matched = 0;
$unmatched = 0;
$csv_data = [];
$csv_data[] = ['old_nid', 'title', 'sku', 'price', 'image_fid', 'filename', 'path', 'filesize'];

foreach ($products as $product) {
  $title = $product->getTitle();
  $product_id = $product->id();

  // Get SKU and price
  $variations = $product->getVariations();
  if (empty($variations)) {
    continue;
  }

  $variation = reset($variations);
  $sku = $variation->getSku();
  $price = $variation->getPrice() ? $variation->getPrice()->getNumber() : 0;

  // Try to match to existing image
  $matched_file = NULL;

  // Create normalized title for matching
  $norm_title = strtolower(preg_replace('/[^a-z0-9]+/', '_', $title));
  $norm_title = trim($norm_title, '_');

  foreach ($existing_images as $image) {
    $norm_image = strtolower(str_replace(['cover_', 'bookcover_', '.jpg', '.jpeg', '.png'], '', $image));

    // Try various matching strategies
    if (
      // Direct match
      strpos($image, $norm_title) !== false ||
      // Partial title match
      strpos($norm_image, substr($norm_title, 0, 15)) !== false ||
      // Specific known mappings
      ($title == 'Africa in Today\'s World' && $image == 'cover_africa_in_todays_world.jpg') ||
      ($title == 'Amulets & Dreams' && $image == 'cover_amulets_dreams.jpg') ||
      ($title == 'Better to die on one\'s feet' && $image == 'cover_better_to_die_on_ones_feet.jpg') ||
      ($title == 'Bonani Africa 2010 Catalogue' && $image == 'cover_bonani.jpg') ||
      ($title == 'Cape Flats Details' && $image == 'cover_cape_flats_details.jpg') ||
      ($title == 'Collected Poems' && ($image == 'bookcover_mafika_gwala_collected_poems.jpg' || $image == 'bookcover_qabula_collected_poems.jpg')) ||
      ($title == 'Community Based Public Works Programme' && $image == 'cover_community_based_public_work.jpg') ||
      ($title == 'Culture in the New South Africa' && $image == 'cover_cultureinnewsa.jpg') ||
      ($title == 'Imperial Ghetto' && $image == 'cover_imperial-ghetto.jpg') ||
      ($title == 'Kali Pani' && $image == 'cover_kalapani.jpg') ||
      ($title == 'Lover of his People' && $image == 'cover_lover_of_his_people.jpg') ||
      ($title == 'My Life' && $image == 'cover_my_life_by_stephanie_kemp.jpg') ||
      ($title == 'One Hundred Years of the ANC' && $image == 'cover_anc_100_years_book.jpg') ||
      ($title == 'Social Identities in the New South Africa' && $image == 'cover_social_identities_in_the_new_south_africa.jpg') ||
      ($title == 'The African National Congress and The Regeneration of Political Power' && $image == 'cover_anc_and_regeneration.jpg') ||
      (strpos($title, 'The African National Congress') !== false && $image == 'cover_anc_and_regeneration.jpg') ||
      ($title == 'The Final Prize' && $image == 'cover_the_final_prize.jpg') ||
      ($title == 'The I of the beholder' && $image == 'cover_the_i_of_the_beholder.jpg') ||
      ($title == 'The People\'s Paper' && $image == 'cover_the_peoples_paper.jpg') ||
      (strpos($title, 'The People') !== false && $image == 'cover_the_peoples_paper.jpg') ||
      (strpos($title, 'Cape Flats') !== false && $image == 'cover_cape_flats_details.jpg')
    ) {
      $matched_file = $image;
      break;
    }
  }

  if ($matched_file) {
    echo "✓ {$title} → {$matched_file}\n";
    $matched++;
  } else {
    echo "⊘ {$title} → NO MATCH\n";
    $matched_file = 'NULL';
    $unmatched++;
  }

  $csv_data[] = [
    $product_id,
    $title,
    $sku,
    $price,
    'NULL',
    $matched_file,
    'NULL',
    'NULL'
  ];
}

// Write updated CSV
$handle = fopen($csv_file, 'w');
foreach ($csv_data as $row) {
  fputcsv($handle, $row);
}
fclose($handle);

echo "\n" . str_repeat('=', 70) . "\n";
echo "Image Matching Complete!\n";
echo str_repeat('=', 70) . "\n\n";
echo "Results:\n";
echo "  ✓ Matched: {$matched} products\n";
echo "  ⊘ Unmatched: {$unmatched} products\n\n";
echo "Updated: {$csv_file}\n\n";

if ($matched > 0) {
  echo "Ready to import! Run:\n";
  echo "  ddev drush -l shop.ddev.site php:script scripts/import_product_images.php\n\n";
}
