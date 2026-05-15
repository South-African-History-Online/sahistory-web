<?php

/**
 * @file
 * Post-update hooks for SAHO Shop Shipping.
 */

use Drupal\Core\Cache\Cache;

/**
 * Backfill weight on variations attached to Publication products.
 *
 * Replaces the earlier post_update that only matched
 * commerce_product_variation.type = 'publication'. The production
 * deploy of v1.15.2 reported "No publication variations found":
 * production has Publication PRODUCTS (commerce_product bundle =
 * publication) but the matching VARIATIONS are not in the
 * `publication` variation bundle. Different sites have ended up
 * with different variation bundles attached to the same product
 * type over the years, and we don't want a single bundle assumption
 * to silently miss every shippable item.
 *
 * This version walks from the product side: find every product of
 * bundle `publication`, load its variations regardless of their
 * bundle, fill weight = 0.5 kg on any variation that exposes a
 * weight field. The final notice lists the variation bundles we
 * saw so the deploy log records exactly what's in the wild - if
 * the count of "with weight field" is lower than the total, a
 * follow-up PR will attach the weight field to the missing bundle.
 *
 * Idempotent: already-weighted variations are left alone.
 */
function saho_shop_shipping_post_update_backfill_publication_weights_via_products(array &$sandbox): string {
  $product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
  $variation_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');

  if (!isset($sandbox['product_ids'])) {
    $sandbox['product_ids'] = array_values($product_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'publication')
      ->execute());
    $sandbox['total'] = count($sandbox['product_ids']);
    $sandbox['processed_products'] = 0;
    $sandbox['updated'] = 0;
    $sandbox['skipped_no_weight_field'] = 0;
    $sandbox['skipped_already_weighted'] = 0;
    $sandbox['bundles_seen'] = [];
  }

  if ($sandbox['total'] === 0) {
    $sandbox['#finished'] = 1;
    return 'No Publication products found - nothing to backfill.';
  }

  $batch = array_slice($sandbox['product_ids'], $sandbox['processed_products'], 25);
  /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
  foreach ($product_storage->loadMultiple($batch) as $product) {
    foreach ($product->getVariations() as $variation) {
      $bundle = $variation->bundle();
      $sandbox['bundles_seen'][$bundle] = ($sandbox['bundles_seen'][$bundle] ?? 0) + 1;
      if (!$variation->hasField('weight')) {
        $sandbox['skipped_no_weight_field']++;
        continue;
      }
      if (!$variation->get('weight')->isEmpty()) {
        $sandbox['skipped_already_weighted']++;
        continue;
      }
      $variation->set('weight', ['number' => '0.500', 'unit' => 'kg']);
      $variation->save();
      $sandbox['updated']++;
    }
    $sandbox['processed_products']++;
  }

  $sandbox['#finished'] = $sandbox['processed_products'] / $sandbox['total'];

  if ($sandbox['#finished'] >= 1) {
    $bundle_summary = empty($sandbox['bundles_seen'])
      ? 'no variations'
      : implode(', ', array_map(
        static fn($b, $c) => "$b: $c",
        array_keys($sandbox['bundles_seen']),
        array_values($sandbox['bundles_seen'])
      ));
    return sprintf(
      'Processed %d Publication products. Variation bundles seen: [%s]. Updated %d, already weighted %d, skipped (no weight field) %d.',
      $sandbox['total'],
      $bundle_summary,
      $sandbox['updated'],
      $sandbox['skipped_already_weighted'],
      $sandbox['skipped_no_weight_field']
    );
  }

  return sprintf('Processed %d / %d Publication products.', $sandbox['processed_products'], $sandbox['total']);
}

