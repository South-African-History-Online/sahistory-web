<?php

namespace Drupal\uc_order;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the order status entity.
 */
interface OrderStatusInterface extends ConfigEntityInterface {

  /**
   * Returns the name of this status.
   *
   * @return string
   *   The name of this status.
   */
  public function getName();

  /**
   * Sets the order status name to the given value.
   *
   * @param string $name
   *   The name of this order status.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Returns the order state this status.
   *
   * @return string
   *   The order state of this status.
   */
  public function getState();

  /**
   * Sets the order state associated with this status to the given value.
   *
   * @param string $state
   *   The order state of this status.
   *
   * @return $this
   */
  public function setState($state);

  /**
   * Returns the weight of this status in relation to other statuses.
   *
   * @return int
   *   The weight of this status.
   */
  public function getWeight();

  /**
   * Sets the status weight to the given value.
   *
   * @param int $weight
   *   The desired weight of this status.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Locked statuses cannot be edited.
   *
   * @return bool
   *   TRUE if the status is locked.
   */
  public function isLocked();

  /**
   * Sets the locked property for this status.
   *
   * @param bool $locked
   *   TRUE if the status should be locked.
   *
   * @return $this
   */
  public function setLocked($locked);

}
