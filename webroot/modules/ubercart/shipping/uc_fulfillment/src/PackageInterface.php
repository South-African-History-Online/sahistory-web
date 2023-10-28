<?php

namespace Drupal\uc_fulfillment;

/**
 * Provides an interface that defines the Package class.
 */
interface PackageInterface {

  /**
   * Sets the shipment ID.
   *
   * @param int $sid
   *
   * @return $this
   */
  public function setSid($sid);

  /**
   * Returns the shipment ID.
   *
   * @return int
   *   The Shipment ID.
   */
  public function getSid();

  /**
   * Sets the order ID of this shipment.
   *
   * @param int $order_id
   *
   * @return $this
   */
  public function setOrderId($order_id);

  /**
   * Returns the order ID of this shipment.
   *
   * @return int
   *   The Order ID.
   */
  public function getOrderId();

  /**
   * Sets the shipping type to the given value.
   *
   * @param string $shipping_type
   *   The name of the shipping type.
   *
   * @return $this
   */
  public function setShippingType($shipping_type);

  /**
   * Returns the shipping type.
   *
   * @return string
   *   The name of the shipping type.
   */
  public function getShippingType();

  /**
   * Sets the package type to the given value.
   *
   * @param string $pkg_type
   *   The name of the package type.
   *
   * @return $this
   */
  public function setPackageType($pkg_type);

  /**
   * Returns the package type.
   *
   * @return string
   *   The name of the package type.
   */
  public function getPackageType();

  /**
   * Sets the package length.
   *
   * @param float $length
   *
   * @return $this
   */
  public function setLength($length);

  /**
   * Returns the package length.
   *
   * @return float
   *   The package length.
   */
  public function getLength();

  /**
   * Sets the package width.
   *
   * @param float $width
   *
   * @return $this
   */
  public function setWidth($width);

  /**
   * Returns the package width.
   *
   * @return float
   *   The package width.
   */
  public function getWidth();

  /**
   * Sets the package height.
   *
   * @param float $height
   *
   * @return $this
   */
  public function setHeight($height);

  /**
   * Returns the package height.
   *
   * @param float $height
   *
   * @return float
   *   The package height.
   */
  public function getHeight();

  /**
   * Sets the package units of length.
   *
   * @param string $length_units
   *
   * @return $this
   */
  public function setLengthUnits($length_units);

  /**
   * Returns the package units of length.
   *
   * @return string
   *   The units used to measure package dimensions.
   */
  public function getLengthUnits();

  /**
   * Sets the package weight.
   *
   * @param float $weight
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Returns the package weight.
   *
   * @param float $weight
   *
   * @return float
   *   The package weight.
   */
  public function getWeight();

  /**
   * Sets the package units of weight.
   *
   * @param string $weight_units
   *
   * @return $this
   */
  public function setWeightUnits($weight_units);

  /**
   * Returns the package units of weight.
   *
   * @return string
   *   The units used to measure package weight.
   */
  public function getWeightUnits();

  /**
   * Sets the package monetary value.
   *
   * @param float $value
   *
   * @return $this
   */
  public function setValue($value);

  /**
   * Returns the package monetary value.
   *
   * @return float
   *   The monetary value.
   */
  public function getValue();

  /**
   * Sets the currency code used for the package value.
   *
   * @param string $currency
   *   The currency code for this package.
   *
   * @return $this
   */
  public function setCurrency($currency);

  /**
   * Returns the currency code used for the package value.
   *
   * @return string
   *   The currency code for this package.
   */
  public function getCurrency();

  /**
   * Sets the package tracking number.
   *
   * @param string $tracking_number
   *
   * @return $this
   */
  public function setTrackingNumber($tracking_number);

  /**
   * Returns the package tracking number.
   *
   * @return string
   *   The tracking number.
   */
  public function getTrackingNumber();

  /**
   * Sets package label image.
   *
   * @param string $label_image
   *
   * @return $this
   */
  public function setLabelImage($label_image);

  /**
   * Returns package label image.
   *
   * @return string
   *   The label image.
   */
  public function getLabelImage();

  /**
   * Adds products to this package.
   *
   * @param \Drupal\uc_order\OrderProductInterface[] $products
   *
   * @return $this
   */
  public function addProducts(array $products);

  /**
   * Sets all the products in this package.
   *
   * @param \Drupal\uc_order\OrderProductInterface[] $products
   *
   * @return $this
   */
  public function setProducts(array $products);

  /**
   * Returns all the products in this package.
   *
   * @return \Drupal\uc_order\OrderProductInterface[]
   *   The package's products.
   */
  public function getProducts();

  /**
   * Sets the list of ship-from addresses for this package.
   *
   * @param \Drupal\uc_store\AddressInterface[] $addresses
   *
   * @return $this
   */
  public function setAddresses(array $addresses);

  /**
   * Returns the list of ship-from addresses for products in this package.
   *
   * @return \Drupal\uc_store\AddressInterface[]
   *   The package's addresses.
   */
  public function getAddresses();

  /**
   * Sets the package description.
   *
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the package description.
   *
   * @return string
   *   The description.
   */
  public function getDescription();

}
