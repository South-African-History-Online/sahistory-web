<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for rewriting a value.
 *
 * @Tamper(
 *   id = "rewrite",
 *   label = @Translation("Rewrite"),
 *   description = @Translation("Rewrite a field using tokens."),
 *   category = "Other"
 * )
 */
class Rewrite extends TamperBase {

  const SETTING_TEXT = 'text';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_TEXT] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_TEXT] = [
      '#type' => 'textarea',
      '#title' => $this->t('Replacement pattern'),
      '#default_value' => $this->getSetting(self::SETTING_TEXT),
    ];

    $replace = [];
    foreach ($this->sourceDefinition->getList() as $source) {
      $replace[] = '[' . $source . ']';
    }

    $form['help'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available replacement patterns'),
      'list' => [
        '#theme' => 'item_list',
        '#items' => $replace,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (is_null($item)) {
      // Nothing to rewrite.
      return $data;
    }

    $trans = [];

    foreach ($item->getSource() as $key => $value) {
      $trans['[' . $key . ']'] = is_array($value) ? reset($value) : $value;
    }

    return strtr($this->getSetting(self::SETTING_TEXT), $trans);
  }

}
