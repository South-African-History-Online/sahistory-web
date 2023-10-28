<?php


namespace Drupal\entity_import\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Define entity import migration stub event.
 */
class EntityImportMigrationStubEvent extends Event {

  /**
   * @var string
   */
  protected $pluginId;

  /**
   * @var array
   */
  protected $configuration = [];

  /**
   * Entity import migration stub event constructor.
   *
   * @param $plugin_id
   *   The migration plugin identifier.
   * @param array $configuration
   *   The migration existing configuration.
   */
  public function __construct($plugin_id, array $configuration = []) {
    $this->pluginId = $plugin_id;
    $this->configuration = $configuration;
  }

  /**
   * Get migration plugin identifier.
   *
   * @return string
   *   The migration plugin id.
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * Get migration plugin configuration.
   *
   * @return array
   *   An array of migration configuration.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Set a configuration value.
   *
   * @param $name
   *   The configuration name directive.
   * @param $value
   *   The configuration value.
   *
   * @return \Drupal\entity_import\Event\EntityImportMigrationStubEvent
   */
  public function setConfigurationValue($name, $value) {
    if (is_array($value)
      && isset($this->configuration[$name])
      && is_array($this->configuration[$name])) {
      $this->configuration[$name] = array_merge_recursive(
        $this->configuration[$name], $value
      );
    }
    else {
      $this->configuration[$name] = $value;
    }

    return $this;
  }
}
