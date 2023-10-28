<?php

namespace Drupal\basic_cart\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'addtocart' formatter.
 *
 * @FieldFormatter(
 *   id = "addtocart",
 *   module = "basic_cart",
 *   label = @Translation("Add to cart"),
 *   field_types = {
 *     "addtocart"
 *   }
 * )
 */
class AddToCartFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'addtocart_wrapper_container_class' => '',
      'addtocart_button_container_class' => '',
      'addtocart_button_class' => '',
      'addtocart_message_wrapper_class' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    if ($entity->get('add_to_cart')->getValue()[0]['value'] == 1) {
      $addtocart_wrapper_container_class = Html::escape($this->getSetting('addtocart_wrapper_container_class'));
      $addtocart_button_container_class = Html::escape($this->getSetting('addtocart_button_container_class'));
      $addtocart_button_class = Html::escape($this->getSetting('addtocart_button_class'));
      $addtocart_message_wrapper_class = Html::escape($this->getSetting('addtocart_message_wrapper_class'));

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
      foreach ($items as $delta => $item) {
        $elements[$delta] = [
          '#type' => 'container',
          '#attributes' => ['class' => 'ajax-addtocart-wrapper ' . $addtocart_message_wrapper_class , 'id' => 'ajax-addtocart-message-' . $entity->id()],
          '#prefix' => '<div class="addtocart-wrapper-container ' . $addtocart_wrapper_container_class . '"><div class="addtocart-link-class ' . $addtocart_button_container_class . '">' . $link . "</div>",
          '#suffix' => '</div>',
        ];
      }
    }

    $elements['#attached']['library'][] = 'core/drupal.ajax';
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element = array();
    $element['addtocart_wrapper_container_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to cart wrapper class (css)'),
      '#default_value' => $this->getSetting('addtocart_wrapper_container_class'),
    );
    $element['addtocart_button_container_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to cart button container class (css)'),
      '#default_value' => $this->getSetting('addtocart_button_container_class'),
    );
    $element['addtocart_button_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to cart button class (css)'),
      '#default_value' => $this->getSetting('addtocart_button_class'),
    );
    $element['addtocart_message_wrapper_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Add to cart button class (css)'),
      '#default_value' => $this->getSetting('addtocart_message_wrapper_class'),
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
