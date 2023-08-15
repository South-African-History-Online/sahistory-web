<?php

namespace Drupal\uc_product\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to provide payment method.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_product_buy_it_now")
 */
class BuyItNow extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $nid = $this->getValue($values);
    $node = node_load($nid);
    if (uc_product_is_product($node)) {
      $form_object = new \Drupal\uc_product\Form\BuyItNowForm($node->id());
      $form = \Drupal::formBuilder()->getForm($form_object, $node);
      return drupal_render($form);
    }
  }

}
