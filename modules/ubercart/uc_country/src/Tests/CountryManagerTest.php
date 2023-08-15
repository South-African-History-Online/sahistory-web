<?php

namespace Drupal\uc_country\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests functionality of the extended CountryManager service.
 *
 * @group Ubercart
 */
class CountryManagerTest extends WebTestBase {

  public static $modules = array('uc_country');

  /**
   * Test overriding the core Drupal country_manager service.
   */
  public function testServiceOverride() {
    $country_manager = \Drupal::service('country_manager');

    // Test core Drupal country_manager functions.

    // getList(): Verify that all Drupal-provided countries were imported without error.
    $this->assertEqual(count($country_manager->getList()), 258, '258 core Drupal countries found');

    // Test new functions provided by this extended service.

    // getAvailableList(): Verify that all Ubercart-provided countries are available.
    $this->assertEqual(count($country_manager->getAvailableList()), 248, '248 Ubercart countries found');

    // getEnabledList(): Verify that no countries are enabled by default.
    $this->assertEqual(count($country_manager->getEnabledList()), 0, 'No Ubercart countries enabled by default');

    // getCountry(): Verify the basic get country config entity works.
    $country_manager->getCountry('US');

    // getByProperty(): Verify we can obtain country entities by property.
    debug($country_manager->getByProperty(['status' => TRUE]));
    debug("Count = " . count($country_manager->getByProperty(['status' => TRUE])));

    // getZoneList(): Verify that CA has 13 zones.
    $this->assertEqual(count($country_manager->getZoneList('CA')), 13, 'Canada has 13 zones');

    // Compare getList() to core getStandardList().

    // Test standard list alter, to make sure we don't break contrib.
  }

}
