<?php

namespace Drupal\uc_order\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_store\Address;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Defines the order entity class.
 *
 * @ContentEntityType(
 *   id = "uc_order",
 *   label = @Translation("Order"),
 *   label_singular = @Translation("order"),
 *   label_plural = @Translation("orders"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order",
 *     plural = "@count orders",
 *   ),
 *   module = "uc_order",
 *   handlers = {
 *     "view_builder" = "Drupal\uc_order\OrderViewBuilder",
 *     "access" = "Drupal\uc_order\OrderAccessControlHandler",
 *     "views_data" = "Drupal\uc_order\OrderViewsData",
 *     "form" = {
 *       "default" = "Drupal\uc_order\OrderForm",
 *       "delete" = "Drupal\uc_order\Form\OrderDeleteForm",
 *       "edit" = "Drupal\uc_order\OrderForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\uc_order\Entity\OrderRouteProvider",
 *     },
 *   },
 *   base_table = "uc_orders",
 *   entity_keys = {
 *     "id" = "order_id",
 *   },
 *   field_ui_base_route = "uc_order.workflow",
 *   links = {
 *     "canonical" = "/admin/store/orders/{uc_order}",
 *     "delete-form" = "/admin/store/orders/{uc_order}/delete",
 *     "edit-form" = "/admin/store/orders/{uc_order}/edit",
 *     "admin-form" = "/admin/store/orders",
 *   }
 * )
 */
class Order extends ContentEntityBase implements OrderInterface {

  use EntityChangedTrait;

  public $products = array();
  public $line_items = array();

