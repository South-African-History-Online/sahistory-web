<?php

/**
 * @file
 * Creates publication-specific fields for Commerce products.
 *
 * Usage: ddev drush -l shop.ddev.site php:script scripts/create_publication_fields.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Vocabulary;

echo "Creating publication fields...\n";
echo str_repeat('=', 60) . "\n";

$entity_type = 'commerce_product';
$bundle = 'publication';

// Helper function to create field.
function create_field($entity_type, $bundle, $field_name, $field_type, $label, $description = '', $settings = [], $cardinality = 1) {

  // Check if field storage already exists.
  $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);

  if (!$field_storage) {
    $storage_config = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $field_type,
      'cardinality' => $cardinality,
    ];

    // Handle different setting types
    if (!empty($settings)) {
      if (isset($settings['allowed_values'])) {
        $storage_config['settings']['allowed_values'] = $settings['allowed_values'];
      }
      if (isset($settings['target_type'])) {
        $storage_config['settings']['target_type'] = $settings['target_type'];
      }
    }

    try {
      $field_storage = FieldStorageConfig::create($storage_config);
      $field_storage->save();
      echo "✓ Created field storage: $field_name\n";
    }
    catch (\Exception $e) {
      echo "✗ Error creating field storage $field_name: " . $e->getMessage() . "\n";
      return;
    }
  }
  else {
    echo "- Field storage exists: $field_name\n";
  }

  // Check if field instance exists.
  $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);

  if (!$field) {
    $field_config = [
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => $label,
      'description' => $description,
      'required' => FALSE,
    ];

    // Add handler settings for entity reference fields
    if (!empty($settings['handler_settings'])) {
      $field_config['settings']['handler_settings'] = $settings['handler_settings'];
    }

    try {
      $field = FieldConfig::create($field_config);
      $field->save();
      echo "  ✓ Added to bundle: $bundle\n";
    }
    catch (\Exception $e) {
      echo "  ✗ Error adding field to bundle: " . $e->getMessage() . "\n";
    }
  }
  else {
    echo "  - Field exists on bundle: $bundle\n";
  }

  echo "\n";
}

// ISBN field.
create_field(
  $entity_type, $bundle,
  'field_isbn',
  'string',
  'ISBN',
  'ISBN-10 or ISBN-13 identifier'
);

// Number of pages.
create_field(
  $entity_type, $bundle,
  'field_pages',
  'integer',
  'Pages',
  'Number of pages in the publication'
);

// Publication year.
create_field(
  $entity_type, $bundle,
  'field_year',
  'string',
  'Publication Year',
  'Year the publication was released'
);

// Format (Hardcover, Paperback, etc.).
create_field(
  $entity_type, $bundle,
  'field_format',
  'list_string',
  'Format',
  'Physical format of the publication',
  [
    'allowed_values' => [
      'hardcover' => 'Hardcover',
      'paperback' => 'Paperback',
      'ebook' => 'eBook',
      'audiobook' => 'Audiobook',
    ],
  ]
);

// Language.
create_field(
  $entity_type, $bundle,
  'field_language',
  'list_string',
  'Language',
  'Primary language of the publication',
  [
    'allowed_values' => [
      'english' => 'English',
      'afrikaans' => 'Afrikaans',
      'zulu' => 'isiZulu',
      'xhosa' => 'isiXhosa',
      'other' => 'Other',
    ],
  ]
);

// Featured product flag.
create_field(
  $entity_type, $bundle,
  'field_featured',
  'boolean',
  'Featured',
  'Mark this as a featured publication'
);

// Product images (multiple).
create_field(
  $entity_type, $bundle,
  'field_images',
  'image',
  'Product Images',
  'Upload product images (cover, back, inside pages, etc.)',
  [],
  -1  // Unlimited
);

// Create Publishers vocabulary if it doesn't exist.
if (!Vocabulary::load('publishers')) {
  $vocabulary = Vocabulary::create([
    'vid' => 'publishers',
    'name' => 'Publishers',
    'description' => 'Publication publishers and imprints',
  ]);
  $vocabulary->save();
  echo "✓ Created 'Publishers' vocabulary\n\n";
}

// Publisher field (taxonomy reference).
create_field(
  $entity_type, $bundle,
  'field_publisher',
  'entity_reference',
  'Publisher',
  'Publishing house or imprint',
  [
    'target_type' => 'taxonomy_term',
    'handler_settings' => [
      'target_bundles' => ['publishers' => 'publishers'],
    ],
  ]
);

// Create Product Categories vocabulary if it doesn't exist.
if (!Vocabulary::load('product_categories')) {
  $vocabulary = Vocabulary::create([
    'vid' => 'product_categories',
    'name' => 'Product Categories',
    'description' => 'Categories for publications (History, Biography, Politics, etc.)',
  ]);
  $vocabulary->save();
  echo "✓ Created 'Product Categories' vocabulary\n\n";
}

// Categories field (taxonomy reference, multiple).
create_field(
  $entity_type, $bundle,
  'field_categories',
  'entity_reference',
  'Categories',
  'Publication categories and topics',
  [
    'target_type' => 'taxonomy_term',
    'handler_settings' => [
      'target_bundles' => ['product_categories' => 'product_categories'],
    ],
  ],
  -1  // Unlimited
);

echo str_repeat('=', 60) . "\n";
echo "✓ All publication fields created successfully!\n";
echo str_repeat('=', 60) . "\n\n";

echo "Fields created:\n";
echo "  - field_isbn (ISBN identifier)\n";
echo "  - field_pages (Number of pages)\n";
echo "  - field_year (Publication year)\n";
echo "  - field_format (Hardcover/Paperback/etc)\n";
echo "  - field_language (Language)\n";
echo "  - field_featured (Featured flag)\n";
echo "  - field_images (Multiple images)\n";
echo "  - field_publisher (Publisher reference)\n";
echo "  - field_categories (Category reference)\n\n";

echo "Vocabularies created:\n";
echo "  - publishers (manage at /admin/structure/taxonomy/manage/publishers)\n";
echo "  - product_categories (manage at /admin/structure/taxonomy/manage/product_categories)\n\n";

echo "Next steps:\n";
echo "  1. Configure field display: /admin/commerce/config/product-types/publication/edit/display\n";
echo "  2. Configure form display: /admin/commerce/config/product-types/publication/edit/form-display\n";
echo "  3. Add publishers: /admin/structure/taxonomy/manage/publishers/add\n";
echo "  4. Add categories: /admin/structure/taxonomy/manage/product_categories/add\n";
