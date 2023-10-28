<?php

namespace Drupal\entity_import;

use Drupal\migrate\Plugin\MigrationInterface;

interface EntityImportSourceManagerInterface {

  /**
   * Get entity import definition options.
   *
   * @return array
   *   An array of import source definitions.
   */
  public function getDefinitionAsOptions();

  /**
   * Create the migrate source stub instance.
   *
   * @param $plugin_id
   *   The migrate source plugin identifier.
   * @param array $configuration
   *   The migration source configuration.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration object.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function createSourceStubInstance(
    $plugin_id,
    $configuration = [],
    MigrationInterface $migration = NULL
  );
}
