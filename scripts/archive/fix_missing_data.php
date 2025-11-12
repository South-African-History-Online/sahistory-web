<?php

/**
 * @file
 * Fix missing product data - add body field and re-import body text and taxonomy.
 *
 * This script:
 * 1. Adds body field to publication product type
 * 2. Re-imports body text from old database
 * 3. Creates taxonomy terms (History, Biographies, etc.)
 * 4. Assigns taxonomy categories to products
 *
 * Usage: ddev drush -l shop.ddev.site php:script scripts/fix_missing_data.php
 */

use Drupal\commerce_product\Entity\Product;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

echo "Fixing Missing Product Data\n";
echo str_repeat('=', 70) . "\n\n";

$entity_type = 'commerce_product';
$bundle = 'publication';

// Step 1: Add body field to publication product type
echo "Step 1: Adding body field to publication product type...\n";
echo str_repeat('-', 70) . "\n";

$field_storage = FieldStorageConfig::loadByName($entity_type, 'body');

if (!$field_storage) {
  $field_storage = FieldStorageConfig::create([
    'field_name' => 'body',
    'entity_type' => $entity_type,
    'type' => 'text_with_summary',
    'cardinality' => 1,
  ]);
  $field_storage->save();
  echo "✓ Created body field storage\n";
}
else {
  echo "- Body field storage already exists\n";
}

$field = FieldConfig::loadByName($entity_type, $bundle, 'body');

if (!$field) {
  $field = FieldConfig::create([
    'field_storage' => $field_storage,
    'bundle' => $bundle,
    'label' => 'Description',
    'description' => 'Full product description',
    'required' => FALSE,
  ]);
  $field->save();
  echo "✓ Added body field to publication bundle\n";
}
else {
  echo "- Body field already exists on publication bundle\n";
}

echo "\n";

// Step 2: Create taxonomy terms
echo "Step 2: Creating taxonomy terms...\n";
echo str_repeat('-', 70) . "\n";

$vocab_id = 'product_categories';
$vocabulary = Vocabulary::load($vocab_id);

if (!$vocabulary) {
  echo "✗ ERROR: Product Categories vocabulary not found!\n";
  echo "Please run create_publication_fields.php first.\n";
  exit(1);
}

// Terms from old database
$terms_to_create = [
  'History',
  'Biographies',
  'Photography',
  'Music',
  'Poetry',
];

$term_mapping = [];  // old_tid => new_tid

foreach ($terms_to_create as $term_name) {
  // Check if term already exists
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties([
      'name' => $term_name,
      'vid' => $vocab_id,
    ]);

  if (!empty($terms)) {
    $term = reset($terms);
    echo "- Term exists: $term_name (TID: {$term->id()})\n";
    $term_mapping[$term_name] = $term->id();
  }
  else {
    $term = Term::create([
      'vid' => $vocab_id,
      'name' => $term_name,
    ]);
    $term->save();
    echo "✓ Created term: $term_name (TID: {$term->id()})\n";
    $term_mapping[$term_name] = $term->id();
  }
}

echo "\n";

// Step 3: Connect to old database and re-import data
echo "Step 3: Re-importing body text and taxonomy from old database...\n";
echo str_repeat('-', 70) . "\n";

try {
  $old_db = \Drupal\Core\Database\Database::getConnection('default', 'old_publications');
}
catch (\Exception $e) {
  echo "✗ ERROR: Cannot connect to old_publications database\n";
  echo "Make sure the database connection is configured in settings.ddev.php\n";
  exit(1);
}

// Map old taxonomy term IDs to names
$old_term_names = [
  2 => 'Music',
  4 => 'Biographies',
  5 => 'History',
  6 => 'Photography',
  7 => 'Poetry',
];

// Get all products with body and taxonomy data
$query = $old_db->select('node_field_data', 'nfd');
$query->fields('nfd', ['nid', 'title']);
$query->leftJoin('uc_products', 'up', 'nfd.nid = up.nid');
$query->addField('up', 'model', 'sku');
$query->leftJoin('node__body', 'b', 'nfd.nid = b.entity_id');
$query->addField('b', 'body_value');
$query->addField('b', 'body_format');
$query->leftJoin('node__taxonomy_catalog', 'tc', 'nfd.nid = tc.entity_id');
$query->addField('tc', 'taxonomy_catalog_target_id', 'old_term_id');
$query->condition('nfd.type', 'product');
$query->condition('nfd.status', 1);
$query->orderBy('nfd.title');

$results = $query->execute()->fetchAll();

$updated = 0;
$skipped = 0;
$errors = 0;

foreach ($results as $row) {
  try {
    // Find product by SKU
    $variation_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
    $variations = $variation_storage->loadByProperties(['sku' => $row->sku]);

    if (empty($variations)) {
      echo "⊘ No product found with SKU: {$row->sku} ({$row->title})\n";
      $skipped++;
      continue;
    }

    $variation = reset($variations);
    $product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
    $products = $product_storage->loadByProperties(['variations' => $variation->id()]);

    if (empty($products)) {
      $skipped++;
      continue;
    }

    $product = reset($products);
    $updated_fields = [];

    // Update body if available
    if (!empty($row->body_value)) {
      $product->set('body', [
        'value' => $row->body_value,
        'format' => !empty($row->body_format) ? $row->body_format : 'full_html',
      ]);
      $updated_fields[] = 'body';
    }

    // Update taxonomy if available
    if (!empty($row->old_term_id) && isset($old_term_names[$row->old_term_id])) {
      $term_name = $old_term_names[$row->old_term_id];
      if (isset($term_mapping[$term_name])) {
        // Check if category already assigned
        $existing_terms = [];
        if ($product->hasField('field_categories') && !$product->get('field_categories')->isEmpty()) {
          foreach ($product->get('field_categories') as $item) {
            $existing_terms[] = $item->target_id;
          }
        }

        if (!in_array($term_mapping[$term_name], $existing_terms)) {
          $existing_terms[] = $term_mapping[$term_name];
          $product->set('field_categories', $existing_terms);
          $updated_fields[] = "category:{$term_name}";
        }
      }
    }

    if (!empty($updated_fields)) {
      $product->save();
      $fields_str = implode(', ', $updated_fields);
      echo "✓ Updated: {$row->title} (SKU: {$row->sku}) - $fields_str\n";
      $updated++;
    }
    else {
      $skipped++;
    }

  }
  catch (\Exception $e) {
    echo "✗ Error updating '{$row->title}': " . $e->getMessage() . "\n";
    $errors++;
  }
}

echo "\n" . str_repeat('=', 70) . "\n";
echo "Data Fix Complete!\n";
echo str_repeat('=', 70) . "\n\n";

echo "Results:\n";
echo "  ✓ Updated: $updated products\n";
echo "  ⊘ Skipped: $skipped products\n";
echo "  ✗ Errors:  $errors\n\n";

if ($updated > 0) {
  echo "Successfully updated $updated products with body text and categories!\n\n";
}

echo "Summary of additions:\n";
echo "  - Body field added to publication product type\n";
echo "  - " . count($terms_to_create) . " taxonomy terms created\n";
echo "  - Body text imported for products\n";
echo "  - Product categories assigned\n\n";

echo "Next steps:\n";
echo "  1. Export config: ddev drush -l shop.ddev.site cex -y\n";
echo "  2. View products: https://shop.ddev.site/admin/commerce/products\n";
echo "  3. Configure body display: /admin/commerce/config/product-types/publication/edit/display\n";
