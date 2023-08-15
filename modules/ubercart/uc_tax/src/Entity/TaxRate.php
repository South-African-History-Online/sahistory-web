<?php

namespace Drupal\uc_tax\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\uc_tax\TaxRateInterface;

/**
 * Defines a tax rate configuration entity.
 *
 * @ConfigEntityType(
 *   id = "uc_tax_rate",
 *   label = @Translation("Tax rate"),
 *   label_singular = @Translation("tax rate"),
 *   label_plural = @Translation("tax rates"),
 *   label_count = @PluralTranslation(
 *     singular = "@count tax rate",
 *     plural = "@count tax rates",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\uc_tax\TaxRateListBuilder",
 *     "form" = {
 *       "default" = "Drupal\uc_tax\Form\TaxRateForm",
 *       "delete" = "Drupal\uc_tax\Form\TaxRateDeleteForm"
 *     }
 *   },
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
 *     "shippable",
 *     "display_include",
 *     "inclusion_text",
 *     "product_types",
 *     "line_item_types",
 *     "plugin",
 *     "settings",
 *   },
 *   config_prefix = "rate",
 *   admin_permission = "administer taxes",
 *   links = {
 *     "edit-form" = "/admin/store/config/tax/{uc_tax_rate}",
 *     "enable" = "/admin/store/config/tax/{uc_tax_rate}/enable",
 *     "disable" = "/admin/store/config/tax/{uc_tax_rate}/disable",
 *     "delete-form" = "/admin/store/config/tax/{uc_tax_rate}/delete",
 *     "clone" = "/admin/store/config/tax/{uc_tax_rate}/clone",
 *     "collection" = "/admin/store/config/tax"
 *   }
 * )
 */
class TaxRate extends ConfigEntityBase implements TaxRateInterface {

  /**
   * The tax rate ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The tax rate label.
   *
   * @var string
   */
  protected $label;

  /**
   * The tax rate.
   *
   * @var float
   */
  protected $rate;

  /**
   * The tax rate weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * Whether to display prices including tax.
   *
   * @var bool
   */
  protected $display_include;

  /**
   * The text to display next to prices if tax is included.
   *
   * @var string
   */
  protected $inclusion_text;

  /**
   * If the tax applies only to shippable products.
   *
   * @var string
   */
  protected $shippable;

  /**
   * Line item types subject to this tax rate.
   *
   * @var string[]
   */
  protected $line_item_types = [];

  /**
   * Product item types subject to this tax rate.
   *
   * @var string[]
   */
  protected $product_types = [];

  /**
   * The tax rate plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The tax rate plugin settings.
   *
   * @var array
   */
  protected $settings = array();

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return \Drupal::service('plugin.manager.uc_tax.rate')->createInstance($this->plugin, $this->settings);
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRate() {
    return $this->rate;
  }

  /**
   * {@inheritdoc}
   */
  public function setRate($rate) {
    $this->rate = $rate;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductTypes() {
    return $this->product_types;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductTypes(array $product_types) {
    $this->product_types = $product_types;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItemTypes() {
    return $this->line_item_types;
  }

  /**
   * {@inheritdoc}
   */
  public function setLineItemTypes(array $line_item_types) {
    $this->line_item_types = $line_item_types;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isIncludedInPrice() {
    return (bool) $this->display_include;
  }

  /**
   * {@inheritdoc}
   */
  public function setIncludedInPrice($included) {
    $this->display_include = (bool) $included;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInclusionText() {
    return $this->inclusion_text;
  }

  /**
   * {@inheritdoc}
   */
  public function setInclusionText($inclusion_text) {
    $this->inclusion_text = $inclusion_text;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isForShippable() {
    return (bool) $this->shippable;
  }

  /**
   * {@inheritdoc}
   */
  public function setForShippable($shippable) {
    $this->shippable = (bool) $shippable;
    return $this;
  }

}
