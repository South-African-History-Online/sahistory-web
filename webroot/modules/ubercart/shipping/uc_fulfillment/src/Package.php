<?php

namespace Drupal\uc_fulfillment;

use Drupal\uc_order\Entity\OrderProduct;

/**
 * Defines the Package class.
 */
class Package implements PackageInterface {

  /** These variables map to DB columns */

  /**
   * Package ID.
   *
   * @var int
   */
  protected $package_id;

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
   * Package shipping type.
   *
   * @var string
   */
  protected $shipping_type = '';

  /**
   * Package package type,
   *
   * @var string
   */
  protected $pkg_type = '';

  /**
   * Package length.
   *
   * @var float
   */
  protected $length = 1;

  /**
   * Package width.
   *
   * @var float
   */
  protected $width = 1;

  /**
   * Package height.
   *
   * @var float
   */
  protected $height = 1;

  /**
   * Package length units.
   *
   * @var string
   */
  protected $length_units = '';

  /**
   * Package weight.
   *
   * @var float
   */
  protected $weight = 0;

  /**
   * Package weight units.
   *
   * @var string
   */
  protected $weight_units = '';

  /**
   * Package monetary value.
   *
   * @var float
   */
  protected $value = 0;

  /**
   * Currency code.
   *
   * @var string
   */
  protected $currency = '';

  /**
   * Package tracking number.
   *
   * @var string
   */
  protected $tracking_number = '';

  /**
   * Package shipping label image.
   *
   * @var string
   */
  protected $label_image;

  /** These variables don't map to DB columns */

  /**
   * Products contained in this shipment.
   *
   * @var \Drupal\uc_order\OrderProductInterface[]
   */
  protected $products = array();

  /**
   * Array of ship-from addresses for products in this package.
   *
   * @var \Drupal\uc_store\Address[]
   */
  protected $addresses = array();

  /**
   * Package description.
   *
   * @var string
   */
  protected $description = '';

