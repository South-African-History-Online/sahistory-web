# PayFast Security Configuration - SAHO Shop

**Date**: 2026-01-29
**Status**: ✅ Configured with placeholder values

---

## How PayFast Credentials are Secured

### The Strategy

We use a **two-layer approach**:
1. **Config file** (in git): Contains placeholder values `abc123`
2. **Settings.php** (not in git): Overrides with real credentials at runtime

This ensures:
- ✅ Config can be version controlled safely
- ✅ Real credentials never touch git
- ✅ Different credentials per environment (local/production)
- ✅ Easy to update credentials without changing config

---

## Files Overview

### 1. Config File (SAFE to commit)
**Location**: `config/shop/commerce_payment.commerce_payment_gateway.payfast.yml`

```yaml
configuration:
  merchant_id: abc123  # Placeholder - won't work with PayFast
  merchant_key: abc123 # Placeholder - won't work with PayFast
  passphrase: ''       # Empty placeholder
```

**Status**: ✅ Committed to git with placeholder values

### 2. Settings Override (Template - SAFE to commit)
**Location**: `PAYFAST_SETTINGS_OVERRIDE.php`

This is a **template file** showing how to override the placeholders.
Contains example code but no real credentials.

**Status**: ✅ Committed to git as documentation

### 3. Settings.php (NEVER commit)
**Location**: `webroot/sites/shop.sahistory.org.za/settings.php`

This file should contain the actual credentials and override code.

**Status**: ⚠️ In `.gitignore` - never committed

---

## How the Override Works

### Step-by-Step Flow

1. **Drupal loads configuration**
   - Reads `config/shop/commerce_payment.commerce_payment_gateway.payfast.yml`
   - Sets `merchant_id: abc123` and `merchant_key: abc123`

2. **Drupal loads settings.php**
   - Runs the override code at the end of settings.php
   - Executes: `$config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_id'] = 'REAL_ID';`

3. **Override replaces placeholders**
   - The abc123 values are replaced with real credentials
   - PayFast now sees real merchant ID and key
   - Transactions can process successfully

4. **Git remains clean**
   - Config file still has abc123 (safe)
   - Settings.php is not tracked by git
   - Real credentials never touch version control

---

## Setup Instructions

### For Local Development (DDEV)

1. **Get PayFast Sandbox Credentials**:
   - Register at: https://sandbox.payfast.co.za
   - Obtain: Merchant ID, Merchant Key, Passphrase

2. **Edit settings.php**:
   ```bash
   nano webroot/sites/shop.sahistory.org.za/settings.php
   ```

3. **Add at the END of file** (before closing `?>`):
   ```php
   // PayFast credential override
   if (isset($_ENV['DDEV_HOSTNAME'])) {
     $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['mode'] = 'test';
     $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_id'] = '10000100';
     $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_key'] = 'YOUR_SANDBOX_KEY';
     $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['passphrase'] = 'YOUR_SANDBOX_PASS';
     $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['debug'] = TRUE;
   }
   ```

4. **Replace placeholders**:
   - `10000100` → Your actual sandbox merchant ID
   - `YOUR_SANDBOX_KEY` → Your actual sandbox merchant key
   - `YOUR_SANDBOX_PASS` → Your actual sandbox passphrase

5. **Clear cache**:
   ```bash
   ddev drush --uri=https://shop.ddev.site cr
   ```

6. **Verify override worked**:
   ```bash
   ddev drush --uri=https://shop.ddev.site config:get commerce_payment.commerce_payment_gateway.payfast configuration.merchant_id
   ```
   Should show your merchant ID, NOT abc123

### For Production (shop.sahistory.org.za)

1. **Get PayFast Production Credentials**:
   - Login at: https://www.payfast.co.za
   - Go to: Settings > Integration
   - Copy: Merchant ID and Merchant Key
   - Set: Passphrase (if not already set)

2. **Edit production settings.php**:
   ```bash
   nano /path/to/webroot/sites/shop.sahistory.org.za/settings.php
   ```

