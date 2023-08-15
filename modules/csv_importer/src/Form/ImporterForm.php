<?php

namespace Drupal\csv_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\csv_importer\ParserInterface;
use Drupal\csv_importer\Plugin\ImporterManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides CSV importer form.
 */
class ImporterForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * The parser service.
   *
   * @var \Drupal\csv_importer\Parser\ParserInterface
   */
  protected $parser;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The importer plugin manager service.
   *
   * @var \Drupal\csv_importer\Plugin\ImporterManager
   */
  protected $importer;

  /**
   * ImporterForm class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The entity bundle info service.
   * @param \Drupal\csv_importer\Parser\ParserInterface $parser
   *   The parser service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\csv_importer\Plugin\ImporterManager $importer
   *   The importer plugin manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_bundle_info, ParserInterface $parser, RendererInterface $renderer, ImporterManager $importer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityBundleInfo = $entity_bundle_info;
    $this->parser = $parser;
    $this->renderer = $renderer;
    $this->importer = $importer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('csv_importer.parser'),
      $container->get('renderer'),
      $container->get('plugin.manager.importer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'csv_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['importer'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'csv-importer',
      ],
    ];

    $form['importer']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose entity type'),
      '#required' => TRUE,
      '#options' => $this->getEntityTypeOptions(),
      '#weight' => 0,
      '#ajax' => [
        'callback' => [$this, 'getContentEntityTypesAjaxForm'],
        'wrapper' => 'csv-importer',
        'event' => 'change',
      ],
    ];

    if ($entity_type = $form_state->getValue('entity_type')) {

      if ($options = $this->getEntityTypeBundleOptions($entity_type)) {
        $form['importer']['entity_type_bundle'] = [
          '#type' => 'select',
          '#title' => $this->t('Choose entity bundle'),
          '#options' => $options,
          '#required' => TRUE,
          '#weight' => 5,
        ];

      }

      $options = $this->getEntityTypeImporterOptions($entity_type);

      $form['importer']['plugin_id'] = [
        '#type' => 'hidden',
        '#value' => key($options),
      ];

      if (count($options) > 1) {
        $form['importer']['plugin_id'] = [
          '#type' => 'select',
          '#title' => $this->t('Choose importer'),
          '#options' => $options,
          '#default_value' => 0,
          '#weight' => 25,
        ];
      }
    }

    $form['importer']['csv'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Choose CSV file'),
      '#required' => TRUE,
      '#autoupload' => TRUE,
      '#upload_validators' => ['file_validate_extensions' => ['csv']],
      '#weight' => 10,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Entity type AJAX form handler.
   */
  public function getContentEntityTypesAjaxForm(array &$form, FormStateInterface $form_state) {
    return $form['importer'];
  }

  /**
   * Get entity type options.
   *
   * @return array
   *   Entity type options.
   */
  protected function getEntityTypeOptions() {
    $options = [];
    $plugin_definitions = $this->importer->getDefinitions();

    foreach ($plugin_definitions as $definition) {
      $entity_type = $definition['entity_type'];
      if ($this->entityTypeManager->hasDefinition($entity_type)) {
        $entity = $this->entityTypeManager->getDefinition($entity_type);
        $options[$entity_type] = $entity->getLabel();
      }
    }

    return $options;
  }

  /**
   * Get entity type bundle options.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return array
   *   Entity type bundle options.
   */
  protected function getEntityTypeBundleOptions(string $entity_type) {
    $options = [];
    $entity = $this->entityTypeManager->getDefinition($entity_type);

    if ($entity && $type = $entity->getBundleEntityType()) {
      $types = $this->entityTypeManager->getStorage($type)->loadMultiple();

      if ($types && is_array($types)) {
        foreach ($types as $type) {
          $options[$type->id()] = $type->label();
        }
      }
    }

    return $options;
  }

  /**
   * Get entity importer plugin options.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return array
   *   Entity importer plugin options.
   */
  protected function getEntityTypeImporterOptions(string $entity_type) {
    $plugin_definitions = $this->importer->getDefinitions();
    $entity_type_importers = array_keys(array_combine(array_keys($plugin_definitions), array_column($plugin_definitions, 'entity_type')), $entity_type);

    if ($entity_type_importers && is_array($entity_type_importers)) {
      $plugin_definitions = array_intersect_key($plugin_definitions, array_flip($entity_type_importers));

      foreach ($plugin_definitions as $plugin_id => $plugin_defintion) {
        $options[$plugin_id] = $plugin_defintion['label'];
      }
    }

    return $options;
  }

  /**
   * Get entity type fields.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string|null $entity_type_bundle
   *   Entity type bundle.
   *
   * @return array
   *   Entity type fields.
   */
  protected function getEntityTypeFields(string $entity_type, string $entity_type_bundle = NULL) {
    $fields = [];

    if (!$entity_type_bundle) {
      $entity_type_bundle = key($this->entityBundleInfo->getBundleInfo($entity_type));
    }

    $entity_fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity_type_bundle);
    foreach ($entity_fields as $entity_field) {
      $fields['fields'][] = $entity_field->getName();

      if ($entity_field->isRequired()) {
        $fields['required'][] = $entity_field->getName();
      }
    }

    return $fields;
  }

  /**
   * Get entity missing fields.
   *
   * @param string $entity_type
   *   Entity type.
   * @param array $required
   *   Entity required fields.
   * @param array $csv
   *   Parsed CSV.
   *
   * @return array
   *   Missing fields.
   */
  protected function getEntityTypeMissingFields(string $entity_type, array $required, array $csv) {
    $entity_definition = $this->entityTypeManager->getDefinition($entity_type);

    if ($entity_definition->hasKey('bundle')) {
      unset($required[array_search($entity_definition->getKey('bundle'), $required)]);
    }

    $csv_fields = [];

    foreach ($csv[0] as $csv_row) {
      $csv_row = explode('|', $csv_row);
      $csv_fields[] = $csv_row[0];
    }

    $csv_fields = array_values(array_unique($csv_fields));

    return array_diff($required, $csv_fields);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type');
    $entity_type_bundle = NULL;
    $csv = current($form_state->getValue('csv'));
    $csv_parse = $this->parser->getCsvById($csv);

    if (isset($form_state->getUserInput()['entity_type_bundle'])) {
      $entity_type_bundle = $form_state->getUserInput()['entity_type_bundle'];
    }

    $entity_fields = $this->getEntityTypeFields($entity_type, $entity_type_bundle);

    if ($required = $this->getEntityTypeMissingFields($entity_type, $entity_fields['required'], $csv_parse)) {
      $render = [
        '#theme' => 'item_list',
        '#items' => $required,
      ];

      drupal_set_message($this->t('Your CSV has missing required fields: @fields', ['@fields' => $this->renderer->render($render)]), 'error');
    }
    else {
      $this->importer->createInstance($form_state->getUserInput()['plugin_id'], [
        'csv' => $csv_parse,
        'csv_entity' => $this->parser->getCsvEntity($csv),
        'entity_type' => $entity_type,
        'entity_type_bundle' => $entity_type_bundle,
        'fields' => $entity_fields['fields'],
      ])->process();
    }
  }

}
