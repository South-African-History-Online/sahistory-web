<?php

namespace Drupal\uc_country;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the uc_country type configuration entity.
 */
interface CountryInterface extends ConfigEntityInterface {

  /**
   * Returns the 3-character ISO 3166-1 code of the country.
   *
   * @return string
   *   The 3-character ISO 3166-1 code of the country.
   */
  public function getAlpha3();

  /**
   * Returns the ISO 3166-1 English short name of the country.
   *
   * @return string
   *   The ISO 3166-1 English short name of the country.
   */
  public function getName();

  /**
   * Returns the numeric ISO 3166-1 code of the country.
   *
   * @return int
   *   The numeric ISO 3166-1 code of the country.
   */
  public function getNumeric();

  /**
   * Gets the ISO 3166-2 zone codes for this country.
   *
   * @return array
   *   An associative array of zone names, keyed by ISO 3166-2 zone code.
   */
  public function getZones();

  /**
   * Returns the address format template.
   *
   * @return string
   */
  public function getAddressFormat();

  /**
   * Sets the address format template.
   *
   * @param string $address_format
   *   A string with placeholders for address elements.
   *
   * @return $this
   */
  public function setAddressFormat($address_format);

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b);

}
