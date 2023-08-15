<?php

namespace Drupal\uc_store\Tests;

/**
 * Tests basic store functionality.
 *
 * @group Ubercart
 */
class StoreTest extends UbercartTestBase {

  /**
   * Tests operation of store configuration page.
   */
  public function testStoreAdmin() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/store');
    $this->assertTitle('Store | Drupal');
    $this->assertText('Configuration');
    $this->assertLink('Store');
    $this->assertLink('Countries and addresses');
    $this->assertText('Store status');

    $edit = array(
      'uc_store_name' => $this->randomMachineName(),
      'uc_store_email' => $this->randomMachineName() . '@example.com',
      'uc_store_phone' => $this->randomMachineName(),
      'uc_store_fax' => $this->randomMachineName(),
      'uc_store_help_page' => $this->randomMachineName(),
      'address[street1]' => $this->randomMachineName(),
      'address[street2]' => $this->randomMachineName(),
      'address[city]' => $this->randomMachineName(),
      'address[postal_code]' => $this->randomMachineName(),
      'uc_currency_code' => $this->randomMachineName(3),
      'uc_currency_sign' => $this->randomMachineName(1),
      'uc_currency_thou' => $this->randomMachineName(1),
      'uc_currency_dec' => $this->randomMachineName(1),
      'uc_currency_prec' => mt_rand(0, 2),
    );

    // Don't try to set the zone unless the store country has zones!
    $country_id = \Drupal::config('uc_store.settings')->get('address.country');
    $zone_list = \Drupal::service('country_manager')->getZoneList($country_id);
    if (!empty($zone_list)) {
      $edit += array(
        'address[zone]' => array_rand($zone_list),
      );
    }

    $this->drupalPostForm('admin/store/config/store', $edit, 'Save configuration');

    foreach ($edit as $name => $value) {
      $this->assertFieldByName($name, $value);
    }
  }

}
