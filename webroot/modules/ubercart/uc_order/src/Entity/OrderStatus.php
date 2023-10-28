<?php

namespace Drupal\uc_order\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\uc_order\OrderStatusInterface;

/**
 * Defines the order status entity.
 *
 * @ConfigEntityType(
 *   id = "uc_order_status",
 *   label = @Translation("Order status"),
 *   label_singular = @Translation("order status"),
 *   label_plural = @Translation("order statuses"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order status",
 *     plural = "@count order statuses",
 *   ),
 *   admin_permission = "administer order workflow",
 *   config_prefix = "status",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "weight" = "weight",
 *   }
 * )
 */
class OrderStatus extends ConfigEntityBase implements OrderStatusInterface {

  /**
   * The order status ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Name of the status.
   *
   * @var string
   */
  protected $name;

  /**
   * Specific state of the status.
   *
   * @var string
   */
  protected $state = '';

  /**
   * The weight of this status in relation to other statuses.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * Locked statuses cannot be edited.
   *
   * @var bool
   */
  protected $locked = FALSE;


  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->state;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($state) {
    if ($this->isLocked()) {
      throw new \LogicException('Locked statuses cannot be modified.');
    }

    $this->state = $state;
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
  public function isLocked() {
    return (bool) $this->locked;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocked($locked) {
    $this->locked = (bool) $locked;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if ($this->isLocked()) {
      throw new \LogicException('Locked statuses cannot be deleted.');
    }

    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    uasort($entities, 'static::sort');
  }

  /**
   * Returns an option list of order statuses.
   *
   * @return string[]
   *   An array of status names, keyed by status ID.
   */
  public static function getOptionsList() {
    $options = [];
    foreach (static::loadMultiple() as $status) {
      $options[$status->id()] = $status->getName();
    }
    return $options;
  }

}
