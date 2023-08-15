<?php

namespace Drupal\search_api_sort_priority\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\node\NodeInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\statistics\StatisticsViewsResult;

/**
 * Adds customized sort priority by Statistics.
 *
 * @SearchApiProcessor(
 *   id = "statistics",
 *   label = @Translation("Sort Priority by Statistics"),
 *   description = @Translation("Sort Priority by Statistics."),
 *   stages = {
 *     "add_properties" = 20,
 *     "pre_index_save" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class Statistics extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  protected $targetFieldId = 'statistics_weight';

  /**
   * Can only be enabled for an index that indexes user related entity.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'node') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        // TODO Come up with better label.
        'label' => $this->t('Sort Priority by Statistics'),
        // TODO Come up with better description.
        'description' => $this->t('Sort Priority by Statistics.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
        // This will be a hidden field,
        // not something a user can add/remove manually.
        'hidden' => TRUE,
      ];
      $properties[$this->targetFieldId] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Only run for node and comment items.
    $entity_type_id = $item->getDatasource()->getEntityTypeId();
    if (!in_array($entity_type_id, $this->configuration['allowed_entity_types'])) {
      return;
    }

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, $this->targetFieldId);

    switch ($entity_type_id) {
      case 'node':
        // Get the node object.
        $node = $this->getNode($item->getOriginalObject());

        // Get statistics for this node.
        $nodeStatistics = $this->statisticsGet($node->id());

        // Set the weight on all the configured fields.
        foreach ($fields as $field) {
          $field->addValue($nodeStatistics['totalcount']);
        }
        break;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'weight' => 0,
      'totalcount' => 0,
      'daycount' => 0,
      'timestamp' => 0,
      'allowed_entity_types' => [
        'node',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    // Automatically add field to index if processor is enabled.
    $field = $this->ensureField(NULL, $this->targetFieldId, 'integer');
    // Hide the field.
    $field->setHidden();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Retrieves a node's "view statistics".
   *
   * See statistics.module.
   */
  public function statisticsGet($id) {
    if ($id > 0) {
      /** @var \Drupal\statistics\StatisticsViewsResult $statistics */
      $statistics = \Drupal::service('statistics.storage.node')->fetchView($id);

      // For backwards compatibility, return FALSE if an invalid node ID was
      // passed in.
      if (!($statistics instanceof StatisticsViewsResult)) {
        return [
          'totalcount' => $this->configuration['totalcount'],
          'daycount' => $this->configuration['daycount'],
          'timestamp' => $this->configuration['timestamp'],
        ];
      }

      return [
        'totalcount' => $statistics->getTotalCount(),
        'daycount' => $statistics->getDayCount(),
        'timestamp' => $statistics->getTimestamp(),
      ];
    }
  }

  /**
   * Retrieves the node related to an indexed search object.
   *
   * Will be either the node itself, or the node the comment is attached to.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   A search object that is being indexed.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node related to that search object.
   */
  protected function getNode(ComplexDataInterface $item) {
    $item = $item->getValue();
    if ($item instanceof NodeInterface) {
      return $item;
    }

    return NULL;
  }

}
