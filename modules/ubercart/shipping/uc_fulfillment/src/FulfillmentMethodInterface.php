<?php

namespace Drupal\uc_fulfillment;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining fulfillment method entities.
 */
interface FulfillmentMethodInterface extends ConfigEntityInterface {

  /**
   * Returns the weight of this fulfillment method (used for sorting).
   *
   * @return int
   *   The fulfillment method weight.
   */
  public function getWeight();

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID for this fulfillment method.
   */
  public function getPluginId();

  /**
   * Returns the plugin configuration.
   *
   * @return array
   *   The plugin configuration for this fulfillment method.
   */
  public function getPluginConfiguration();

  /**
   * Returns the supported package type for this fulfillment method.
   * @todo package type should be a configuration entity.
   *
   * @return string
   *   The package type.
   */
  public function getPackageType();

}
