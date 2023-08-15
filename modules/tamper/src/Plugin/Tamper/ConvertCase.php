<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for converting case.
 *
 * @Tamper(
 *   id = "convert_case",
 *   label = @Translation("Convert case"),
 *   description = @Translation("Convert case."),
 *   category = "Text"
 * )
 */
class ConvertCase extends TamperBase {

  const SETTING_OPERATION = 'operation';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_OPERATION] = 'ucfirst';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_OPERATION] = [
      '#type' => 'select',
      '#title' => $this->t('How to convert case'),
      '#options' => $this->getOptions(),
      '#default_value' => $this->getSetting(self::SETTING_OPERATION),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([self::SETTING_OPERATION => $form_state->getValue(self::SETTING_OPERATION)]);
  }

  /**
   * Get the case conversion options.
   *
   * @return array
   *   List of options, keyed by method on Drupal's unicode class.
   */
  protected function getOptions() {
    return [
      'strtoupper' => $this->t('Convert to uppercase'),
      'strtolower' => $this->t('Convert to lowercase'),
      'ucfirst' => $this->t('Capitalize the first character'),
      'lcfirst' => $this->t('Convert the first character to lowercase'),
      'ucwords' => $this->t('Capitalize the first character of each word'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }
    $operation = $this->getSetting(self::SETTING_OPERATION);

    switch ($operation) {
      case 'strtoupper':
        return mb_strtoupper($data);

      case 'strtolower':
        return mb_strtolower($data);

      default:
        if (!is_callable(['\Drupal\Component\Utility\Unicode', $operation])) {
          throw new TamperException('Invalid operation ' . $operation);
        }

        return call_user_func(['\Drupal\Component\Utility\Unicode', $operation], $data);
    }
  }

}
