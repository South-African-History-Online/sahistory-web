<?php

namespace Drupal\uc_tax\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\uc_order\Entity\Order;

/**
 * Tests that historical tax data is stored correctly, and that the proper amount is displayed.
 *
 * @group Ubercart
 */
class StoredTaxTest extends TaxTestBase {

  public function testTaxDisplay() {
    $this->drupalLogin($this->adminUser);

    // Enable a payment method for the payment preview checkout pane.
    $this->createPaymentMethod('check');

    // Create a 20% inclusive tax rate.
    $rate = (object) array(
      'name' => $this->randomMachineName(8),
      'rate' => 0.2,
      'taxed_product_types' => array('product'),
      'taxed_line_items' => array(),
      'weight' => 0,
      'shippable' => 0,
      'display_include' => 1,
      'inclusion_text' => '',
    );
    uc_tax_rate_save($rate);

    $this->drupalGet('admin/store/config/taxes');
    $this->assertText($rate->name, 'Tax was saved successfully.');

    // $this->drupalGet("admin/store/config/taxes/manage/uc_tax_$rate->id");
    // $this->assertText(t('Conditions'), 'Rules configuration linked to tax.');

    $this->addToCart($this->product);

    // Manually step through checkout. $this->checkout() doesn't know about taxes.
    $this->drupalPostForm('cart', array(), 'Checkout');
    $this->assertText(
      t('Enter your billing address and information here.'),
      'Viewed cart page: Billing pane has been displayed.'
    );
    $this->assertRaw($rate->name, 'Tax line item displayed.');
    $this->assertRaw(uc_currency_format($rate->rate * $this->product->price->value), 'Correct tax amount displayed.');

    // Submit the checkout page.
    $edit = $this->populateCheckoutForm();
    $this->drupalPostForm('cart/checkout', $edit, t('Review order'));
    $this->assertRaw(t('Your order is almost complete.'));
    $this->assertRaw($rate->name, 'Tax line item displayed.');
    $this->assertRaw(uc_currency_format($rate->rate * $this->product->price->value), 'Correct tax amount displayed.');

    // Complete the review page.
    $this->drupalPostForm(NULL, array(), t('Submit order'));

    $order_ids = \Drupal::entityQuery('uc_order')
      ->condition('delivery_first_name', $edit['panes[delivery][first_name]'])
      ->execute();
    $order_id = reset($order_ids);
    if ($order_id) {
      $this->pass(
        SafeMarkup::format('Order %order_id has been created', ['%order_id' => $order_id])
      );

      $this->drupalGet('admin/store/orders/' . $order_id . '/edit');
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'on initial order load');

      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', array(), t('Save changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'after saving order');

      // Change tax rate and ensure order doesn't change.
      $oldrate = $rate->rate;
      $rate->rate = 0.1;
      $rate = uc_tax_rate_save($rate);

      // Save order because tax changes are only updated on save.
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', array(), t('Save changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $oldrate, 'after rate change');

      // Change taxable products and ensure order doesn't change.
      $class = $this->createProductClass();
      $rate->taxed_product_types = array($class->getEntityTypeId());
      uc_tax_rate_save($rate);
      // entity_flush_caches();
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', array(), t('Save changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $oldrate, 'after applicable product change');

      // Change order Status back to in_checkout and ensure tax-rate changes now update the order.
      Order::load($order_id)->setStatusId('in_checkout')->save();
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', array(), t('Save changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertFalse($this->loadTaxLine($order_id), 'The tax line was removed from the order when order status changed back to in_checkout.');

      // Restore taxable product and ensure new tax is added.
      $rate->taxed_product_types = array('product');
      uc_tax_rate_save($rate);
      $this->drupalPostForm('admin/store/orders/' . $order_id . '/edit', array(), t('Save changes'));
      $this->assertText(t('Order changes saved.'));
      $this->assertTaxLineCorrect($this->loadTaxLine($order_id), $rate->rate, 'when order status changed back to in_checkout');
    }
    else {
      $this->fail('No order was created.');
    }
  }

  /**
   * Loads a tax line item from the database.
   */
  protected function loadTaxLine($order_id) {
    // Reset uc_order entity cache then load order.
    \Drupal::entityTypeManager()->getStorage('uc_order')->resetCache([$order_id]);
    $order = Order::load($order_id);
    foreach ($order->line_items as $line) {
      if ($line['type'] == 'tax') {
        return $line;
      }
    }
    return FALSE;
  }

  /**
   * Complex assert to check various parts of the tax line item.
   */
  protected function assertTaxLineCorrect($line, $rate, $when) {
    $this->assertTrue($line, 'The tax line item was saved to the order ' . $when);
    $this->assertTrue(number_format($rate * $this->product->price->value, 2) == number_format($line['amount'], 2), 'Stored tax line item has the correct amount ' . $when);
    $this->assertFieldByName('line_items[' . $line['line_item_id'] . '][li_id]', $line['line_item_id'], 'Found the tax line item ID ' . $when);
    $this->assertText($line['title'], 'Found the tax title ' . $when);
    $this->assertText(uc_currency_format($line['amount']), 'Tax display has the correct amount ' . $when);
  }

}
