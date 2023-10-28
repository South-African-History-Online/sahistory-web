<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for setting a value or default value.
 *
 * @Tamper(
 *   id = "default_value",
 *   label = @Translation("Set value or default value"),
 *   description = @Translation("Set value or default value."),
 *   category = "Text"
 * )
 */
class DefaultValue extends TamperBase {

  const SETTING_DEFAULT_VALUE = 'default_value';
  const SETTING_ONLY_IF_EMPTY = 'only_if_empty';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_DEFAULT_VALUE] = '';
    $config[self::SETTING_ONLY_IF_EMPTY] = FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_DEFAULT_VALUE] = [
      '#type' => 'textarea',
      '#title' => $this->t('Value'),
      '#default_value' => $this->getSetting(self::SETTING_DEFAULT_VALUE),
      '#description' => $this->t('This field will be set to the value specified.'),
    ];

    $form[self::SETTING_ONLY_IF_EMPTY] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only if empty'),
      '#default_value' => $this->getSetting(self::SETTING_ONLY_IF_EMPTY),
      '#description' => $this->t('This field will be set to the value specified only if the imported field is empty.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([self::SETTING_DEFAULT_VALUE => $form_state->getValue(self::SETTING_DEFAULT_VALUE)]);
    $this->setConfiguration([self::SETTING_ONLY_IF_EMPTY => $form_state->getValue(self::SETTING_ONLY_IF_EMPTY)]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    // Setting a default value.
    $only_if_empty = $this->getSetting(self::SETTING_ONLY_IF_EMPTY);
    if (!empty($only_if_empty) && !$data) {
      $data = $this->getSetting(self::SETTING_DEFAULT_VALUE);
    }
    elseif (empty($only_if_empty)) {
      $data = $this->getSetting(self::SETTING_DEFAULT_VALUE);
    }

    return $data;
  }

}
