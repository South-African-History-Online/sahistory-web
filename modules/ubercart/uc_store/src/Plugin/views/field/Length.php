<?php

namespace Drupal\uc_store\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler to provide formatted lengths.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("uc_length")
 */
class Length extends NumericField {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['format'] = array('default' => 'uc_length');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['format'] =  array(
      '#title' => $this->t('Format'),
      '#type' => 'radios',
      '#options' => array(
        'uc_weight' => $this->t('Ubercart length'),
        'numeric' => $this->t('Numeric'),
      ),
      '#default_value' => $this->options['format'],
      '#weight' => -1,
    );

    foreach (array('separator', 'format_plural', 'prefix', 'suffix') as $field) {
      $form[$field]['#states']['visible']['input[name="options[format]"]']['value'] = 'numeric';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if ($this->options['format'] == 'uc_length') {
      $value = $this->getValue($values);

      if (is_null($value) || ($value == 0 && $this->options['empty_zero'])) {
        return '';
      }

      return uc_length_format($value, $values->{$this->aliases['length_units']});
    }
    else {
      return parent::render($values);
    }
  }
}
