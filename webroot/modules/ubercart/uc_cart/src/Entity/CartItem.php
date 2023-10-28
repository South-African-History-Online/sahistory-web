<?php

namespace Drupal\uc_cart\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\uc_cart\CartItemInterface;
use Drupal\uc_order\Entity\OrderProduct;

/**
 * Defines the cart item entity class.
 *
 * @ContentEntityType(
 *   id = "uc_cart_item",
 *   label = @Translation("Cart item"),
 *   label_singular = @Translation("cart item"),
 *   label_plural = @Translation("cart items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count cart item",
 *     plural = "@count cart items",
 *   ),
 *   module = "uc_cart",
 *   handlers = {
 *     "storage" = "Drupal\uc_cart\CartItemStorage",
 *     "views_data" = "Drupal\uc_cart\CartItemViewsData",
 *   },
 *   base_table = "uc_cart_products",
 *   entity_keys = {
 *     "id" = "cart_item_id",
 *   }
 * )
 */
class CartItem extends ContentEntityBase implements CartItemInterface {

  use EntityChangedTrait;

  /**
   * The user-facing name of this item.
   *
   * @var string
   */
  public $title;

  /**
   * The SKU of this item.
   *
   * @var string
   */
  public $model;

  /**
   * The cost of this item.
   *
   * @var float
   */
  public $cost;

  /**
   * The price of this item.
   *
   * @var float
   */
  public $price;

  /**
   * The shipping weight of this item.
   *
   * @var float
   */
  public $weight;

  /**
   * The units of $weight.
   *
   * @var string
   */
  public $weight_units;

  /**
   * {@inheritdoc}
   */
  public function toOrderProduct() {
    $order_product = OrderProduct::create(array(
      'nid' => $this->nid->target_id,
      'title' => $this->title,
      'model' => $this->model,
      'qty' => $this->qty->value,
      'cost' => $this->cost,
      'price' => $this->price,
      'weight' => $this->weight,
      'data' => $this->data,
    ));
    $order_product->weight->units = $this->weight_units;
    return $order_product;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$items) {
    foreach ($items as $item) {
      $item->product = uc_product_load_variant($item->nid->target_id, $item->data->first()->toArray());
      if ($item->product) {
        $item->title = $item->product->label();
        $item->model = $item->product->model;
        $item->cost = $item->product->cost->value;
        $item->price = $item->product->price;
        $item->weight = $item->product->weight->value;
        $item->weight_units = $item->product->weight->units;
      }

      $item->module = $item->data->module;
    }
    parent::postLoad($storage, $items);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['cart_item_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cart item ID'))
      ->setDescription(t('The cart item ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);
    $fields['cart_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Cart ID'))
      ->setDescription(t('A user-specific cart ID. For authenticated users, their {users}.uid. For anonymous users, a token.'))
      ->setSetting('default_value', 0);
    $fields['nid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Node ID'))
      ->setDescription(t('The node ID of the product.'))
      ->setSetting('target_type', 'node')
      ->setSetting('default_value', 0);
    $fields['qty'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The number of this product in the cart.'))
      ->setSetting('default_value', 0)
      ->setSetting('unsigned', TRUE);
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the cart item was last edited.'));
    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of extra data.'));

    return $fields;
  }

}
