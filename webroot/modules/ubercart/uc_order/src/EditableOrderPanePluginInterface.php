<?php

namespace Drupal\uc_order;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for editable order pane plugins.
 */
interface EditableOrderPanePluginInterface {

  /**
   * Form constructor.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being viewed.
   * @param array $form
   *   An array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(OrderInterface $order, array $form, FormStateInterface $form_state);

  /**
   * Form submission handler.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being viewed.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(OrderInterface $order, array &$form, FormStateInterface $form_state);

}
