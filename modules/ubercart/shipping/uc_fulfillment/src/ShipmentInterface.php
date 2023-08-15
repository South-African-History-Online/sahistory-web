<?php

namespace Drupal\uc_fulfillment;

use Drupal\uc_store\AddressInterface;

/**
 * Provides an interface that defines the Shipment class.
 */
interface ShipmentInterface {

  /**
   * Sets the order ID to the given value.
   *
   * @param int $order_id
   *   The name of this order status.
   *
   * @return $this
   */
  public function setOrderId($order_id);

  /**
   * Returns the order ID of this shipment.
   *
   * @return int
   *   The name of this status.
   */
  public function getOrderId();

  /**
   * Sets the shipping method to the given value.
   *
   * @param string $shipping_method
   *   The name of the shipping method.
   *
   * @return $this
   */
  public function setShippingMethod($shipping_method);

  /**
   * Returns the name of the shipping method.
   *
   * @return string
   *   The name of the shipping method.
   */
  public function getShippingMethod();

  /**
   * Sets the shipping quote accessorials of this shipment.
   *
   * @param string $accessorials
   *   The accessorials.
   *
   * @return $this
   */
  public function setAccessorials($accessorials);

  /**
   * Returns the shipping quote accessorials of this shipment.
   *
   * @return string
   *   The accessorials.
   */
  public function getAccessorials();

  /**
   * Sets the common carrier name to the given value.
   *
   * @param string $carrier
   *   The name of the common carrier.
   *
   * @return $this
   */
  public function setCarrier($carrier);

  /**
   * Returns the name of the common carrier.
   *
   * @return string
   *   The name of the common carrier.
   */
  public function getCarrier();

  /**
   * Sets the transaction ID of this shipment.
   *
   * @param string $transaction_id
   *   The transaction ID.
   *
   * @return $this
   */
  public function setTransactionId($transaction_id);

  /**
   * Returns the transaction ID of this shipment.
   *
   * @return string
   *   The transaction ID.
   */
  public function getTransactionId();

  /**
   * Sets the tracking number of this shipment.
   *
   * @param string $tracking_number
   *   The tracking number.
   *
   * @return $this
   */
  public function setTrackingNumber($tracking_number);

  /**
   * Returns the tracking number of this shipment.
   *
   * @return string
   *   The tracking number.
   */
  public function getTrackingNumber();

  /**
   * Sets the ship date timestamp to the given value.
   *
   * @param int $ship_date
   *   The ship date timestamp.
   *
   * @return $this
   */
  public function setShipDate($ship_date);

  /**
   * Returns the ship date timestamp of this shipment.
   *
   * @return int
   *   The ship date timestamp.
   */
  public function getShipDate();

  /**
   * Sets the expected delivery timestamp.
   *
   * @param int $expected_delivery
   *   The expected delivery timestamp.
   *
   * @return $this
   */
  public function setExpectedDelivery($expected_delivery);

  /**
   * Returns the expected delivery timestamp.
   *
   * @return int
   *   The expected delivery timestamp.
   */
  public function getExpectedDelivery();

  /**
   * Sets the shipping cost for this shipment.
   *
   * @param float $cost
   *   The shipping cost.
   *
   * @return $this
   */
  public function setCost($cost);

  /**
   * Returns the shipping cost for this shipment.
   *
   * @return float
   *   The shipping cost.
   */
  public function getCost();

  /**
   * Sets the currency code used for the shipping cost.
   *
   * @param string $currency
   *   The currency code for this shipment.
   *
   * @return $this
   */
  public function setCurrency($currency);

  /**
   * Returns the currency code used for the shipping cost.
   *
   * @return string
   *   The currency code for this shipment.
   */
  public function getCurrency();

  /**
   * Sets the last modified timestamp.
   *
   * @param int $changed
   *   The last modified timestamp.
   *
   * @return $this
   */
  public function setChanged($changed);

  /**
   * Returns the last modified timestamp.
   *
   * @return int
   *   The last modified timestamp.
   */
  public function getChanged();

  /**
   * Sets the packages in this shipment.
   *
   * @param \Drupal\uc_fulfillment\Package[] $packages
   *   An array of packages in this shipment.
   *
   * @return $this
   */
  public function setPackages(array $packages);

  /**
   * Returns the packages in this shipment.
   *
   * @return \Drupal\uc_fulfillment\Package[]
   *   An array of packages in this shipment.
   */
  public function getPackages();

  /**
   * Sets the origin address for this shipment.
   *
   * @param \Drupal\uc_store\AddressInterface $origin
   *   The origin address for this shipment.
   *
   * @return $this
   */
  public function setOrigin(AddressInterface $origin);

  /**
   * Returns the origin address for this shipment.
   *
   * @return \Drupal\uc_store\Address
   *   The origin address for this shipment.
   */
  public function getOrigin();

  /**
   * Sets the destination address for this shipment.
   *
   * @param \Drupal\uc_store\AddressInterface $destination
   *   The destination address.
   *
   * @return $this
   */
  public function setDestination(AddressInterface $destination);

  /**
   * Returns the destination address for this shipment.
   *
   * @return \Drupal\uc_store\Address
   *   The destination address.
   */
  public function getDestination();

}
