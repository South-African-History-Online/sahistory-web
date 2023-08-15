<?php

namespace Drupal\entity_import\Entity;

/**
 * Define entity importer field mapping interface.
 */
interface EntityImporterFieldMappingInterface {

  /**
   * Get field source name.
   *
   * @return string
   *   The field mapping machine source name.
   */
  public function name();

  /**
   * Get field source.
   *
   * @return string
   *   The field mapping source.
   */
  public function getSource();

  /**
   * Get field destination.
   *
   * @return string
   *   The field mapping destination.
   */
  public function getDestination();

  /**
   * Get importer type.
   *
   * @return string
   *   The field mapping importer type.
   */
  public function getImporterType();

  /**
   * Get importer bundle.
   *
   * @return string
   *   The field mapping importer bundle.
   */
  public function getImporterBundle();

  /**
   * Get importer processing plugins.
   *
   * @return array
   *   The field mapping processing plugins.
   */
  public function getProcessingPlugins();

  /**
   * Get importer processing configuration
   * .
   * @return array
   *   The field mapping processing configuration.
   */
  public function getProcessingConfiguration();

  /**
   * Has processing plugin.
   *
   * @param $plugin_id
   *   The processing plugin identifier.
   *
   * @return bool
   *   Determine if the process exist.
   */
  public function hasProcessingPlugin($plugin_id);

  /**
   * Set importer type.
   *
   * @param $type
   *   The importer type.
   *
   * @return $this
   */
  public function setImporterType($type);

  /**
   * Get importer entity instance.
   *
   * @return \Drupal\entity_import\Entity\EntityImporter
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getImporterEntity();
}
