<?php

namespace Drupal\entity_import\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_import\EntityImportProcessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define the entity importer field mapping form.
 */
class EntityImporterFieldMappingForm extends EntityForm {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\entity_import\EntityImportProcessManager
   */
  protected $entityImportProcessManager;

  /**
   * Entity importer field mapping form.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\entity_import\EntityImportProcessManagerInterface $entity_import_process_manager
   */
  public function __construct(
    EntityFieldManagerInterface $entity_field_manager,
    EntityImportProcessManagerInterface $entity_import_process_manager
  ) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityImportProcessManager = $entity_import_process_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_import.process.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_import\Entity\EntityImporterFieldMapping $mapping_entity */
    $mapping_entity = $this->entity;
    $mapping_bundle = $mapping_entity->getImporterBundle();

    /** @var \Drupal\entity_import\Entity\EntityImporter $importer_entity */
    $importer_entity = $mapping_entity->getImporterEntity();
    $importer_bundles = $importer_entity->getImporterBundles();

    // Disable the field mapping form if the bundle no longer supported.
    if (isset($mapping_bundle) && !in_array($mapping_bundle, $importer_bundles, TRUE)) {
      $form['#disabled'] = TRUE;

      // Add a warning message so users knows why the form is disabled.
      $this->messenger()->addWarning(
        $this->t('This field mapping is no longer valid as @bundle is no longer
          supported in the importer.'
        , ['@bundle' => $mapping_entity->getImporterBundle()])
      );
    }
    $form['#parents'] = [];

    $form['label'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Input the field label.'),
      '#required' => TRUE,
      '#size' => 25,
      '#default_value' => $mapping_entity->label(),
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine Name'),
      '#description' => $this->t('Input the field machine name.'),
      '#machine_name' => [
        'exists' => [$mapping_entity, 'entityExist'],
      ],
      '#required' => TRUE,
      '#default_value' => $mapping_entity->name(),
    ];
    $importer_bundle = $this->getEntityPropertyValue(
      'importer_bundle', $form_state
    );

