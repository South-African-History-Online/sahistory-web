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
 * Multisite-safe: the champion product variations only exist on the
 * shop site. On the main site - and any other site in the multisite
 * without commerce_product installed - the commerce_product_variation
 * entity type is not defined at all, so the hook detects that up front
 * and returns a no-op message instead of throwing
 * PluginNotFoundException and failing the whole update batch.
 */
function saho_donate_post_update_lower_champion_prices(?array &$sandbox = NULL): string {
  $targets = [
    'CHAMPION-MONTHLY' => '100.00',
    'CHAMPION-ANNUAL' => '1000.00',
  ];

  // The champion product variations only exist on the shop site. On a
  // site without commerce_product installed the commerce_product_variation
  // entity type is undefined, and getStorage() would throw
  // PluginNotFoundException - failing the entire update batch. Bail out
  // cleanly before touching entity storage.
  $entity_type_manager = \Drupal::entityTypeManager();
  if (!$entity_type_manager->hasDefinition('commerce_product_variation')) {
    return 'Skipped: commerce_product_variation is not available on this site (expected on every site in the multisite except the shop).';
  }

  /** @var \Drupal\commerce_product\ProductVariationStorageInterface $storage */
  $storage = $entity_type_manager->getStorage('commerce_product_variation');
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

/**
 * Create the SAHO Champion - Patron Support product on the shop.
 *
 * The Patron tier (R2,500/year) is advertised on the champion landing
 * page, the shop front page and the main-site donate block, but no
 * purchasable product existed - /product/saho-champion-patron-support
 * was a 404. This creates it to match the existing Monthly and Annual
 * champion products: product type "default", variation type "default",
 * assigned to the SAHO Shop store.
 *
 * Idempotent: if a CHAMPION-PATRON variation already exists the hook
 * does nothing, so it is safe to re-run and safe if the product was
 * created by hand in the admin UI first.
 *
 * Multisite-safe: like saho_donate_post_update_lower_champion_prices(),
 * the commerce_product_variation entity type only exists on the shop.
 */
function saho_donate_post_update_create_patron_product(?array &$sandbox = NULL): string {
  $entity_type_manager = \Drupal::entityTypeManager();
  if (!$entity_type_manager->hasDefinition('commerce_product_variation')) {
    return 'Skipped: commerce_product_variation is not available on this site (expected on every site in the multisite except the shop).';
  }

  $variation_storage = $entity_type_manager->getStorage('commerce_product_variation');

  // Idempotency guard - bail if the Patron variation already exists.
  $existing = $variation_storage->loadByProperties(['sku' => 'CHAMPION-PATRON']);
  if (!empty($existing)) {
    return 'Skipped: a CHAMPION-PATRON variation already exists - nothing to create.';
  }

  // Load the default store so the hook is resilient to the store ID
  // differing between environments.
  $store_storage = $entity_type_manager->getStorage('commerce_store');
  $stores = $store_storage->loadByProperties(['is_default' => TRUE]);
  if (empty($stores)) {
    $stores = $store_storage->loadMultiple();
  }
  $store = reset($stores);
  if (!$store) {
    return 'Skipped: no commerce store found - cannot create the Patron product.';
  }

  $title = 'SAHO Champion - Patron Support';

  // Mirror the Monthly/Annual champion products: "default" bundle
  // variation, R2,500 ZAR, matching SKU naming (CHAMPION-PATRON).
  $variation = $variation_storage->create([
    'type' => 'default',
    'sku' => 'CHAMPION-PATRON',
    'title' => $title,
    'status' => TRUE,
    'price' => new Price('2500.00', 'ZAR'),
  ]);
  $variation->save();

  $body = '<p>Become a SAHO Champion at the Patron level with an annual donation of <strong>R2,500</strong>.</p>' . "\n"
    . '<p>Patron Champions provide the deepest level of support for preserving and sharing South African history.</p>' . "\n"
    . '<h3>Champion Benefits:</h3>' . "\n"
    . '<ul>' . "\n"
    . '<li>All Annual Champion benefits, plus:</li>' . "\n"
    . '<li>An annual heritage photographic publication</li>' . "\n"
    . '<li>A framed Champion certificate</li>' . "\n"
    . '<li>Permanent recognition on the Wall of Champions</li>' . "\n"
    . '<li>A personal thank you from SAHO leadership</li>' . "\n"
    . '</ul>' . "\n"
    . '<p>Your patronage makes a lasting difference. A tax certificate is available via the Marion Institute.</p>';

  $product_storage = $entity_type_manager->getStorage('commerce_product');
  $product = $product_storage->create([
    'type' => 'default',
    'title' => $title,
    'status' => TRUE,
    'stores' => [$store->id()],
    'variations' => [$variation->id()],
    'body' => [
      'value' => $body,
      'format' => 'basic_html',
    ],
  ]);
  $product->save();

  // Give the product the URL alias the templates already link to.
  $alias = '/product/saho-champion-patron-support';
  $path_alias_storage = $entity_type_manager->getStorage('path_alias');
  if (empty($path_alias_storage->loadByProperties(['alias' => $alias]))) {
    $path_alias_storage->create([
      'path' => '/product/' . $product->id(),
      'alias' => $alias,
      'langcode' => 'en',
    ])->save();
  }

  return sprintf(
    'Created the SAHO Champion - Patron Support product (product %d, variation %d, R2,500 ZAR) at %s.',
    $product->id(),
    $variation->id(),
    $alias
  );
}
