<?php

namespace Drupal\saho_donate;

use Drupal\Core\Site\Settings;

/**
 * Resolves PayFast credentials for the saho_donate one-off donation flow.
 *
 * Two storage locations are checked, in order:
 *
 * 1. PHP $settings[] in settings.php / env vars (Settings::get()).
 * 2. The Drupal config entity for Commerce's PayFast gateway
 *    (commerce_payment.commerce_payment_gateway.payfast), which is what
 *    the shop's admin UI writes to.
 *
 * Operators can drive credentials either way. On cPanel hosts where env
 * vars are awkward, filling the Commerce gateway admin form is enough -
 * the saho_donate /donate page will pick the same values up via this
 * helper.
 */
final class PayfastCredentials {

  /**
   * Returns merchant_id / merchant_key / passphrase.
   *
   * @return array
   *   Keys: merchant_id, merchant_key, passphrase. Empty strings when unset.
   */
  public static function get(): array {
    $id = (string) Settings::get('payfast_merchant_id', '');
    $key = (string) Settings::get('payfast_merchant_key', '');
    $passphrase = (string) Settings::get('payfast_passphrase', '');

    // If any of the three is missing, peek at the commerce_payment_gateway
    // config entity so the admin UI on the shop becomes the single source
    // of truth.
    if ($id === '' || $key === '' || $passphrase === '') {
      $gateway = \Drupal::config('commerce_payment.commerce_payment_gateway.payfast')
        ->get('configuration') ?? [];
      $id = $id !== '' ? $id : (string) ($gateway['merchant_id'] ?? '');
      $key = $key !== '' ? $key : (string) ($gateway['merchant_key'] ?? '');
      $passphrase = $passphrase !== '' ? $passphrase : (string) ($gateway['passphrase'] ?? '');
    }

    return [
      'merchant_id' => $id,
      'merchant_key' => $key,
      'passphrase' => $passphrase,
    ];
  }

  /**
   * Quick boolean: are id + key both set?
   *
   * Passphrase is optional for the redirect flow (only required for ITN
   * signature verification), so it's not part of the missing() check.
   */
  public static function missing(): bool {
    $creds = self::get();
    return $creds['merchant_id'] === '' || $creds['merchant_key'] === '';
  }

}
