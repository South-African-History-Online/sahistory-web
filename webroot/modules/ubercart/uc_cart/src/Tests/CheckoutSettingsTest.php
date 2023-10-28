<?php

namespace Drupal\uc_cart\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the checkout settings page.
 *
 * @group Ubercart
 */
class CheckoutSettingsTest extends UbercartTestBase {

  public function testEnableCheckout() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/config/checkout');
    $this->assertField(
      'uc_checkout_enabled',
      'Enable checkout field exists'
    );

    $this->drupalPostForm(
      'admin/store/config/checkout',
      array('uc_checkout_enabled' => FALSE),
      t('Save configuration')
    );

    $this->drupalPostForm(
      'node/' . $this->product->id(),
      [],
      t('Add to cart')
    );
    $this->assertNoRaw(t('Checkout'));
    $buttons = $this->xpath('//input[@value="' . t('Checkout') . '"]');
    $this->assertFalse(
      isset($buttons[0]),
      'The checkout button is not shown.'
    );
  }

  public function testAnonymousCheckout() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/config/checkout');
    $this->assertField(
      'uc_checkout_anonymous',
      'Anonymous checkout field exists'
    );

    $this->drupalPostForm(
      'admin/store/config/checkout',
      array('uc_checkout_anonymous' => FALSE),
      t('Save configuration')
    );

    $this->drupalLogout();
    $this->drupalPostForm(
      'node/' . $this->product->id(),
      [],
      t('Add to cart')
    );
    $this->drupalPostForm(
      'cart',
      [],
      t('Checkout')
    );
    $this->assertNoText(
      'Enter your billing address and information here.',
      'The checkout page is not displayed.'
    );
  }
}
