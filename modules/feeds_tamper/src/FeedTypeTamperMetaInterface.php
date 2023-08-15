<?php

namespace Drupal\feeds_tamper;

use Drupal\Core\Plugin\ObjectWithPluginCollectionInterface;

/**
 * Interface for managing tamper plugins for a feed type.
 */
interface FeedTypeTamperMetaInterface extends ObjectWithPluginCollectionInterface {

  /**
   * Returns a specific Tamper plugin.
   *
   * @param string $instance_id
   *   The tamper plugin instance ID.
   *
   * @return \Drupal\tamper\TamperInterface
   *   The tamper plugin instance.
   */
  public function getTamper($instance_id);

  /**
   * Returns the tamper plugin instances for this feed type.
   *
   * @return \Drupal\feeds_tamper\TamperPluginCollection|\Drupal\tamper\TamperInterface[]
   *   The tamper plugin collection.
   */
  public function getTampers();

  /**
   * Returns the tamper plugin instances for this feed type, keyed by source.
   *
   * @return \Drupal\tamper\TamperInterface[][]
   *   An associative array of plugin instances, keyed by source.
   */
  public function getTampersGroupedBySource();

  /**
   * Adds a tamper plugin instance for this feed type.
   *
   * @param array $configuration
   *   An array of tamper configuration.
   *
   * @return string
   *   The tamper plugin instance ID.
   */
  public function addTamper(array $configuration);

  /**
   * Sets the configuration for a tamper plugin instance.
   *
   * @param string $instance_id
   *   The ID of a tamper plugin to set the configuration for.
   * @param array $configuration
   *   The tamper plugin configuration to set.
   *
   * @return $this
   */
  public function setTamperConfig($instance_id, array $configuration);

  /**
   * Removes a tamper plugin instance from this feed type.
   *
   * @param string $instance_id
   *   The ID of a tamper plugin to remove.
   *
   * @return $this
   */
  public function removeTamper($instance_id);

  /**
   * Removes tamper instances whose source was removed from the mapping.
   */
  public function rectifyInstances();

  /**
   * Returns an unique list of sources used in mappings.
   *
   * @return string[]
   *   A list of sources.
   */
  public function getUniqueSourceList();

  /**
   * Returns a definition of sources.
   *
   * @return \Drupal\tamper\SourceDefinitionInterface
   *   A source definition, which can be used by Tamper plugins.
   */
  public function getSourceDefinition();

}
