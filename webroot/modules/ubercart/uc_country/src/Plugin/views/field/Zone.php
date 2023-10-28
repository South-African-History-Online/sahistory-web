<?php

namespace Drupal\uc_country\Plugin\views\field;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide proper displays for zone.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_zone")
 */
class Zone extends FieldPluginBase {

  /**
   * The country storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $countryStorage;

  /**
   * Constructs a Zone object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $country_storage
   *   The country storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigEntityStorageInterface $country_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->countryStorage = $country_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('uc_country')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $country_value = $this->getValue($values, 'country');
    if ($country_value && $country = $this->countryStorage->load($country_value)) {
      $zone_value = $this->getValue($values);
      if ($zone_value) {
        return $country->getZones()[$zone_value];
      }
    }
    return '';
  }

}
