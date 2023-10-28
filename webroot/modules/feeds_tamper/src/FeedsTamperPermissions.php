<?php

namespace Drupal\feeds_tamper;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\feeds\Entity\FeedType;

/**
 * Defines a class containing permission callbacks.
 */
class FeedsTamperPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of feeds tamper permissions.
   *
   * @return array
   *   An array of permissions.
   */
  public function feedTypeTamperPermissions() {
    $perms = [];
    foreach (FeedType::loadMultiple() as $type) {
      $args = ['%name' => $type->label()];
      /** @var \Drupal\feeds\Entity\FeedType $type */
      $perms['tamper ' . $type->id()] = [
        'title' => $this->t('Tamper %name feed type', $args),
        'description' => $this->t('Create, edit and delete plugins for %name feed type', $args),
      ];
    }

    return $perms;
  }

}
