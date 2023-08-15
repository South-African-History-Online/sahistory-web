<?php

namespace Drupal\uc_quote\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\uc_quote\Annotation\UbercartShippingQuote;
use Drupal\uc_quote\ShippingQuotePluginInterface;

/**
 * Provides the shipping quote plugin manager.
 */
class ShippingQuotePluginManager extends DefaultPluginManager {

  /**
   * Constructor for ShippingQuotePluginManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Ubercart/ShippingQuote', $namespaces, $module_handler, ShippingQuotePluginInterface::class, UbercartShippingQuote::class);

    $this->alterInfo('uc_quote_method');
    $this->setCacheBackend($cache_backend, 'uc_quote_method');
  }

}
