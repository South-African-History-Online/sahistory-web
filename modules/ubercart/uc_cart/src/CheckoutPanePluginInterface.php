<?php

namespace Drupal\uc_cart;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;

/**
 * Defines an interface for checkout pane plugins.
 *
 * The checkout screen for Ubercart is a compilation of enabled checkout panes.
 * A checkout pane can be used to display order information, collect data from
 * the customer, or interact with other panes. Panes are defined in enabled
 * modules as plugins that implement this interface.
 */
interface CheckoutPanePluginInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Prepares a pane for display.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed.
   * @param array $form
   *   The checkout form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The checkout form state array.
   */
  public function prepare(OrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Returns the contents of a checkout pane.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed.
   * @param array $form
   *   The checkout form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The checkout form state array.
   *
   * @return array
   *   A form array, with an optional '#description' key to provide help text
   *   for the pane.
   */
  public function view(OrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Processes a checkout pane.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed.
   * @param array $form
   *   The checkout form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The checkout form state array.
   *
   * @return bool
   *   TRUE if the pane is valid, FALSE otherwise.
   */
  public function process(OrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Returns the review contents of a checkout pane.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed.
   *
   * @return array
   *   A checkout review array. Each item contains contains "title" and "data"
   *   keys which have HTML to be displayed on the checkout review page.
   */
  public function review(OrderInterface $order);

  /**
   * Returns the settings form for a checkout pane.
   *
   * @return array
   *   A form array.
   */
  public function settingsForm();

  /**
   * Returns the title of the pane, to be displayed on the checkout form.
   *
   * @return string
   *   The pane title.
   */
  public function getTitle();

  /**
   * Returns whether the checkout pane is enabled.
   *
   * @return bool
   *   TRUE if the pane is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Returns the weight of the checkout pane.
   *
   * @return int
   *   The integer weight of the checkout pane.
   */
  public function getWeight();
}
