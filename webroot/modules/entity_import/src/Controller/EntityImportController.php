<?php

namespace Drupal\entity_import\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Define the entity import controller.
 */
class EntityImportController extends ControllerBase {

  /**
   * Render a list of importers.
   */
  public function importerPages() {
    $build = [
      '#theme' => 'entity_import_list',
    ];
    $content = [];

    foreach ($this->getDisplayPageEntityImporters() as $identifier => $importer) {
      $content[$identifier] = $importer;
    }
    $build['#content'] = $content;

    return $build;
  }

  /**
   * Get display page entity importers.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getDisplayPageEntityImporters() {
    return $this
      ->entityTypeManager()
      ->getStorage('entity_importer')
      ->loadByProperties(['expose_importer' => TRUE]);
  }
}
