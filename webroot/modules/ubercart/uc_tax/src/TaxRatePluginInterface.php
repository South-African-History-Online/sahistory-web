<?php

namespace Drupal\uc_tax;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Defines an interface for TaxRate plugins.
 */
interface TaxRatePluginInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns a short description of this tax rate.
   *
   * @return string
   *   The description.
   */
  public function getSummary();

  /**
   * Returns the amount of tax for the order.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed.
   *
   * @return array
   */
  public function calculateTax(OrderInterface $order);

}
