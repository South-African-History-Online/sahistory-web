<?php

/**
 * @file
 * Apply publication metadata enrichment + create missing publications.
 *
 * Reads webroot/private/migration_csv/publications_enrichment_2026-05-13.csv
 * and either updates existing commerce_product entities (action=update) or
 * creates new ones (action=create) under the `publication` bundle.
 *
 * Usage:
 *   ddev drush --uri=https://shop.ddev.site scr scripts/migrations/apply_publication_enrichment.php
 *
 * Pass --dry to log what would happen without saving:
 *   ddev drush --uri=https://shop.ddev.site scr scripts/migrations/apply_publication_enrichment.php -- --dry
 *
 * Fields touched on the `publication` bundle (field machine names):
 *   title, field_subtitle, field_author, field_editor, field_isbn,
 *   field_year, field_publication_date, field_publisher (taxonomy term)
 *
 * Price is set on the default product variation (commerce_price ZAR).
 *
 * Idempotent: re-runs only overwrite a field if the CSV provides a value;
 * empty cells in the CSV are skipped so prior manual edits are preserved.
 */

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\taxonomy\Entity\Term;

$args = $extra ?? [];
$dry_run = in_array('--dry', $args, TRUE);

$csv_path = DRUPAL_ROOT . '/private/migration_csv/publications_enrichment_2026-05-13.csv';
if (!file_exists($csv_path)) {
  echo "CSV not found at $csv_path\n";
  return;
}

$rows = [];
$fh = fopen($csv_path, 'r');
$header = fgetcsv($fh);
while (($row = fgetcsv($fh)) !== FALSE) {
  if (count($row) !== count($header)) {
    continue;
  }
  $rows[] = array_combine($header, $row);
}
fclose($fh);

$product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
$variation_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

// Helper: find-or-create a publisher taxonomy term. Auto-creates the term
// in the first vocabulary that exists (preferring `publishers`). Returns
// NULL if no candidate vocabulary is present.
$resolve_publisher = function (string $name) use ($term_storage, $dry_run) {
  if ($name === '') {
    return NULL;
  }
  $vocab_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary');
  $candidate_vids = ['publishers', 'publisher', 'commerce_publisher'];
  foreach ($candidate_vids as $vid) {
    if (!$vocab_storage->load($vid)) {
      continue;
    }
    $terms = $term_storage->loadByProperties(['vid' => $vid, 'name' => $name]);
    if ($terms) {
      return reset($terms);
    }
    if ($dry_run) {
      // Don't create during dry-run; return a placeholder so caller knows
      // we would have created it.
      return NULL;
    }
    $term = Term::create(['vid' => $vid, 'name' => $name]);
    $term->save();
    return $term;
  }
  return NULL;
};

$set_if_value = function (Product $p, string $field, $value) {
  if ($value === NULL || $value === '') {
    return FALSE;
  }
  if (!$p->hasField($field)) {
    return FALSE;
  }
  $p->set($field, $value);
  return TRUE;
};

$summary = ['updated' => 0, 'created' => 0, 'skipped' => 0, 'errors' => []];

foreach ($rows as $r) {
  $action = trim($r['action'] ?? '');
  $pid = trim($r['product_id'] ?? '');
  $title = trim($r['title'] ?? '');

  if ($title === '') {
    $summary['skipped']++;
    continue;
  }

  if ($action === 'update') {
    if (!$pid || !is_numeric($pid)) {
      $summary['errors'][] = "update row missing product_id for $title";
      continue;
    }
    // @var \Drupal\commerce_product\Entity\Product|null $p
    $p = $product_storage->load((int) $pid);
    if (!$p) {
      $summary['errors'][] = "product $pid not found ($title)";
      continue;
    }
  }
  elseif ($action === 'create') {
    $p = Product::create([
      'type' => 'publication',
      'title' => $title,
      'status' => 1,
      'stores' => [1],
    ]);
  }
  else {
    $summary['skipped']++;
    continue;
  }

  $touched = FALSE;
  $touched |= $set_if_value($p, 'field_subtitle', trim($r['subtitle'] ?? ''));
  $touched |= $set_if_value($p, 'field_author', trim($r['author'] ?? ''));
  $touched |= $set_if_value($p, 'field_editor', trim($r['editor'] ?? ''));
  $touched |= $set_if_value($p, 'field_isbn', trim($r['isbn'] ?? ''));
  $touched |= $set_if_value($p, 'field_year', trim($r['publication_year'] ?? ''));

  $year = trim($r['publication_year'] ?? '');
  if ($year !== '' && $p->hasField('field_publication_date')) {
    // Store as YYYY-01-01 so we have a sortable date even without a full ISO.
    $p->set('field_publication_date', sprintf('%s-01-01T00:00:00', $year));
    $touched = TRUE;
  }

  $publisher_name = trim($r['publisher'] ?? '');
  if ($publisher_name !== '' && $p->hasField('field_publisher')) {
    $term = $resolve_publisher($publisher_name);
    if ($term) {
      $p->set('field_publisher', $term->id());
      $touched = TRUE;
    }
    else {
      $summary['errors'][] = "publisher term not found for '$publisher_name' on $title - field_publisher left untouched";
    }
  }

  if ($dry_run) {
    echo sprintf("[DRY] %s: %s (touched=%s)\n", strtoupper($action), $title, $touched ? 'yes' : 'no');
    continue;
  }

  if ($action === 'create' || $touched) {
    try {
      $p->save();
      $summary[$action === 'create' ? 'created' : 'updated']++;
    }
    catch (\Exception $e) {
      $summary['errors'][] = sprintf('save failed for %s: %s', $title, $e->getMessage());
      continue;
    }
  }
  else {
    $summary['skipped']++;
  }

  // Price: set on the default variation. For `create`, we need to spin up
  // a variation since new products don't have one yet.
  $price_raw = trim($r['price_zar'] ?? '');
  if ($price_raw !== '' && is_numeric($price_raw)) {
    $price = new Price((string) (float) $price_raw, 'ZAR');
    $variations = $p->getVariations();
    if (!$variations && $action === 'create') {
      $sku_base = strtoupper(preg_replace('/[^A-Z0-9]+/i', '-', $title));
      $sku = 'PUB-' . substr($sku_base, 0, 40) . '-' . $p->id();
      $v = ProductVariation::create([
        'type' => 'publication',
        'sku' => $sku,
        'title' => $title,
        'price' => $price,
        'status' => 1,
      ]);
      $v->save();
      $p->addVariation($v);
      $p->save();
    }
    elseif ($variations) {
      $v = reset($variations);
      $v->setPrice($price);
      $v->save();
    }
  }
}

echo sprintf(
  "Done. updated=%d created=%d skipped=%d errors=%d\n",
  $summary['updated'],
  $summary['created'],
  $summary['skipped'],
  count($summary['errors'])
);
foreach ($summary['errors'] as $e) {
  echo "  ! $e\n";
}
