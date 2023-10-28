<?php

namespace Drupal\uc_order\Tests;

use Drupal\uc_country\Entity\Country;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_store\Tests\UbercartTestBase;

/**
 * Tests customer administration page functionality.
 *
 * @group Ubercart
 */
class CustomerAdminTest extends UbercartTestBase {

  /**
   * A user with permission to view customers.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user created the order.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $customer;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array('views');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array(
      'access user profiles',
      'view customers',
    ));
    $this->customer = $this->drupalCreateUser();
  }

  /**
   * Tests customer overview.
   */
  public function testCustomerAdminPages() {
    $this->drupalLogin($this->adminUser);

    $country = Country::load('US');
    Order::create(array(
      'uid' => $this->customer->id(),
      'billing_country' => $country->id(),
      'billing_zone' => 'AK',
    ))->save();

    $this->drupalGet('admin/store/customers/view');
    $this->assertResponse(200);
    $this->assertLinkByHref('user/' . $this->customer->id());
    $this->assertText($country->getZones()['AK']);
    $this->assertText($country->label());
  }

}
