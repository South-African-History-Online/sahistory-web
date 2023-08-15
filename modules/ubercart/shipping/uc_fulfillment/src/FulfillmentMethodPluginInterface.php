<?php

namespace Drupal\uc_fulfillment;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Defines an interface for fulfillment method plugins.
 */
interface FulfillmentMethodPluginInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns a description of this shipping method.
   *
   * @return string
   *   The description.
   */
  public function getDescription();

  /**
   * Fulfills the order using this method.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order to fulfill.
   * @param array $package_ids
   *   An array of package ids to shipped.
   *
   * @return \Drupal\Core\Form\FormInterface;
   *   A form to process the order fulfillment.
   */
  public function fulfillOrder(OrderInterface $order, array $package_ids);

}
