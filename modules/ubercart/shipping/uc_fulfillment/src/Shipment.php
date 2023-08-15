<?php

namespace Drupal\uc_fulfillment;

use Drupal\uc_order\Entity\Order;
use Drupal\uc_store\Address;
use Drupal\uc_store\AddressInterface;

/**
 * Defines the Shipment class.
 */
class Shipment implements ShipmentInterface {

  /**
   * Shipment ID.
   *
   * @var int
   */
  protected $sid;

  /**
   * Order ID of this shipment.
   *
   * @var int
   */
  protected $order_id;

  /**
   * Name of the shipping method.
   *
   * @var string
   */
  protected $shipping_method = '';

  /**
   * Shipping quote accessorials.
   *
   * @var array
   */
  protected $accessorials = '';

  /**
   * Name of the common carrier.
   *
   * @var string
   */
  protected $carrier = '';

  /**
   * Shipment transaction ID.
   *
   * @var string
   */
  protected $transaction_id = '';

  /**
   * Shipment tracking number,
   *
   * @var string
   */
  protected $tracking_number = '';

  /**
   * Ship date timestamp.
   *
   * @var int
   */
  protected $ship_date = 0;

  /**
   * Expected delivery timestamp.
   *
   * @var int
   */
  protected $expected_delivery = 0;

  /**
   * Name of the status.
   *
   * @var float
   */
  protected $cost = 0;

  /**
   * Currency code.
   *
   * @var string
   */
  protected $currency = '';

  /**
   * Last modified timestamp.
   *
   * @var int
   */
  protected $changed = 0;

  /**
   * Packages contained in this shipment.
   *
   * @var \Drupal\uc_fulfillment\Package[]
   */
  protected $packages = array();

  /**
   * Shipment origin address.
   *
   * @var \Drupal\uc_store\Address
   */
  protected $origin;

