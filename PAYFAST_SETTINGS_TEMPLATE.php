<?php

/**
 * @file
 * PayFast Configuration Override Template for SAHO Shop
 *
 * SECURITY: These credentials should NEVER be committed to Git!
 *
 * Add this code to settings.php in one of these locations:
 * 1. webroot/sites/shop.sahistory.org.za/settings.php (shop-specific)
 * 2. webroot/sites/shop.sahistory.org.za/settings.local.php (local/not in git)
 * 3. settings.php at the end, wrapped in environment detection
 *
 * IMPORTANT: Different credentials for each environment!
 */

// ==============================================================================
// APPROACH 1: Environment-Specific Settings (RECOMMENDED)
// ==============================================================================
// Add this to webroot/sites/shop.sahistory.org.za/settings.php

/**
 * PayFast Payment Gateway Configuration Override
 *
 * This overrides the configuration stored in the database/config files
 * to keep sensitive credentials out of version control.
 */

// Detect environment
$environment = getenv('ENVIRONMENT') ?: 'production';

if ($environment === 'local' || $environment === 'development') {
  // LOCAL / DEVELOPMENT - Use PayFast Sandbox
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration'] = [
    'mode' => 'test',  // Sandbox mode
    'merchant_id' => 'SANDBOX_MERCHANT_ID',  // Replace with your sandbox merchant ID
    'merchant_key' => 'SANDBOX_MERCHANT_KEY',  // Replace with your sandbox merchant key
    'passphrase' => 'SANDBOX_PASSPHRASE',  // Replace with your sandbox passphrase
    'debug' => TRUE,  // Enable debug logging
  ];
}
elseif ($environment === 'production') {
  // PRODUCTION - Use PayFast Live Credentials
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration'] = [
    'mode' => 'live',  // PRODUCTION MODE - REAL MONEY!
    'merchant_id' => 'PRODUCTION_MERCHANT_ID',  // ⚠️ Replace with REAL merchant ID
    'merchant_key' => 'PRODUCTION_MERCHANT_KEY',  // ⚠️ Replace with REAL merchant key
    'passphrase' => 'PRODUCTION_PASSPHRASE',  // ⚠️ Replace with REAL passphrase
    'debug' => FALSE,  // Disable debug in production
  ];
}

// ==============================================================================
// APPROACH 2: Environment Variables (MOST SECURE)
// ==============================================================================
// Add this to settings.php and set environment variables on server

/**
 * PayFast credentials from environment variables
 *
 * Set these in your hosting environment (Pantheon, Acquia, custom server):
 * - PAYFAST_MODE (test or live)
 * - PAYFAST_MERCHANT_ID
 * - PAYFAST_MERCHANT_KEY
 * - PAYFAST_PASSPHRASE
 */

if (getenv('PAYFAST_MERCHANT_ID')) {
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration'] = [
    'mode' => getenv('PAYFAST_MODE') ?: 'live',
    'merchant_id' => getenv('PAYFAST_MERCHANT_ID'),
    'merchant_key' => getenv('PAYFAST_MERCHANT_KEY'),
    'passphrase' => getenv('PAYFAST_PASSPHRASE'),
    'debug' => (getenv('PAYFAST_MODE') === 'test'),
  ];
}

// ==============================================================================
// APPROACH 3: Server-Specific Settings File (ALTERNATIVE)
// ==============================================================================
// Create different files for each environment (NOT in Git):
// - settings.local.php (local development)
// - settings.staging.php (staging server)
// - settings.production.php (production server)

// Add this to main settings.php:
if (file_exists(__DIR__ . '/settings.local.php')) {
  include __DIR__ . '/settings.local.php';
}

// Then in settings.local.php (NEVER commit this):
// $config['commerce_payment.commerce_payment_gateway.payfast']['configuration'] = [
//   'mode' => 'test',
//   'merchant_id' => 'LOCAL_SANDBOX_ID',
//   'merchant_key' => 'LOCAL_SANDBOX_KEY',
//   'passphrase' => 'LOCAL_SANDBOX_PASS',
//   'debug' => TRUE,
// ];

