<?php

namespace Drupal\entity_import\Routing\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\Routing\Route;

class MigrationConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The migration param converter.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   */
  public function __construct(
    MigrationPluginManagerInterface $migration_plugin_manager
  ) {
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (isset($value) && !empty($value)) {
      return $this->migrationPluginManager->createInstance($value);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'migration');
  }
}
