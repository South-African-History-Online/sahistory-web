<?php

namespace Drupal\uc_order\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\uc_order\Annotation\UbercartLineItem;
use Drupal\uc_order\LineItemPluginInterface;

/**
 * Manages discovery and instantiation of line item plugins.
 */
class LineItemManager extends DefaultPluginManager {

  /**
   * Constructs an LineItemManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Ubercart/LineItem', $namespaces, $module_handler, LineItemPluginInterface::class, UbercartLineItem::class);
    $this->alterInfo('uc_line_item');
    $this->setCacheBackend($cache_backend, 'uc_line_item');
  }

}
