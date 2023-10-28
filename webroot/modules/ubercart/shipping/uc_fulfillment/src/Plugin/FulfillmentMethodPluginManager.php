<?php

namespace Drupal\uc_fulfillment\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\uc_fulfillment\Annotation\UbercartFulfillmentMethod;
use Drupal\uc_fulfillment\FulfillmentMethodPluginInterface;

/**
 * Manages discovery and instantiation of fulfillment methods.
 */
class FulfillmentMethodPluginManager extends DefaultPluginManager {

  /**
   * Constructs a FulfillmentMethodPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Ubercart/FulfillmentMethod', $namespaces, $module_handler, FulfillmentMethodPluginInterface::class, UbercartFulfillmentMethod::class);
    $this->alterInfo('uc_fulfillment_method');
    $this->setCacheBackend($cache_backend, 'uc_fulfillment_method');
  }

}
