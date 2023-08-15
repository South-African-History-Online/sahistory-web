<?php

namespace Drupal\uc_credit\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests credit card payments with the test gateway.
 *
 * This class is intended to be subclassed for use in testing other credit
 * card gateways. Subclasses which test other gateways need to:
 * - Override public static $modules, if necessary to enable the module
 *   providing the gateway and any other needed modules.
 * - Override configureGateway() to implement gateway-specific configuration.
 * No other overrides are necessary, although a subclass may want to add
 * additional test functions to cover cases not included in this base class.
 *
 * @group Ubercart
 */
class CreditCardTest extends UbercartTestBase {

  /**
   * A selection of "test" numbers to use for testing credit card payemnts.
   * These numbers all pass the Luhn algorithm check and are reserved by
   * the card issuer for testing purposes.
   */
  protected static $test_cards = array(
    '378282246310005',  // American Express
    '371449635398431',
    '370000000000002',
    '378734493671000',  // American Express Corporate
    '5610591081018250', // Australian BankCard
    '30569309025904',   // Diners Club
    '38520000023237',
    '38000000000006',   // Carte Blanche
    '6011111111111117', // Discover
    '6011000990139424',
    '6011000000000012',
    '3530111333300000', // JCB
    '3566002020360505',
    '3088000000000017',
    '5555555555554444', // MasterCard
    '5105105105105100',
    '4111111111111111', // Visa
    '4012888888881881',
    '4007000000027',
    '4012888818888',
  );

  protected $paymentMethod;

  public static $modules = array('uc_payment', 'uc_credit');
  public static $adminPermissions = array('administer credit cards', 'process credit cards');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need admin permissions in order to change credit card settings.
    $this->drupalLogin($this->adminUser);

