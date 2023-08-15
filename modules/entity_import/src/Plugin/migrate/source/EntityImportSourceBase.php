<?php

namespace Drupal\entity_import\Plugin\migrate\source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define the entity import source base.
 */
abstract class EntityImportSourceBase extends SourcePluginBase implements EntityImportSourceInterface, ContainerFactoryPluginInterface {

  /**
   * @var bool
   */
  protected $required = FALSE;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $settings;

  /**
   * @var bool
   */
  protected $skipCleanup = FALSE;

  /**
   * @var \Drupal\entity_import\Entity\EntityImporterInterface
   */
  protected $entityImporter;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entityTypeManager = $entity_type_manager;
    $this->settings = $config_factory->get('entity_import.settings');
  }

  /**
   * {@inheritdoc}
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritDoc}
   */
  public function skipCleanup() {
    $this->skipCleanup = TRUE;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function runCleanup() {
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return $this->required;
  }

  /**
   * Set source as required.
   */
  public function setRequired() {
    $this->required = TRUE;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [];

    /** @var \Drupal\entity_import\Entity\EntityImporter $importer */
    $importer = $this->getEntityImporter();

    /** @var \Drupal\entity_import\Entity\EntityImporterFieldMapping $field_mapping */
    foreach ($importer->getFieldMapping() as $field_mapping) {
      $fields[$field_mapping->name()] = $field_mapping->label();
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $unique_ids = [];
    /** @var \Drupal\entity_import\Entity\EntityImporter $importer */
    $importer = $this->getEntityImporter();

    foreach ($importer->getFieldMappingUniqueIdentifiers() as $info) {
      if (!isset($info['reference_type']) || !isset($info['identifier_type'])) {
        continue;
      }
      $reference_type = $info['reference_type'];
      $identifier_type = $info['identifier_type'];

      $identifier_name = $reference_type === 'field_type'
        ? $info['identifier_name']
        : $identifier_type;

      $unique_ids[$identifier_name]['type'] = $identifier_type;

      if (isset($info['identifier_settings'])
        && !empty($info['identifier_settings'])) {
        $settings = json_decode($info['identifier_settings'], TRUE);

        if (isset($settings)) {
          $unique_ids[$identifier_name] + $settings;
        }
      }
    }

    return $unique_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function buildImportForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateImportForm(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitImportForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = array_merge_recursive(
      $this->configuration, $form_state->cleanValues()->getValues()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Get source default configuration.
   *
   * @return array
   *   An array of default configurations.
   */
  protected function defaultConfiguration() {
    return [];
  }

  /**
   * Get migrate source configuration.
   *
   * @return array
   */
  protected function getConfiguration() {
    return $this->configuration + $this->defaultConfiguration();
  }

  /**
   * Get the entity importer instance.
   *
   * @return \Drupal\entity_import\Entity\EntityImporter
   *   The entity importer instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\migrate\MigrateException
   */
  protected function getEntityImporter() {
    if (!$this->entityImporter) {
      $this->entityImporter = $this->entityTypeManager
        ->getStorage('entity_importer')
        ->load($this->getImporterId());
    }

    return $this->entityImporter;
  }

  /**
   * Get importer identifier.
   *
   * @return string
   *   The importer identifier.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function getImporterId() {
    $configuration = $this->getConfiguration();

    if (!isset($configuration['importer_id'])) {
      throw new MigrateException(
        'The importer_id directive in the migrate source is required.'
      );
    }

    return $configuration['importer_id'];
  }
}
