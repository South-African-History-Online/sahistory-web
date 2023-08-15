<?php

namespace Drupal\uc_payment_pack\Tests;

use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Base class for payment method pack tests.
 */
abstract class PaymentPackTestBase extends UbercartTestBase {

  public static $modules = array('uc_payment', 'uc_payment_pack');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Log in and add a product to the cart for testing.
    $this->drupalLogin($this->adminUser);
    $this->addToCart($this->product);

    // Disable address panes during checkout.
    $edit = array(
      'panes[delivery][status]' => FALSE,
      'panes[billing][status]' => FALSE,
    );
    $this->drupalPostForm('admin/store/config/checkout', $edit, t('Save configuration'));
  }

}
