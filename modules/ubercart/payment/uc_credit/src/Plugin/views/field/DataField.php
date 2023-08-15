<?php

namespace Drupal\uc_credit\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display encrypted credit card data.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_credit_data")
 */
class DataField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // Initialize the encryption key and class.
    $key = uc_credit_encryption_key();
    $crypt = \Drupal::service('uc_store.encryption');
    $data = unserialize($values->{$this->field_alias});
    if (isset($data['cc_data'])) {
      $cc_data = $crypt->decrypt($key, $data['cc_data']);
      if (strpos($cc_data, ':') === FALSE) {
        $cc_data = base64_decode($cc_data);
      }
      $cc_data = unserialize($cc_data);

      if (isset($cc_data[$this->definition['cc field']])) {
        return $cc_data[$this->definition['cc field']];
      }
    }
  }

}
