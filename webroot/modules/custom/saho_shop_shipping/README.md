# SAHO Shop Shipping

Shipping configuration, rate calculators, and post-purchase fulfilment
for the SAHO shop (`shop.sahistory.org.za`). Multisite-aware: enabled
only on the shop site (`config/shop/core.extension.yml`).

## What this module ships

Five shipping methods available to ZA-domestic and international
customers at checkout, plus a free-shipping-over-R750 promotion that
applies to the two cheapest options only.

| Method | Price | Conditions |
|---|---|---|
| Local pickup - SAHO offices, Cape Town | **R0** | ZA-wide (customer self-selects) |
| Paxi to PEP store (cheapest) | **R59.95** | ZA, weight <= 10 kg |
| Pargo pickup point | **R95** | ZA, weight <= 5 kg |
| The Courier Guy - door-to-door | **R150** | ZA, weight <= 5 kg |
| International - small parcel | **R350** | non-ZA, weight <= 2 kg |

Free-shipping-over-R750 promotion: when the order subtotal is >= R750
the cost of **Local pickup** or **Paxi** drops to R0. Door-to-door
courier rates are unchanged so SAHO doesn't subsidise premium delivery
just because a cart crossed the threshold.

Phase 0 ships these as flat-rate fallbacks. Phase 1 (planned) will
replace them with live multi-carrier quotes through Bob Go.

## Architecture

Shipping methods are **ContentEntities** (not config-exportable), so
they're created in `hook_install()` via the entity API. Each method
has a stable UUID that the free-shipping promotion references back to.
Re-running `hook_install()` is idempotent — existing entities are
left alone.

`hook_install()` runs four passes:

1. **`_saho_shop_shipping_install_shipments_field()`** — provision the
   `shipments` field on the `commerce_order` default bundle. Safety
   net for the case where config sync hasn't created the field
   storage yet (config sync is the primary source of truth — see
   `config/shop/field.storage.commerce_order.shipments.yml` +
   `config/shop/field.field.commerce_order.default.shipments.yml`).
2. **`_saho_shop_shipping_install_shipping_methods()`** — create the
   five methods listed above. UUIDs in `_saho_shop_shipping_method_
   specs()` must stay stable across environments because the
   free-shipping promotion stores them as `eligible` shipping
   methods.
3. **`_saho_shop_shipping_install_free_shipping_promotion()`** — create
   the over-R750 promotion if it doesn't already exist (matched by
   name "Free shipping over R750").
4. **`_saho_shop_shipping_backfill_publication_weights()`** — fill
   `weight = 0.5 kg` on existing publication-bundle variations. Only
   covers the manual-install path; the deploy-time backfill runs as
   a post_update (see below).

## Configuration files in `config/shop/`

| File | What it does |
|---|---|
| `field.storage.commerce_product_variation.weight.yml` | `weight` field storage on commerce_product_variation |
| `field.field.commerce_product_variation.publication.weight.yml` | weight field instance on the `publication` variation bundle |
| `core.entity_form_display.commerce_product_variation.publication.default.yml` | renders the weight input in the admin variation form |
| `field.storage.commerce_order.shipments.yml` | shipments field storage on commerce_order |
| `field.field.commerce_order.default.shipments.yml` | shipments field instance on the default order bundle |
| `commerce_checkout.commerce_checkout_flow.default.yml` | shipping_information pane enabled on the default checkout flow |
| `commerce_order.commerce_order_type.default.yml` | `third_party_settings.commerce_shipping.shipment_type = default` |
| `commerce_product.commerce_product_variation_type.publication.yml` | publication variation bundle (used for books) |

## Post-update hooks

The module accumulates **four post_update hooks** because the
production deploy sequence exposed three subtle mismatches between
config sync and entity definitions. Each hook is idempotent.

### `..._backfill_publication_weights` (v1.15.2)

Backfills weight on `publication`-bundle variations. Was the first
attempt at fixing the "no shipping at checkout" symptom but reported
**"No publication variations found"** on production — production
books were in a different bundle (see migration hook below).

### `..._backfill_publication_weights_via_products` (v1.15.3)

Walks from the product side (Publication products → their variations
regardless of variation bundle) and reports the bundle distribution
in the completion notice. Diagnostic + corrective in one pass. Told
us production had `[default: 33]` — all 33 book variations were in
the `default` bundle.

### `..._migrate_default_book_variations` (v1.15.4)

Reassigns the 33 production book variations from `default` bundle to
`publication` bundle via raw UPDATE statements (bundle is immutable
through Drupal's entity API), then inserts a `weight` row at 0.5 kg
for each. Cache tags are invalidated at the end so subsequent loads
pick up the new bundle. Champion membership variations (also bundle
`default` but attached to `default`-bundle products) are deliberately
untouched — the query filters on **product** bundle = `publication`.

## Why production diverged from local

The `publication` variation bundle was added in `a6acd650` (Jan 2026).
Books created **after** that commit got the `publication` bundle
straight away (which is what local has). Books created **before**
that commit got the `default` bundle and never migrated. The
production site had 33 such legacy book variations; local had zero.
This wasn't visible until shipping was wired up because nothing else
cared about the bundle distinction.

## How shipping is determined at checkout

1. Order has a `shipments` field (provisioned by the shop's
   `field.field.commerce_order.default.shipments.yml`).
2. `ShippingOrderManager::isShippable()` returns TRUE when at least
   one order item has a `weight` field on its purchased entity.
3. `DefaultPacker` packs each item with a non-empty weight into the
   shipment. Items without weight are skipped.
4. Each shipping method's `conditions` (shipment_address + sometimes
   shipment_weight) filter the available methods.
5. The shipping_information pane renders the methods that match.

So the field's existence determines whether the pane shows;
the field's value determines whether any methods match.

## Known limitations + future work

- **Flat rates are conservative fallbacks.** Phase 1 will integrate
  Bob Go's aggregator API to return live multi-carrier rates.
- **All publications default to 0.5 kg.** Editors should refine the
  weight on hardcovers and heavy academic volumes via the variation
  edit form (`/admin/commerce/products`).
- **`local_pickup_cpt` has no Western Cape filter** because the SA
  Address Drupal module doesn't expose a province dropdown — relying
  on customer self-selection.
- **International is a single R350 flat for parcels <= 2 kg.** Above
  2 kg or for rushed orders, customers email shop@sahistory.org.za
  for a quote. Phase 2 (after Bob Go) may integrate DHL/Aramex direct
  for live international rates.
