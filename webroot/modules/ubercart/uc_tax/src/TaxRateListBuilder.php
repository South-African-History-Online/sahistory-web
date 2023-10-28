<?php

namespace Drupal\uc_tax;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_tax\Plugin\TaxRatePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of tax rate entities.
 */
class TaxRateListBuilder extends DraggableListBuilder implements FormInterface {

  /**
   * The tax rate plugin manager.
   *
   * @var \Drupal\uc_tax\Plugin\TaxRatePluginManager
   */
  protected $taxRatePluginManager;

  /**
   * Constructs a new TaxRateListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\uc_tax\Plugin\TaxRatePluginManager $tax_rate_plugin_manager
   *   The tax rate plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, TaxRatePluginManager $tax_rate_plugin_manager) {
    parent::__construct($entity_type, $storage);
    $this->taxRatePluginManager = $tax_rate_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.uc_tax.rate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_tax_rates_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = array(
      'data' => $this->t('Name'),
    );
    $header['description'] = array(
      'data' => $this->t('Description'),
    );
    $header['shippable'] = array(
      'data' => $this->t('Taxed products'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    );
    $header['product_types'] = array(
      'data' => $this->t('Taxed product types'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    );
    $header['line_item_types'] = array(
      'data' => $this->t('Taxed line items'),
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
    $plugin = $entity->getPlugin();
    $row['label'] = $entity->label();
    $row['description']['#markup'] = $plugin->getSummary();
    $row['shippable']['#markup'] = $entity->isForShippable() ? $this->t('Shippable products') : $this->t('Any product');
    $row['product_types']['#markup'] = implode(', ', $entity->getProductTypes());
    $row['line_item_types']['#markup'] = implode(', ', $entity->getLineItemTypes());
    $row['status']['#markup'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = parent::buildOperations($entity);
    $build['#links']['clone'] = array(
      'title' => $this->t('Clone'),
      'url' => Url::fromRoute('entity.uc_tax_rate.clone', ['uc_tax_rate' => $entity->id()]),
      'weight' => 10, // 'edit' is 0, 'delete' is 100
    );

    uasort($build['#links'], 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = array_map(function ($definition) {
      return $definition['label'];
    }, $this->taxRatePluginManager->getDefinitions());
    uasort($options, 'strnatcasecmp');

    $form['add'] = array(
      '#type' => 'details',
      '#title' => $this->t('Add a tax rate'),
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
      '#value' => $this->t('Add tax rate'),
      '#validate' => array('::validateAddMethod'),
      '#submit' => array('::submitAddMethod'),
      '#limit_validation_errors' => array(array('plugin')),
    );

    $form = parent::buildForm($form, $form_state);
    $form[$this->entitiesKey]['#empty'] = $this->t('No tax rates have been configured yet.');

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
   * Form validation handler for adding a new rate.
   */
  public function validateAddMethod(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('plugin')) {
      $form_state->setErrorByName('plugin', $this->t('You must select a tax rate type.'));
    }
  }

  /**
   * Form submission handler for adding a new method.
   */
  public function submitAddMethod(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.uc_tax_rate.add_form', ['plugin_id' => $form_state->getValue('plugin')]);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>This is a list of the tax rates currently"
        . " defined on your Drupal site.</p><p>You may use the 'Add tax rate'"
        . " button to add a new rate, or use the widget in the 'Operations'"
        . " column to edit, delete, enable/disable, or clone existing tax rates."
        . " Rates that are disabled will not be applied at checkout and will not"
        . " be included in product prices.</p>"
        . "<p>Taxes are sorted by weight and then applied to the order sequentially."
        . " This order is important when taxes need to be applied to other tax line items."
        . " To re-order, drag the method to its desired location using the drag icon then save"
        . " the configuration using the button at the bottom of the page.</p>"
      ),
    );
    $build += parent::render();

    return $build;
  }

}
