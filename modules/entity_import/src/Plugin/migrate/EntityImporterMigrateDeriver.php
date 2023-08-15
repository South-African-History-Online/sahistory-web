<?php

namespace Drupal\entity_import\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity importer migrate deriver.
 */
class EntityImporterMigrateDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new class instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the fetcher.
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   *
   * @return static
   *   Returns an instance of this fetcher.
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Entity importer migrate deriver constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $importers = $this->entityTypeManager->getStorage('entity_importer')->loadMultiple();

    /** @var \Drupal\entity_import\Entity\EntityImporter $importer */
    foreach ($importers as $importer_id => $importer) {
      foreach ($importer->getImporterBundles() as $bundle) {
        /** @var \Drupal\migrate\Plugin\Migration $migration */
        $migration = $importer->createMigrationInstance($bundle);
        $this->derivatives["{$importer_id}:{$bundle}"] = $migration->getPluginDefinition();
      }
    }

    return $this->derivatives;
  }
}
