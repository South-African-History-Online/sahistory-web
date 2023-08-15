<?php

namespace Drupal\tamper;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base class to tamper data from.
 */
abstract class TamperBase extends PluginBase implements TamperInterface {

  /**
   * The source definition.
   *
   * @var \Drupal\tamper\SourceDefinitionInterface
   */
  protected $sourceDefinition;

  /**
   * Constructs a TamperBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\tamper\SourceDefinitionInterface $source_definition
   *   A definition of which sources there are that Tamper plugins can use.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SourceDefinitionInterface $source_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->sourceDefinition = $source_definition;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    return isset($this->configuration[$key]) ? $this->configuration[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Ignore source definition from configuration as that shouldn't be stored
    // on config files.
    unset($configuration['source_definition']);

    // Merge with default configuration.
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
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

}
