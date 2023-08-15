<?php

namespace Drupal\uc_fulfillment\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_fulfillment\Plugin\FulfillmentMethodPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to edit fulfillment method entities.
 */
class FulfillmentMethodForm extends EntityForm {

  /**
   * The fulfillment method plugin.
   *
   * @var \Drupal\uc_fulfillment\FulfillmentMethodPluginInterface
   */
  protected $plugin;

  /**
   * The fulfillment method plugin manager.
   *
   * @var \Drupal\uc_fulfillment\Plugin\FulfillmentMethodPluginManager
   */
  protected $fulfillmentMethodManager;

  /**
   * Constructs a FulfillmentMethod object.
   *
   * @param \Drupal\uc_fulfillment\Plugin\FulfillmentMethodPluginManager $fulfillment_method_manager
   *   The fulfillment method plugin manager.
   */
  public function __construct(FulfillmentMethodPluginManager $fulfillment_method_manager) {
    $this->fulfillmentMethodManager = $fulfillment_method_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.uc_fulfillment.method'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->plugin = $this->fulfillmentMethodManager->createInstance($this->entity->getPluginId(), $this->entity->getPluginConfiguration());
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
      '#description' => $this->t('The name shown to the customer when they choose a fulfillment method at checkout.'),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\uc_fulfillment\Entity\FulfillmentMethod::load',
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

    drupal_set_message($this->t('Saved the %label fulfillment method.', ['%label' => $this->entity->label()]));

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