  /**
   * {@inheritdoc}
   */
  public function label($langcode = NULL) {
    return t('Order @order_id', ['@order_id' => $this->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$orders) {
    parent::postLoad($storage, $orders);

    foreach ($orders as $id => $order) {
      $order->products = \Drupal::entityTypeManager()->getStorage('uc_order_product')->loadByProperties(['order_id' => $id]);

      // Load line items... has to be last after everything has been loaded.
      $order->line_items = $order->getLineItems();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);

    // Set default values.
    $store_config = \Drupal::config('uc_store.settings');
    $values += [
      'order_status' => uc_order_state_default('in_checkout'),
      'currency' => $store_config->get('currency.code'),
      'billing_country' => $store_config->get('address.country'),
      'delivery_country' => $store_config->get('address.country'),
      'created' => REQUEST_TIME,
    ];

    // Take the primary email address from the user, if necessary.
    if (empty($values['primary_email']) && !empty($values['uid'])) {
      if ($account = User::load($values['uid'])) {
        $values['primary_email'] = $account->getEmail();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $this->order_total->value = $this->getTotal();
    $this->product_count->value = $this->getProductCount();
    $this->host->value = \Drupal::request()->getClientIp();
    $this->setChangedTime(REQUEST_TIME);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    foreach ($this->products as $product) {
      \Drupal::moduleHandler()->alter('uc_order_product', $product, $this);
      uc_order_product_save($this->id(), $product);
    }

    // Record a log entry if the order status has changed.
    if ($update && $this->getStatusId() != $this->original->getStatusId()) {
      $this->logChanges([
        (string) t('Order status') => [
          'old' => $this->original->getStatus()->getName(),
          'new' => $this->getStatus()->getName(),
        ]
      ]);

      // rules_invoke_event('uc_order_status_update', $this->original, $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $orders) {
    parent::postDelete($storage, $orders);

    // Delete data from the appropriate Ubercart order tables.
    $ids = array_keys($orders);
    $result = \Drupal::entityQuery('uc_order_product')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    if (!empty($result)) {
      entity_delete_multiple('uc_order_product', array_keys($result));
    }
    db_delete('uc_order_comments')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    db_delete('uc_order_admin_comments')
      ->condition('order_id', $ids, 'IN')
      ->execute();
    db_delete('uc_order_log')
      ->condition('order_id', $ids, 'IN')
      ->execute();

    foreach ($orders as $order_id => $order) {
      // Delete line items for the order.
      uc_order_delete_line_item($order_id, TRUE);

      // Log the action in the database.
      \Drupal::logger('uc_order')->notice('Order @order_id deleted by user @uid.', ['@order_id' => $order_id, '@uid' => \Drupal::currentUser()->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }


  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLineItems() {
    $items = array();

    $result = db_query("SELECT * FROM {uc_order_line_items} WHERE order_id = :id", [':id' => $this->id()]);
    foreach ($result as $row) {
      $items[] = array(
        'line_item_id' => $row->line_item_id,
        'type' => $row->type,
        'title' => $row->title,
        'amount' => $row->amount,
        'weight' => $row->weight,
        'data' => unserialize($row->data),
      );
    }

    $line_item_manager = \Drupal::service('plugin.manager.uc_order.line_item');
    foreach ($line_item_manager->getDefinitions() as $type) {
      if (!$type['stored'] && !$type['display_only']) {
        $result = $line_item_manager->createInstance($type['id'])->load($this);
        if ($result !== FALSE && is_array($result)) {
          foreach ($result as $line) {
            $items[] = array(
              'line_item_id' => $line['id'],
              'type' => $type['id'],
              'title' => $line['title'],
              'amount' => $line['amount'],
              'weight' => isset($line['weight']) ? $line['weight'] : $type['weight'],
              'data' => isset($line['data']) ? $line['data'] : array(),
            );
          }
        }
      }
    }

    usort($items, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLineItems() {
    $line_items = $this->getLineItems();

    $line_item_manager = \Drupal::service('plugin.manager.uc_order.line_item');
    foreach ($line_item_manager->getDefinitions() as $item) {
      if ($item['display_only']) {
        $result = $line_item_manager->createInstance($item['id'])->display($this);
        if (is_array($result)) {
          foreach ($result as $line) {
            $line_items[] = array(
              'line_item_id' => $line['id'],
              'type' => $item['id'],
              'title' => $line['title'],
              'amount' => $line['amount'],
              'weight' => isset($line['weight']) ? $line['weight'] : $item['weight'],
              'data' => isset($line['data']) ? $line['data'] : array(),
            );
          }
        }
      }
    }

    foreach ($line_items as &$item) {
      $item['formatted_amount'] = uc_currency_format($item['amount']);
    }

    usort($line_items, 'Drupal\Component\Utility\SortArray::sortByWeightElement');

    return $line_items;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('order_status')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusId() {
    return $this->get('order_status')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatusId($status) {
    $this->set('order_status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateId() {
    return $this->getStatus()->getState();
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('primary_email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($email) {
    $this->set('primary_email', $email);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtotal() {
    $subtotal = 0;
    foreach ($this->products as $product) {
      $subtotal += $product->price->value * $product->qty->value;
    }
    return $subtotal;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal() {
    $total = $this->getSubtotal();
    $definitions = \Drupal::service('plugin.manager.uc_order.line_item')->getDefinitions();
    foreach ($this->line_items as $item) {
      if (!empty($definitions[$item['type']]['calculated'])) {
        $total += $item['amount'];
      }
    }
    return $total;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductCount() {
    $count = 0;
    foreach ($this->products as $product) {
      $count += $product->qty->value;
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->get('currency')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethodId() {
    return $this->get('payment_method')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentMethodId($payment_method) {
    $this->set('payment_method', $payment_method);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddress($type) {
    $address = Address::create();
    $address
      ->setFirstName($this->get($type . '_first_name')->value)
      ->setLastName($this->get($type . '_last_name')->value)
      ->setCompany($this->get($type . '_company')->value)
      ->setStreet1($this->get($type . '_street1')->value)
      ->setStreet2($this->get($type . '_street2')->value)
      ->setCity($this->get($type . '_city')->value)
      ->setZone($this->get($type . '_zone')->value)
      ->setCountry($this->get($type . '_country')->value)
      ->setPostalCode($this->get($type . '_postal_code')->value)
      ->setPhone($this->get($type . '_phone')->value);
    return $address;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddress($type, Address $address) {
    $this->set($type . '_first_name', $address->getFirstName());
    $this->set($type . '_last_name', $address->getLastName());
    $this->set($type . '_company', $address->getCompany());
    $this->set($type . '_street1', $address->getStreet1());
    $this->set($type . '_street2', $address->getStreet2());
    $this->set($type . '_city', $address->getCity());
    $this->set($type . '_zone', $address->getZone());
    $this->set($type . '_country', $address->getCountry());
    $this->set($type . '_postal_code', $address->getPostalCode());
    $this->set($type . '_phone', $address->getPhone());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isShippable() {
    foreach ($this->products as $product) {
      if (uc_order_product_is_shippable($product)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function logChanges(array $changes) {
    if (!empty($changes)) {
      foreach ($changes as $key => $value) {
        if (is_array($value)) {
          $entry = t('@key changed from %old to %new.', ['@key' => $key, '%old' => $value['old'], '%new' => $value['new']]);
        }
        else {
          $entry = (string) $value;
        }

        $markup = array('#markup' => $entry);

        db_insert('uc_order_log')
          ->fields(array(
            'order_id' => $this->id(),
            'uid' => \Drupal::currentUser()->id(),
            'changes' => \Drupal::service('renderer')->renderPlain($markup),
            'created' => REQUEST_TIME,
          ))
          ->execute();
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['order_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Order ID'))
      ->setDescription(t('The order ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Customer'))
      ->setDescription(t('The user that placed the order.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\uc_order\Entity\Order::getCurrentUserId');
    $fields['order_status'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order status'))
      ->setDescription(t('The uc_order_status entity ID indicating the order status'))
      ->setSetting('target_type', 'uc_order_status')
      ->setSetting('default_value', '')
      ->setSetting('max_length', 32);
    $fields['order_total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Order total'))
      ->setDescription(t('The total amount to be paid for the order.'))
      ->setSetting('default_value', 0.0)
      ->setSetting('precision', 16)
      ->setSetting('scale', 5);
    $fields['product_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Product count'))
      ->setDescription(t('The total product quantity of the order.'))
      ->setSetting('default_value', 0)
      ->setSetting('unsigned', TRUE);
    $fields['primary_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-mail address'))
      ->setDescription(t('The email address of the customer.'))
      ->setSetting('default_value', '')
      ->setSetting('max_length', 96);
    $fields['delivery_first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery first name'))
      ->setDescription(t('The first name of the person receiving shipment.'))
      ->setSetting('default_value', '');
    $fields['delivery_last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery last name'))
      ->setDescription(t('The last name of the person receiving shipment.'))
      ->setSetting('default_value', '');
    $fields['delivery_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery phone'))
      ->setDescription(t('The phone number at the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_company'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery company'))
      ->setDescription(t('The company at the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_street1'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery street 1'))
      ->setDescription(t('The street address of the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_street2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery street 2'))
      ->setDescription(t('The second line of the street address.'))
      ->setSetting('default_value', '');
    $fields['delivery_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery city'))
      ->setDescription(t('The city of the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_zone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery state/province'))
      ->setDescription(t('The state/zone/province id of the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_postal_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery postal code'))
      ->setDescription(t('The postal code of the delivery location.'))
      ->setSetting('default_value', '');
    $fields['delivery_country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery country'))
      ->setDescription(t('The country ID of the delivery location.'))
      ->setSetting('size', 'medium')
      ->setSetting('default_value', '');
    $fields['billing_first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing first name'))
      ->setDescription(t('The first name of the person paying for the order.'))
      ->setSetting('default_value', '');
    $fields['billing_last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing last name'))
      ->setDescription(t('The last name of the person paying for the order.'))
      ->setSetting('default_value', '');
    $fields['billing_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing phone'))
      ->setDescription(t('The phone number for the billing address.'))
      ->setSetting('default_value', '');
    $fields['billing_company'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing company'))
      ->setDescription(t('The company of the billing address.'))
      ->setSetting('default_value', '');
    $fields['billing_street1'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing street 1'))
      ->setDescription(t('The street address where the bill will be sent.'))
      ->setSetting('default_value', '');
    $fields['billing_street2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing street 2'))
      ->setDescription(t('The second line of the street address.'))
      ->setSetting('default_value', '');
    $fields['billing_city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing city'))
      ->setDescription(t('The city where the bill will be sent.'))
      ->setSetting('default_value', '');
    $fields['billing_zone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing state/province'))
      ->setDescription(t('The state/zone/province ID where the bill will be sent.'))
      ->setSetting('default_value', '')
      ->setSetting('size', 'medium');
    $fields['billing_postal_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing postal code'))
      ->setDescription(t('The postal code where the bill will be sent.'))
      ->setSetting('default_value', '');
    $fields['billing_country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing country'))
      ->setDescription(t('The country ID where the bill will be sent.'))
      ->setSetting('default_value', '')
      ->setSetting('size', 'medium');
    $fields['payment_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The method of payment.'))
      ->setSetting('default_value', '')
      ->setSetting('max_length', 32);
    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of extra data.'));
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the order was created.'));
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the order was last edited.'));
    $fields['host'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Host'))
      ->setDescription(t('Host IP address of the person paying for the order.'))
      ->setSetting('default_value', '');
    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency'))
      ->setDescription(t('The ISO currency code for the order.'))
      ->setPropertyConstraints('value', array('Length' => array('max' => 3)))
      ->setSetting('default_value', '')
      ->setSetting('max_length', 3);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

}
