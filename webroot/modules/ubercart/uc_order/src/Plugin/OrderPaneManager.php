<?php

namespace Drupal\uc_order\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\uc_order\Annotation\UbercartOrderPane;
use Drupal\uc_order\OrderPanePluginInterface;

/**
 * Manages discovery and instantiation of order panes.
 */
class OrderPaneManager extends DefaultPluginManager {

  /**
   * Constructs an OrderPaneManager object.
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
    parent::__construct('Plugin/Ubercart/OrderPane', $namespaces, $module_handler, OrderPanePluginInterface::class, UbercartOrderPane::class);
    $this->alterInfo('uc_order_pane');
    $this->setCacheBackend($cache_backend, 'uc_order_panes');
  }

  /**
   * Gets instances of order pane plugins.
   *
   * @return array
   *   An array of order pane plugin instances.
   */
  public function getPanes() {
    $instances = array();
    foreach ($this->getDefinitions() as $id => $definition) {
      $instances[$id] = $this->createInstance($id);
    }
    return $instances;
  }

}
