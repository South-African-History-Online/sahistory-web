<?php

namespace Drupal\uc_quote\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_quote\Plugin\ShippingQuotePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to edit shipping quote method entities.
 */
class ShippingQuoteMethodForm extends EntityForm {

  /**
   * The shipping quote plugin.
   *
   * @var \Drupal\uc_quote\ShippingQuotePluginInterface
   */
  protected $plugin;

  /**
   * The shipping quote plugin manager.
   *
   * @var \Drupal\uc_quote\Plugin\ShippingQuotePluginManager
   */
  protected $shippingQuoteManager;

  /**
   * Constructs a ShippingQuoteMethod object.
   *
   * @param \Drupal\uc_quote\Plugin\ShippingQuotePluginManager $shipping_method_manager
   *   The shipping quote plugin manager.
   */
  public function __construct(ShippingQuotePluginManager $shipping_method_manager) {
    $this->shippingQuoteManager = $shipping_method_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.uc_quote.method'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->plugin = $this->shippingQuoteManager->createInstance($this->entity->getPluginId(), $this->entity->getPluginConfiguration());
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $definition = $this->plugin->getPluginDefinition();
    $form['type'] = array(
      '#type' => 'item',
      '#title' => $this->t('Type'),
      '#markup' => $definition['admin_label'],
    );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('The name shown to the customer when they choose a shipping method at checkout.'),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\uc_quote\Entity\ShippingQuoteMethod::load',
      ),
      '#disabled' => !$this->entity->isNew(),
    );

    $form['settings'] = $this->plugin->buildConfigurationForm([], $form_state);
    $form['settings']['#tree'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $this->plugin->validateConfigurationForm($form['settings'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->plugin->submitConfigurationForm($form['settings'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();

    drupal_set_message($this->t('Saved the %label shipping method.', ['%label' => $this->entity->label()]));

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
