<?php

/**
 * @file
 * PayFast Settings Override for SAHO Shop
 *
 * SECURITY: Add this code to your settings.php file to override the placeholder
 * credentials (abc123) in the exported configuration.
 *
 * The config file has merchant_id and merchant_key set to "abc123" as placeholders.
 * This settings.php override replaces them with real credentials at runtime.
 *
 * Location: Add to webroot/sites/shop.sahistory.org.za/settings.php
 * Position: At the END of the file (after all other configuration)
 */

// =============================================================================
// PAYFAST CONFIGURATION OVERRIDE
// =============================================================================
// This overrides config/shop/commerce_payment.commerce_payment_gateway.payfast.yml
// which has placeholder values (abc123)

/**
 * PayFast Payment Gateway - Credential Override
 *
 * The exported config has merchant_id: abc123 and merchant_key: abc123
 * This settings.php code replaces those with real credentials.
 */

// Detect environment
if (isset($_ENV['DDEV_HOSTNAME']) || isset($_ENV['IS_DDEV_PROJECT'])) {
  // =============================================================================
  // LOCAL DEVELOPMENT (DDEV)
  // =============================================================================
  // Override the abc123 placeholders with PayFast SANDBOX credentials
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['mode'] = 'test';
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_id'] = '10000100';  // Your sandbox merchant ID
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_key'] = 'sandbox_key_here';  // Your sandbox key
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['passphrase'] = 'sandbox_passphrase';  // Your sandbox passphrase
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['debug'] = TRUE;

} elseif (strpos($_SERVER['HTTP_HOST'] ?? '', '.sahistory.org.za') !== FALSE) {
  // =============================================================================
  // PRODUCTION (shop.sahistory.org.za)
  // =============================================================================
  // Override the abc123 placeholders with PayFast PRODUCTION credentials

  // OPTION A: From environment variables (MOST SECURE)
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['mode'] = 'live';
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_id'] = getenv('PAYFAST_MERCHANT_ID');
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_key'] = getenv('PAYFAST_MERCHANT_KEY');
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['passphrase'] = getenv('PAYFAST_PASSPHRASE');
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['debug'] = FALSE;

  // OPTION B: Hardcoded (Only if environment variables not available)
  // Uncomment and replace with real credentials:
  // $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['mode'] = 'live';
  // $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_id'] = 'YOUR_PRODUCTION_MERCHANT_ID';
  // $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_key'] = 'YOUR_PRODUCTION_MERCHANT_KEY';
  // $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['passphrase'] = 'YOUR_PRODUCTION_PASSPHRASE';
  // $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['debug'] = FALSE;
}

// =============================================================================
// HOW THIS WORKS
// =============================================================================
/**
 * 1. Config file (config/shop/commerce_payment.commerce_payment_gateway.payfast.yml)
 *    has placeholders:
 *      merchant_id: abc123
 *      merchant_key: abc123
 *
 * 2. This is SAFE to commit to git because abc123 won't work with PayFast
 *
 * 3. When Drupal loads, settings.php runs AFTER config is imported
 *
 * 4. This code OVERWRITES the abc123 values with real credentials
 *
 * 5. Real credentials are NEVER in git, only in settings.php on each server
 */

// =============================================================================
// SETUP INSTRUCTIONS
// =============================================================================
/**
 * 1. Copy this entire file content
 *
 * 2. Open: webroot/sites/shop.sahistory.org.za/settings.php
 *
 * 3. Scroll to the VERY END of the file
 *
 * 4. Paste this code BEFORE the closing ?>
 *
 * 5. Replace placeholder credentials:
 *    - LOCAL: Get PayFast sandbox credentials
 *    - PRODUCTION: Get PayFast production credentials
 *
 * 6. Clear cache:
 *    ddev drush --uri=https://shop.ddev.site cr
 *
 * 7. Verify credentials loaded:
 *    ddev drush --uri=https://shop.ddev.site config:get commerce_payment.commerce_payment_gateway.payfast configuration
 *
 * 8. Test checkout with PayFast test card: 4000 0000 0000 0002
 */

// =============================================================================
// OBTAINING PAYFAST CREDENTIALS
// =============================================================================
/**
 * SANDBOX (for development/testing):
 * - URL: https://sandbox.payfast.co.za
 * - Register for free sandbox account
 * - Get: Merchant ID (format: 10000100)
 * - Get: Merchant Key (long alphanumeric)
 * - Set: Passphrase (in Settings > Integration)
 *
 * PRODUCTION (for live shop):
 * - URL: https://www.payfast.co.za
 * - Login to your SAHO PayFast account
 * - Go to: Settings > Integration
 * - Copy: Merchant ID
 * - Copy: Merchant Key
 * - Set: Passphrase (if not already set)
 * - Whitelist: Your server IP (if required)
 */

// =============================================================================
// VERIFICATION
// =============================================================================
/**
 * After adding to settings.php, verify the override works:
 *
 * Check if abc123 is replaced:
 *   ddev drush --uri=https://shop.ddev.site config:get commerce_payment.commerce_payment_gateway.payfast configuration.merchant_id
 *
 * Should show your actual merchant ID, NOT abc123
 *
 * If it still shows abc123:
 * - Check settings.php has the override code
 * - Check it's at the END of settings.php
 * - Clear cache again: ddev drush cr
 * - Check for PHP syntax errors: ddev drush status
 */

// =============================================================================
// SECURITY NOTES
// =============================================================================
/**
 * ✅ SAFE TO COMMIT TO GIT:
 * - config/shop/commerce_payment.commerce_payment_gateway.payfast.yml (has abc123)
 * - This file (PAYFAST_SETTINGS_OVERRIDE.php) - it's a template
 *
 * ❌ NEVER COMMIT TO GIT:
 * - settings.php with REAL credentials pasted in
 * - Any file containing actual merchant ID or merchant key
 *
 * Best Practice:
 * - Use environment variables in production
 * - Keep credentials in settings.local.php (in .gitignore)
 * - Never share credentials via email/Slack
 */

// =============================================================================
// TROUBLESHOOTING
// =============================================================================
/**
 * Problem: Payment fails with "Invalid merchant ID"
 * Solution: Verify merchant_id in settings.php matches PayFast dashboard
 *
 * Problem: Still seeing abc123 in config
 * Solution: Settings.php override not loaded. Check file location and syntax.
 *
 * Problem: PayFast says "Invalid signature"
 * Solution: Check passphrase matches exactly (case-sensitive)
 *
 * Problem: ITN callback not received
 * Solution: For local dev, use ddev share to get public URL for PayFast
 *
 * Problem: Mode is test but should be live (or vice versa)
 * Solution: Check environment detection logic in settings.php
 */
