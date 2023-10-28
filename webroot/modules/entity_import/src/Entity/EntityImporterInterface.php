<?php

namespace Drupal\entity_import\Entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * Define the entity importer interface.
 */
interface EntityImporterInterface extends EntityInterface {

  /**
   * On change to the importer.
   */
  public function onChange();

  /**
   * Get entity importer description.
   *
   * @return mixed
   *   The entity importer description.
   */
  public function getDescription();

  /**
   * Get entity importer display page.
   *
   * @return bool
   *   The entity importer display page boolean.
   */
  public function exposeImporter();

  /**
   * Get entity importer source information.
   *
   * @return array
   *   An array of source information.
   */
  public function getMigrationSource();

  /**
   * Get entity importer entity information.
   *
   * @return array
   *   An array of entity information.
   */
  public function getMigrationEntity();

  /**
   * Create a entity importer url.
   *
   * @param $route_name
   *   The URL route name.
   *
   * @return \Drupal\Core\Url
   *   The drupal URL instance.
   */
  public function createUrl($route_name);

  /**
   * Create a entity importer link.
   *
   * @param $text
   *   The link text.
   * @param $route_name
   *   The link route name.
   *
   * @return \Drupal\Core\Link
   *   The drupal link instance.
   */
  public function createLink($text, $route_name);

  /**
   * Get the migration plugin id.
   *
   * @param $bundle
   *   The bundle on which to retrieve.
   *
   * @return string
   *   The migration plugin ID.
   */
  public function getMigrationPluginId($bundle);

  /**
   * Get importer source plugin id.
   *
   * @return mixed|NULL
   *   The importer source plugin id.
   */
  public function getImporterSourcePluginId();

  /**
   * Get importer source configuration.
   *
   * @return array
   *   The importer source plugin configuration.
   */
  public function getImporterSourceConfiguration();

  /**
   * Get importer entity bundles.
   *
   * @return array
   *   An array of allowed entity bundles.
   */
  public function getImporterBundles();

  /**
   * Get importer entity type.
   *
   * @return mixed|NULL
   *   The importer entity type.
   */
  public function getImporterEntityType();

  /**
   * Get importer migration dependencies.
   *
   * @return array
   *   The importer migration dependencies.
   */
  public function getMigrationDependencies();

  /**
   * Has importer page display changed.
   *
   * @return boolean
   *   Determine if the page display has changed.
   */
  public function hasPageDisplayChanged();

  /**
   * Create migration instance.
   *
   * @param $bundle
   *   The entity bundle name.
   * @param array $definition
   *   Additional definitions that should be merged.
   *
   * @return \Drupal\migrate\Plugin\Migration|\Drupal\migrate\Plugin\MigrationPluginManager
   */
  public function createMigrationInstance($bundle, array $definition = []);

  /**
   * Get entity importer first bundle.
   *
   * @return string
   */
  public function getFirstBundle();

  /**
   * Entity importer has multiple bundles.
   *
   * @return bool
   */
  public function hasMultipleBundles();

  /**
   * Get entity importer field mapping.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFieldMapping();

  /**
   * Has field mappings.
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function hasFieldMappings();

  /**
   * Get field mapping options.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getFieldMappingOptions();

  /**
   * Get missing unique identifiers.
   *
   * @param array $identifiers
   *   An array of identifiers to check against.
   *
   * @return array
   *   An array of missing unique identifiers.
   */
  public function getMissingUniqueIdentifiers(array $identifiers);

  /**
   * Has field mapping unique identifiers.
   *
   * @return bool
   *   Return TRUE if field mapping have unique identifiers defined.
   */
  public function hasFieldMappingUniqueIdentifiers();

  /**
   * Get field mapping unique identifiers.
   *
   * @return array
   *   An array of field mapping unique identifiers.
   */
  public function getFieldMappingUniqueIdentifiers();

  /**
   * Get dependency migrations.
   *
   * @param $bundle
   *   The entity bundle.
   * @param bool $order
   *   Order the migrations.
   * @param array $definition
   *   An array of migration definition values.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getDependencyMigrations($bundle, $order = TRUE, $definition = []);

  /**
   * Load dependency migration by plugin id.
   *
   * @param $plugin_id
   *   The migration plugin id.
   * @param $bundle
   *   The bundle associated with the required migration.
   *
   * @return MigrationInterface|null
   *   Return the dependency migration instance; otherwise NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function loadDependencyMigration($plugin_id, $bundle);
}
