<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for converting country to ISO code.
 *
 * @Tamper(
 *   id = "country_to_code",
 *   label = @Translation("Country to ISO code"),
 *   description = @Translation("Converts this field from a country name string to the two character ISO 3166-1 alpha-2 code."),
 *   category = "Text"
 * )
 */
class CountryToCode extends TamperBase implements ContainerFactoryPluginInterface {

  /**
   * Holds the CountryManager object so we can grab the country list.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    /**
     * Holds the list of countries in an array.
     * @static
     */
    static $countries = [];

    if (empty($countries)) {
      $countries = $this->countryManager->getList();
      foreach ($countries as $country_code => $country_name) {
        $countries[$country_code] = mb_strtolower((string) $country_name);
      }
      $countries = array_flip($countries);
    }

    // If it's already a valid country code, leave it alone.
    if (in_array($data, $countries)) {
      return $data;
    }

    // Trim whitespace, set to lowercase.
    $country = mb_strtolower(trim($data));
    if (isset($countries[$country])) {
      return $countries[$country];
    }
    else {
      throw new TamperException('Could not find country name ' . $country . ' in list of countries.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition, $configuration['source_definition']);
    $instance->setCountryManager($container->get('country_manager'));
    return $instance;
  }

  /**
   * Setter function for the CountryManagerInterface.
   *
   * @param object $country_manager
   *   The country manager used to grab country list.
   */
  public function setCountryManager($country_manager) {
    $this->countryManager = $country_manager;
  }

}
