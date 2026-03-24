<?php

/**
 * @file
 * Theme hooks for Commerce entities.
 */

/**
 * Implements hook_preprocess_commerce_subscription().
 *
 * Prepares display-ready variables for the subscription customer view template.
 */
function saho_shop_preprocess_commerce_subscription(&$variables) {
  /** @var \Drupal\commerce_recurring\Entity\SubscriptionInterface $sub */
  $sub = $variables['subscription_entity'];

  $state = $sub->get('state')->value;
  $variables['sub_state'] = $state;
  $variables['sub_is_active'] = $state === 'active';
  $variables['sub_is_canceled'] = $state === 'canceled';
  $variables['sub_is_expired'] = $state === 'expired';

  // Product / variation details.
  $variables['sub_product_title'] = NULL;
  $variables['sub_product_url'] = NULL;
  $variables['sub_price'] = NULL;

  $variation = $sub->getPurchasedEntity();
  if ($variation) {
    $variables['sub_product_title'] = $variation->getTitle();
    $price = $variation->getPrice();
    if ($price) {
      $variables['sub_price'] = 'R' . number_format((float) $price->getNumber(), 0, '.', ' ');
    }
    $product = $variation->getProduct();
    if ($product) {
      $variables['sub_product_url'] = $product->toUrl()->toString();
    }
  }

  // Billing schedule label.
  $variables['sub_billing_label'] = NULL;
  $billing = $sub->getBillingSchedule();
  if ($billing) {
    $variables['sub_billing_label'] = strtolower($billing->label());
  }

  // Dates.
  $variables['sub_start_date'] = $sub->getStartTime()
    ? \Drupal::service('date.formatter')->format($sub->getStartTime(), 'custom', 'd M Y')
    : NULL;

  $variables['sub_end_date'] = $sub->getEndTime()
    ? \Drupal::service('date.formatter')->format($sub->getEndTime(), 'custom', 'd M Y')
    : NULL;
}
