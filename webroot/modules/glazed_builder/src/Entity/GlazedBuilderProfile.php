<?php

namespace Drupal\glazed_builder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\glazed_builder\GlazedBuilderProfileInterface;

/**
 * Defines the glazed builder profile entity type.
 *
 * @ConfigEntityType(
 *   id = "glazed_builder_profile",
 *   label = @Translation("Glazed Builder Profile"),
 *   handlers = {
 *     "list_builder" = "Drupal\glazed_builder\GlazedBuilderProfileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\glazed_builder\Form\GlazedBuilderProfileForm",
 *       "edit" = "Drupal\glazed_builder\Form\GlazedBuilderProfileForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "glazed_builder_profile",
 *   admin_permission = "administer glazed_builder_profile",
 *   links = {
 *     "collection" = "/admin/config/content/glazed_builder/profile",
 *     "add-form" = "/admin/config/content/glazed_builder/profile/add",
 *     "edit-form" = "/admin/config/content/glazed_builder/profile/{glazed_builder_profile}",
 *     "delete-form" = "/admin/config/content/glazed_builder/profile/{glazed_builder_profile}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight"
 *   }
 * )
 */
class GlazedBuilderProfile extends ConfigEntityBase implements GlazedBuilderProfileInterface {

  /**
   * The profile ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The profile label.
   *
   * @var string
   */
  protected $label;

  /**
   * The profile status.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * Profile weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The profile roles
   *
   * @var string[]
   */
  protected $roles = [];

  /**
   * The profile elements.
   *
   * @var string[]
   */
  protected $elements = [];

  /**
   * The profile blocks.
   *
   * @var string[]
   */
  protected $blocks = [];

  /**
   *  The profile views.
   *
   * @var string[]
   */
  protected $views = [];

  /**
   * The profile buttons (inline mode).
   *
   * @var string[]
   */
  protected $inline_buttons = [];

  /**
   * The profile modal buttons.
   *
   * @var string[]
   */
  protected $modal_buttons = [];

  /**
   * {@inheritdoc}
   */
  public static function loadByRoles(array $roles) {
    $profiles = GlazedBuilderProfile::loadMultiple();
    uasort($profiles, [GlazedBuilderProfile::class, 'sort']);
    foreach ($profiles as $profile) {
      if ($profile->status && array_intersect($profile->get('roles'), $roles)) {
        return $profile;
      }
    }
  }

}
