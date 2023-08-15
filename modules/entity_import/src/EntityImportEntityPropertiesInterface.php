<?php

namespace Drupal\entity_import;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Define the entity properties interface.
 */
interface EntityImportEntityPropertiesInterface {

  /**
   * Get the entity properties options.
   *
   * Entity properties options are provided based on if the entity type is an
   * interface of \Drupal\Core\Config\Entity\ConfigEntityTypeInterface or
   * \Drupal\Core\Entity\ContentEntityTypeInterface.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type instance.
   * @param $bundle
   *   The bundle type identifier.
   *
   * @return array
   *   An array of entity properties.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntityPropertiesOptions(EntityTypeInterface $entity_type, $bundle);
}