3. **Add at the END of file**:
   ```php
   // PayFast credential override - PRODUCTION
   if (strpos($_SERVER['HTTP_HOST'] ?? '', '.sahistory.org.za') !== FALSE) {
     $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['mode'] = 'live';
     $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_id'] = getenv('PAYFAST_MERCHANT_ID');
     $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_key'] = getenv('PAYFAST_MERCHANT_KEY');
     $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['passphrase'] = getenv('PAYFAST_PASSPHRASE');
     $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['debug'] = FALSE;
   }
   ```

4. **Set environment variables** on production server:
   ```bash
   export PAYFAST_MERCHANT_ID="your_production_id"
   export PAYFAST_MERCHANT_KEY="your_production_key"
   export PAYFAST_PASSPHRASE="your_production_passphrase"
   ```

   Or hardcode (less secure but simpler):
   ```php
   $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_id'] = 'REAL_PRODUCTION_ID';
   $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['merchant_key'] = 'REAL_PRODUCTION_KEY';
   $config['commerce_payment.commerce_payment_gateway.payfast']['configuration']['passphrase'] = 'REAL_PRODUCTION_PASS';
   ```

5. **Clear cache on production**:
   ```bash
   drush --uri=https://shop.sahistory.org.za cr
   ```

6. **Test with small transaction**:
   - Use REAL credit card
   - Purchase cheapest item or R1 test amount
   - Verify payment processes and order updates

---

## Verification Commands

### Check if override is working
```bash
# Should show your merchant ID, NOT abc123
ddev drush --uri=https://shop.ddev.site config:get commerce_payment.commerce_payment_gateway.payfast configuration.merchant_id

# View entire gateway config
ddev drush --uri=https://shop.ddev.site config:get commerce_payment.commerce_payment_gateway.payfast configuration

# Check mode (test or live)
ddev drush --uri=https://shop.ddev.site config:get commerce_payment.commerce_payment_gateway.payfast configuration.mode
```

### What you should see
```yaml
# LOCAL (after override):
merchant_id: '10000100'         # Your sandbox ID
merchant_key: 'sandbox_key...'  # Your sandbox key
mode: test                       # Sandbox mode
debug: 1                         # Debug enabled

# PRODUCTION (after override):
merchant_id: 'your_prod_id'      # Your production ID
merchant_key: 'your_prod_key'    # Your production key
mode: live                       # LIVE mode
debug: 0                         # Debug disabled
```

### What you should NOT see
```yaml
merchant_id: abc123  # ❌ Means override didn't work
merchant_key: abc123 # ❌ Means override didn't work
```

---

## Git Status

### What IS in git (safe)
✅ `config/shop/commerce_payment.commerce_payment_gateway.payfast.yml` (with abc123)
✅ `PAYFAST_SETTINGS_OVERRIDE.php` (template with no real credentials)
✅ `PAYFAST_SETTINGS_TEMPLATE.php` (examples)
✅ `PAYFAST_SECURITY_README.md` (this file)

### What is NOT in git (protected)
❌ `webroot/sites/shop.sahistory.org.za/settings.php` (in .gitignore)
❌ Any file with real merchant IDs or keys
❌ Environment variable files with credentials

### Verify git protection
```bash
# This should show abc123 (safe)
git show HEAD:config/shop/commerce_payment.commerce_payment_gateway.payfast.yml | grep merchant_id

# This should NOT show settings.php
git status | grep settings.php

# Check gitignore has settings.php
cat .gitignore | grep settings
```

---

## Security Checklist

### Before Committing
- [ ] Config file has `abc123` placeholders (not real credentials)
- [ ] Settings.php is in `.gitignore`
- [ ] No real credentials in any committed file
- [ ] Run: `git diff` to verify no credentials being committed
- [ ] Run: `git log --all --full-history -- "*payfast*"` to check history

### Before Going Live
- [ ] Production credentials obtained from PayFast
- [ ] Settings.php override added on production server
- [ ] Mode set to `live` in production
- [ ] Debug set to `FALSE` in production
- [ ] Cache cleared on production
- [ ] Verification command shows real credentials (not abc123)
- [ ] ONE test transaction completed successfully
- [ ] Order status updates correctly
- [ ] Email confirmations received