    if ($importer_entity->hasMultipleBundles()) {
      $form['importer_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Target Bundle'),
        '#required' => TRUE,
        '#description' => $this->t('Select the associated bundle.'),
        '#options' => $importer_bundles,
        '#default_value' => $importer_bundle,
        '#ajax' => [
          'event' => 'change',
          'method' => 'replace',
          'wrapper' => 'entity-importer-field-mapping',
          'callback' => [$this, 'ajaxChangeCallback'],
        ],
      ];
    }
    else {
      $importer_bundle = current($importer_bundles);
      $form['importer_bundle'] = [
        '#type' => 'value',
        '#value' => $importer_bundle,
      ];
    }

    // Update the field mapping name to use the prefix id when looking for
    // existing entities. Needs to be at the bottom due to the importer bundle
    // being dynamic.
    $form['name']['#prefix_id'] = "{$mapping_entity->getImporterType()}.{$importer_bundle}";

    $form['#prefix'] = '<div id="entity-importer-field-mapping">';
    $form['#suffix'] = '</div>';

    $form['source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source Name'),
      '#description' => $this->t('Input the source name where the value derives from.'),
      '#default_value' => $this->getEntityPropertyValue('source', $form_state),
      '#required' => TRUE,
      '#size' => 25,
    ];

    // Dynamically add the entity destination location based on the bundle.
    if (isset($importer_bundle) && !empty($importer_bundle)) {
      $importer_entity_type = $importer_entity->getImporterEntityType();
      $mapping_entity_fields = $this->loadFieldMappingFields(
        $importer_entity_type, $importer_bundle
      );
      $destination = $this->getEntityPropertyValue('destination', $form_state);

      $form['destination'] = [
        '#type' => 'select',
        '#title' => $this->t('Field Destination'),
        '#description' => $this->t('Select the field destination where data will be referenced.'),
        '#options' => $this->getEntityPropertyOptions($mapping_entity_fields),
        '#required' => TRUE,
        '#empty_option' => $this->t('- Select -'),
        '#default_value' => $destination,
        '#ajax' => [
          'event' => 'change',
          'method' => 'replace',
          'wrapper' => 'entity-importer-field-mapping',
          'callback' => [$this, 'ajaxChangeCallback'],
        ],
      ];
      $processing_info = $this->getMigrationProcessingInfo();

      $processing_options = $processing_info['options'] ?? [];
      $processing_instances = $processing_info['instances'] ?? [];

      if (!empty($processing_options)) {
        $form['processing'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Data Processing'),
          '#tree' => TRUE,
        ];
        $form['processing']['#prefix'] = '<div id="entity-importer-field-mapping-processing">';
        $form['processing']['#suffix'] = '</div>';

        $processing = $this->getEntityPropertyValue('processing', $form_state);
        $processing_plugin = isset($processing['plugins'])
          ? array_values($processing['plugins'])
          : [];

        $form['processing']['plugins'] = [
          '#type' => 'select',
          '#title' => $this->t('Process Plugins'),
          '#description' => $this->t('Select the processes that should be performed.'),
          '#options' => $processing_options,
          '#multiple' => TRUE,
          '#default_value' => $processing_plugin,
          '#ajax' => [
            'event' => 'change',
            'method' => 'replace',
            'wrapper' => 'entity-importer-field-mapping-processing',
            'callback' => [$this, 'ajaxChangeCallback'],
          ],
        ];
        $form['processing']['configuration'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Configuration'),
        ];
        $form['processing']['configuration']['plugins'] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Name'),
            $this->t('Settings'),
            $this->t('Weight'),
          ],
          '#tabledrag' => [
            [
              'action' => 'order',
              'relationship' => 'sibling',
              'group' => 'table-sort-weight',
            ],
          ],
          '#empty' => $this->t('No process plugins to configure.'),
        ];

        foreach ($processing_plugin as $delta => $plugin_id) {
          if (!isset($processing_instances[$plugin_id])) {
            continue;
          }
          $process_name = $processing_options[$plugin_id];

          $form['processing']['configuration']['plugins'][$plugin_id]['#attributes']['class'][] = 'draggable';
          $form['processing']['configuration']['plugins'][$plugin_id]['#weight'] = $delta;

          $form['processing']['configuration']['plugins'][$plugin_id]['name'] = [
            '#markup' => $process_name,
          ];
          $plugin_info = $processing['configuration']['plugins'][$plugin_id] ?? [];
          $process_configuration = $plugin_info['settings'] ?? [];

          /** @var \Drupal\entity_import\Plugin\migrate\process\EntityImportProcessInterface $process_instance */
          $process_instance = $this->entityImportProcessManager
            ->createPluginInstance($plugin_id, $process_configuration);

          $subform = ['#parents' => [
            'processing', 'configuration', 'plugins', $plugin_id, 'settings',
          ]];

          $form['processing']['configuration']['plugins'][$plugin_id]['settings'] = $process_instance
            ->buildConfigurationForm(
              $subform,
              SubformState::createForSubform($subform, $form, $form_state)
            );

          if (empty($form['processing']['configuration']['plugins'][$plugin_id]['settings'])) {
            $form['processing']['configuration']['plugins'][$plugin_id]['settings'] = [];
          }

          $process_weight = $plugin_info['weight'] ?? $delta;

          $form['processing']['configuration']['plugins'][$plugin_id]['weight'] = [
            '#type' => 'weight',
            '#title' => $this->t('Weight for @process_name', [
              '@process_name' => $process_name,
            ]),
            '#title_display' => 'invisible',
            '#default_value' => $process_weight,
            '#attributes' => [
              'class' => [
                'table-sort-weight',
              ],
            ],
          ];
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Recorder the processing plugins by the defined weight.
    $processing = $form_state->getValue(['processing'], []);

    $form_state->setValue(
      ['processing', 'plugins'],
      $this->reorderProcessingPluginsByWeight($processing)
    );

    $this->processProcessingPlugins($form, $form_state, 'validateConfigurationForm');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->processProcessingPlugins($form, $form_state, 'submitConfigurationForm');

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * Ajax element change callback.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return mixed
   */
  public function ajaxChangeCallback(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();

    return NestedArray::getValue(
      $form, array_splice($trigger['#array_parents'], 0, -1)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    /** @var \Drupal\entity_import\Entity\EntityImporterFieldMapping $entity */
    $entity = parent::getEntityFromRouteMatch($route_match, $entity_type_id);

    if ($route_match->getRawParameter('entity_importer') !== NULL) {
      $entity->setImporterType($route_match->getParameter('entity_importer'));
    }

    return $entity;
  }

  /**
   * Process processing plugins.
   *
   * @param array $form
   *   An array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param $method
   *   A process submit or validate method to call.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function processProcessingPlugins(array $form, FormStateInterface $form_state, $method) {
    $processing_plugins = $form_state->getValue(
      ['processing', 'configuration', 'plugins']
    );
    $processing_plugins = $processing_plugins ?: [];

    foreach ($processing_plugins as $plugin_id => $plugin_info) {
      $settings = $plugin_info['settings'] ?? [];
      /** @var \Drupal\entity_import\Plugin\migrate\process\EntityImportProcessInterface $process */
      $process = $this
        ->entityImportProcessManager
        ->createPluginInstance($plugin_id, $settings);

      $subform = ['#parents' => [
        'processing', 'configuration', 'plugins', $plugin_id, 'settings',
      ]];

      if (!method_exists($process, $method)) {
        continue;
      }

      call_user_func_array([$process, $method], [
        &$subform,
        SubformState::createForSubform($subform, $form, $form_state),
      ]);
      $settings = $process->getConfiguration();

      $form_state->setValue($subform['#parents'], $settings);
    }
  }

  /**
   * Reorder processing plugins by weight.
   *
   * @param array $processing
   *   An array of processing values.
   *
   * @return array
   *   The processing plugins ordered by weight.
   */
  protected function reorderProcessingPluginsByWeight(array $processing) {
    $plugins = [];

    if (empty($processing) || !isset($processing['plugins'])) {
      return $plugins;
    }
    $configuration = isset($processing['configuration'])
      ? $processing['configuration']
      : [];

    foreach ($processing['plugins'] as $plugin_id) {

      if (isset($configuration['plugins'][$plugin_id])) {
        $plugin_info = $configuration['plugins'][$plugin_id];
        if (!isset($plugin_info['weight'])) {
          continue;
        }
        $weight = $this->determineWeightValue($plugin_info['weight'], $plugins);

        $plugins[$weight] = $plugin_id;
      }
      else {
        $plugins[] = $plugin_id;
      }
    }
    ksort($plugins, SORT_NUMERIC);

    $plugins = array_values($plugins);

    return array_combine($plugins, $plugins);
  }

  /**
   * Determine the weight value.
   *
   * @param $weight
   *   The weight value.
   * @param $values
   *   An array of all reordered values.
   *
   * @return mixed
   */
  protected function determineWeightValue($weight, $values) {
    if (!isset($values[$weight])) {
      return $weight;
    }
    $weight++;

    return $this->determineWeightValue($weight, $values);
  }

  /**
   * Load field mapping fields.
   *
   * @param $entity_type
   * @param $bundle
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected function loadFieldMappingFields($entity_type, $bundle) {
    return $this
      ->entityFieldManager
      ->getFieldDefinitions($entity_type, $bundle);
  }

  /**
   * Get entity property value.
   *
   * @param $property
   *   The property name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return mixed
   */
  protected function getEntityPropertyValue($property, FormStateInterface $form_state) {
    return $form_state->hasValue($property)
      ? $form_state->getValue($property)
      : $this->entity->{$property};
  }

  /**
   * Get entity importer entity.
   *
   * @return \Drupal\entity_import\Entity\EntityImporter
   */
  protected function getEntityImporterEntity() {
    return $this->entity->getImporterEntity();
  }

  /**
   * Get entity property options.
   *
   * @param array $fields
   *
   * @return array
   *   An array of entity property options.
   */
  protected function getEntityPropertyOptions(array $fields) {
    $options = [];

    foreach ($fields as $field_name => $field) {
      if ($field->isComputed()) {
        continue;
      }
      /** @var \Drupal\Core\Field\TypedData\FieldItemDataDefinition $item_definition */
      $item_definition = $field->getItemDefinition();

      $options["{$field_name}"] = $this->t('@field_name', [
        '@field_name' => $field_name,
      ]);
      $field_property_options = [];

      /** @var \Drupal\Core\TypedData\DataDefinition $data_definition */
      foreach ($item_definition->getPropertyDefinitions() as $property_name => $data_definition) {
        if ($data_definition->isComputed() || $data_definition->isReadOnly()) {
          continue;
        }
        $field_property_options["{$field_name}/{$property_name}"] = $this->t('@field_name/@property_name', [
          '@field_name' => $field_name,
          '@property_name' => $property_name,
        ]);
      }

      if (count($field_property_options) > 1) {
        $options = array_merge($options, $field_property_options);
      }
    }

    return $options;
  }

  /**
   * Get migration process information.
   *
   * @return array
   *   Return an array of migration process info: options, instances, etc.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getMigrationProcessingInfo() {
    return $this
      ->entityImportProcessManager
      ->getMigrationProcessInfo();
  }
}
