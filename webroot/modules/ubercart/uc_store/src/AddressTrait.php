<?php

namespace Drupal\uc_store;

use Drupal\Component\Utility\Unicode;

/**
 * Defines a trait implementing \Drupal\uc_store\AddressInterface.
 */
trait AddressTrait {

  /**
   * The unique address identifier.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable nickname of the location.
   *
   * @var string
   */
  protected $label;

  /**
   * Given name.
   *
   * @var string
   */
  public $first_name = '';

  /**
   * Surname.
   *
   * @var string
   */
  public $last_name = '';

  /**
   * Company or organization.
   *
   * @var string
   */
  public $company = '';

  /**
   * First line of street address.
   *
   * @var string
   */
  public $street1 = '';

  /**
   * Second line of street address.
   *
   * @var string
   */
  public $street2 = '';

  /**
   * City name.
   *
   * @var string
   */
  public $city = '';

  /**
   * State, provence, or region id.
   *
   * @var string
   */
  public $zone = '';

  /**
   * Postal code.
   *
   * @var string
   */
  public $postal_code = '';

  /**
   * ISO 3166-1 2-character numeric country code.
   *
   * @var string
   */
  public $country = '';

  /**
   * Telephone number.
   *
   * @var string
   */
  public $phone = '';

  /**
   * Email address.
   *
   * @var string
   */
  public $email = '';

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    return $this->first_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirstName($first_name) {
    $this->first_name = $first_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    return $this->last_name;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastName($last_name) {
    $this->last_name = $last_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompany() {
    return $this->company;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompany($company) {
    $this->company = $company;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStreet1() {
    return $this->street1;
  }

  /**
   * {@inheritdoc}
   */
  public function setStreet1($street1) {
    $this->street1 = $street1;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStreet2() {
    return $this->street2;
  }

  /**
   * {@inheritdoc}
   */
  public function setStreet2($street2) {
    $this->street2 = $street2;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCity() {
    return $this->city;
  }

  /**
   * {@inheritdoc}
   */
  public function setCity($city) {
    $this->city = $city;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getZone() {
    return $this->zone;
  }

  /**
   * {@inheritdoc}
   */
  public function setZone($zone) {
    $this->zone = $zone;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPostalCode() {
    return $this->postal_code;
  }

  /**
   * {@inheritdoc}
   */
  public function setPostalCode($postal_code) {
    $this->postal_code = $postal_code;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountry() {
    return $this->country;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountry($country) {
    $this->country = $country;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhone() {
    return $this->phone;
  }

  /**
   * {@inheritdoc}
   */
  public function setPhone($phone) {
    $this->phone = $phone;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($email) {
    $this->email = $email;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSamePhysicalLocation(AddressInterface $address) {
    $physicalProperty = array(
      'street1', 'street2', 'city', 'zone', 'country', 'postal_code'
    );

    foreach ($physicalProperty as $property) {
      // Canonicalize properties before comparing.
      if (Address::makeCanonical($this->$property)   !=
          Address::makeCanonical($address->$property)  ) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function makeCanonical($string = '') {
    // Remove all whitespace.
    $string = preg_replace('/\s+/', '', $string);
    // Make all characters upper case.
    $string = Unicode::strtoupper($string);

    return $string;
  }

}
