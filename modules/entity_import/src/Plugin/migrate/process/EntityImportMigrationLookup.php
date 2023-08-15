<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity import migration lookup.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_migrate_lookup",
 *   label = @Translation("Migrate Lookup")
 * )
 */
class EntityImportMigrationLookup extends MigrationLookup implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id, $plugin_definition,
    MigrationInterface $migration,
    $migrate_lookup,
    $migrate_stub = NULL,
    MigrationPluginManagerInterface $migrate_plugin_manager
  ) {
    parent::__construct($configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $migrate_lookup,
      $migrate_stub
    );
    $this->migrationPluginManager = $migrate_plugin_manager;
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
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('migrate.lookup'),
      $container->get('migrate.stub'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * Define default configurations.
   *
   * @return array
   */
  public function defaultConfigurations() {
    return [
      'stub_id' => NULL,
      'no_stub' => FALSE,
      'migration' => [],
      'source_ids' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['#prefix'] = '<div id="entity-import-processing-migration-lookup">';
    $form['#suffix'] = '</div>';

    $form['migration'] = [
      '#type' => 'select',
      '#title' => $this->t('Migration'),
      '#options' => $this->getMigrationOptions(),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#default_value' => $configuration['migration'],
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'entity-import-processing-migration-lookup',
        'callback' => [$this, 'ajaxProcessCallback']
      ]
    ];
    $form['no_stub'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No Stub'),
      '#description' => $this->t('Prevents the creation of a stub entity.'),
      '#default_value' => $configuration['no_stub'],
    ];
    $form['stub_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Stub ID'),
      '#description' => $this->t('Identifies the migration which will be used
        to create any stub entities'),
      '#default_value' => $configuration['stub_id'],
      '#empty_option' => $this->t('- None -'),
      '#options' => $configuration['migration'],
      '#states' => [
        'visible' => [
          ':input[name="processing[configuration][plugins][entity_import_migrate_lookup][settings][no_stub]"]' => ['checked' => FALSE]
        ]
      ]
    ];
    $form['source_ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Source IDs'),
      '#description' => $this->t('Define the source IDs using a JSON format.
        <br/> The source IDs need to be keyed by the migration ID.'),
      '#default_value' => is_array($configuration['source_ids']) && !empty($configuration['source_ids'])
        ? Json::encode($configuration['source_ids'])
        : NULL,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {
    $elements = NestedArray::getValue(
      $form_state->getCompleteForm(), $form['#parents']
    );

    if ($source_ids = $form_state->getValue('source_ids', [])) {
      $source_ids = Json::decode($source_ids);

      if ($source_ids === NULL) {
        $form_state->setError(
          $elements['source_ids'],
          $this->t('The migration lookup source IDs JSON syntax is invalid!')
        );
      }
      else {
        if ($migration = $form_state->getValue('migration')) {
          $migration_keys = array_values($migration);

          foreach (array_keys($source_ids) as $migration_id) {
            if (!in_array($migration_id, $migration_keys)) {
              $form_state->setError(
                $elements['source_ids'],
                $this->t('The migration ID "@migration_id" was not defined in
                  the migration dropdown.', ['@migration_id' => $migration_id])
              );
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(
    array &$form,
    FormStateInterface $form_state
  ) {
    $this->configuration['source_ids'] = Json::decode(
      $form_state->getValue('source_ids', [])
    );
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
      $options[$plugin_id] = $definition['label'];
    }

    return $options;
  }
}
