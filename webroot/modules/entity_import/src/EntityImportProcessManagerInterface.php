<?php

namespace Drupal\entity_import;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Define entity import process manager interface.
 */
interface EntityImportProcessManagerInterface {

  /**
   * Get entity import migration process information.
   *
   * @return array
   *   An array of migration process information.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getMigrationProcessInfo();

  /**
   * Create the migrate process instance.
   *
   * @param $plugin_id
   *   The plugin instance identifier.
   * @param array $configuration
   *   An array of a plugin configurations.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration instance.
   *
   * @return object
   *   The plugin instance.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function createPluginInstance(
    $plugin_id,
    $configuration = [],
    MigrationInterface $migration = NULL
  );
}
