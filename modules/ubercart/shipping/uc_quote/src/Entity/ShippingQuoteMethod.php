<?php

namespace Drupal\uc_quote\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\uc_quote\ShippingQuoteMethodInterface;

/**
 * Defines the shipping quote method configuration entity.
 *
 * @ConfigEntityType(
 *   id = "uc_quote_method",
 *   label = @Translation("Shipping quote"),
 *   label_singular = @Translation("shipping quote"),
 *   label_plural = @Translation("shipping quotes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count shipping quote",
 *     plural = "@count shipping quotes",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\uc_quote\ShippingQuoteMethodListBuilder",
 *     "form" = {
 *       "default" = "Drupal\uc_quote\Form\ShippingQuoteMethodForm",
 *       "delete" = "Drupal\uc_quote\Form\ShippingQuoteMethodDeleteForm"
 *     }
 *   },
 *   config_prefix = "method",
 *   admin_permission = "configure quotes",
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
 *     "settings",
 *   },
 *   links = {
 *     "edit-form" = "/admin/store/config/quotes/{uc_quote_method}",
 *     "enable" = "/admin/store/config/quotes/{uc_quote_method}/enable",
 *     "disable" = "/admin/store/config/quotes/{uc_quote_method}/disable",
 *     "delete-form" = "/admin/store/config/quotes/{uc_quote_method}/delete",
 *     "collection" = "/admin/store/config/quotes"
 *   }
 * )
 */
class ShippingQuoteMethod extends ConfigEntityBase implements ShippingQuoteMethodInterface {

  /**
   * The shipping quote method ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The shipping quote method label.
   *
   * @var string
   */
  protected $label;

  /**
   * The shipping quote method weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The plugin instance ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin instance settings.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
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
  public function getDisplayLabel() {
    $build = $this->getPlugin()->getDisplayLabel($this->label());
    return \Drupal::service('renderer')->renderPlain($build);
  }

}
