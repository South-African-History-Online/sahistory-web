<?php

namespace Drupal\uc_payment_pack\Tests;

use Drupal\uc_order\Entity\Order;
use Drupal\uc_store\Address;

/**
 * Tests the payment method pack Check payment method.
 *
 * @group Ubercart
 */
class CheckTest extends PaymentPackTestBase {

  /**
   * Tests for Check payment method.
   */
  public function testCheck() {
    $this->drupalGet('admin/store/config/payment/add/check');
    $this->assertText('Check');
    $this->assertFieldByName('settings[policy]', 'Personal and business checks will be held for up to 10 business days to ensure payment clears before an order is shipped.', 'Default check payment policy found.');

    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'settings[policy]' => $this->randomString(),
    ];

    // Fill in and save the check address settings.
    $address = Address::create();
    $address
      ->setFirstName($this->randomMachineName(6))
      ->setCompany($this->randomMachineName(10))
      ->setStreet1(mt_rand(100, 1000) . ' ' . $this->randomMachineName(10))
      ->setStreet2('Suite ' . mt_rand(100, 999))
      ->setCity($this->randomMachineName(10))
      ->setPostalCode(mt_rand(10000, 99999));
    $country_id = array_rand(\Drupal::service('country_manager')->getEnabledList());
    $address->setCountry($country_id);
    $this->drupalPostAjaxForm(NULL, ['settings[address][country]' => $address->getCountry()], 'settings[address][country]');

    $edit += array(
      'settings[name]' => $address->getFirstName(),
      'settings[address][company]' => $address->getCompany(),
      'settings[address][street1]' => $address->getStreet1(),
      'settings[address][street2]' => $address->getStreet2(),
      'settings[address][city]' => $address->getCity(),
      'settings[address][country]' => $address->getCountry(),
      'settings[address][postal_code]' => $address->getPostalCode(),
    );
    // Don't try to set the zone unless the country has zones!
    $zone_list = \Drupal::service('country_manager')->getZoneList($country_id);
    if (!empty($zone_list)) {
      $address->setZone(array_rand($zone_list));
      $edit += array(
        'settings[address][zone]' => $address->getZone(),
      );
    }

    $this->drupalPostForm(NULL, $edit, 'Save');

    // Test that check settings show up on checkout page.
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', $edit['id'], 'Check payment method is selected at checkout.');
    $this->assertText('Checks should be made out to:');
    $this->assertRaw((string) $address, 'Properly formatted check mailing address found.');
    $this->assertEscaped($edit['settings[policy]'], 'Check payment policy found at checkout.');

    // Test that check settings show up on review order page.
    $this->drupalPostForm(NULL, array(), 'Review order');
    $this->assertText('Check', 'Check payment method found on review page.');
    $this->assertText('Mail to', 'Check payment method help text found on review page.');
    $this->assertRaw((string) $address, 'Properly formatted check mailing address found.');
    $this->drupalPostForm(NULL, array(), 'Submit order');

    // Test user order view.
    $order = Order::load(1);
    $this->assertEqual($order->getPaymentMethodId(), $edit['id'], 'Order has check payment method.');

    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    $this->assertText('Method: Check', 'Check payment method displayed.');

    // Test admin order view - receive check.
    $this->drupalGet('admin/store/orders/' . $order->id());
    $this->assertText('Method: Check', 'Check payment method displayed.');
    $this->assertLink('Receive Check');
    $this->clickLink('Receive Check');
    $this->assertFieldByName('amount', number_format($order->getTotal(), 2, '.', ''), 'Amount field defaults to order total.');

    // Random receive date between tomorrow and 1 year from now.
    $receive_date = strtotime('now +' . mt_rand(1, 365) . ' days');
    $formatted = \Drupal::service('date.formatter')->format($receive_date, 'uc_store');

    $edit = array(
      'comment' => $this->randomString(),
      'clear_date[date]' => date('Y-m-d', $receive_date),
    );
    $this->drupalPostForm(NULL, $edit, 'Receive check');
    $this->assertNoLink('Receive Check');
    $this->assertText('Clear Date: ' . $formatted, 'Check clear date found.');

    // Test that user order view shows check received.
    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    $this->assertText('Check received');
    $this->assertText('Expected clear date:');
    $this->assertText($formatted, 'Check clear date found.');
  }

}
