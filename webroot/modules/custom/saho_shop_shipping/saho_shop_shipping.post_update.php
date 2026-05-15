<?php

/**
 * @file
 * Post-update hooks for SAHO Shop Shipping.
 */

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
