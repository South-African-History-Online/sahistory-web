<?php

namespace Drupal\media_bulk_upload;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media_bulk_upload\Entity\MediaBulkConfig;

/**
 * Provides dynamic permissions for nodes of different types.
 */
class MediaBulkConfigPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The node type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function mediaBulkConfigPermissions() {
    $perms = [];
    // Generate node permissions for all node types.
    foreach (MediaBulkConfig::loadMultiple() as $mediaBulkConfig) {
      $perms += $this->buildPermissions($mediaBulkConfig);
    }

    return $perms;
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(MediaBulkConfig $mediaBulkConfig) {
    $mediaBulkConfigId = $mediaBulkConfig->id();
    $type_params = ['%type_name' => $mediaBulkConfig->label()];

    return [
      "use $mediaBulkConfigId bulk upload form" => [
        'title' => $this->t('%type_name : Use upload form', $type_params),
      ],
    ];
  }

}
