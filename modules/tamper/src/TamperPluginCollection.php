<?php

namespace Drupal\tamper;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of tamper plugins.
 */
class TamperPluginCollection extends DefaultLazyPluginCollection {

  /**
   * A definition of which sources there are that Tamper plugins can use.
   *
   * @var \Drupal\tamper\SourceDefinitionInterface
   */
  protected $sourceDefinition;

  /**
   * Constructs a new TamperPluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param \Drupal\tamper\SourceDefinitionInterface $source_definition
   *   A definition of which sources there are that Tamper plugins can use.
   * @param array $configurations
   *   (optional) An associative array containing the initial configuration for
   *   each plugin in the collection, keyed by plugin instance ID.
   */
  public function __construct(PluginManagerInterface $manager, SourceDefinitionInterface $source_definition, array $configurations = []) {
    $this->sourceDefinition = $source_definition;
    parent::__construct($manager, $configurations);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $configuration = isset($this->configurations[$instance_id]) ? $this->configurations[$instance_id] : [];
    if (!isset($configuration[$this->pluginKey])) {
      throw new PluginNotFoundException($instance_id);
    }

    // Pass source definition.
    $configuration['source_definition'] = $this->sourceDefinition;
    $this->set($instance_id, $this->manager->createInstance($configuration[$this->pluginKey], $configuration));
  }

}
