# SAHO Shop - Delivery Options Configuration Guide

**Shop URL:** shop.sahistory.org.za
**Date:** 2026-02-13
**Payment Gateway:** PayFast (NOT PayPal)

## Delivery Options Overview

The SAHO shop offers three delivery methods:

### 1. Pickup (Free) - Cape Town Only
- **Cost:** R0 (Free)
- **Availability:** Cape Town customers only
- **Instructions:** Customer collects from 349 Albert Road, Woodstock

### 2. Local Delivery - Courier Guy (Tiered Pricing)
- **Courier:** Courier Guy or similar
- **Pricing by Quantity:**
  - 1 book: R150
  - 4 books: R200
  - 8+ books: R250

### 3. International Delivery - Manual Quote
- **Process:** Customer places order, then receives email for shipping quote
- **Note:** Shipping cost determined based on destination and weight

---

## Configuration Steps

### Prerequisites

Ensure the following modules are installed and enabled:

```bash
# Check if Commerce Shipping is installed
drush pm:list --filter=commerce_shipping

# If not installed, add it
composer require drupal/commerce_shipping

# Enable required modules
drush en commerce_shipping -y
```

---

## Step 1: Create Shipping Methods

Navigate to: **Commerce > Configuration > Shipping > Shipping methods**
URL: `/admin/commerce/config/shipping-methods`

### Shipping Method 1: Cape Town Pickup (Free)

1. Click "Add shipping method"
2. **Name:** Cape Town Pickup
3. **Plugin:** Flat rate
4. **Stores:** Select shop store
5. **Conditions:** None (available to all)
6. **Services:**
   - **Service name:** Pickup at 349 Albert Road, Woodstock
   - **Amount:** 0.00 ZAR
   - **Description:** Free pickup for Cape Town customers. Collect from our office at 349 Albert Road, Woodstock during business hours (Mon-Fri 9am-5pm).
7. Save

### Shipping Method 2: Local Delivery (Tiered)

**Option A: Using Flat Rate per Quantity (Recommended)**

1. Click "Add shipping method"
2. **Name:** Local Delivery (Courier Guy)
3. **Plugin:** Flat rate per item
4. **Stores:** Select shop store
5. **Conditions:**
   - Add condition: Order > Total quantity
   - Configure rules for tiered pricing
6. **Services:**

Create THREE separate flat rate services with conditions:

**Service 1: 1-3 Books**
- **Service name:** Courier Guy (1-3 books)
- **Amount:** 150.00 ZAR
- **Condition:** Order quantity is between 1 and 3

**Service 2: 4-7 Books**
- **Service name:** Courier Guy (4-7 books)
- **Amount:** 200.00 ZAR
- **Condition:** Order quantity is between 4 and 7

**Service 3: 8+ Books**
- **Service name:** Courier Guy (8+ books)
- **Amount:** 250.00 ZAR
- **Condition:** Order quantity is greater than or equal to 8

**Option B: Custom Shipping Rate Plugin (Advanced)**

If flat rate conditions don't support quantity-based pricing, create a custom shipping rate plugin:

**File:** `webroot/modules/custom/saho_shop_shipping/src/Plugin/Commerce/ShippingMethod/CourierGuyTiered.php`

```php
<?php

namespace Drupal\saho_shop_shipping\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Courier Guy tiered shipping method.
 *
 * @CommerceShippingMethod(
 *   id = "courier_guy_tiered",
 *   label = @Translation("Courier Guy (Tiered Pricing)"),
 * )
 */
class CourierGuyTiered extends ShippingMethodBase {

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    $rates = [];
    $order = $shipment->getOrder();

    // Calculate total quantity of items
    $total_quantity = 0;
    foreach ($order->getItems() as $item) {
      $total_quantity += (int) $item->getQuantity();
    }

    // Determine shipping cost based on quantity
    if ($total_quantity >= 1 && $total_quantity <= 3) {
      $amount = '150.00';
      $label = $this->t('Courier Guy (1-3 books) - R150');
    }
    elseif ($total_quantity >= 4 && $total_quantity <= 7) {
      $amount = '200.00';
      $label = $this->t('Courier Guy (4-7 books) - R200');
    }
    else {
      $amount = '250.00';
      $label = $this->t('Courier Guy (8+ books) - R250');
    }

    $rates[] = new ShippingRate([
      'shipping_method_id' => $this->parentEntity->id(),
      'service' => [
        'id' => 'courier_guy',
        'label' => $label,
      ],
      'amount' => new Price($amount, 'ZAR'),
    ]);

    return $rates;
  }

}
```

### Shipping Method 3: International (Manual Quote)

1. Click "Add shipping method"
2. **Name:** International Delivery
3. **Plugin:** Flat rate
4. **Stores:** Select shop store
5. **Conditions:** None
6. **Services:**
   - **Service name:** International Shipping (Quote Required)
   - **Amount:** 0.00 ZAR
   - **Description:** International shipping cost will be calculated and invoiced separately. We will contact you with a quote based on your location and order weight.
7. Save

**Note:** Add a checkout message/pane to inform international customers they will be contacted for shipping quote.

---

## Step 2: Configure Checkout Flow

Navigate to: **Commerce > Configuration > Checkout flows**
URL: `/admin/commerce/config/checkout-flows`

### Add Shipping Information Pane

