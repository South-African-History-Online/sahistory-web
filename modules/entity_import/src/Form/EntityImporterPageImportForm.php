<?php /** @noinspection PhpUndefinedMethodInspection */

namespace Drupal\entity_import\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\entity_import\Entity\EntityImporterInterface;
use Drupal\entity_import\Plugin\migrate\source\EntityImportSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity importer content form.
 */
class EntityImporterPageImportForm extends EntityImporterBundleFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * Entity importer page import form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MigrationPluginManagerInterface $migration_plugin_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_import.page.content';
  }

  /**
   * Set the form title.
   *
   * @param \Drupal\entity_import\Entity\EntityImporterInterface|null $entity_importer
   *   The entity importer instance.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function setTitle(EntityImporterInterface $entity_importer = NULL) {
    return $this->t('@label: Import', [
      '@label' => $entity_importer->label()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    EntityImporterInterface $entity_importer = NULL
  ) {
    $form = parent::buildForm($form, $form_state, $entity_importer);

    $bundle = $this->getBundle();

    if (!isset($bundle) || empty($bundle)) {
      return $form;
    }
    $plugin_id = $entity_importer->getMigrationPluginId($bundle);

    /** @var MigrationInterface $migration */
    $migration = $this->migrationPluginManager->createInstance($plugin_id);

    if (!$migration) {
      throw new \RuntimeException(
        'The entity importer page migration is required.'
      );
    }
    $parents = ['migrations'];

    $migrations = $this->buildMigrationExecuteOrder(
      $migration, $form_state, $parents
    );

    $form['migrations'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#parents' => $parents
    ];

    $this->buildMigrationForm(
      $migration,
      $migrations,
      $form['migrations'],
      $form_state,
      $parents
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Import')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->entityImporter->hasFieldMappingUniqueIdentifiers()) {
      $form_state->setError(
        $form,
        $this->t('At least one unique identifier needs to be defined.')
      );
    }
    $parents = ['migrations'];

    if ($migrations = $form_state->getValue('migrations')) {
      foreach ($migrations as $migration_key => $configuration) {
        $migration_parents = explode('::', $migration_key);
        $migration_id = end($migration_parents);

        $migration = $this->migrationPluginManager->createInstance($migration_id);

        if (!$migration instanceof MigrationInterface) {
          continue;
        }
        $source = $migration->getSourcePlugin();

        if ($source instanceof EntityImportSourceInterface) {
          $subform = ['#parents' => array_merge($parents, [$migration_key, 'configuration'])];
          $source->validateImportForm(
            $subform,
            SubformState::createForSubform($subform, $form, $form_state)
          );
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = $this->buildMigrationBatchOperations(
      $form, $form_state, ['migrations']
    );
    $this->setImporterBatchProcess($operations, $form_state, [
      'title' => $this->t('Entity Importer')
    ]);
  }

  /**
   * Build migration batch operations.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param array $parents
   *   An array of parents.
   *
   * @return array
   *   An array of migration operations.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function buildMigrationBatchOperations(array $form, FormStateInterface $form_state, array $parents = []) {
    $operations = [];

    if ($migrations = $form_state->getValue('migrations')) {
      foreach ($migrations as $migration_key => $configuration) {
        $migration_parents = explode('::', $migration_key);
        $migration_id = end($migration_parents);

        /** @var \Drupal\migrate\Plugin\Migration $migration */
        $migration = $this->migrationPluginManager->createInstance($migration_id);

        if (!$migration instanceof MigrationInterface) {
          continue;
        }
        $source = $migration->getSourcePlugin();

        if ($source instanceof EntityImportSourceInterface) {
          $subform = ['#parents' => array_merge($parents, [$migration_key, 'configuration'])];
          $source->submitImportForm(
            $subform,
            SubformState::createForSubform($subform, $form, $form_state)
          );

          if (!$source->isValid()) {
            continue;
          }
        }

        $operations[] = [
          '\Drupal\entity_import\Form\EntityImporterBatchProcess::import',
          [$migration, $configuration['update'], MigrationInterface::STATUS_IDLE]
        ];
      }
    }

    return $operations;
  }

  /**
   * Build migration form elements.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $base_migration
   * @param array $migrations
   *   An array of migrations.
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param array $parents
   *   An array of parents.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildMigrationForm(
    MigrationInterface $base_migration,
    array $migrations,
    array &$form,
    FormStateInterface $form_state,
    array $parents = []
  ) {
    foreach ($migrations as $migration_key => $info) {
      if (!isset($info['migration'])
        || !$info['migration'] instanceof MigrationInterface) {
        continue;
      }
      /** @var \Drupal\migrate\Plugin\Migration $migration */
      $migration = $info['migration'];

      $id_map = $migration->getIdMap();
      $plugin_id = $migration->id();
      $processed_count = $id_map->processedCount();

      $form[$migration_key] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#tree' => TRUE,
        '#title' => $this->t('@label', [
          '@label' => implode(
            ' > ',
            array_merge($info['parents']['labels'] ?? [], [$info['label']])
          ),
        ]),
        '#open' => $base_migration->id() === $plugin_id || $processed_count === 0
      ];
      $plugin_parents = array_merge($parents, [$migration_key]);

      /** @var \Drupal\entity_import\Entity\EntityImporter $entity_importer */
      $entity_importer = $this->loadEntityImporterByMigration($migration);

      // Display the entity importer description if one is defined.
      if (($entity_importer instanceof EntityImporterInterface)
        && $description = $entity_importer->getDescription()) {
        $form[$migration_key]['#description'] = [
          '#type' => 'processed_text',
          '#text' => $description,
          '#format' => 'basic_html',
        ];
      }

      /** @var \Drupal\migrate\Plugin\MigrateSourceInterface $source */
      $source = $migration->getSourcePlugin();

      if ($source instanceof EntityImportSourceInterface) {
        if ($processed_count === 0 && $base_migration->id() !== $plugin_id) {
          $source->setRequired();
        }
        $subform = ['#parents' => array_merge($plugin_parents, ['configuration'])];
        $form[$migration_key]['configuration'] = $source
          ->buildImportForm(
            $subform,
            SubformState::createForSubform($subform, $form, $form_state)
          );
      }
      $settings = $this->getFormStateValue($plugin_parents, $form_state);

      $form[$migration_key]['update'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Update'),
        '#description' => $this->t('Update entity content from source.'),
        '#default_value' => $settings['update'] ?? FALSE,
      ];
    }
  }

  /**
   * Build migration execute order.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The required migration object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param array $parents
   *   An array of parents.
   *
   * @return array
   *   Build migration execute order based on dependencies.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function buildMigrationExecuteOrder(
    MigrationInterface $migration,
    FormStateInterface $form_state,
    array $parents = []
  ) {
    $migrations = $this->buildMigrationDependencyInfo(
      $migration, $form_state, $parents
    );

    return array_reverse($migrations);
  }

  /**
   * Build the migration dependency information.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The required migration object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param array $plugin_parents
   *   An array of the plugin parents.
   * @param array $migration_parents
   *   An array of the migration parent ids.
   * @param array $info
   *   An array of the migration dependency info.
   *     - label: The migration label.
   *     - parents: An array of the migration parent hierarchy.
   *     - migration: The current migration instance.
   *
   * @return array
   *   An array of optional dependency migrations.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function buildMigrationDependencyInfo(
    MigrationInterface $migration,
    FormStateInterface $form_state,
    array $plugin_parents = [],
    array $migration_parents = [],
    array &$info = []
  ): array {
    $payload = [
      'label' => $migration->label(),
      'parents' => $migration_parents,
      'migration' => $migration,
    ];
    $migration_parents['ids'][] = $migration->id();
    $migration_parents['labels'][] = $migration->label();

    $info[implode('::', $migration_parents['ids'])] = $payload;

    foreach ($migration->getMigrationDependencies()['optional'] as $plugin_id) {
      $plugin_parents = array_merge($plugin_parents, [$plugin_id, 'configuration']);

      $source = $this->getFormStateValue(
        $plugin_parents, $form_state, []
      );

      /** @var MigrationInterface $instance */
      $instance = $this
        ->migrationPluginManager
        ->createInstance($plugin_id, ['source' => $source]);

      $this->buildMigrationDependencyInfo($instance, $form_state, $plugin_parents, $migration_parents, $info);
    }

    return $info;
  }

  /**
   * Load entity importer.
   *
   * @param $importer_id
   *   The entity importer identifier.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadEntityImporter($importer_id) {
    return $this
      ->entityTypeManager
      ->getStorage('entity_importer')
      ->load($importer_id);
  }

  /**
   * Load entity importer by migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration instance.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadEntityImporterByMigration(MigrationInterface $migration) {
    $identifier = $migration->getDerivativeId();

    $importer_id = substr(
      $identifier, 0, strpos($identifier, ':')
    );

    return $this->loadEntityImporter($importer_id);
  }
}
