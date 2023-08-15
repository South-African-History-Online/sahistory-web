<?php

namespace Drupal\uc_country;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides list of countries.
 */
class CountryManager implements CountryManagerInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Stores the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An array of country code => country name pairs.
   */
  protected $countries;

  /*
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getList() {
    // Populate the country list if it is not already populated.
    if (!isset($this->countries)) {
      $this->countries = \Drupal\Core\Locale\CountryManager::getStandardList();
      $this->moduleHandler->alter('countries', $this->countries);
    }

    return $this->countries;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableList() {
    $countries = $this->entityTypeManager->getStorage('uc_country')->loadMultiple(NULL);
    $country_names = [];
    foreach ($countries as $alpha_2 => $country) {
      $country_names[$alpha_2] = t($country->getName());
    }
    natcasesort($country_names);
    $this->moduleHandler->alter('countries', $country_names);
    return $country_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledList() {
    $countries = $this->entityTypeManager->getStorage('uc_country')->loadByProperties(['status' => TRUE]);
    $country_names = [];
    foreach ($countries as $alpha_2 => $country) {
      $country_names[$alpha_2] = t($country->getName());
    }
    natcasesort($country_names);
    $this->moduleHandler->alter('countries', $country_names);
    return $country_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountry($alpha_2) {
    return $this->entityTypeManager->getStorage('uc_country')->load($alpha_2);
  }

  /**
   * {@inheritdoc}
   */
  public function getByProperty(array $properties) {
    $countries = $this->entityTypeManager->getStorage('uc_country')->loadByProperties($properties);
    $country_names = [];
    foreach ($countries as $alpha_2 => $country) {
      $country_names[$alpha_2] = t($country->getName());
    }
    natcasesort($country_names);
    return $country_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getZoneList($alpha_2) {
    if ($country = $this->entityTypeManager->getStorage('uc_country')->load($alpha_2)) {
      return $country->getZones();
    }
    return array();
  }

}
