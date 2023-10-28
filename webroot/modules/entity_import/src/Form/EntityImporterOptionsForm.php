<?php

namespace Drupal\entity_import\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_import\Entity\EntityImporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity importer options.
 */
class EntityImporterOptionsForm extends FormBase {

  /**
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    FieldTypePluginManagerInterface $field_type_manager
  ) {
    $this->configFactory = $config_factory;
    $this->fieldTypeManager = $field_type_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_import_field_mapping_options';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_importer_type = NULL) {
    /** @var \Drupal\entity_import\Entity\EntityImporter $entity_importer */
    $entity_importer = $this->loadEntityImporter($entity_importer_type);

    if (!isset($entity_importer)) {
      return $form;
    }

    $form['options'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Options')
    ];
    $form['unique_identifiers'] = [
      '#type' => 'details',
      '#title' => $this->t('Unique Identifiers'),
      '#description' => t('Define unique identifiers that are available in the 
        provided source data. All provided identifiers will need to contain a 
        value in the source and can not be NULL.'),
      '#group' => 'options',
      '#tree' => TRUE,
    ];

    if ($entity_importer->hasFieldMappings()
      && !$entity_importer->hasFieldMappingUniqueIdentifiers()) {
      $form['unique_identifiers']['messages'] = [
        '#markup' => 'At least one unique identifier needs to be defined.',
        '#prefix' => '<div class="messages messages--warning">',
        '#suffix' => '</div>'
      ];
    }
    $form['unique_identifiers']['items'] = [
      '#type' => 'container',
      '#prefix' => '<div id="entity-importer-unique-identifiers-items">',
      '#suffix' => '</div>',
    ];
    $parents = ['unique_identifiers', 'items'];
    $configurations = $this->getFormItemValue(
      $parents, $form_state, $entity_importer
    );
    $property = ['entity_importer', 'unique_identifier_count'];

    $items_count = $form_state->has($property)
      ? $form_state->get($property)
      : $form_state->set($property, count($configurations))->get($property);

    for ($i = 0; $i < $items_count; $i++) {
      $form['unique_identifiers']['items'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Identifier @number', ['@number' => $i + 1]),
        '#prefix' => "<div id='entity-importer-unique-identifiers-items-{$i}'>",
        '#suffix' => '</div>',
      ];
      $items = $configurations[$i];
      $parents = array_merge($parents, [$i]);

      $reference_type = isset($items['reference_type'])
        ? $items['reference_type']
        : NULL;
      $form['unique_identifiers']['items'][$i]['reference_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Reference Type'),
        '#required' => TRUE,
        '#empty_option' => $this->t('- Select -'),
        '#options' => [
          'field_type' => $this->t('Field Type'),
          'field_plugin' => $this->t('Field Plugin')
        ],
        '#ajax' => [
          'event' => 'change',
          'method' => 'replace',
          'callback' => [$this, 'replaceAjaxCallback'],
          'wrapper' => "entity-importer-unique-identifiers-items-{$i}"
        ],
        '#default_value' => $reference_type,
      ];

      if (isset($reference_type) && !empty($reference_type)) {
        if ($reference_type === 'field_type') {
          $form['unique_identifiers']['items'][$i]['identifier_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Identifier Name'),
            '#size' => 15,
            '#required' => TRUE,
            '#default_value' => isset($items['identifier_name'])
              ? $items['identifier_name']
              : NULL,
          ];
        }
        $options = $reference_type === 'field_type'
          ? $this->getFieldTypeOptions()
          : $entity_importer->getFieldMappingOptions();

        $identifier_type = isset($items['identifier_type'])
          ? $items['identifier_type']
          : NULL;

        $form['unique_identifiers']['items'][$i]['identifier_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Identifier Type'),
          '#options' => $options,
          '#required' => TRUE,
          '#empty_option' => $this->t('- Select -'),
          '#default_value' => (isset($options[$identifier_type]) && !empty($options[$identifier_type]))
            ? $identifier_type
            : NULL,
        ];

