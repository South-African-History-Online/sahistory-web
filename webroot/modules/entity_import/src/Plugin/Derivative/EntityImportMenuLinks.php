<?php

namespace Drupal\entity_import\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity import menu links deriver.
 */
class EntityImportMenuLinks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Define class constructor.
   *
   * @param $base_plugin_id
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static (
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $links = [];

    foreach ($this->getEntityImporterPages() as $entity_type_id => $entity_importer) {
      $links["{$entity_type_id}.import_form"] = [
        'title' => $entity_importer->label(),
        'route_name' => 'entity_import.importer.page.import_form',
        'route_parameters' => [
          'entity_importer' => $entity_type_id
        ],
        'parent' => 'entity_import.content.importers'
      ] + $base_plugin_definition;
    }

    return $links;
  }

  /**
   * Get entity importer pages.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityImporterPages() {
    return $this
      ->entityTypeManager
      ->getStorage('entity_importer')
      ->loadByProperties(['expose_importer' => TRUE]);
  }
}
