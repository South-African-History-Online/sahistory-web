<?php

namespace Drupal\uc_country\Tests;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Import, edit, and remove countries and their settings.
 *
 * @group Ubercart
 */
class CountryTest extends WebTestBase {

  public static $modules = array('uc_country', 'uc_store');

  /**
   * Test enable/disable of countries.
   */
  public function testCountryUI() {
    $this->drupalLogin($this->drupalCreateUser(array('administer countries', 'administer store')));

    // Testing all countries is too much, so we just enable a random selection
    // of 8 countries. All countries will then be tested at some point.
    $countries = \Drupal::service('country_manager')->getAvailableList();
    $country_ids = array_rand($countries, 8);
    $last_country = array_pop($country_ids);

    // Loop over the first seven.
    foreach ($country_ids as $country_id) {
      // Verify this country isn't already enabled.
      $this->drupalGet('admin/store/config/country');
      $this->assertLinkByHref(
        'admin/store/config/country/' . $country_id . '/enable',
        0,
        SafeMarkup::format('%country is not enabled by default.', ['%country' => $countries[$country_id]])
      );

      // Enable this country.
      $this->clickLinkInRow($countries[$country_id], 'Enable');
      $this->assertText(t('The country @country has been enabled.', ['@country' => $countries[$country_id]]));
      $this->assertLinkByHref(
        'admin/store/config/country/' . $country_id . '/disable',
        0,
        SafeMarkup::format('%country is now enabled.', ['%country' => $countries[$country_id]])
      );
    }

    // Verify that last random country doesn't show up as available.
    $this->drupalGet('admin/store/config/store');
    $this->assertNoOption(
      'edit-address-country',
      $last_country,
      SafeMarkup::format('%country not listed in uc_address select country field.', ['%country' => $countries[$last_country]])
    );

    // Enable the last country.
    $this->drupalGet('admin/store/config/country');
    $this->clickLinkInRow($countries[$last_country], 'Enable');
    $this->assertText(t('The country @country has been enabled.', ['@country' => $countries[$last_country]]));
    $this->assertLinkByHref(
      'admin/store/config/country/' . $last_country . '/disable',
      0,
      SafeMarkup::format('%country is now enabled.', ['%country' => $countries[$last_country]])
    );

    // Verify that last random country now shows up as available.
    $this->drupalGet('admin/store/config/store');
    $this->assertOption(
      'edit-address-country',
      $last_country,
      SafeMarkup::format('%country is listed in uc_address select country field.', ['%country' => $countries[$last_country]])
    );

    // Disable the last country using the operations button.
    $this->drupalGet('admin/store/config/country');
    $this->clickLink('Disable', 7); // The 8th Disable link.
    $this->assertText(t('The country @country has been disabled.', ['@country' => $countries[$last_country]]));
    $this->assertLinkByHref(
      'admin/store/config/country/' . $last_country . '/enable',
      0,
      SafeMarkup::format('%country is now disabled.', ['%country' => $countries[$last_country]])
    );
  }

  /**
   * Test functionality with all countries disabled.
   */
  public function testAllDisabled() {
    $this->drupalLogin($this->drupalCreateUser(array(
      'administer countries',
      'administer store',
      'access administration pages',
    )));

    // Disable all countries.
    $manager = \Drupal::service('country_manager');
    $countries = $manager->getEnabledList();
    foreach (array_keys($countries) as $code) {
      $manager->getCountry($code)->disable()->save();
    }

    // Verify that an error is shown.
    $this->drupalGet('admin/store');
    $this->assertText('No countries are enabled.');

    // Verify that the country fields are hidden.
    $this->drupalGet('admin/store/config/store');
    $this->assertNoText('State/Province');
    $this->assertNoText('Country');
  }

  /**
   * Follows a link in the same table row as the label text.
   *
   * @param $label
   *   The label to find in a table column.
   * @param $link
   *   The link text to find in the same table row.
   *
   * @return bool|string
   *   Page contents on success, or FALSE on failure.
   */
  protected function clickLinkInRow($label, $link) {
    return $this->clickLinkHelper($label, 0, '//td[normalize-space()=:label]/ancestor::tr[1]//a[normalize-space()="' . $link . '"]');
  }

  /**
   * Overrides AssertContentTrait::assertText().
   *
   * Workaround for country names with single quote characters; they get
   * escaped as &#039; but the parent method does not handle this properly.
   *
   * @see https://www.drupal.org/node/2534240
   */
  protected function assertText($text, $message = '', $group = 'Other') {
    $text = Html::escape($text);
    return parent::assertText($text, $message, $group);
  }

}
