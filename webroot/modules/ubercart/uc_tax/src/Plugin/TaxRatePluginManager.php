<?php

namespace Drupal\uc_tax\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\uc_tax\Annotation\UbercartTaxRate;
use Drupal\uc_tax\TaxRatePluginInterface;

/**
 * Manages discovery and instantiation of TaxRate plugins.
 */
class TaxRatePluginManager extends DefaultPluginManager {
  /**
   * Constructs a TaxRatePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin
   *   implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Ubercart/TaxRate', $namespaces, $module_handler, TaxRatePluginInterface::class, UbercartTaxRate::class);
    $this->alterInfo('uc_tax_rate');
    $this->setCacheBackend($cache_backend, 'uc_tax_rate');
  }

}
