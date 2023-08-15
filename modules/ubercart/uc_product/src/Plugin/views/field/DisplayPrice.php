<?php

namespace Drupal\uc_product\Plugin\views\field;

use Drupal\uc_store\Plugin\views\field\Price;
use Drupal\views\ResultRow;

/**
 * Field handler to provide formatted display prices.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_product_display_price")
 */
class DisplayPrice extends Price {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['label']['default'] = $this->t('Price');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    $nid = parent::getValue($values, $field);
    if (!is_null($nid)) {
      // @todo Refactor to allow display price to be calculated.
      $node = node_load($nid);
      return $node->price->value;

      // !TODO Refactor so that all variants are loaded at once in the pre_render hook.
      $node = node_view(node_load($nid), 'teaser');
      return $node['display_price']['#value'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clickSort($order) {
    $params = $this->options['group_type'] != 'group' ? array('function' => $this->options['group_type']) : array();
    $this->query->addOrderBy(NULL, NULL, $order, 'price', $params);
  }

}
