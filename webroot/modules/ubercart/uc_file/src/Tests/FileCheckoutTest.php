<?php

namespace Drupal\uc_file\Tests;

use Drupal\uc_order\Entity\Order;
use Drupal\user\Entity\User;

/**
 * Tests file download upon checkout.
 *
 * @group Ubercart
 */
class FileCheckoutTest extends FileTestBase {

  /** Authenticated but unprivileged user. */
  protected $customer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a simple customer user account.
    $this->customer = $this->drupalCreateUser();

    // Ensure test mails are logged.
    \Drupal::configFactory()->getEditable('system.mail')
      ->set('interface.uc_order', 'test_mail_collector')
      ->save();
  }

  public function testCheckoutFileDownload() {
    $this->drupalLogin($this->adminUser);
    $method = $this->createPaymentMethod('other');

    // Add file download to the test product.
    $filename = $this->getTestFile();
    $this->drupalPostForm('node/' . $this->product->id() . '/edit/features', array('feature' => 'file'), t('Add'));
    $this->drupalPostForm(NULL, array('uc_file_filename' => $filename), t('Save feature'));

    // Process an anonymous, shippable order.
    $order = $this->createOrder([
      'uid' => 0,
      'payment_method' => $method['id'],
    ]);
    $order->products[1]->data->shippable = 1;
    $order->save();
    uc_payment_enter($order->id(), $method['id'], $order->getTotal());

    // Find the order uid.
    $uid = db_query('SELECT uid FROM {uc_orders} ORDER BY order_id DESC')->fetchField();

    // @todo Re-enable when Rules is available.
    // $account = User::load($uid);
    // $this->assertTrue($account->hasFile($fid), 'New user was granted file.');
    $order = Order::load($order->id());
    $this->assertEqual($order->getStatusId(), 'payment_received', 'Shippable order was set to payment received.');

    // Test that the file shows up on the user's purchased files list.
    //$this->drupalGet('user/' . $uid . '/purchased-files');
    //$this->assertText($filename, 'File found in list of purchased files.');

    // 4 e-mails: new account, customer invoice, admin invoice, file download.
    $this->assertMailString('subject', 'Account details', 4, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 4, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 4, 'Admin notification was sent');
    // @todo Re-enable when Rules is available.
    // $this->assertMailString('subject', 'File Downloads', 4, 'File download notification was sent');

    \Drupal::state()->set('system.test_email_collector', []);

    // Test again with an existing authenticated user and a non-shippable order.
    $order = $this->createOrder(array(
      'uid' => 0,
      'primary_email' => $this->customer->getEmail(),
      'payment_method' => $method['id'],
    ));
    $order->products[2]->data->shippable = 0;
    $order->save();
    uc_payment_enter($order->id(), $method['id'], $order->getTotal());
    $account = User::load($this->customer->id());
    // @todo Re-enable when Rules is available.
    // $this->assertTrue($account->hasFile($fid), 'Existing user was granted file.');
    $order = Order::load($order->id());
    $this->assertEqual($order->getStatusId(), 'completed', 'Non-shippable order was set to completed.');

    // 3 e-mails: customer invoice, admin invoice, file download.
    $this->assertNoMailString('subject', 'Account details', 3, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 3, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 3, 'Admin notification was sent');
    // @todo Re-enable when Rules is available.
    // $this->assertMailString('subject', 'File Downloads', 3, 'File download notification was sent');
  }

}
