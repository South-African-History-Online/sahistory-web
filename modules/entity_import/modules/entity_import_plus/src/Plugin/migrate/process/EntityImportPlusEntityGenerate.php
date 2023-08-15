<?php

namespace Drupal\entity_import_plus\Plugin\migrate\process;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_import\Plugin\migrate\process\EntityImportProcessInterface;
use Drupal\entity_import\Plugin\migrate\process\EntityImportProcessTrait;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity import plus entity generate process.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_plus_entity_generate",
 *   label = @Translation("Entity Generate")
 * )
 */
class EntityImportPlusEntityGenerate extends EntityGenerate implements EntityImportProcessInterface {

    use EntityImportProcessTrait;

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var \Drupal\Core\Entity\EntityFieldManagerInterface
     */
    protected $entityFieldManager;

    /**
     * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
     */
    protected $entityTypeBundleInfo;

    /**
     * @inheritdoc
     */
    public function __construct(
        array $configuration,
        $pluginId,
        $pluginDefinition,
        MigrationInterface $migration,
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager,
        EntityTypeBundleInfoInterface $entityTypeBundleInfo
    ) {
        parent::__construct(
            $configuration,
            $pluginId,
            $pluginDefinition
        );
        $this->migration = $migration;
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    }

    /**
     * @inheritdoc
     */
    public static function create(
        ContainerInterface $container,
        array $configuration,
        $pluginId,
        $pluginDefinition,
        MigrationInterface $migration = NULL
    ) {
        return new static(
            $configuration,
            $pluginId,
            $pluginDefinition,
            $migration,
            $container->get('entity_type.manager'),
            $container->get('entity_field.manager'),
            $container->get('entity_type.bundle.info')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfigurations() {
        return [
            'bundle' => NULL,
            'value_key' => NULL,
            'bundle_key' => NULL,
            'entity_type' => NULL,
            'ignore_case' => FALSE,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
        $form['#prefix'] = '<div id="entity-import-plus-entity-generate">';
        $form['#suffix'] = '</div>';

        $entity_info = $this->getEntityTypeInfo();
        $entity_type_id = $this->getFormStateValue('entity_type', $form_state);

        $form['entity_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Entity Type'),
            '#options' => $entity_info['entities'],
            '#required' => TRUE,
            '#ajax' => [
                'event' => 'change',
                'method' => 'replace',
                'wrapper' => 'entity-import-plus-entity-generate',
                'callback' => [$this, 'ajaxProcessCallback']
            ],
            '#default_value' => $entity_type_id,
        ];

        if (isset($entity_type_id) && !empty($entity_type_id)) {
            $bundle = (array) $this->getFormStateValue('bundle', $form_state);
            $form['bundle'] = [
                '#type' => 'select',
                '#title' => $this->t('Bundle'),
                '#options' => $entity_info['bundles'][$entity_type_id],
                '#required' => TRUE,
                '#multiple' => TRUE,
                '#ajax' => [
                    'event' => 'change',
                    'method' => 'replace',
                    'wrapper' => 'entity-import-plus-entity-generate',
                    'callback' => [$this, 'ajaxProcessCallback']
                ],
                '#default_value' => $bundle,
            ];
            $entity_type = $this->entityTypeManager
                ->getDefinition($entity_type_id);

            $form['bundle_key'] = [
                '#type' => 'value',
                '#value' => $entity_type->getKey('bundle'),
            ];

            if (isset($bundle) && !empty($bundle)) {
                $form['value_key'] = [
                    '#type' => 'select',
                    '#title' => $this->t('Property'),
                    '#options' => $this->getEntityFieldOptions($entity_type_id, $bundle),
                    '#required' => TRUE,
                    '#default_value' => $this->getFormStateValue('value_key', $form_state),
                ];
            }
        }
        $form['ignore_case'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Ignore Case'),
            '#description' => $this->t('If checked then value casing is irrelevant.'),
            '#default_value' => $this->getFormStateValue('ignore_case', $form_state, FALSE)
        ];

        return $form;
    }

    /**
     * Get the entity type information.
     *
     * @return array
     *   An array of the entity type information.
     */
    protected function getEntityTypeInfo() {
        $entity_info = [];

        foreach ($this->entityTypeManager->getDefinitions() as $plugin_id => $definition) {
            $class = $definition->getOriginalClass();

            if (!is_subclass_of($class, 'Drupal\Core\Entity\FieldableEntityInterface')) {
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
     * Get entity type bundle options.
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

    /**
     * Get entity field options.
     *
     * @param $entity_type_id
     *   The entity type identifier.
     * @param array $bundles
     *   An array of entity bundles.
     *
     * @return array
     *   An array of entity fields.
     */
    protected function getEntityFieldOptions($entity_type_id, array $bundles) {
        $options = [];

        $definitions = $this->entityFieldManager
            ->getFieldStorageDefinitions($entity_type_id);

        foreach ($definitions as $name => $definition) {
            if ($definition instanceof FieldDefinitionInterface) {
                $options[$name] = $definition->getLabel();
            }

            if ($definition instanceof FieldStorageConfigInterface) {
                // Verify that the selected bundles are defined in the field definition
                // bundles. The field needs to exist in all bundles for it to show up
                // as an option.
                foreach ($bundles as $bundle) {
                    if (!in_array($bundle, $definition->getBundles())) {
                        continue 2;
                    }
                }
                $options[$name] = $definition->getName();
            }
        }

        return $options;
    }
}
