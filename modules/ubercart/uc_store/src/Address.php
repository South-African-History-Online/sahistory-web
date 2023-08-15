<?php

namespace Drupal\uc_store;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines an object to hold Ubercart mailing address information.
 */
class Address implements AddressInterface {
  use AddressTrait;
  use StringTranslationTrait;

  /** Store default country code. */
  protected $default_country;

  /**
   * Constructor.
   *
   * For convenience, country defaults to store country.
   */
  protected function __construct() {
    $this->default_country = \Drupal::config('uc_store.settings')->get('address.country');
    $this->country = $this->default_country;
  }

  /**
   * Creates an Address.
   *
   * @param array $values
   *   (optional) Array of initialization values.
   *
   * @return \Drupal\uc_store\AddressInterface
   *   An Address object.
   */
  public static function create(array $values = NULL) {
    $address = new Address();
    if (isset($values)) {
      foreach ($values as $key => $value) {
        if (property_exists($address, $key)) {
          $address->$key = $value;
        }
      }
    }
    return $address;
  }

  /**
   * Formats the address for display based on the country's address format.
   *
   * @return string
   *   A formatted string containing the address.
   */
  public function __toString() {
    $variables = array(
      '!company' => $this->company,
      '!first_name' => $this->first_name,
      '!last_name' => $this->last_name,
      '!street1' => $this->street1,
      '!street2' => $this->street2,
      '!city' => $this->city,
      '!postal_code' => $this->postal_code,
    );

    $country = $this->country ? \Drupal::service('country_manager')->getCountry($this->country) : NULL;
    if ($country) {
      $variables += array(
        '!zone_code' => $this->zone ?: $this->t('N/A'),
        '!zone_name' => isset($country->getZones()[$this->zone]) ? $country->getZones()[$this->zone] : $this->t('Unknown'),
        '!country_name' => $this->t($country->getName()),
        '!country_code2' => $country->id(),
        '!country_code3' => $country->getAlpha3(),
      );

      if ($this->country != $this->default_country) {
        $variables['!country_name_if'] = $variables['!country_name'];
        $variables['!country_code2_if'] = $variables['!country_code2'];
        $variables['!country_code3_if'] = $variables['!country_code3'];
      }
      else {
        $variables['!country_name_if']  = '';
        $variables['!country_code2_if'] = '';
        $variables['!country_code3_if'] = '';
      }

      $format = implode("\n", $country->getAddressFormat());
    }
    else {
      $format = "!company\n!first_name !last_name\n!street1\n!street2\n!city\n!postal_code";
    }

    $address = Html::escape(strtr($format, $variables));
    // Remove empty lines in the middle of an address string (0 or more
    // whitespace characters bracketed by \n) then remove (trim) whitespace
    // from the beginning and end of the string.
    $address = trim(preg_replace("/\n\s*\n/", "\n", $address));

    if (\Drupal::config('uc_store.settings')->get('capitalize_address')) {
      $address = Unicode::strtoupper($address);
    }

    // <br> instead of <br />, because Twig will change it to <br> anyway and it's nice
    // to be able to test the Raw output.
    return nl2br($address, FALSE);
  }

}
