<?php

/**
 * @file
 * Post-update hooks for SAHO Shop Shipping.
 */

/**
 * Backfill publication weights that hook_install missed (race condition).
 *
 * When saho_shop_shipping is installed via drush config:import, the
 * module-enable step fires hook_install BEFORE the field.storage +
 * field.field configs for `weight` have been imported. The backfill
 * inside hook_install checks $variation->hasField('weight') and
 * silently skips every variation when the field doesn't exist yet -
 * so on Phase 0's production deploy (v1.15.0), every publication
 * ended up with an empty weight column.
 *
 * Symptom on production: shipping pane appears at checkout, but the
 * 5 shipping methods never list - because DefaultPacker can't compute
 * a shipment weight when every order item's purchased entity has an
 * empty weight, so isShippable() returns false.
 *
 * post_update hooks run during `drush deploy:hook`, which is the
 * fourth step of `drush deploy`, after config:import has finished
 * importing all field configs. By the time this runs, the weight
 * field is guaranteed to exist. Safe + idempotent: if a variation
 * already has a weight, we leave it alone.
 *
 * 0.5 kg is a conservative starting point that maps to a typical
 * paperback. Editors should refine weights for hardcovers / heavy
 * academic volumes via the variation edit form.
 */
function saho_shop_shipping_post_update_backfill_publication_weights(array &$sandbox): string {
  $storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');

  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = array_values($storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'publication')
      ->execute());
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['processed'] = 0;
    $sandbox['updated'] = 0;
  }

  if ($sandbox['total'] === 0) {
    $sandbox['#finished'] = 1;
    return 'No publication variations found - nothing to backfill.';
  }

  $batch = array_slice($sandbox['ids'], $sandbox['processed'], 50);
  foreach ($storage->loadMultiple($batch) as $variation) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    if (!$variation->hasField('weight')) {
      // Field still missing - bail loud so the operator notices and
      // imports the shop config before re-running drush updb.
      throw new \RuntimeException('The weight field is missing on commerce_product_variation.publication. Run drush cim --uri=https://shop.sahistory.org.za before this post_update.');
    }
    if ($variation->get('weight')->isEmpty()) {
      $variation->set('weight', ['number' => '0.500', 'unit' => 'kg']);
      $variation->save();
      $sandbox['updated']++;
    }
    $sandbox['processed']++;
  }

  $sandbox['#finished'] = $sandbox['processed'] / $sandbox['total'];

  if ($sandbox['#finished'] >= 1) {
    return sprintf(
      'Backfilled weight on %d of %d publication variations (already-weighted variations were left alone).',
      $sandbox['updated'],
      $sandbox['total']
    );
  }

  return sprintf('Processed %d / %d publication variations.', $sandbox['processed'], $sandbox['total']);
}
