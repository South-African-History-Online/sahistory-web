<?php

namespace Drupal\uc_payment\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\Annotation\UbercartPaymentMethod;
use Drupal\uc_payment\Entity\PaymentMethod;
use Drupal\uc_payment\PaymentMethodPluginInterface;

/**
 * Manages discovery and instantiation of payment methods.
 */
class PaymentMethodManager extends DefaultPluginManager {

  /**
   * Constructs a PaymentMethodManager object.
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
    parent::__construct('Plugin/Ubercart/PaymentMethod', $namespaces, $module_handler, PaymentMethodPluginInterface::class, UbercartPaymentMethod::class);
    $this->alterInfo('uc_payment_method');
    $this->setCacheBackend($cache_backend, 'uc_payment_methods');
  }

  /**
   * Returns an instance of the payment method plugin for a specific order.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order from which the plugin should be instantiated.
   *
   * @return \Drupal\uc_payment\PaymentMethodPluginInterface
   *   A fully configured plugin instance.
   */
  public function createFromOrder(OrderInterface $order) {
    return PaymentMethod::load($order->getPaymentMethodId())->getPlugin();
  }

  /**
   * Populates a key-value pair of available payment methods.
   *
   * @return array
   *   An array of payment method labels, keyed by ID.
   */
  public function listOptions() {
    $options = array();
    foreach ($this->getDefinitions() as $key => $definition) {
      $options[$key] = $definition['name'];
    }
    return $options;
  }

}
