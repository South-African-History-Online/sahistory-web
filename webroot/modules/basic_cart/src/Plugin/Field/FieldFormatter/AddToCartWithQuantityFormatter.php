<?php

namespace Drupal\basic_cart\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'addtocartwithquantity' formatter.
 *
 * @FieldFormatter(
 *   id = "addtocartwithquantity",
 *   module = "basic_cart",
 *   label = @Translation("Add to cart with quantity"),
 *   field_types = {
 *     "addtocart"
 *   }
 * )
 */
class AddToCartWithQuantityFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'quantity_addtocart_wrapper_container_class' => '',
      'quantity_addtocart_button_container_class' => '',
      'quantity_addtocart_button_class' => '',
      'quantity_addtocart_message_wrapper_class' => '',
      'quantity_addtocart_quantity_wrapper_container_class' => '',
      'quantity_addtocart_quantity_textfield_class' => '',
      'quantity_addtocart_quantity_label_class' => '',
      'quantity_addtocart_quantity_label_value' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $config = \Drupal::config('basic_cart.settings');
    $addtocart_wrapper_container_class = Html::escape($this->getSetting('quantity_addtocart_wrapper_container_class'));
    $addtocart_button_container_class = Html::escape($this->getSetting('quantity_addtocart_button_container_class'));
    $addtocart_button_class = Html::escape($this->getSetting('quantity_addtocart_button_class'));
    $addtocart_message_wrapper_class = Html::escape($this->getSetting('quantity_addtocart_message_wrapper_class'));
    $addtocart_quantity_wrapper_container_class = Html::escape($this->getSetting('quantity_addtocart_quantity_wrapper_container_class'));
    $addtocart_quantity_textfield_class = Html::escape($this->getSetting('quantity_addtocart_quantity_textfield_class'));
    $addtocart_quantity_label_class = Html::escape($this->getSetting('quantity_addtocart_quantity_label_class'));
    $addtocart_quantity_label_value = t(Html::escape($this->getSetting('quantity_addtocart_quantity_label_value')));

    $entity = $items->getEntity();
    $config = \Drupal::config('basic_cart.settings');
    $elements = array();

    $option = [
      'query' => ['entitytype' => $entity->getEntityTypeId(), 'quantity' => ''],
      'absolute' => TRUE,
    ];

    if (trim($config->get('add_to_cart_redirect')) != "<none>" && trim($config->get('add_to_cart_redirect')) != "") {
      $url = Url::fromRoute('basic_cart.cartadddirect', ["nid" => $entity->id()], $option);
      $link = '<a id="forquantitydynamictext_' . $entity->id() . '" class="basic_cart-get-quantity button ' . $addtocart_button_class . '" href="' . $url->toString() . '">' . $this->t($config->get('add_to_cart_button')) . '</a>';
    }
    else {
      $url = Url::fromRoute('basic_cart.cartadd', ["nid" => $entity->id()], $option);
      $link = '<a id="forquantitydynamictext_' . $entity->id() . '" class="basic_cart-get-quantity button use-basic_cart-ajax ' . $addtocart_button_class . '" href="' . $url->toString() . '">' . $this->t($config->get('add_to_cart_button')) . '</a>';
    }

    $quantity_content = $config->get('quantity_status') ? '<div id="quantity-wrapper_' . $entity->id() . '" class="addtocart-quantity-wrapper-container ' . $addtocart_quantity_wrapper_container_class . '"></div>' : '';

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'container',
        '#attributes' => ['class' => 'ajax-addtocart-wrapper ' . $addtocart_message_wrapper_class , 'id' => 'ajax-addtocart-message-' . $entity->id()],
        '#prefix' => '<div class="addtocart-wrapper-container ' . $addtocart_wrapper_container_class . '">' . $quantity_content . '<div class="addtocart-link-class ' . $addtocart_button_container_class . '">' . $link . "</div>",
        '#suffix' => '</div>',
      ];
    }

    $elements['#attached']['library'][] = 'core/drupal.ajax';
    $elements['#attached']['drupalSettings']['basic_cart']['textfield_class'] = $addtocart_quantity_textfield_class;
    $elements['#attached']['drupalSettings']['basic_cart']['label_class'] = $addtocart_quantity_label_class;
    $elements['#attached']['drupalSettings']['basic_cart']['label_value'] = $addtocart_quantity_label_value;
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element = array();
    $element['quantity_addtocart_wrapper_container_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to cart wrapper class (css)'),
      '#default_value' => $this->getSetting('quantity_addtocart_wrapper_container_class'),
    );
    $element['quantity_addtocart_button_container_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to cart button container class (css)'),
      '#default_value' => $this->getSetting('quantity_addtocart_button_container_class'),
    );
    $element['quantity_addtocart_button_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to cart button class (css)'),
      '#default_value' => $this->getSetting('quantity_addtocart_button_class'),
    );
    $element['quantity_addtocart_message_wrapper_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to cart button class (css)'),
      '#default_value' => $this->getSetting('quantity_addtocart_message_wrapper_class'),
    );
    $element['quantity_addtocart_quantity_wrapper_container_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Quantity wrapper class (css)'),
      '#default_value' => $this->getSetting('quantity_addtocart_quantity_wrapper_container_class'),
    );
    $element['quantity_addtocart_quantity_textfield_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Quantity textfield class (css)'),
      '#default_value' => $this->getSetting('quantity_addtocart_quantity_textfield_class'),
    );
    $element['quantity_addtocart_quantity_label_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Quantity label class (css)'),
      '#default_value' => $this->getSetting('quantity_addtocart_quantity_label_class'),
    );
    $element['quantity_addtocart_quantity_label_value'] = array(
      '#type' => 'textfield',
      '#title' => t('Quantity label value'),
      '#default_value' => $this->getSetting('quantity_addtocart_quantity_label_value'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[]['#markup'] = t('Custom css classes for add to cart');
    return $summary;
  }

}