        if ($reference_type === 'field_type') {
          $form['unique_identifiers']['items'][$i]['identifier_settings'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Identifier Settings'),
            '#description' => $this->t('Input the field storage settings using a 
              JSON format.'),
            '#default_value' => isset($items['identifier_settings']) ? $items['identifier_settings'] : NULL,
          ];
        }
      }
    }
    $form['unique_identifiers']['items']['actions']['#type'] = 'actions';
    $form['unique_identifiers']['items']['actions']['add'] = [
      '#op' => 'add',
      '#type' => 'submit',
      '#value' => $this->t('Add Identifier'),
      '#limit_validation_errors' => [],
      '#submit' => [
        [$this, 'itemsSubmitCallback']
      ],
      '#ajax' => [
        'method' => 'replace',
        'wrapper' => 'entity-importer-unique-identifiers-items',
        'callback' => [$this, 'itemsAjaxCallback']
      ]
    ];
    // Allow identifiers to be removed.
    if ($items_count !== 0) {
      $form['unique_identifiers']['items']['actions']['remove'] = [
        '#op' => 'remove',
        '#type' => 'submit',
        '#value' => $this->t('Remove Identifier'),
        '#limit_validation_errors' => [],
        '#submit' => [
          [$this, 'itemsSubmitCallback']
        ],
        '#ajax' => [
          'method' => 'replace',
          'wrapper' => 'entity-importer-unique-identifiers-items',
          'callback' => [$this, 'itemsAjaxCallback']
        ]
      ];
    }
    $form['#importer'] = $entity_importer;

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save')
    ];

    return $form;
  }

  /**
   * Get form item value.
   *
   * @param array $property
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @param \Drupal\entity_import\Entity\EntityImporterInterface $entity_importer
   *
   * @return mixed
   */
  protected function getFormItemValue(
    array $property,
    FormStateInterface $form_state,
    EntityImporterInterface $entity_importer
  ) {
    // Get the value from the form state then fallback to user input data.
    $value = $form_state->hasValue($property)
      ? $form_state->getValue($property)
      : NestedArray::getValue($form_state->getUserInput(), $property);

    if (isset($value)) {
      return $value;
    }

    // If value is empty then default to looking in saved configuration array.
    return $this
      ->getConfig($entity_importer->id())
      ->get(implode('.', $property)) ?: [];
  }

  /**
   * Replace AJAX callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function replaceAjaxCallback(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_splice($trigger['#array_parents'], 0, -1));
  }

  /**
   * Identifier items AJAX callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function itemsAjaxCallback(array $form, FormStateInterface $form_state) {
    return $form['unique_identifiers']['items'];
  }

  /**
   * Identifier items submit callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function itemsSubmitCallback(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();

    if (!isset($trigger['#op'])) {
      return;
    }
    $property = [
      'entity_importer',
      'unique_identifier_count'
    ];
    $identifier_count = $form_state->get($property);

    switch ($trigger['#op']) {
      case 'add':
        ++$identifier_count;
        break;
      case 'remove':
        --$identifier_count;
        break;
    }
    $form_state
      ->set($property, $identifier_count)
      ->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $identifiers = $form_state->getValue('unique_identifiers', []);

    if (isset($identifiers['items'])
      && !empty($identifiers['items'])) {

      $identifier_items = [];

      // Iterate over all unique identifiers items.
      foreach ($identifiers['items'] as $delta => $item) {
        if (!isset($item['reference_type'])) {
          continue;
        }
        $element = $form['unique_identifiers']['items'][$delta];

        // React on different reference type.
        switch ($item['reference_type']) {
          case 'field_type':

            // Verify that the identifier settings has content prior.
            if (isset($item['identifier_settings'])
              && !empty($item['identifier_settings'])) {
              $element = $element['identifier_settings'];

              // Validate the identifier settings contain valid JSON.
              if (json_decode($item['identifier_settings']) === NULL) {
                $form_state->setError(
                  $element,
                  $this->t('@title JSON format is not valid.', [
                    '@title' => $element['#title']
                  ])
                );
              }
            }
            break;
        }
        $identifier_items['items'][] = $item;
      }

      // Set the identifier items without non numeric keys.
      $form_state->setValue('unique_identifiers', $identifier_items);
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
 */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!isset($form['#importer'])) {
      return;
    }
    /** @var \Drupal\entity_import\Entity\EntityImporter $importer */
    $importer = $form['#importer'];

    if (!$importer instanceof EntityImporterInterface) {
      return;
    }
    $values = $form_state->cleanValues()->getValues();
    $values = isset($values['unique_identifiers']) && !empty($values['unique_identifiers'])
      ? $values
      : [];

    $this->updateConfigurationValues(
      $importer->id(),
      $values
    );
  }

  /**
   * Update importer options configuration values.
   *
   * @param $importer_type_id
   *   The importer type identifier.
   * @param array $values
   *   The unique identifier values.
   *
   * @return \Drupal\Core\Config\Config
   */
  protected function updateConfigurationValues($importer_type_id, array $values) {
    $config = $this->getConfig($importer_type_id, TRUE);

    if (empty($values)) {
      return $config->delete();
    }

    return $config->setData($values)->save();
  }

  /**
   * Get configuration object.
   *
   * @param $importer_type_id
   *   The importer type identifier.
   * @param bool $editable
   *   Determine if the configuration is editable.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected function getConfig($importer_type_id, $editable = FALSE) {
    $config = $this->configFactory;
    $config_name = "entity_import.options.{$importer_type_id}";

    if (!$editable) {
      return $config->get($config_name);
    }

    return $config->getEditable($config_name);
  }

  /**
   * Load entity importer type.
   *
   * @param $entity_type_id
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadEntityImporter($entity_type_id) {
    return $this->entityTypeManager
      ->getStorage('entity_importer')
      ->load($entity_type_id);
  }

  /**
   * Get field type as options.
   *
   * @return array
   */
  protected function getFieldTypeOptions() {
    $options = [];

    foreach ($this->fieldTypeManager->getDefinitions() as $id => $definition) {
      if ($definition['no_ui']) {
        continue;
      }
      $options[$id] = $definition['label'];
    }

    return $options;
  }
}
