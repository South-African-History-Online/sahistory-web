<?php

namespace Drupal\basic_cart;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;

/**
 * Utilty functions for basic cart.
 */
class Utility extends Settings {

  private $storage;

  const FIELD_ADDTOCART    = 'addtocart';
  const FIELD_ORDERCONNECT = 'orderconnect';
  const BASICCART_ORDER    = 'basic_cart_order';

  /**
   * Get Storage session or table.
   */
  private static function getStorage() {
    $user = \Drupal::currentUser();
    $config = self::cartSettings();
    $storage = new CartStorageSelect($user, $config->get('use_cart_table'));
    return $storage;
  }

  /**
   * Check if its basic cart.
   *
   * @param string $bundle
   *   Content type name.
   *
   * @return bool
   *   Returns true or false
   */
  public static function isBasicCartOrder($bundle) {
    if ($bundle == self::BASICCART_ORDER) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function for shopping cart retrieval.
   *
   * @param int $nid
   *   We are using the node id to store the node in the shopping cart.
   *
   * @return mixed
   *   Returning the shopping cart contents.
   *   An empty array if there is nothing in the cart
   */
  public static function getCart($nid = NULL) {
    $storage = static::getStorage();
    return $storage->getCart($nid);
  }

  /**
   * Returns the final price for the shopping cart.
   *
   * @return mixed $total_price
   *   The total price for the shopping cart.
   */

  /**
   * Callback function for cart/remove/.
   *
   * @param int $nid
   *   We are using the node id to remove the node in the shopping cart.
   */
  public static function removeFromCart($nid = NULL) {
    $nid = (int) $nid;
    $storage = static::getStorage();
    $storage->removeFromCart($nid);
  }

  /**
   * Shopping cart reset.
   */
  public static function emptyCart() {
    $storage = static::getStorage();
    $storage->emptyCart();
  }

  /**
   * Add to cart.
   *
   * @param int $id
   *   Node id.
   * @param array $params
   *   Quantity and entity types.
   */
  public static function addToCart($id, array $params = array()) {
    $storage = static::getStorage();
    $storage->addToCart($id, $params);
  }

  /**
   * Login Sync.
   */
  public function loggedInActionCart() {
    $storage = static::getStorage();
    return $storage->loggedInActionCart();
  }

  /**
   * Returns the fields we need to create.
   *
   * @param string $type
   *   Type of field sets to be created.
   *
   * @return mixed
   *   Key / Value pair of field name => field type.
   */
  public static function getFieldsConfig($type = NULL) {

    $config = self::cartSettings();
    $fields['bundle_types'] = $config->get('content_type');
    foreach ($config->get('content_type') as $key => $value) {
      if ($value) {
        $bundles[$key] = $key;
      }
    }
    $fields['bundle_types'] = $bundles;
    if ($type == self::FIELD_ORDERCONNECT) {

      $fields['bundle_types'] = array(
        'basic_cart_order' => 'basic_cart_order',
      );
      $fields['fields'] = array(
        'basic_cart_content' => array(
          'type' => 'entity_reference_quantity',
          'entity_type' => 'node',
          'bundle' => 'basic_cart_order',
          'title' => t('Basic cart content'),
          'label' => t('Basic cart content'),
          'required' => FALSE,
          'description' => t('Basic cart reference content'),
          'cardinality' => -1,
          'formatter' => array(
            'default' => array(
              'label' => 'inline',
              'type' => 'entity_reference_quantity_label',
              'settings' => ['view_mode' => 'basic_cart_order'],
            ),
            'search_result' => 'default',
            'teaser' => 'default',
          ),
          'widget' => array(
            'type' => 'entity_reference_quantity_autocomplete',
            'cardinality' => -1,
          ),
          'settings' => array(
            'handler' => 'default:node',
            'handler_settings' => array(
              "target_bundles" => $bundles,
            ),
          ),

        ),
      );
    }
    else {
      $fields['fields'] = array(
        'add_to_cart_price' => array(
          'type' => 'decimal',
          'entity_type' => 'node',
          'title' => t($config->get('price_label')),
          'label' => t($config->get('price_label')),
          'required' => FALSE,
          'description' => t("Please enter this item's price."),
          'cardinality' => 1,
          'widget' => array('type' => 'number'),
          'formatter' => array(
            'default' => array(
              'label' => 'inline',
              'type' => 'number_decimal',
              'weight' => 11,
            ), 'search_result' => 'default',
            'teaser' => 'default',
          ),
        ),
        'add_to_cart' => array(
          'type' => 'addtocart',
          'entity_type' => 'node',
          'title' => t($config->get('add_to_cart_button')),
          'label' => t($config->get('add_to_cart_button')),
          'required' => FALSE,
          'description' => 'Enable add to cart button',
          'cardinality' => 1,
          'widget' => array('type' => 'addtocart'),
          'formatter' => array(
            'default' => array(
              'label' => 'hidden',
              'weight' => 11,
              'type' => $config->get('quantity_status') ? 'addtocartwithquantity' : 'addtocart',
            ), 'search_result' => array(
              'label' => 'hidden',
              'weight' => 11,
              'type' => 'addtocart',
            ), 'teaser' => array(
              'label' => 'hidden',
              'weight' => 11,
              'type' => 'addtocart',
            ),
          ),

        ),
      );

    }
    return (object) $fields;
  }

  /**
   * Create Fields for content type basic cart enabled.
   *
   * @param string $type
   *   Type fields to be created.
   */
  public static function createFields($type = NULL) {
    $fields = ($type == self::FIELD_ORDERCONNECT) ? self::getFieldsConfig(self::FIELD_ORDERCONNECT) : self::getFieldsConfig();
    $view_modes = \Drupal::service('entity_display.repository')->getViewModes('node');
    $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');

    foreach ($fields->fields as $field_name => $config) {
      $field_storage = FieldStorageConfig::loadByName($config['entity_type'], $field_name);
      if (empty($field_storage)) {
        FieldStorageConfig::create(array(
          'field_name' => $field_name,
          'entity_type' => $config['entity_type'],
          'type' => $config['type'],
          'cardinality' => $config['cardinality'],
        ))->save();
      }
    }
    foreach ($fields->bundle_types as $bundle) {

      $view_display = $storage->load($config['entity_type'] . '.' . $bundle . '.basic_cart_order');
      if ($view_display == NULL) {
        $storage->create([
          'targetEntityType' => $config['entity_type'],
          'bundle' => $bundle,
          'mode' => 'basic_cart_order',
          'status' => TRUE,
        ])->save();
      }

      foreach ($fields->fields as $field_name => $config) {
        $config_array = array(
          'field_name' => $field_name,
          'entity_type' => $config['entity_type'],
          'bundle' => $bundle,
          'label' => $config['label'],
          'required' => $config['required'],
          'cardinality' => $config['cardinality'],
        );

        if (isset($config['settings'])) {
          $config_array['settings'] = $config['settings'];
        }
        $field = FieldConfig::loadByName($config['entity_type'], $bundle, $field_name);
        if (!empty($field)) {
          $field->setLabel($config['label'])->save();
          $field->setRequired($config['required'])->save();
        }
        else {
          FieldConfig::create($config_array)->save();
          if ($config['widget']) {
            \Drupal::service('entity_display.repository')->getFormDisplay($config['entity_type'], $bundle, 'default')
              ->setComponent($field_name, $config['widget'])
              ->save();
          }
          if ($config['formatter']) {
            foreach ($config['formatter'] as $view => $formatter) {
              if (isset($view_modes[$view]) || $view == "default") {
                $formatter_view = \Drupal::service('entity_display.repository')->getViewDisplay($config['entity_type'], $bundle, $view);
                if ($view != 'basic_cart_order') {
                  $formatter_view->setComponent($field_name, !is_array($formatter) ? $config['formatter']['default'] : $config['formatter']['default']);
                }
                elseif ($view == 'basic_cart_order' && $field_name == "add_to_cart_price") {
                  $formatter_view->setComponent($field_name, !is_array($formatter) ? $config['formatter']['default'] : $config['formatter']['default']);
                }
                elseif ($view == 'basic_cart_order' && $field_name != "add_to_cart_price") {
                  $formatter_view->removeComponent($field_name);
                }

                $formatter_view->save();
              }
            }
          }
        }
      }
      // Display mode basic_cart_order add fields to title and
      // price feild in formatter and remove other fields.
      $view_display = $storage->load($config['entity_type'] . '.' . $bundle . '.basic_cart_order');
      if ($view_display != NULL) {
        foreach (\Drupal::service('entity_field.manager')->getFieldDefinitions($config['entity_type'], $bundle) as $field_name => $field_definition) {
          if (!empty($field_definition->getTargetBundle())) {
            if (!in_array($field_definition->getName(), ['add_to_cart_price'])) {
              $view_display->removeComponent($field_definition->getName());
            }
            else {
              $view_display->setComponent($field_definition->getName(), ['type' => 'number_decimal']);
            }
          }
        }
        if ($view_display != NULL) {
          $view_display->removeComponent('links');
          $view_display->save();
        }
      }
    }
  }

  /**
   * Remove Fields from content types.
   */
  public static function removeFields() {
    $entityFieldManager = \Drupal::service('entity_field.manager');

    $content_types = \Drupal::config('basic_cart.settings')->get('content_type');

    foreach ($content_types as $key => $value) {
      $fields = $entityFieldManager->getFieldDefinitions('node', $key);

      if (isset($fields['add_to_cart'])) {
        $fields['add_to_cart']->delete();
      }
      if (isset($fields['add_to_cart_price'])) {
        $fields['add_to_cart_price']->delete();
      }

    }

  }

  /**
   * Create Order connect Fields.
   */
  public static function orderConnectFields() {
    self::createFields(self::FIELD_ORDERCONNECT);
  }

  /**
   * Render function.
   *
   * @param string $theme_hook
   *   Name of the theme hook.
   */
  public static function render($theme_hook = 'basic_cart_cart_template', $variable = NULL) {
    $build = ['#theme' => $theme_hook, '#basic_cart' => $variable ? $variable : self::getCartData()];
    return \Drupal::service('renderer')->render($build);
  }

  /**
   * Get Cart Data.
   */
  public static function getCartData() {
    $config = self::cartSettings();
    $cart = self::getCart();
    //$quantity_enabled = $config->get('quantity_status');
    $total_price = self::getTotalPrice();
    $cart_cart = isset($cart['cart']) ? $cart['cart'] : array();

    $basic_cart = array();
    $basic_cart['config']['quantity_enabled'] = $config->get('quantity_status');
    $basic_cart['empty']['text'] = $config->get('empty_cart');

    if (empty($cart_cart)) {
      $basic_cart['empty']['status'] = TRUE;
    }
    else {
      if (is_array($cart_cart) && count($cart_cart) >= 1) {

        foreach ($cart_cart as $nid => $node) {
          if (!isset($node)) {
            continue;
          }
          $langcode = $node->language()->getId();
          if ($node->isTranslatable()) {
            $node = $node->getTranslation($langcode);
          }
          $price_value = $node->get('add_to_cart_price')->getValue();
          $title = $node->get('title')->getValue();
          $url = new Url('entity.node.canonical', ["node" => $nid]);
          $link = new Link($title[0]['value'], $url);
          $basic_cart['data']['contents'][$nid] = [
            "quantity" => $cart['cart_quantity'][$nid],
            'price_value' => isset($price_value[0]) ? self::formatPrice($price_value[0]['value']) : '',
            'link' => $link->toString(),
          ];
        }

        $basic_cart['config']['total_price_label'] = $config->get('total_price_label');
        $basic_cart['config']['total_price'] = self::formatPrice($total_price->total);
        $basic_cart['config']['vat_enabled'] = $config->get('vat_state');
        $basic_cart['config']['vat_label'] = 'Total VAT';
        $basic_cart['config']['total_price_vat'] = self::formatPrice($total_price->vat);
        $basic_cart['config']['view_cart_button'] = $config->get('view_cart_button');
        $url = new Url('basic_cart.cart');
        $basic_cart['config']['view_cart_url'] = $url->toString();
        $basic_cart['empty']['status'] = FALSE;
      }
    }
    return $basic_cart;
  }

  /**
   * Get Total price data.
   */
  public static function getTotalPriceMarkupData() {
    $config = Utility::cartSettings();
    $price = Utility::getTotalPrice();
    $total = Utility::formatPrice($price->total);
    $vat_is_enabled = (int) $config->get('vat_state');
    $vat_value = !empty($vat_is_enabled) && $vat_is_enabled ? Utility::formatPrice($price->vat) : 0;

    $basic_cart = array(
      'total_price' => $total,
      'vat_enabled' => $vat_is_enabled,
      'vat_value' => $vat_value,
      'total_price_label' => $config->get('total_price_label'),
      'total_vat_label' => 'Total VAT',
    );
    return $basic_cart;
  }

  /**
   * Get Quantity prefix data.
   *
   * @param int $nid
   *   Node id of content.
   */
  public static function quantityPrefixData($nid) {
    global $base_url;
    $url = new Url('basic_cart.cartremove', array("nid" => $nid));
    $cart = Utility::getCart($nid);
    $basic_cart = array();
    $basic_cart['delete_url'] = $url->toString();
    $basic_cart['module_url'] = $base_url . '/' . drupal_get_path('module', 'basic_cart');
    $basic_cart['notempty'] = FALSE;
    if (!empty($cart['cart'])) {
      $basic_cart['notempty'] = TRUE;
      $langcode = $cart['cart']->language()->getId();
      if ($cart['cart']->isTranslatable()) {
        $cart['cart'] = $cart['cart']->getTranslation($langcode);
      }
      $unit_price = $cart['cart']->get('add_to_cart_price')->getValue();
      $unit_price = isset($unit_price[0]['value']) ? $unit_price[0]['value'] : 0;
      $title = $cart['cart']->get('title')->getValue()[0]['value'];
      // Price and currency.
      $url = new Url('entity.node.canonical', array("node" => $nid));
      $link = new Link($title, $url);
      $unit_price = isset($unit_price) ? $unit_price : 0;
      $unit_price = Utility::formatPrice($unit_price);
      $basic_cart['unit_price'] = $unit_price;
      $basic_cart['title_link'] = $link->toString();
    }
    return $basic_cart;
  }

  /**
   * Get Cart count.
   */
  public static function cartCount() {
    $cart = Utility::getCart();
    return isset($cart['cart_quantity']) ? array_sum($cart['cart_quantity']) : 0;
  }

  /**
   * Returns if basic_cart fields are empty in the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Basic cart Order node.
   *
   * @return bool
   *   TRUE if order is empty, FALSE otherwise.
   */
  public static function isOrderEmpty(NodeInterface $node): bool {
    return $node->get('basic_cart_content')->isEmpty() &&
      $node->get('basic_cart_vat')->isEmpty() &&
      $node->get('basic_cart_total_price')->isEmpty();
  }

}
