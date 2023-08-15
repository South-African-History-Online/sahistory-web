<?php

namespace Drupal\uc_payment\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests the order payments form.
 *
 * @group Ubercart
 */
class OrderPaymentsFormTest extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack');
  public static $adminPermissions = array('view payments', 'manual payments', 'delete payments');

  /**
   * @var int
   *   Number of digits after decimal point, for currency rounding.
   */
  protected $precision = 2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need page_title_block because we test page titles.
    $this->drupalPlaceBlock('page_title_block');

    // Get configured currency precision.
    $config = \Drupal::config('uc_store.settings')->get('currency');
    $this->precision = $config['precision'];
  }

  /**
   * Tests administration form for displaying, entering, and deleting payments.
   */
  public function testOrderPayments() {
    // Check out with the test product.
    $method = $this->createPaymentMethod('check', ['id' => 'check']);
    $this->addToCart($this->product);
    $order = $this->checkout();
    // Add a payment of $1 so that the order total and current balance are different.
    uc_payment_enter($order->id(), $method['id'], 1.0);

    // Log in as admin user to test order payments form.
    $this->drupalLogin($this->adminUser);

    // Goto order payments form and confirm order total and payments total of $1 show up.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/payments');
    $this->assertRaw(
      '<span class="uc-price">' . uc_currency_format($order->getTotal()) . '</span>',
      'Order total is correct'
    );
    $this->assertRaw(
      '<span class="uc-price">' . uc_currency_format($order->getTotal() - 1.0) . '</span>',
      'Current balance is correct'
    );

    // Add a partial payment.
    $first_payment = round($order->getTotal() / 4.0, $this->precision);
    $edit = array(
      'amount' => $first_payment,
      'method' => 'check',
      'comment' => 'Test <b>markup</b> in comments.',
    );
    $this->drupalPostForm(
      'admin/store/orders/' . $order->id() . '/payments',
      $edit,
      t('Record payment')
    );
    $this->assertText('Payment entered.');
    // Verify partial payment shows up in table.
    $this->assertRaw(
      '<span class="uc-price">' . uc_currency_format($first_payment) . '</span>',
      'Payment appears on page.'
    );
    // Verify balance.
    $this->assertRaw(
      '<span class="uc-price">' . uc_currency_format($order->getTotal() - 1.0 - $first_payment) . '</span>',
      'Current balance is correct'
    );
    // Verify markup in comments.
    $this->assertRaw(
      'Test <b>markup</b> in comments.',
      'Markup is preserved in payment receipt comments.'
    );
    // Add another partial payment.
    $second_payment = round($order->getTotal() / 2.0, $this->precision);
    $edit = array(
      'amount' => $second_payment,
      'method' => 'check',
      'comment' => 'Test <em>markup</em> in comments.',
    );
    $this->drupalPostForm(
      'admin/store/orders/' . $order->id() . '/payments',
      $edit,
      t('Record payment')
    );
    // Verify partial payment shows up in table.
    $this->assertRaw(
      '<span class="uc-price">' . uc_currency_format($second_payment) . '</span>',
      'Payment appears on page.'
    );
    // Verify balance.
    $this->assertRaw(
      '<span class="uc-price">' . uc_currency_format($order->getTotal() - 1.0 - $first_payment - $second_payment) . '</span>',
      'Order total is correct'
    );

    // Delete first partial payment.
    $this->assertLink(t('Delete'));
    $this->clickLink(t('Delete'));
    // Delete takes us to confirm page.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/payments/1/delete');
    $this->assertText(
      'Are you sure you want to delete this payment?',
      'Deletion confirm question found.'
    );
    // "Cancel" returns to the payments list page.
    $this->clickLink(t('Cancel'));
    $this->assertLinkByHref('admin/store/orders/' . $order->id() . '/payments');

    // Again with the "Delete".
    // Delete the first partial payment, not the $1 initial payment.
    $this->clickLink(t('Delete'), 1);
    $this->drupalPostForm(NULL, array(), t('Delete'));
    // Delete returns to new payments page.
    $this->assertUrl('admin/store/orders/' . $order->id() . '/payments');
    $this->assertText('Payment deleted.');

    // Verify balance has increased.
    $this->assertRaw(
      '<span class="uc-price">' . uc_currency_format($order->getTotal() - 1.0 - $second_payment) . '</span>',
      'Current balance is correct'
    );

    // Go to order log and ensure two payments and one payment deletion were logged.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/log');
    $this->assertText(
      'Check payment for ' . uc_currency_format($first_payment) .' entered.',
      'First payment was logged'
    );
    $this->assertText(
      'Check payment for ' . uc_currency_format($second_payment) .' entered.',
      'Second payment was logged'
    );
    $this->assertText(
      'Check payment for ' . uc_currency_format($first_payment) .' deleted.',
      'Payment deletion was logged'
    );
  }
}