  /**
   * Shipment destination address.
   *
   * @var \Drupal\uc_store\Address
   */
  protected $destination;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->sid;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderId($order_id) {
    $this->order_id = $order_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->order_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setShippingMethod($shipping_method) {
    $this->shipping_method = $shipping_method;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingMethod() {
    return $this->shipping_method;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessorials($accessorials) {
    $this->accessorials = $accessorials;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessorials() {
    return $this->accessorials;
  }

  /**
   * {@inheritdoc}
   */
  public function setCarrier($carrier) {
    $this->carrier = $carrier;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCarrier() {
    return $this->carrier;
  }

  /**
   * {@inheritdoc}
   */
  public function setTransactionId($transaction_id) {
    $this->transaction_id = $transaction_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionId() {
    return $this->transaction_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setTrackingNumber($tracking_number) {
    $this->tracking_number = $tracking_number;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackingNumber() {
    return $this->tracking_number;
  }

  /**
   * {@inheritdoc}
   */
  public function setShipDate($ship_date) {
    $this->ship_date = $ship_date;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getShipDate() {
    return $this->ship_date;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpectedDelivery($expected_delivery) {
    $this->expected_delivery = $expected_delivery;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpectedDelivery() {
    return $this->expected_delivery;
  }

  /**
   * {@inheritdoc}
   */
  public function setCost($cost) {
    $this->cost = $cost;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCost() {
    return $this->cost;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrency($currency) {
    $this->currency = $currency;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->currency;
  }

  /**
   * {@inheritdoc}
   */
  public function setChanged($changed) {
    $this->changed = $changed;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChanged() {
    return $this->changed;
  }

  /**
   * {@inheritdoc}
   */
  public function setPackages(array $packages) {
    $this->packages = $packages;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackages() {
    return $this->packages;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrigin(AddressInterface $origin) {
    $this->origin = $origin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrigin() {
    return $this->origin;
  }

  /**
   * {@inheritdoc}
   */
  public function setDestination(AddressInterface $destination) {
    $this->destination = $destination;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * Constructor.
   */
  protected function __construct() {
  }

  /**
   * Creates a Shipment.
   *
   * @param array $values
   *   (optional) Array of initialization values.
   *
   * @return \Drupal\uc_fulfillment\Shipment
   *   A Shipment object.
   */
  public static function create(array $values = NULL) {
    $shipment = new Shipment();
    if (isset($values)) {
      foreach ($values as $key => $value) {
        $shipment->$key = $value;
      }
    }
    return $shipment;
  }

  /**
   * Loads a shipment and its packages for a given order.
   *
   * @param array $order_id
   *   An order ID.
   *
   * @return \Drupal\uc_fulfillment\Shipment[]
   *   Array of shipment objects for the given order.
   */
  public static function loadByOrder($order_id) {
    $shipments = array();
    $result = db_query('SELECT sid FROM {uc_shipments} WHERE order_id = :id', [':id' => $order_id]);
    while ($shipment_id = $result->fetchField()) {
      $shipments[] = Shipment::load($shipment_id);
    }

    return $shipments;
  }

  /**
   * Loads a shipment and its packages.
   *
   * @param int $shipment_id
   *   The shipment ID.
   *
   * @return \Drupal\uc_fulfillment\Shipment|null
   *   The Shipment object, or NULL if there isn't one.
   */
  public static function load($shipment_id) {
    $shipment = NULL;
    $result = db_query('SELECT * FROM {uc_shipments} WHERE sid = :sid', [':sid' => $shipment_id]);
    if ($assoc = $result->fetchAssoc()) {
      $shipment = Shipment::create();
      $origin_fields = array();
      $destination_fields = array();

      foreach ($assoc as $key => $value) {
        $subkey = substr($key, 0, 2);
        if ($subkey == 'o_') {
          $origin_fields[substr($key, 2)] = $value;
        }
        elseif ($subkey == 'd_') {
          $destination_fields[substr($key, 2)] = $value;
        }
        else {
          $shipment->$key = $value;
        }
      }
      // Reconstitute Address objects from individual fields.
      $shipment->setOrigin(Address::create($origin_fields));
      $shipment->setDestination(Address::create($destination_fields));

      $result2 = db_query('SELECT package_id FROM {uc_packages} WHERE sid = :sid', [':sid' => $shipment_id]);
      $packages = array();
      foreach ($result2 as $package) {
        $packages[$package->package_id] = Package::load($package->package_id);
      }
      $shipment->setPackages($packages);

      $extra = \Drupal::moduleHandler()->invokeAll('uc_shipment', array('load', $shipment));
      if (is_array($extra)) {
        foreach ($extra as $key => $value) {
          $shipment->$key = $value;
        }
      }
    }

    return $shipment;
  }

  /**
   * Saves this shipment.
   */
  public function save() {
    $this->changed = time();

    // Break Address objects into individual fields for saving.
    $fields = array();
    if (isset($this->origin)) {
      foreach ($this->origin as $field => $value) {
        $field = 'o_' . $field;
        $fields[$field] = $value;
      }
    }
    if (isset($this->destination)) {
      foreach ($this->destination as $field => $value) {
        $field = 'd_' . $field;
        $fields[$field] = $value;
      }
    }

    // Yuck.
    $fields += array(
      'order_id' => $this->order_id,
      'shipping_method' => $this->shipping_method,
      'accessorials' => $this->accessorials,
      'carrier' => $this->carrier,
      'transaction_id' => $this->transaction_id,
      'tracking_number' => $this->tracking_number,
      'ship_date' => $this->ship_date,
      'expected_delivery' => $this->expected_delivery,
      'cost' => $this->cost,
      'currency' => $this->currency,
      'changed' => $this->changed,
    );
    if (!isset($this->sid)) {
      $this->sid = db_insert('uc_shipments')
        ->fields($fields)
        ->execute();
      $this->is_new = TRUE;
    }
    else {
      db_update('uc_shipments')
        ->fields($fields)
        ->condition('sid', $this->sid, '=')
        ->execute();
      $this->is_new = FALSE;
    }

    if (is_array($this->packages)) {
      foreach ($this->packages as $package) {
        $package->setSid($this->sid);
        // Since the products haven't changed, we take them out of the object so
        // that they are not deleted and re-inserted.
        $products = $package->getProducts();
        $package->setProducts([]);
        $package->save();
        // But they're still necessary for hook_uc_shipment(), so they're added
        // back in.
        $package->setProducts($products);
      }
    }

    \Drupal::moduleHandler()->invokeAll('uc_shipment', array('save', $this));
    $order = Order::load($this->order_id);
    // rules_invoke_event('uc_shipment_save', $order, $shipment);
  }

  /**
   * Deletes this shipment.
   */
  public function delete() {
    db_update('uc_packages')
      ->fields(array(
        'sid' => NULL,
        'tracking_number' => NULL,
        'label_image' => NULL,
      ))
      ->condition('sid', $this->sid)
      ->execute();

    db_delete('uc_shipments')
      ->condition('sid', $this->sid)
      ->execute();

    foreach ($this->packages as $package) {
      if ($package->getLabelImage()) {
        file_delete($package->getLabelImage());
        $package->setLabelImage('');
      }
    }

    \Drupal::moduleHandler()->invokeAll('uc_shipment', array('delete', $this));
    drupal_set_message(t('Shipment @id has been deleted.', ['@id' => $this->sid]));
  }

}