/**
 * Migrate default-bundle book variations to the publication bundle.
 *
 * Production has 33 commerce_product_variation entities of bundle
 * `default` that are attached to commerce_product entities of bundle
 * `publication`. They predate the publication variation type
 * (created in commit a6acd650, Jan 2026) and have been quietly
 * coexisting with the new schema ever since. The weight field is
 * attached to the publication variation bundle only, so as long as
 * these books stay in `default` they have no weight, no shipment
 * weight gets calculated, and the new shipping methods never fire
 * at checkout - which is exactly the production-only bug the team
 * surfaced after the v1.15.0 deploy.
 *
 * This post_update brings production in line with the intended
 * schema by reassigning those variations to the publication bundle,
 * then backfilling weight = 0.5 kg on each one. champion membership
 * variations (also bundle `default` but attached to `default`-bundle
 * products) are deliberately untouched.
 *
 * Bundle is normally immutable through the entity API, so the
 * reassignment is done via raw UPDATE statements against both the
 * base table (commerce_product_variation) and the data table
 * (commerce_product_variation_field_data). After the bundle change
 * we write the weight rows directly via MERGE so we never have to
 * re-load entities with a stale-bundle in-memory cache.
 *
 * Idempotent: if no default-bundle variations remain under
 * Publication products, the routine reports "nothing to migrate"
 * and exits.
 */
function saho_shop_shipping_post_update_migrate_default_book_variations(array &$sandbox): string {
  $product_storage = \Drupal::entityTypeManager()->getStorage('commerce_product');
  $variation_storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
  $db = \Drupal::database();

  if (!isset($sandbox['ids'])) {
    $product_ids = $product_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'publication')
      ->execute();
    $sandbox['ids'] = [];
    if (!empty($product_ids)) {
      $sandbox['ids'] = array_values($variation_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('product_id', array_values($product_ids), 'IN')
        ->condition('type', 'default')
        ->execute());
    }
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['migrated'] = 0;
    $sandbox['weighted'] = 0;
    $sandbox['processed'] = 0;
  }

  if ($sandbox['total'] === 0) {
    $sandbox['#finished'] = 1;
    return 'No default-bundle variations found under Publication products - nothing to migrate.';
  }

  $batch = array_slice($sandbox['ids'], $sandbox['processed'], 25);
  foreach ($batch as $vid) {
    // Reassign bundle on both base table and data table.
    $db->update('commerce_product_variation')
      ->fields(['type' => 'publication'])
      ->condition('variation_id', $vid)
      ->condition('type', 'default')
      ->execute();
    $db->update('commerce_product_variation_field_data')
      ->fields(['type' => 'publication'])
      ->condition('variation_id', $vid)
      ->condition('type', 'default')
      ->execute();
    $sandbox['migrated']++;

    // Insert (or update) the weight row directly. commerce_product_variation
    // is not revisionable, so revision_id == variation_id by convention.
    $existing = $db->select('commerce_product_variation__weight', 'w')
      ->fields('w', ['entity_id'])
      ->condition('entity_id', $vid)
      ->condition('deleted', 0)
      ->condition('delta', 0)
      ->execute()
      ->fetchField();
    if (!$existing) {
      $db->insert('commerce_product_variation__weight')
        ->fields([
          'bundle' => 'publication',
          'deleted' => 0,
          'entity_id' => $vid,
          'revision_id' => $vid,
          'langcode' => 'en',
          'delta' => 0,
          'weight_number' => '0.500000',
          'weight_unit' => 'kg',
        ])
        ->execute();
      $sandbox['weighted']++;
    }
    $sandbox['processed']++;
  }

  $sandbox['#finished'] = $sandbox['processed'] / $sandbox['total'];

  if ($sandbox['#finished'] >= 1) {
    // Invalidate entity caches so the reassigned variations are
    // re-loaded with their new bundle on next access. Raw UPDATEs
    // skipped Drupal's save lifecycle so we explicitly drop the
    // static + persistent caches for each touched entity.
    $variation_storage->resetCache($sandbox['ids']);
    $tags = array_map(static fn($id) => 'commerce_product_variation:' . $id, $sandbox['ids']);
    Cache::invalidateTags($tags);

    return sprintf(
      'Migrated %d default-bundle variations to publication; backfilled weight on %d of them.',
      $sandbox['migrated'],
      $sandbox['weighted']
    );
  }

  return sprintf('Migrated %d / %d variations.', $sandbox['processed'], $sandbox['total']);
}
