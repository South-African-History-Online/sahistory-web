<?php

namespace Drupal\entity_import\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Define entity importer list builder.
 */
class EntityImporterList extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Label'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    return [
      'label' => $entity->label(),
    ] + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['field_mapping'] = [
      'title' => $this->t('Field Mapping'),
      'url' => Url::fromRoute('entity.entity_importer_field_mapping.collection', [
        'entity_importer' => $entity->id()
      ]),
      'weight' => 10
    ];

    return $operations;
  }
}
