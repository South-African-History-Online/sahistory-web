<?php

namespace Drupal\entity_import\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\entity_import\EntityImportSourceManagerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Define entity importer form.
 */
class EntityImporterForm extends EntityForm  {

  /**
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * @var \Drupal\entity_import\EntityImportSourceManagerInterface
   */
  protected $entityImportSources;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Entity import form constructor.
   *
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   * @param \Drupal\entity_import\EntityImportSourceManagerInterface $entity_import_sources
   */
  public function __construct(
    RouteBuilderInterface $route_builder,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_bundle_info,
    MigrationPluginManagerInterface $migration_plugin_manager,
    EntityImportSourceManagerInterface $entity_import_sources
  ) {
    $this->routeBuilder = $route_builder;
    $this->entityBundleInfo = $entity_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityImportSources = $entity_import_sources;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('router.builder'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.migration'),
      $container->get('entity_import.source.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\entity_import\Entity\EntityImporter $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Label for the entity importer.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$entity, 'entityExist'],
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $form['expose_importer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expose importer'),
      '#description' => $this->t(
        'The entity importer will be available from the listing page.'
      ),
      '#default_value' => $entity->exposeImporter(),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $entity->getDescription(),
    ];

    $form['migration_config'] = [
      '#title' => $this->t('Migration Configuration'),
      '#type' => 'vertical_tabs',
    ];

    $form['migration_source'] = [
      '#type' => 'details',
      '#group' => 'migration_config',
      '#title' => $this->t('Source'),
      '#tree' => TRUE,
    ];
    $form['migration_source']['source'] = [
      '#prefix' => '<div id="entity-importer-migration-source">',
      '#suffix' => '</div>',
    ];
    $migration_source = $entity->getMigrationSource();

    $source_plugin_id = $this->getEntityFormValue(
      ['migration_source', 'source', 'plugin_id'],
      $form_state,
      $migration_source['plugin_id'] ?? NULL
    );

    $form['migration_source']['source']['plugin_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Source Plugin'),
      '#required' => TRUE,
      '#options' => $this->getSourceOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'entity-importer-migration-source',
        'callback' => [$this, 'ajaxReloadCallback'],
      ],
      '#default_value' => $source_plugin_id,
    ];

    if (isset($source_plugin_id)) {
      $configuration = $this->getEntityFormValue(
        ['migration_source', 'source', 'configuration'],
        $form_state,
        $migration_source['configuration'] ?? []
      );
      $source_plugin = $this
        ->entityImportSources
        ->createSourceStubInstance($source_plugin_id, $configuration);

      if ($source_plugin instanceof PluginFormInterface) {
        $subform = ['#parents' => ['migration_source', 'source', 'configuration']];
        $form['migration_source']['source']['configuration'] = $source_plugin
          ->buildConfigurationForm(
            $subform,
            $form_state
          );
      }
    }

    $form['migration_entity'] = [
      '#type' => 'details',
      '#group' => 'migration_config',
      '#title' => $this->t('Entity'),
      '#tree' => TRUE,
    ];
    $form['migration_entity']['entity'] = [
      '#prefix' => '<div id="entity-importer-migration-entity">',
      '#suffix' => '</div>',
    ];
    $migration_entity = $entity->getMigrationEntity();

    $options = $this->getEntityOptions();
    $entity_type = $this->getEntityFormValue(
      ['migration_entity', 'entity', 'type'],
      $form_state,
      $migration_entity['type'] ?? NULL
    );

    $form['migration_entity']['entity']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => $options['entities'],
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'entity-importer-migration-entity',
        'callback' => [$this, 'ajaxReloadCallback'],
      ],
      '#default_value' => $entity_type,
    ];

    if (isset($entity_type) && !empty($entity_type)) {
      $entity_bundle = $this->getEntityFormValue(
        ['migration_entity', 'entity', 'bundles'],
        $form_state,
        $migration_entity['bundles'] ?? []
      );
      $form['migration_entity']['entity']['bundles'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundles'),
        '#description' => $this->t('Select all bundles that can be imported.'),
        '#options' => $options['bundles'][$entity_type],
        '#multiple' => TRUE,
        '#required' => TRUE,
        '#default_value' => $entity_bundle,
      ];
    }
    $form['migration_dependencies'] = [
      '#type' => 'details',
      '#group' => 'migration_config',
      '#title' => $this->t('Dependencies'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    $dependencies = $entity->getMigrationDependencies();

    $form['migration_dependencies']['optional']['migration'] = [
      '#type' => 'select',
      '#title' => $this->t('Dependencies'),
      '#options' => $this->getMigrationOptions(),
      '#multiple' => TRUE,
      '#default_value' => $dependencies['optional']['migration'] ?? NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    /** @var \Drupal\entity_import\Entity\EntityImporter $entity */
    $entity = $this->entity;

    $this->rebuildRouteCache();

    if (!$entity->hasFieldMappings()) {
      return $form_state->setRedirect('entity.entity_importer_field_mapping.collection', [
        'entity_importer' => $this->entity->id(),
      ]);
    }

    return $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * Ajax reload callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function ajaxReloadCallback(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();

    return NestedArray::getValue(
      $form, array_slice($trigger['#array_parents'], 0, -1)
    );
  }

  /**
   * Get entity form value.
   *
   * @param string $property
   *   The entity property name or an array of nested properties.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @param null $default
   *
   * @return mixed|null
   *   The entity form value.
   */
  protected function getEntityFormValue($property, FormStateInterface $form_state, $default = NULL) {
    $property = !is_array($property)
      ? [$property]
      : $property;

    $array = (array) $this->entity;
    $value = $form_state->hasValue($property)
      ? $form_state->getValue($property)
      : NestedArray::getValue($array, $property);

    if (isset($value) && !empty($value)) {
      return $value;
    }

    return $default;
  }

  /**
   * Rebuild route cache.
   */
  protected function rebuildRouteCache() {
    /** @var \Drupal\entity_import\Entity\EntityImporter $entity */
    $entity = $this->entity;

    if ($entity->hasPageDisplayChanged()) {
      $this->routeBuilder->rebuild();
    }
  }

  /**
   * Get source options.
   *
   * @return mixed
   */
  protected function getSourceOptions() {
    return $this->entityImportSources->getDefinitionAsOptions();
  }

  /**
   * Get entity options.
   *
   * @return array
   *   An array of entity options.
   */
  protected function getEntityOptions() {
    $entity_info = [];

    foreach ($this->entityTypeManager->getDefinitions() as $plugin_id => $definition) {
      $class = $definition->getOriginalClass();
      $interface = FieldableEntityInterface::class;

      if (!is_subclass_of($class, $interface)) {
       continue;
      }
      $entity_info['entities'][$plugin_id] = $definition->getLabel();

      if ($bundles = $this->getEntityBundleOptions($definition->id())) {
        $entity_info['bundles'][$plugin_id] = $bundles;
      }
    }

    return $entity_info;
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

    foreach ($this->entityBundleInfo->getBundleInfo($entity_type_id) as $name => $definition) {
      if (!isset($definition['label'])) {
        continue;
      }
      $options[$name] = $definition['label'];
    }

    return $options;
  }

  /**
   * Get migration options.
   *
   * @return array
   *   An array of migration options.
   */
  protected function getMigrationOptions() {
    $options = [];

    foreach ($this->migrationPluginManager->getDefinitions() as $plugin_id => $definition) {
      if (!isset($definition['label'])) {
        continue;
      }
      $options[$plugin_id] = $definition['label'];
    }

    return $options;
  }
}
