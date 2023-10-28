<?php

namespace Drupal\uc_store\Tests;

use Drupal\uc_store\Address;

/**
 * Utility functions to provide addresses for test purposes.
 */
trait AddressTestTrait {

  /**
   * Creates an address object based on default settings.
   *
   * @param array $settings
   *   (optional) An associative array of settings to change from the defaults,
   *   keys are address properties. For example, 'city' => 'London'.
   *
   * @return \Drupal\uc_store\AddressInterface
   *   Address object.
   */
  protected function createAddress($settings = array()) {
    $street = array_flip(array(
      'Street',
      'Avenue',
      'Place',
      'Way',
      'Road',
      'Boulevard',
      'Court',
    ));

    // Populate any fields that weren't passed in $settings.
    $values = $settings + array(
      'first_name'  => $this->randomMachineName(6),
      'last_name'   => $this->randomMachineName(12),
      'company'     => $this->randomMachineName(10) . ', Inc.',
      'street1'     => mt_rand(10, 1000) . ' ' .
                       $this->randomMachineName(10) . ' ' .
                       array_rand($street),
      'street2'     => 'Suite ' . mt_rand(100, 999),
      'city'        => $this->randomMachineName(10),
      'postal_code' => mt_rand(10000, 99999),
      'phone'       => '(' . mt_rand(100, 999) . ') ' .
                       mt_rand(100, 999) . '-' . mt_rand(0, 9999),
      'email'       => $this->randomMachineName(6) . '@' .
                       $this->randomMachineName(8) . '.com',
    );

    // Set the country if it isn't set already.
    $country_id = array_rand(\Drupal::service('country_manager')->getEnabledList());
    $values += array('country' => $country_id);

    // Don't try to set the zone unless the country has zones!
    $zone_list = \Drupal::service('country_manager')->getZoneList($values['country']);
    if (!empty($zone_list)) {
      $values += array('zone' => array_rand($zone_list));
    }

    // Create object.
    $address = Address::create($values);

    return $address;
  }

}
