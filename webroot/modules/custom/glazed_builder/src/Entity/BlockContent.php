<?php

namespace Drupal\glazed_builder\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\block_content\Entity\BlockContent as BlockContentEntity;

class BlockContent extends BlockContentEntity {

  /**
   * Add an empty string to any field that would otherwise be completely empty.
   * Without this code, the frontend editor has nothing to attach to.
   *
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::presave($storage);

    \Drupal::service('glazed_builder.service')->setEmptyStringToGlazedFieldsOnEntity($this);
  }
}
