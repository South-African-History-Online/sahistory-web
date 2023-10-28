<?php

namespace Drupal\feeds_tamper;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\tamper\SourceDefinition;
use Drupal\tamper\TamperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for managing tamper plugins for a feed type.
 */
class FeedTypeTamperMeta implements FeedTypeTamperMetaInterface {

  /**
   * The Uuid generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * The Tamper plugin manager.
   *
   * @var \Drupal\tamper\TamperManagerInterface
   */
  protected $tamperManager;

  /**
   * The feed type to manage tamper plugins for.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * Holds the collection of tampers that are used by the feed type.
   *
   * @var \Drupal\feeds_tamper\TamperPluginCollection
   */
  protected $tamperCollection;

  /**
   * Constructs a new FeedTypeTamperMeta object.
   *
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The Uuid generator.
   * @param \Drupal\tamper\TamperManagerInterface $tamper_manager
   *   The Tamper plugin manager.
   * @param \Drupal\feeds\FeedTypeInterface $feed_type
   *   The feed type to manage tamper plugins for.
   */
  public function __construct(UuidInterface $uuid_generator, TamperManagerInterface $tamper_manager, FeedTypeInterface $feed_type) {
    $this->uuidGenerator = $uuid_generator;
    $this->tamperManager = $tamper_manager;
    $this->feedType = $feed_type;
  }

  /**
   * Creates a new instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\feeds\FeedTypeInterface $feed_type
   *   The feed type to manage tamper plugins for.
   *
   * @return static
   *   A new FeedTypeTamperMeta instance.
   */
  public static function create(ContainerInterface $container, FeedTypeInterface $feed_type) {
    return new static(
      $container->get('uuid'),
      $container->get('plugin.manager.tamper'),
      $feed_type
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTamper($instance_id) {
    return $this->getTampers()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getTampers() {
    if (!isset($this->tamperCollection)) {
      $tampers = $this->feedType->getThirdPartySetting('feeds_tamper', 'tampers');
      $tampers = empty($tampers) ? [] : $tampers;
      $this->tamperCollection = new TamperPluginCollection($this->tamperManager, $this->getSourceDefinition(), $tampers);
      $this->tamperCollection->sort();
    }
    return $this->tamperCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getTampersGroupedBySource() {
    $grouped_tampers = [];
    $this->getTampers()->sort();
    foreach ($this->getTampers() as $id => $tamper) {
      $grouped_tampers[(string) $tamper->getSetting('source')][$id] = $tamper;
    }
    return $grouped_tampers;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['tampers' => $this->getTampers()];
  }

  /**
   * {@inheritdoc}
   */
  public function addTamper(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator->generate();
    $configuration['source_definition'] = $this->getSourceDefinition();
    $this->getTampers()->addInstanceId($configuration['uuid'], $configuration);
    $this->updateFeedType();
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function setTamperConfig($instance_id, array $configuration) {
    $configuration['uuid'] = $instance_id;
    $this->getTampers()->setInstanceConfiguration($instance_id, $configuration);
    $this->updateFeedType();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeTamper($instance_id) {
    $this->getTampers()->removeInstanceId($instance_id);
    $this->updateFeedType();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function rectifyInstances() {
    // Check the difference between the tampers grouped by source and a list of
    // all sources used in mapping. By diffing we keep an array of tampers
    // belonging to a source that is no longer used in the mapping.
    $tampers_by_source_to_remove = array_diff_key($this->getTampersGroupedBySource(), $this->getUniqueSourceList());

    // Remove these tamper instances.
    foreach ($tampers_by_source_to_remove as $tampers) {
      foreach ($tampers as $uuid => $tamper) {
        $this->removeTamper($uuid);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueSourceList() {
    // Extract used sources from mappings.
    $sources = [];
    foreach ($this->feedType->getMappings() as $mapping) {
      foreach ($mapping['map'] as $column => $source) {
        if ($source == '') {
          continue;
        }
        $sources[$source] = $source;
      }
    }

    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceDefinition() {
    $source_list = [];
    foreach ($this->feedType->getMappingSources() as $key => $source) {
      $source_list[$key] = $key;
    }

    return new SourceDefinition($source_list);
  }

  /**
   * Writes tampers back on the feed type.
   */
  protected function updateFeedType() {
    $this->getTampers()->sort();
    foreach ($this->getPluginCollections() as $plugin_config_key => $plugin_collection) {
      $this->feedType->setThirdPartySetting('feeds_tamper', $plugin_config_key, $plugin_collection->getConfiguration());
    }
  }

}
