<?php

namespace Drupal\uc_payment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;

/**
 * Builds the form that payment methods use to redirect off-site.
 */
class OffsitePaymentMethodForm extends FormBase {

  /**
   * The payment method plugin.
   *
   * @var \Drupal\uc_payment\OffsitePaymentMethodPluginInterface
   */
  protected $plugin;

  /**
   * Constructs the form.
   *
   * @param \Drupal\uc_payment\OffsitePaymentMethodPluginInterface $plugin
   *   The payment method plugin.
   */
  public function __construct(OffsitePaymentMethodPluginInterface $plugin) {
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $definition = $this->plugin->getPluginDefinition();
    return 'uc_payment_' . $definition['id'] . '_offsite_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    $form_state->disableCache();

    return $this->plugin->buildRedirectForm($form, $form_state, $order);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This will never be called, as the form will redirect off-site.
  }

}
