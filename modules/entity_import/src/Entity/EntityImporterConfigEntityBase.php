<?php

namespace Drupal\entity_import\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Define the entity import config entity base class.
 */
abstract class EntityImporterConfigEntityBase extends ConfigEntityBase {

  use StringTranslationTrait;

  /**
   * Determine if configuration exist already.
   *
   * @param $id
   * @param array $element
   *
   * @return array|int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function entityExist($id, array $element) {
    $identifier = isset($element['#prefix_id'])
      ? "{$element['#prefix_id']}.{$id}"
      : $id;

    return $this->checkIfEntityIdExist($identifier);
  }

  /**
   * Check if entity identifier exist.
   *
   * @param $identifier
   *
   * @return array|int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkIfEntityIdExist($identifier) {
    return (bool) $this->getQuery()->condition('id', $identifier)->execute();
  }

  /**
   * Get entity storage query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getQuery() {
    return $this->getStorage()->getQuery();
  }

  /**
   * Get entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function getStorage() {
    return $this->entityTypeManager()
      ->getStorage($this->getEntityTypeId());
  }
}
