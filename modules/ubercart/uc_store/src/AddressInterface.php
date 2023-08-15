<?php

namespace Drupal\uc_store;


/**
 * Defines an object to hold Ubercart mailing address information.
 */
interface AddressInterface {

  /**
   * Returns the first (given) name.
   *
   * @return string
   *   The given name.
   */
  public function getFirstName();

  /**
   * Sets the first (given) name.
   *
   * @param string $first_name
   *   The given name.
   *
   * @return $this
   */
  public function setFirstName($first_name);

  /**
   * Returns the surname.
   *
   * @return string
   *   The surname.
   */
  public function getLastName();

  /**
   * Sets the surname.
   *
   * @param string $last_name
   *   The surname.
   *
   * @return $this
   */
  public function setLastName($last_name);

  /**
   * Returns the company or organization name.
   *
   * @return string
   *   The company or organization name.
   */
  public function getCompany();

  /**
   * Sets the company or organization name.
   *
   * @param string $company
   *   The company or organization name.
   *
   * @return $this
   */
  public function setCompany($company);

  /**
   * Returns the first line of street address.
   *
   * @return string
   *   First line of street address.
   */
  public function getStreet1();

  /**
   * Sets the first line of street address.
   *
   * @param string $street1
   *   First line of street address.
   *
   * @return $this
   */
  public function setStreet1($street1);

  /**
   * Returns the second line of street address.
   *
   * @return string
   *   Second line of street address.
   */
  public function getStreet2();

  /**
   * Sets the second line of street address.
   *
   * @param string $street2
   *   Second line of street address.
   *
   * @return $this
   */
  public function setStreet2($street2);

  /**
   * Returns the city name.
   *
   * @return string
   *   The city.
   */
  public function getCity();

  /**
   * Sets the city name.
   *
   * @param string $city
   *   The city.
   *
   * @return $this
   */
  public function setCity($city);

  /**
   * Returns the state, provence, or region id.
   *
   * @return string
   *   The zone.
   */
  public function getZone();

  /**
   * Sets the state, provence, or region id.
   *
   * @param string $zone
   *   The zone.
   *
   * @return $this
   */
  public function setZone($zone);

  /**
   * Returns the ISO 3166-1 2-character numeric country code.
   *
   * @return string
   *   The country code.
   */
  public function getCountry();

  /**
   * Sets the ISO 3166-1 2-character numeric country code.
   *
   * @param string $country
   *   The country code.
   *
   * @return $this
   */
  public function setCountry($country);

  /**
   * Returns the postal code.
   *
   * @return string
   *   The postal code.
   */
  public function getPostalCode();

  /**
   * Sets the postal code.
   *
   * @param string $postal_code
   *   The postal code.
   *
   * @return $this
   */
  public function setPostalCode($postal_code);

  /**
   * Returns the telephone number.
   *
   * @return string
   *   The telephone number.
   */
  public function getPhone();

  /**
   * Sets the telephone number.
   *
   * @param string $phone
   *   The telephone number.
   *
   * @return $this
   */
  public function setPhone($phone);

  /**
   * Returns the email address.
   *
   * @return string
   *   The email address.
   */
  public function getEmail();

  /**
   * Sets the email address.
   *
   * @param string $email
   *   The email address.
   *
   * @return $this
   */
  public function setEmail($email);

  /**
   * Compares two Address objects to determine if they represent the same
   * physical address.
   *
   * Address properties such as first_name, phone, and email aren't considered
   * in this comparison because they don't contain information about the
   * physical location.
   *
   * @param \Drupal\uc_store\AddressInterface $address
   *   An object of type AddressInterface.
   *
   * @return bool
   *   TRUE if the two addresses are the same physical location, else FALSE.
   */
  public function isSamePhysicalLocation(AddressInterface $address);

  /**
   * Utility function to simplify comparison of address properties.
   *
   * For the purpose of this function, the canonical form is stripped of all
   * whitespace and has been converted to all upper case. This ensures that we
   * don't get false inequalities when comparing address properties that a
   * human would consider identical, but may be capitalized differently or
   * have different whitespace.
   *
   * @param string $string
   *   String to make canonical.
   *
   * @return string
   *   Canonical form of input string.
   */
  public static function makeCanonical($string);

}
