<?php

namespace Drupal\uc_payment_pack\Tests;

use Drupal\uc_order\Entity\Order;

/**
 * Tests the payment method pack CashOnDelivery payment method.
 *
 * @group Ubercart
 */
class CashOnDeliveryTest extends PaymentPackTestBase {

  /**
   * Tests for CashOnDelivery payment method.
   */
  public function testCashOnDelivery() {
    $this->drupalGet('admin/store/config/payment/add/cod');
    $this->assertFieldByName('settings[policy]', 'Full payment is expected upon delivery or prior to pick-up.', 'Default COD policy found.');

    $cod = $this->createPaymentMethod('cod', [
      'settings[policy]' => $this->randomString(),
    ]);
    // @todo: Test enabling delivery date on settings page.

    // Test checkout page.
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', $cod['id'], 'COD payment method is selected at checkout.');
    $this->assertEscaped($cod['settings[policy]'], 'COD policy found at checkout.');

    // Test review order page.
    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Cash on delivery', 'COD payment method found on review page.');
    $this->drupalPostForm(NULL, array(), 'Submit order');

    // Test user order view.
    $order = Order::load(1);
    $this->assertEqual($order->getPaymentMethodId(), $cod['id'], 'Order has COD payment method.');

    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    $this->assertText('Method: Cash on delivery', 'COD payment method displayed.');

    // Test admin order view.
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText('Method: Cash on delivery', 'COD payment method displayed.');
  }

}
