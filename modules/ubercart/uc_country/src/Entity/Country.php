<?php

namespace Drupal\uc_country\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\uc_country\CountryInterface;

/**
 * Defines the uc_country type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "uc_country",
 *   label = @Translation("Country"),
 *   label_singular = @Translation("country"),
 *   label_plural = @Translation("countries"),
 *   label_count = @PluralTranslation(
 *     singular = "@count country",
 *     plural = "@count countries",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\uc_country\CountryListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\uc_country\Form\CountryForm",
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "alpha_2",
 *     "label" = "name",
 *     "status" = "status",
 *   },
 *   config_prefix = "country",
 *   admin_permission = "administer countries",
 *   links = {
 *     "edit-form" = "/admin/store/config/country/{uc_country}",
 *     "enable" = "/admin/store/config/country/{uc_country}/enable",
 *     "disable" = "/admin/store/config/country/{uc_country}/disable"
 *   }
 * )
 */
class Country extends ConfigEntityBase implements CountryInterface {

  /**
   * The 2-character ISO 3166-1 code identifying the country.
   *
   * @var string
   */
  protected $alpha_2;

  /**
   * The 3-character ISO 3166-1 code identifying the country.
   *
   * @var string
   */
  protected $alpha_3;

  /**
   * The human-readable name of the country.
   *
   * @var string
   */
  protected $name;

  /**
   * The numeric ISO 3166-1 code of the country.
   *
   * @var int
   */
  protected $numeric;

  /**
   * An associative array of zone names, keyed by ISO 3166-2 zone code.
   *
   * @var string[]
   */
  protected $zones = [];

  /**
   * The address format string for the country.
   *
   * @var string[]
   */
  protected $address_format;


  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->alpha_2;
  }

  /**
   * {@inheritdoc}
   */
  public function getAlpha3() {
    return $this->alpha_3;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getNumeric() {
    return $this->numeric;
  }

  /**
   * {@inheritdoc}
   */
  public function getZones() {
    return $this->zones;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressFormat() {
    return $this->address_format;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressFormat($address_format) {
    $this->address_format = $address_format;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    $a_status = (int) $a->status();
    $b_status = (int) $b->status();
    if ($a_status != $b_status) {
      return ($a_status > $b_status) ? -1 : 1;
    }
    return parent::sort($a, $b);
  }

}
