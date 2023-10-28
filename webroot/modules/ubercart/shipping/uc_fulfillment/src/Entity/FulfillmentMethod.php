<?php

namespace Drupal\uc_fulfillment\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\uc_fulfillment\FulfillmentMethodInterface;

/**
 * Defines a configured fulfillment method.
 *
 * @ConfigEntityType(
 *   id = "uc_fulfillment_method",
 *   label = @Translation("Fulfillment method"),
 *   label_singular = @Translation("fulfillment method"),
 *   label_plural = @Translation("fulfillment methods"),
 *   label_count = @PluralTranslation(
 *     singular = "@count fulfillment method",
 *     plural = "@count fulfillment methods",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\uc_fulfillment\FulfillmentMethodListBuilder",
 *     "form" = {
 *       "default" = "Drupal\uc_fulfillment\Form\FulfillmentMethodForm",
 *       "delete" = "Drupal\uc_fulfillment\Form\FulfillmentMethodDeleteForm"
 *     }
 *   },
 *   config_prefix = "method",
 *   admin_permission = "fulfill orders",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "plugin",
 *     "locked",
 *     "settings"
 *   },
 *   links = {
 *     "edit-form" = "/admin/store/config/fulfillment/method/{uc_fulfillment_method}",
 *     "enable" = "/admin/store/config/fulfillment/method/{uc_fulfillment_method}/enable",
 *     "disable" = "/admin/store/config/fulfillment/method/{uc_fulfillment_method}/disable",
 *     "delete-form" = "/admin/store/config/fulfillment/method/{uc_fulfillment_method}/delete",
 *     "collection" = "/admin/store/config/fulfillment"
 *   }
 * )
 */
class FulfillmentMethod extends ConfigEntityBase implements FulfillmentMethodInterface {

  /**
   * The fulfillment method ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The fulfillment method label.
   *
   * @var string
   */
  protected $label;

  /**
   * The fulfillment method weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The locked status of this payment method.
   *
   * @var bool
   */
  protected $locked = FALSE;

  /**
   * The fulfillment method plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The fulfillment method plugin settings.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * The package type supported by this plugin.
   *
   * @var string
   */
  protected $package_type = 'small_package';

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return $this->locked;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageType() {
    return $this->package_type;
  }

}