    // Configure and enable Credit card module and Test gateway.
    $this->configureCreditCard();
    $this->configureGateway();
  }

  /**
   * Helper function to configure Credit Card payment method settings.
   */
  protected function configureCreditCard() {
    // Create key directory, make it readable and writeable.
    // Putting this under sites/default/files because SimpleTest needs to be
    // able to create the directory - this is NOT where you'd put the key file
    // on a live site.  On a live site, it should be outside the web root.
    \Drupal::service('file_system')->mkdir('sites/default/files/simpletest.keys', 0755);

    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      array(
        'uc_credit_encryption_path' => 'sites/default/files/simpletest.keys',
      ),
      t('Save configuration')
    );

    $this->assertFieldByName(
      'uc_credit_encryption_path',
      'sites/default/files/simpletest.keys',
      'Key file path has been set.'
    );

    $this->assertTrue(
      file_exists('sites/default/files/simpletest.keys/' . UC_CREDIT_KEYFILE_NAME),
      'Key has been generated and stored.'
    );
    $this->pass('Key = ' . uc_credit_encryption_key());

  }

  /**
   * Helper function to configure Credit Card gateway.
   */
  protected function configureGateway() {
    $this->paymentMethod = $this->createPaymentMethod('test_gateway');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Cleanup keys directory after test.
    \Drupal::service('file_system')->unlink('sites/default/files/simpletest.keys/' . UC_CREDIT_KEYFILE_NAME);
    \Drupal::service('file_system')->rmdir('sites/default/files/simpletest.keys');
    parent::tearDown();
  }

  /**
   * Tests security settings configuration.
   */
  public function testSecuritySettings() {
    // TODO:  Still need tests with existing key file
    // where key file is not readable or doesn't contain a valid key

    // Create key directory, make it readable and writeable.
    \Drupal::service('file_system')->mkdir('sites/default/files/testkey', 0755);

    // Try to submit settings form without a key file path.
    // Save current variable, reset to its value when first installed.
    $config = \Drupal::configFactory()->getEditable('uc_credit.settings');
    $temp_variable = $config->get('encryption_path');
    $config->set('encryption_path', '')->save();

    $this->drupalGet('admin/store');
    $this->assertText('You must review your credit card security settings and enable encryption before you can accept credit card payments.');

    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      array(),
      t('Save configuration')
    );
    $this->assertFieldByName(
      'uc_credit_encryption_path',
      t('Not configured.'),
      'Key file has not yet been configured.'
    );
    // Restore variable setting.
    $config->set('encryption_path', $temp_variable)->save();

    // Try to submit settings form with an empty key file path.
    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      array('uc_credit_encryption_path' => ''),
      t('Save configuration')
    );
    $this->assertText('Key path must be specified in security settings tab.');

    // Specify non-existent directory
    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      array('uc_credit_encryption_path' => 'sites/default/ljkh/asdfasfaaaaa'),
      t('Save configuration')
    );
    $this->assertText('You have specified a non-existent directory.');

    // Next, specify existing directory that's write protected.
    // Use /dev, as that should never be accessible.
    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      array('uc_credit_encryption_path' => '/dev'),
      t('Save configuration')
    );
    $this->assertText('Cannot write to directory, please verify the directory permissions.');

    // Next, specify writeable directory, but with excess whitespace
    // and trailing /
    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      array('uc_credit_encryption_path' => '  sites/default/files/testkey/ '),
      t('Save configuration')
    );
    // See that the directory has been properly re-written to remove
    // whitespace and trailing /
    $this->assertFieldByName(
      'uc_credit_encryption_path',
      'sites/default/files/testkey',
      'Key file path has been set.'
    );
    $this->assertText('Credit card encryption key file generated.');

    // Check that warning about needing key file goes away.
    $this->assertNoText(t('Credit card security settings must be configured in the security settings tab.'));
    // Remove key file.
    \Drupal::service('file_system')->unlink('sites/default/files/testkey/' . UC_CREDIT_KEYFILE_NAME);

    // Finally, specify good directory
    $this->drupalPostForm(
      'admin/store/config/payment/credit',
      array('uc_credit_encryption_path' => 'sites/default/files/testkey'),
      t('Save configuration')
    );
    $this->assertText('Credit card encryption key file generated.');

    // Test contents - must contain 32-character hexadecimal string.
    $this->assertTrue(
      file_exists('sites/default/files/simpletest.keys/' . UC_CREDIT_KEYFILE_NAME),
      'Key has been generated and stored.'
    );
    $this->assertTrue(
      preg_match("([0-9a-fA-F]{32})", uc_credit_encryption_key()),
      'Valid key detected in key file.'
    );

    // Cleanup keys directory after test.
    \Drupal::service('file_system')->unlink('sites/default/files/testkey/' . UC_CREDIT_KEYFILE_NAME);
    \Drupal::service('file_system')->rmdir('sites/default/files/testkey');
  }

  /**
   * Tests that an order can be placed using the test gateway even if
   * the user changes their mind and fails a payment attempt.
   */
  public function testCheckout() {
    $this->addToCart($this->product);

    // Submit the checkout page.
    $edit = $this->populateCheckoutForm(array(
      'panes[payment][details][cc_number]' => array_rand(array_flip(self::$test_cards)),
      'panes[payment][details][cc_cvv]' => mt_rand(100, 999),
      'panes[payment][details][cc_exp_month]' => mt_rand(1, 12),
      'panes[payment][details][cc_exp_year]' => mt_rand(date('Y') + 1, 2022),
    ));
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');
    $this->assertText('(Last 4) ' . substr($edit['panes[payment][details][cc_number]'], -4), 'Truncated credit card number found.');
    $this->assertText($edit['panes[payment][details][cc_exp_year]'], 'Expiry date found.');

    // Go back.
    $this->drupalPostForm(NULL, [], 'Back');
    $this->assertFieldByName('panes[payment][details][cc_number]', '(Last 4) ' . substr($edit['panes[payment][details][cc_number]'], -4), 'Truncated credit card number found.');
    $this->assertFieldByName('panes[payment][details][cc_cvv]', '---', 'Masked CVV found.');
    $this->assertFieldByName('panes[payment][details][cc_exp_month]', $edit['panes[payment][details][cc_exp_month]'], 'Expiry month found.');
    $this->assertFieldByName('panes[payment][details][cc_exp_year]', $edit['panes[payment][details][cc_exp_year]'], 'Expiry year found.');

    // Change the number and fail with a known-bad CVV.
    $edit = array(
      'panes[payment][details][cc_number]' => array_rand(array_flip(self::$test_cards)),
      'panes[payment][details][cc_cvv]' => '000',
    );
    $this->drupalPostForm(NULL, $edit, 'Review order');
    $this->assertText('(Last 4) ' . substr($edit['panes[payment][details][cc_number]'], -4), 'Truncated updated credit card number found.');

    // Try to submit the bad CVV.
    $this->drupalPostForm(NULL, [], 'Submit order');
    $this->assertText('We were unable to process your credit card payment. Please verify your details and try again.');

    // Go back.
    $this->drupalPostForm(NULL, [], 'Back');
    $this->assertFieldByName('panes[payment][details][cc_number]', '(Last 4) ' . substr($edit['panes[payment][details][cc_number]'], -4), 'Truncated updated credit card number found.');
    $this->assertFieldByName('panes[payment][details][cc_cvv]', '---', 'Masked CVV found.');

    // Fix the CVV.
    $edit = array(
      'panes[payment][details][cc_cvv]' => mt_rand(100, 999),
    );
    $this->drupalPostForm(NULL, $edit, 'Review order');

    // Check for success.
    $this->drupalPostForm(NULL, [], 'Submit order');
    $this->assertText('Your order is complete!');
  }

  /**
   * Tests that expiry date validation functions correctly.
   */
  public function testExpiryDate() {
    $order = $this->createOrder(array('payment_method' => $this->paymentMethod['id']));

    $year = date('Y');
    $month = date('n');
    for ($y = $year; $y <= $year + 2; $y++) {
      for ($m = 1; $m <= 12; $m++) {
        $edit = array(
          'amount' => 1,
          'cc_data[cc_number]' => '4111111111111111',
          'cc_data[cc_cvv]' => '123',
          'cc_data[cc_exp_month]' => $m,
          'cc_data[cc_exp_year]' => $y,
        );
        $this->drupalPostForm('admin/store/orders/' . $order->id() . '/credit/' . $this->paymentMethod['id'], $edit, 'Charge amount');

        if ($y > $year || $m >= $month) {
          $this->assertText('The credit card was processed successfully.', SafeMarkup::format('Card with expiry date @month/@year passed validation.', ['@month' => $m, '@year' => $y]));
        }
        else {
          $this->assertNoText('The credit card was processed successfully.', SafeMarkup::format('Card with expiry date @month/@year correctly failed validation.', ['@month' => $m, '@year' => $y]));
        }
      }
    }
  }
}
