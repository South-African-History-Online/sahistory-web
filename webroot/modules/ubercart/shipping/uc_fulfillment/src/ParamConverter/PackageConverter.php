<?php

namespace Drupal\uc_fulfillment\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\uc_fulfillment\Package;
use Symfony\Component\Routing\Route;

/**
 * Provides upcasting for a node entity in preview.
 */
class PackageConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return Package::load($value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'uc_package');
  }

}
