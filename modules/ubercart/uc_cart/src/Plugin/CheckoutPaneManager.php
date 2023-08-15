<?php

namespace Drupal\uc_cart\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\uc_cart\Annotation\CheckoutPane;
use Drupal\uc_cart\CheckoutPanePluginInterface;

/**
 * Manages discovery and instantiation of checkout panes.
 */
class CheckoutPaneManager extends DefaultPluginManager {

  /**
   * Configuration for the checkout panes.
   */
  protected $paneConfig;

  /**
   * {@inheritdoc}
   */
  protected $defaults = array(
    'status' => TRUE,
    'weight' => 0,
  );

  /**
   * Constructs a CheckoutPaneManager object.
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
    parent::__construct('Plugin/Ubercart/CheckoutPane', $namespaces, $module_handler, CheckoutPanePluginInterface::class, CheckoutPane::class);
    $this->alterInfo('uc_checkout_pane');
    $this->setCacheBackend($cache_backend, 'uc_checkout_panes');

    $this->paneConfig = \Drupal::config('uc_cart.settings')->get('panes');
  }

  /**
   * Gets instances of checkout pane plugins, optionally filtered.
   *
   * @param array $filter
   *   An array of definition keys to filter by.
   *
   * @return array
   *   An array of checkout pane plugin instances.
   */
  public function getPanes($filter = array()) {
    $instances = array();
    foreach ($this->getDefinitions() as $id => $definition) {
      foreach ($filter as $key => $value) {
        if (isset($definition[$key]) && $definition[$key] == $value) {
          continue 2;
        }
      }

      $instance = $this->createInstance($id, $this->paneConfig[$id] ?: array());
      if (!isset($filter['enabled']) || $filter['enabled'] != $instance->isEnabled()) {
        $instances[$id] = $instance;
      }
    }

    uasort($instances, array($this, 'sortHelper'));

    return $instances;
  }

  /**
   * Provides uasort() callback to sort plugins.
   */
  public function sortHelper($a, $b) {
    $a_weight = $a->getWeight();
    $b_weight = $b->getWeight();

    if ($a_weight == $b_weight) {
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

}
