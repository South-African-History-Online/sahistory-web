<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_import\EntityImportEntityPropertiesInterface;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\Plugin\migrate\process\MakeUniqueEntityField;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define the entity import make unique entity field.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_make_unique_entity_field",
 *   label = @Translation("Unique Entity Field")
 * )
 */
class EntityImportMakeUniqueEntityField extends MakeUniqueEntityField implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\entity_import\EntityImportEntityPropertiesInterface
   */
  protected $entityImportEntityProperties;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityImportEntityPropertiesInterface $entity_import_entity_properties
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $entity_type_manager
    );
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityImportEntityProperties = $entity_import_entity_properties;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration = NULL
  ) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_import.entity_properties')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfigurations() {
    return [
      'start' => 0,
      'field' => NULL,
      'length' => NULL,
      'postfix' => NULL,
      'migrated' => FALSE,
      'entity_type' => NULL,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="entity-import-unique-entity-field">';
    $form['#suffix'] = '</div>';

    $entity_options = $this->getEntityTypeOptions();
    $entity_type_id = $this->getFormStateValue('entity_type', $form_state);

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => $entity_options['entities'],
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'entity-import-unique-entity-field',
        'callback' => [$this, 'ajaxProcessCallback']
      ],
      '#default_value' => $entity_type_id,
    ];

    if (isset($entity_type_id) && !empty($entity_type_id)) {
      $options_bundle = $entity_options['bundles'][$entity_type_id];
      $entity_bundle = $this->getFormStateValue('bundle', $form_state);

      $form['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle Type'),
        '#options' => $options_bundle,
        '#empty_option' => $this->t('- Select -'),
        '#ajax' => [
          'event' => 'change',
          'method' => 'replace',
          'wrapper' => 'entity-import-unique-entity-field',
          'callback' => [$this, 'ajaxProcessCallback']
        ],
        '#required' => TRUE,
        '#default_value' => isset($options_bundle[$entity_bundle])
          ? $entity_bundle
          : NULL
      ];

      if (isset($entity_bundle)
        && !empty($entity_bundle)
        && isset($options_bundle[$entity_bundle])) {
        $options = $this->getEntityTypePropertiesOptions(
          $entity_type_id, $entity_bundle
        );
        $form['field'] = [
          '#type' => 'select',
          '#title' => $this->t('Field'),
          '#options' => $options,
          '#required' => TRUE,
          '#empty_option' => $this->t('- Select -'),
          '#default_value' => $this->getFormStateValue('field', $form_state)
        ];
      }
    }
    $form['start'] = [
      '#type' => 'number',
      '#title' => $this->t('Start'),
      '#description' => $this->t('The position at which to start reading.'),
      '#default_value' => $this->getFormStateValue('start', $form_state),
    ];
    $form['length'] = [
      '#type' => 'number',
      '#title' => $this->t('Length'),
      '#description' => $this->t('The number of characters to read.'),
      '#default_value' => $this->getFormStateValue('length', $form_state),
    ];
    $form['postfix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postfix'),
      '#description' => $this->t('A string to insert before the numeric postfix.'),
      '#default_value' => $this->getFormStateValue('postfix', $form_state),
    ];
    $form['migrated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Migrated'),
      '#description' => $this->t('Indicate that making the field unique only 
        occurs for migrated entities.'),
      '#default_value' => $this->getFormStateValue('migrated', $form_state),
    ];

    return $form;
  }

  /**
   * Get entity type properties options.
   *
   * @param $entity_type_id
   *   The entity type identifier.
   * @param $bundle_type
   *   The entity bundle type.
   *
   * @return array
   *   An array of the entity type properties options.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityTypePropertiesOptions($entity_type_id, $bundle_type) {
    $options = [];

    if ($entity_type = $this->entityTypeManager->getDefinition($entity_type_id)) {
      $options = $this
        ->entityImportEntityProperties
        ->getEntityPropertiesOptions($entity_type, $bundle_type);
    }

    return $options;
  }

  /**
   * Get entity type options.
   *
   * @return array
   *   An array of entity type options.
   */
  protected function getEntityTypeOptions() {
    $options = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $definition) {
      if (!is_subclass_of($definition, EntityTypeInterface::class)) {
        continue;
      }
      $group_type = $definition->getGroupLabel()->render();
      $options['entities'][$group_type][$entity_type_id] = $definition->getLabel();

      if ($bundles = $this->getEntityBundleOptions($definition->id())) {
        $options['bundles'][$entity_type_id] = $bundles;
      }
    }

    return $options;
  }

  /**
   * Get entity bundle options.
   *
   * @param $entity_type_id
   *   The entity type identifier.
   *
   * @return array
   *   An array of entity bundle options.
   */
  protected function getEntityBundleOptions($entity_type_id) {
    $options = [];

    foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type_id) as $name => $definition) {
      if (!isset($definition['label'])) {
        continue;
      }
      $options[$name] = $definition['label'];
    }

    return $options;
  }
}
