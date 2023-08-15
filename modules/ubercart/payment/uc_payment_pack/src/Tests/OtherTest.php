<?php

namespace Drupal\uc_payment_pack\Tests;

use Drupal\uc_order\Entity\Order;

/**
 * Tests the payment method pack Other payment method.
 *
 * @group Ubercart
 */
class OtherTest extends PaymentPackTestBase {

  /**
   * Tests for Other payment method.
   */
  public function testOther() {
    $other = $this->createPaymentMethod('other');

    // Test checkout page
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', $other['id'], 'Other payment method is selected at checkout.');

    // Test review order page
    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Other', 'Other payment method found on review page.');
    $this->drupalPostForm(NULL, array(), 'Submit order');

    // Test user order view
    $order = Order::load(1);
    $this->assertEqual($order->getPaymentMethodId(), $other['id'], 'Order has other payment method.');

    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    $this->assertText('Method: Other', 'Other payment method displayed.');

    // Test admin order view
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText('Method: Other', 'Other payment method displayed.');

    $this->drupalGet('admin/store/orders/' . $order->id() . '/edit');
    $this->assertFieldByName('payment_method', $other['id'], 'Other payment method is selected in the order edit form.');
    $edit = array(
      'payment_details[description]' => $this->randomString(),
    );
    $this->drupalPostForm(NULL, array(), 'Save changes');
    // @todo: Test storage of payment details.
  }

}