  /** Cache loaded packages */
  protected static $packages = array();

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->package_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setSid($sid) {
    $this->sid = $sid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSid() {
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
  public function setShippingType($shipping_type) {
    $this->shipping_type = $shipping_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getShippingType() {
    return $this->shipping_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setPackageType($pkg_type) {
    $this->pkg_type = $pkg_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPackageType() {
    return $this->pkg_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setLength($length) {
    $this->length = $length;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLength() {
    return $this->length;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidth($width) {
    $this->width = $width;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeight($height) {
    $this->height = $height;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * {@inheritdoc}
   */
  public function setLengthUnits($length_units) {
    $this->length_units = $length_units;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLengthUnits() {
    return $this->length_units;
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
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeightUnits($weight_units) {
    $this->weight_units = $weight_units;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeightUnits() {
    return $this->weight_units;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value) {
    $this->value = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->value;
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
  public function setLabelImage($label_image) {
    $this->label_image = $label_image;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelImage() {
    return $this->label_image;
  }

  /**
   * {@inheritdoc}
   */
  public function addProducts(array $products) {
    $this->products = array_replace($this->products, $products);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProducts(array $products) {
    $this->products = $products;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProducts() {
    return $this->products;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddresses(array $addresses) {
    $this->addresses = $addresses;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddresses() {
    return $this->addresses;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Constructor.
   */
  protected function __construct() {
    $store_config = \Drupal::config('uc_store.settings');
    $this->weight_units = $store_config->get('weight.units');
    $this->length_units = $store_config->get('length.units');
    $this->currency = $store_config->get('currency.code');
  }

  /**
   * Creates a Package.
   *
   * @param array $values
   *   (optional) Array of initialization values.
   *
   * @return \Drupal\uc_fulfillment\Package
   *   A Package object.
   */
  public static function create(array $values = NULL) {
    $package = new Package();
    if (isset($values)) {
      foreach ($values as $key => $value) {
        $package->$key = $value;
      }
    }
    return $package;
  }

  /**
   * Loads packages for a given order.
   *
   * @param array $order_id
   *   An order ID.
   *
   * @return \Drupal\uc_fulfillment\Package[]
   *   Array of Package objects for the given order.
   */
  public static function loadByOrder($order_id) {
    $packages = array();
    $result = db_query('SELECT package_id FROM {uc_packages} WHERE order_id = :id ORDER BY package_id', [':id' => $order_id]);
    while ($package_id = $result->fetchField()) {
      $packages[] = Package::load($package_id);
    }

    return $packages;
  }

  /**
   * Loads a package and its products.
   *
   * @param int $package_id
   *   The package ID.
   *
   * @return \Drupal\uc_fulfillment\Package|null
   *   The Package object, or NULL if there isn't one with the given ID.
   */
  public static function load($package_id) {
    if (!isset(self::$packages[$package_id])) {
      $result = db_query('SELECT * FROM {uc_packages} WHERE package_id = :id', [':id' => $package_id]);
      if ($assoc = $result->fetchAssoc()) {
        $package = Package::create($assoc);

        $products = array();
        $description = array();
        $addresses = array();
        $result = db_query('SELECT op.order_product_id, pp.qty, op.weight__value AS weight, op.weight__units as weight_units, op.nid, op.title, op.model, op.price, op.data FROM {uc_packaged_products} pp LEFT JOIN {uc_order_products} op ON op.order_product_id = pp.order_product_id WHERE pp.package_id = :id ORDER BY op.order_product_id', [':id' => $package->package_id]);
        foreach ($result as $product) {
          $address = uc_quote_get_default_shipping_address($product->nid);
          if (!in_array($address, $addresses)) {
            $addresses[] = $address;
          }
          $description[] = $product->qty . ' x ' . $product->model;
          $product->data = unserialize($product->data);
          $products[$product->order_product_id] = $product;
        }
        $package->addresses = $addresses;
        $package->description = implode(', ', $description);
        $package->products = $products;

        if ($package->label_image && $image = file_load($package->label_image)) {
          $package->label_image = $image;
        }

        self::$packages[$package_id] = $package;
        return $package;
      }
      else {
        return NULL;
      }
    }
    // Return package from cache.
    return self::$packages[$package_id];
  }

  /**
   * Saves this package.
   */
  public function save() {
    $status = '';
    $fields = array(
      'order_id' => $this->order_id,
      'shipping_type' => $this->shipping_type,
      'pkg_type' => $this->pkg_type,
      'length' => $this->length,
      'width' => $this->width,
      'height' => $this->height,
      'length_units' => $this->length_units,
      'weight' => $this->weight,
      'weight_units' => $this->weight_units,
      'value' => $this->value,
      'currency' => $this->currency,
      'tracking_number' => $this->tracking_number,
    );

    if ($this->sid) {
      $fields['sid'] = $this->sid;
    }
    if ($this->label_image) {
      $fields['label_image'] = $this->label_image->fid;
    }

    if (!$this->package_id) {
      // This is a new package, do an INSERT.
      $this->package_id = db_insert('uc_packages')
        ->fields($fields)
        ->execute();
      $status = SAVED_NEW;
    }
    else {
      // This is a package we're modifying, do an UPDATE.
      db_update('uc_packages')
        ->fields($fields)
        ->condition('package_id', $this->package_id)
        ->execute();
      $status = SAVED_UPDATED;
    }

    // Now take care of saving the product relations.
    if ($this->products) {
      $insert = db_insert('uc_packaged_products')
        ->fields(array('package_id', 'order_product_id', 'qty'));

      foreach ($this->products as $id => $product) {
        $insert->values(array(
            'package_id' => $this->package_id,
            'order_product_id' => $id,
            'qty' => $product->qty,
          ));

        // Save the package_id to the OrderProduct.
        // 'package_id' is a key in the serialized 'data' array.
        // data->package_id is an array keyed by package ID with a
        // value equal to the quantity in that package.
        $order_product = OrderProduct::load($id);
        if (NULL != $order_product) {
          $package_array = (array) $order_product->data->package_id;
          $package_array[intval($this->package_id)] = (int) $product->qty;
          $order_product->data->package_id = $package_array;
          $order_product->save();
        }
      }

      // Remove any old data for this package.
      db_delete('uc_packaged_products')
        ->condition('package_id', $this->package_id)
        ->execute();

      // Write the current data for this package.
      $insert->execute();
    }

    return $status;
  }

  /**
   * Deletes this package.
   */
  public function delete() {
    db_delete('uc_packages')
      ->condition('package_id', $this->package_id)
      ->execute();
    db_delete('uc_packaged_products')
      ->condition('package_id', $this->package_id)
      ->execute();

    if ($this->label_image) {
      file_usage_delete($this->label_image, 'uc_fulfillment', 'package', $this->package_id);
      file_delete($this->label_image);
    }

    // Remove the package_id from the OrderProduct.
    foreach ($this->products as $id => $product) {
      $order_product = OrderProduct::load($id);
      if (NULL != $order_product) {
        // There may be multiple packages per OrderProduct.
        $package_array = $order_product->data->package_id;
        unset($package_array[$this->package_id]);
        $order_product->data->package_id = $package_array;
        $order_product->save();
      }
    }

    drupal_set_message(t('Package @id has been deleted.', ['@id' => $this->package_id]));
  }

}
