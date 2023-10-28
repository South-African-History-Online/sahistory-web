<?php

namespace Drupal\uc_fulfillment;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_fulfillment\Plugin\FulfillmentMethodPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of fulfillment method entities.
 */
class FulfillmentMethodListBuilder extends DraggableListBuilder implements FormInterface {

  /**
   * The fulfillment method plugin manager.
   *
   * @var \Drupal\uc_fulfillment\Plugin\FulfillmentMethodPluginManager
   */
  protected $fulfillmentMethodPluginManager;

  /**
   * Constructs a new FulfilllmentMethodListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\uc_fulfillment\Plugin\FulfillmentMethodPluginManager $fulfillment_method_plugin_manager
   *   The fulfillment method plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FulfillmentMethodPluginManager $fulfillment_method_plugin_manager) {
    parent::__construct($entity_type, $storage);
    $this->fulfillmentMethodPluginManager = $fulfillment_method_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.uc_fulfillment.method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_methods_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = array(
      'data' => $this->t('Fulfillment method'),
    );
    $header['description'] = array(
      'data' => $this->t('Description'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    );
    $header['status'] = array(
      'data' => $this->t('Status'),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $plugin = $this->fulfillmentMethodPluginManager->createInstance($entity->getPluginId(), $entity->getPluginConfiguration());
    $row['label'] = $entity->label();
    $row['description']['#markup'] = $plugin->getDescription();
    $row['status']['#markup'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Locked payment methods may not be deleted.
    if (isset($operations['delete']) && $entity->isLocked()) {
      unset($operations['delete']);
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = array_map(function ($definition) {
      return $definition['admin_label'];
    }, array_filter($this->fulfillmentMethodPluginManager->getDefinitions(), function ($definition) {
      return !$definition['no_ui'];
    }));
    uasort($options, 'strnatcasecmp');

    $form['add'] = array(
      '#type' => 'details',
      '#title' => $this->t('Add fulfillment method'),
      '#open' => TRUE,
      '#attributes' => array(
        'class' => array('container-inline'),
      ),
    );
    $form['add']['plugin'] = array(
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#empty_option' => $this->t('- Choose -'),
      '#options' => $options,
    );
    $form['add']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add fulfillment method'),
      '#validate' => array('::validateAddMethod'),
      '#submit' => array('::submitAddMethod'),
      '#limit_validation_errors' => array(array('plugin')),
    );

    $form = parent::buildForm($form, $form_state);
    $form[$this->entitiesKey]['#empty'] = $this->t('No fulfillment methods have been configured yet.');

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * Form validation handler for adding a new method.
   */
  public function validateAddMethod(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('plugin')) {
      $form_state->setErrorByName('plugin', $this->t('You must select the new fulfillment method.'));
    }
  }

  /**
   * Form submission handler for adding a new method.
   */
  public function submitAddMethod(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.uc_fulfillment_method.add_form', ['plugin_id' => $form_state->getValue('plugin')]);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['description'] = array(
      '#markup' => '<p>' . t('By default, only the "Ship manually" fulfillment method is listed here. To see additional fulfillment methods you must <a href=":install">install additional modules</a>. For more information about payment methods and settings please read the <a href=":doc">Ubercart Documentation</a>.', [':install' => Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-ubercart-shipping'])->toString(), ':doc' => Url::fromUri('http://www.drupal.org/documentation/modules/ubercart')->toString()]) . '</p><p>' . t('The order of methods shown below is the order those methods will appear on the checkout page. To re-order, drag the method to its desired location using the drag icon then save the configuration using the button at the bottom of the page.') . '</p>',
    );
    $build += parent::render();

    return $build;
  }

}
