<?php

namespace Drupal\entity_import\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ConfigEntityType;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Define entity importer field mapping.
 *
 * @ConfigEntityType(
 *   id = "entity_importer_field_mapping",
 *   label = @Translation("Field Mapping"),
 *   config_prefix = "field_mapping",
 *   admin_permission = "administer entity import",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "name",
 *     "description",
 *     "source",
 *     "processing",
 *     "destination",
 *     "importer_type",
 *     "importer_bundle"
 *   },
 *   handlers = {
 *     "form" = {
 *       "add" = "\Drupal\entity_import\Form\EntityImporterFieldMappingForm",
 *       "edit" = "\Drupal\entity_import\Form\EntityImporterFieldMappingForm",
 *       "delete" = "\Drupal\entity_import\Form\EntityImporterFieldMappingDeleteForm"
 *     },
 *     "list_builder" = "\Drupal\entity_import\Controller\EntityImporterFieldMappingList",
 *     "route_provider" = {
 *       "html" = "\Drupal\entity_import\Entity\Routing\EntityImporterRouteDefault"
 *     }
 *   },
 *   links = {
 *     "collection" = "/admin/config/system/entity-importer/{entity_importer}/field-mapping",
 *     "add-form" =  "/admin/config/system/entity-importer/{entity_importer}/field-mapping/add",
 *     "edit-form" = "/admin/config/system/entity-importer/{entity_importer}/field-mapping/{entity_importer_field_mapping}/edit",
 *     "delete-form" = "/admin/config/system/entity-importer/{entity_importer}/field-mapping/{entity_importer_field_mapping}/delete"
 *   }
 * )
 */
class EntityImporterFieldMapping extends EntityImporterConfigEntityBase implements EntityImporterFieldMappingInterface {

  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $label;

  /**
   * @var string
   */
  public $name;

  /**
   * @var string
   */
  public $source;

  /**
   * @var array
   */
  public $processing = [];

  /**
   * @var string
   */
  public $destination;

  /**
   * @var string
   */
  public $importer_type;

  /**
   * @var string
   */
  public $importer_bundle;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return isset($this->importer_type) && isset($this->importer_bundle) && isset($this->name)
      ? "{$this->importer_type}.{$this->importer_bundle}.{$this->name}"
      : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * {@inheritdoc}
   */
  public function getImporterType() {
    return $this->importer_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getImporterBundle() {
    return $this->importer_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    if ($importer_entity = $this->getImporterEntity()) {
      $importer_entity->onChange();
    }
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if ($importer_entity = $this->getImporterEntity()) {
      $importer_entity->onChange();
    }
    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    if ($entity_importer = $this->getImporterEntity()) {
      $this->addDependency('config', $entity_importer->getConfigDependencyName());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessingPlugins() {
    return isset($this->processing['plugins'])
      ? $this->processing['plugins']
      : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessingConfiguration() {
    return isset($this->processing['configuration'])
      ? $this->processing['configuration']
      : [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasProcessingPlugin($plugin_id) {
    return isset($this->getProcessingPlugins()[$plugin_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function setImporterType($type) {
    $this->importer_type = $type;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getImporterEntity() {
    return $this->loadEntityImporterType($this->importer_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $url_route_parameters = parent::urlRouteParameters($rel);

    if (in_array($rel, ['collection', 'edit-form', 'delete-form', 'config-translation-overview'])) {
      $url_route_parameters['entity_importer'] = $this->getImporterType();
    }

    return $url_route_parameters;
  }

  /**
   * Load entity importer type.
   *
   * @param $entity_type_id
   *   The entity importer identifier.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadEntityImporterType($entity_type_id) {
    return $this->entityTypeManager()
      ->getStorage('entity_importer')
      ->load($entity_type_id);
  }
}