### After Going Live
- [ ] Monitor first 10 transactions
- [ ] Check watchdog logs for PayFast errors
- [ ] Verify ITN callbacks processing
- [ ] Confirm payment amounts match orders
- [ ] Test subscription billing (if applicable)

---

## Troubleshooting

### Problem: Config still shows abc123
**Cause**: Settings.php override not loaded or has syntax error

**Solutions**:
1. Check settings.php file exists: `ls webroot/sites/shop.sahistory.org.za/settings.php`
2. Check override code is at END of settings.php
3. Check for PHP syntax errors: `ddev drush status`
4. Clear cache: `ddev drush cr`
5. Verify environment detection logic (DDEV vs production)

### Problem: Payment fails with "Invalid merchant ID"
**Cause**: Merchant ID doesn't match PayFast account

**Solutions**:
1. Login to PayFast dashboard
2. Verify merchant ID matches exactly
3. Check mode matches (test for sandbox, live for production)
4. Ensure no extra spaces or quotes in settings.php

### Problem: "Invalid signature" error
**Cause**: Passphrase mismatch

**Solutions**:
1. Check passphrase in settings.php matches PayFast dashboard
2. Passphrase is case-sensitive
3. Check for hidden characters or spaces
4. Regenerate passphrase in PayFast if needed

### Problem: ITN callback not received
**Cause**: PayFast can't reach your server (common in local dev)

**Solutions**:
1. For local dev: Use `ddev share` to get public URL
2. Update PayFast settings with ngrok URL
3. Check firewall allows PayFast IPs
4. Verify ITN URL in PayFast dashboard
5. Check watchdog logs: `ddev drush wd-show --type=commerce_payment`

### Problem: Different credentials in different places
**Cause**: Multiple configuration sources conflicting

**Solutions**:
1. Check database: May have old credentials stored
2. Clear config cache: `ddev drush cr`
3. Reimport config: `ddev drush cim -y`
4. Verify settings.php is last to load (end of file)

---

## Support Resources

**PayFast Documentation**:
- Sandbox: https://sandbox.payfast.co.za/documentation
- API Docs: https://developers.payfast.co.za/
- Support: support@payfast.co.za
- Phone: 0861 729 327

**SAHO Shop Documentation**:
- Main report: `reports/SAHO_SHOP_CONSOLIDATED_REPORT_2026-01-29.md`
- Subscription fixes: `reports/SUBSCRIPTION_ERROR_FIX_2026-01-29.md`
- Testing guide: Test checkout with card 4000 0000 0000 0002

**Drupal Commerce**:
- Payment docs: https://docs.drupalcommerce.org/commerce2/developer-guide/payments
- Config overrides: https://www.drupal.org/docs/configuration-management/configuration-override-system

---

## Quick Reference

### Test Card (Sandbox)
```
Card Number: 4000 0000 0000 0002
CVV: 123
Expiry: Any future date (e.g., 12/28)
Name: Any test name
```

### URLs
- **Sandbox Login**: https://sandbox.payfast.co.za
- **Production Login**: https://www.payfast.co.za
- **Shop URL**: https://shop.sahistory.org.za (or https://shop.ddev.site local)
- **Payment Gateway Admin**: /admin/commerce/config/payment-gateways

### Key Commands
```bash
# Export config with placeholders
ddev drush --uri=https://shop.ddev.site cex -y

# Check credentials (should NOT be abc123)
ddev drush --uri=https://shop.ddev.site config:get commerce_payment.commerce_payment_gateway.payfast configuration.merchant_id

# Clear cache after settings.php change
ddev drush --uri=https://shop.ddev.site cr

# View payment logs
ddev drush --uri=https://shop.ddev.site wd-show --type=commerce_payment
```

---

**Last Updated**: 2026-01-29
**Status**: Configuration exported with abc123 placeholders
**Next Step**: Add settings.php override with real credentials
**Security**: ✅ No credentials in git

---

**End of PayFast Security Documentation**