1. Edit the default checkout flow
2. Ensure these panes are included and ordered:
   - **Order information** (step 1)
   - **Shipping information** (step 2) ← Add if missing
   - **Order review** (step 3)
   - **Payment** (step 4)
   - **Complete** (step 5)

3. Configure "Shipping information" pane:
   - **Require shipping profile:** Yes
   - **Available shipping methods:** Select all three methods
   - **Default shipping method:** Cape Town Pickup (Free)

---

## Step 3: Add Order Note for International Customers

Create a custom checkout pane message for international orders:

### Option A: Using Checkout Messaging Module

```bash
composer require drupal/commerce_checkout_message
drush en commerce_checkout_message -y
```

Then configure a message that displays when "International Delivery" is selected.

### Option B: Custom Template Override

**File:** `webroot/themes/custom/saho/templates/commerce/commerce-checkout-flow--with-shipping.html.twig`

```twig
{# Add after shipping method selection #}
<div class="international-shipping-notice alert alert-info" style="display:none;">
  <h4>International Shipping Notice</h4>
  <p>
    Thank you for your international order! We will contact you within 1-2 business days
    with a shipping quote based on your location and order details.
  </p>
  <p>
    <strong>Contact:</strong> shop@sahistory.org.za
  </p>
</div>

<script>
// Show notice when international shipping is selected
(function() {
  const shippingSelect = document.querySelector('[name*="shipping_method"]');
  if (shippingSelect) {
    shippingSelect.addEventListener('change', function() {
      const notice = document.querySelector('.international-shipping-notice');
      if (this.value.includes('international')) {
        notice.style.display = 'block';
      } else {
        notice.style.display = 'none';
      }
    });
  }
})();
</script>
```

---

## Step 4: Configure Default Store Address

Navigate to: **Commerce > Configuration > Stores**
URL: `/admin/commerce/config/stores`

1. Edit the shop store
2. Set **Store address:**
   - **Address line 1:** 349 Albert Road
   - **City:** Woodstock
   - **State/Province:** Western Cape
   - **Postal code:** 7925
   - **Country:** South Africa
3. Save

This address will be used for:
- Pickup location reference
- Origin address for shipping calculations

---

## Step 5: Configure PayFast Payment Gateway

Navigate to: **Commerce > Configuration > Payment gateways**
URL: `/admin/commerce/config/payment-gateways`

**IMPORTANT:** Remove any PayPal payment gateways if they exist.

1. Ensure only PayFast gateway is enabled
2. Configure PayFast credentials:
   - Merchant ID
   - Merchant Key
   - Passphrase
   - **Mode:** Production (or Test for staging)

---

## Step 6: Create Order Confirmation Email for International Orders

Navigate to: **Commerce > Configuration > Emails**
URL: `/admin/commerce/config/emails`

1. Add new email: "International Shipping Quote Request"
2. **Subject:** International Order Received - Shipping Quote Coming Soon
3. **Body:**

```
Dear {{ order.mail }},

Thank you for your order from SAHO Shop!

Order Number: {{ order.order_number }}
Order Total: {{ order.total_price }}

We noticed you selected International Delivery. We will calculate the best shipping
option for your location and contact you within 1-2 business days with:

- Shipping cost quote
- Estimated delivery time
- Payment instructions for shipping

If you have any questions, please contact us at shop@sahistory.org.za

Best regards,
South African History Online
349 Albert Road, Woodstock, Cape Town
```

4. **Event:** Order placed
5. **Conditions:** Shipping method contains "international"
6. Save

---

## Testing Checklist

### Test on Staging (shop.staging.sahistory.org.za)

- [ ] **Pickup Option:**
  - Add 1 book to cart
  - Select "Cape Town Pickup"
  - Verify cost: R0
  - Verify pickup instructions display

- [ ] **Local Delivery - 1 Book:**
  - Add 1 book to cart
  - Select "Courier Guy"
  - Verify cost: R150

- [ ] **Local Delivery - 4 Books:**
  - Add 4 books to cart
  - Select "Courier Guy"
  - Verify cost: R200

- [ ] **Local Delivery - 8 Books:**
  - Add 8+ books to cart
  - Select "Courier Guy"
  - Verify cost: R250

- [ ] **International:**
  - Add any books to cart
  - Select "International Delivery"
  - Verify notice displays about manual quote
  - Complete order
  - Verify email sent

- [ ] **Payment:**
  - Verify only PayFast appears (NO PayPal)
  - Test PayFast payment flow
  - Verify order confirmation

---

## Production Deployment

After testing on staging:

```bash
# Export configuration
drush @shop config:export -y

# Commit changes
git add config/sync/commerce.commerce_shipping_method.*
git commit -m "Configure shop delivery options: Pickup, Courier Guy tiered, International"

# Deploy to production
git tag v1.6.4
git push origin v1.6.4
```

---

## Support & Troubleshooting

### Common Issues

**Issue:** Tiered pricing not working
**Solution:** Use custom shipping plugin (see Option B above)

**Issue:** International notice not showing
**Solution:** Clear Drupal cache: `drush cr`

**Issue:** Wrong shipping costs
**Solution:** Check shipping method conditions and re-save

### Contact

**Shop Admin:** shop@sahistory.org.za
**Technical Support:** GitHub Issues

---

**Last Updated:** 2026-02-13
**Shop Version:** v1.6.4 (pending)
