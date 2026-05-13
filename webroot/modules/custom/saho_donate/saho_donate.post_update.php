<?php

/**
 * @file
 * Post-update hooks for the saho_donate module.
 */

use Drupal\commerce_price\Price;

/**
 * Lower Champion membership prices per the v1.12.0 plan.
 *
 * Prices were lowered locally via a one-off drush eval, but never made
 * it to prod. This post-update bakes the price drop into a hook so a
 * single `drush updb -y` on prod picks it up:
 *
 *   CHAMPION-MONTHLY: R200 -> R100
 *   CHAMPION-ANNUAL:  R2,000 -> R1,000
 *
 * Idempotent: if a variation is already at the target price, it's
 * skipped (avoids touching custom admin edits the operator may have
 * made after the original drop).
 *
 * Only effective on the site where the champion product variations
 * live (the shop). On any other site in the multisite, the loadBy
 * call returns empty and the hook becomes a no-op.
 */
function saho_donate_post_update_lower_champion_prices(?array &$sandbox = NULL): string {
  $targets = [
    'CHAMPION-MONTHLY' => '100.00',
    'CHAMPION-ANNUAL' => '1000.00',
  ];

  /** @var \Drupal\commerce_product\ProductVariationStorageInterface $storage */
  $storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
  $variations = $storage->loadByProperties(['sku' => array_keys($targets)]);

  $updated = 0;
  $skipped = 0;
  foreach ($variations as $variation) {
    $target_amount = $targets[$variation->getSku()] ?? NULL;
    if ($target_amount === NULL) {
      continue;
    }
    $current = $variation->getPrice();
    $currency = $current ? $current->getCurrencyCode() : 'ZAR';

    // Skip if already at target - keeps the hook safely re-runnable
    // and respectful of any manual admin adjustments since first run.
    if ($current && (string) $current->getNumber() === $target_amount) {
      $skipped++;
      continue;
    }

    $variation->setPrice(new Price($target_amount, $currency));
    $variation->save();
    $updated++;
  }

  return sprintf(
    'Lowered Champion prices: %d variation(s) updated, %d already at target, %d not found.',
    $updated,
    $skipped,
    count($targets) - count($variations)
  );
}