// ==============================================================================
// ALTERNATIVE: Just Add Through UI (Simple but Less Secure)
// ==============================================================================
// If you don't want settings.php overrides, you can:
// 1. Configure PayFast through UI: /admin/commerce/config/payment-gateways/add
// 2. Credentials stored in database
// 3. Export config: drush cex -y
// 4. DO NOT commit the payment gateway config file
// 5. Add to .gitignore:
//    config/shop/commerce_payment.commerce_payment_gateway.payfast.yml

// To gitignore the payment gateway config:
// echo "config/shop/commerce_payment.commerce_payment_gateway.payfast.yml" >> .gitignore

// ==============================================================================
// TESTING YOUR CONFIGURATION
// ==============================================================================
// After adding settings, verify with Drush:
// ddev drush --uri=https://shop.ddev.site config:get commerce_payment.commerce_payment_gateway.payfast configuration
//
// This should show your merchant ID, but keys should be masked if configured correctly.

// ==============================================================================
// PAYFAST SANDBOX CREDENTIALS (FOR TESTING)
// ==============================================================================
// Obtain from: https://sandbox.payfast.co.za
// Register for a sandbox account and get:
// - Merchant ID (10000100 format)
// - Merchant Key (long alphanumeric string)
// - Passphrase (custom secret phrase you create)

// ==============================================================================
// PAYFAST PRODUCTION CREDENTIALS
// ==============================================================================
// Obtain from: https://www.payfast.co.za
// From your PayFast account dashboard:
// 1. Login to PayFast
// 2. Go to Settings > Integration
// 3. Copy Merchant ID and Merchant Key
// 4. Set a Passphrase (Settings > Integration > Passphrase)
// 5. Whitelist your server IP if required

// ==============================================================================
// SECURITY BEST PRACTICES
// ==============================================================================
// ✅ DO:
// - Use environment variables in production
// - Use settings.php overrides
// - Keep credentials in settings.local.php (not in git)
// - Use different credentials for dev/staging/production
// - Test in sandbox first
// - Enable 2FA on PayFast account
//
// ❌ DON'T:
// - Commit credentials to Git
// - Share credentials in Slack/email
// - Use production credentials in development
// - Hardcode credentials in module code
// - Leave debug mode enabled in production

// ==============================================================================
// RECOMMENDED SETUP FOR SAHO
// ==============================================================================
/**
 * Add to: webroot/sites/shop.sahistory.org.za/settings.php
 * At the very end of the file:
 */

// PayFast configuration override (environment-specific)
if (isset($_ENV['DDEV_HOSTNAME']) || isset($_ENV['IS_DDEV_PROJECT'])) {
  // DDEV Local Development
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration'] = [
    'mode' => 'test',
    'merchant_id' => '10000100',  // Replace with your sandbox ID
    'merchant_key' => 'your_sandbox_key_here',  // Replace
    'passphrase' => 'your_sandbox_passphrase',  // Replace
    'debug' => TRUE,
  ];
}
elseif (strpos($_SERVER['HTTP_HOST'], '.sahistory.org.za') !== FALSE) {
  // Production SAHO
  $config['commerce_payment.commerce_payment_gateway.payfast']['configuration'] = [
    'mode' => 'live',
    'merchant_id' => getenv('PAYFAST_MERCHANT_ID') ?: 'YOUR_PRODUCTION_ID',
    'merchant_key' => getenv('PAYFAST_MERCHANT_KEY') ?: 'YOUR_PRODUCTION_KEY',
    'passphrase' => getenv('PAYFAST_PASSPHRASE') ?: 'YOUR_PRODUCTION_PASS',
    'debug' => FALSE,
  ];
}

// Load environment-specific settings if available
$environment_settings = __DIR__ . '/settings.' . $environment . '.php';
if (file_exists($environment_settings)) {
  include $environment_settings;
}

// ==============================================================================
// NEXT STEPS
// ==============================================================================
/**
 * 1. Get PayFast sandbox credentials from stakeholder
 * 2. Add settings override to settings.php using RECOMMENDED SETUP above
 * 3. Clear cache: ddev drush --uri=https://shop.ddev.site cr
 * 4. Test checkout flow
 * 5. Use PayFast test card: 4000 0000 0000 0002
 * 6. Verify ITN callback (may need ngrok: ddev share)
 * 7. Before production: Get production credentials
 * 8. Update settings.php with production credentials
 * 9. Test with ONE small real transaction
 * 10. Go live!
 */
