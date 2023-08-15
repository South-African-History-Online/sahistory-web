<?php

namespace Drupal\media_bulk_upload\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Media Bulk Config entity.
 *
 * @ConfigEntityType(
 *   id = "media_bulk_config",
 *   label = @Translation("Media Bulk Config"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\media_bulk_upload\MediaBulkConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\media_bulk_upload\Form\MediaBulkConfigForm",
 *       "edit" = "Drupal\media_bulk_upload\Form\MediaBulkConfigForm",
 *       "delete" = "Drupal\media_bulk_upload\Form\MediaBulkConfigDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\media_bulk_upload\MediaBulkConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "media_bulk_config",
 *   admin_permission = "administer media_bulk_upload configuration",
 *   permission_granularity = "bundle",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/media/media-bulk-config/{media_bulk_config}",
 *     "add-form" = "/admin/config/media/media-bulk-config/add",
 *     "edit-form" = "/admin/config/media/media-bulk-config/{media_bulk_config}/edit",
 *     "delete-form" = "/admin/config/media/media-bulk-config/{media_bulk_config}/delete",
 *     "collection" = "/admin/config/media/media-bulk-config"
 *   }
 * )
 */
class MediaBulkConfig extends ConfigEntityBase implements MediaBulkConfigInterface {

  /**
   * The Media Bulk Config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Media Bulk Config label.
   *
   * @var string
   */
  protected $label;

}
