<?php

namespace Drupal\uc_fulfillment\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\uc_fulfillment\Shipment;
use Symfony\Component\Routing\Route;

/**
 * Provides upcasting for a node entity in preview.
 */
class ShipmentConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return Shipment::load($value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'uc_shipment');
  }

}
