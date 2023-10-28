<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for number format.
 *
 * @Tamper(
 *   id = "number_format",
 *   label = @Translation("Format a number"),
 *   description = @Translation("Format a number."),
 *   category = "Number"
 * )
 */
class NumberFormat extends TamperBase {

  const SETTING_DECIMALS = 'decimals';
  const SETTING_DEC_POINT = 'dec_point';
  const SETTING_THOUSANDS_SEP = 'thousands_sep';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_DECIMALS] = 0;
    $config[self::SETTING_DEC_POINT] = '.';
    $config[self::SETTING_THOUSANDS_SEP] = ',';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_DECIMALS] = [
      '#type' => 'number',
      '#title' => $this->t('Decimals'),
      '#default_value' => $this->getSetting(self::SETTING_DECIMALS),
      '#description' => $this->t('The number of decimal places.'),
    ];

    $form[self::SETTING_DEC_POINT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Decimal point'),
      '#default_value' => $this->getSetting(self::SETTING_DEC_POINT),
      '#description' => $this->t('The character to use as the decimal point.'),
    ];

    $form[self::SETTING_THOUSANDS_SEP] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thousands separator'),
      '#default_value' => $this->getSetting(self::SETTING_THOUSANDS_SEP),
      '#description' => $this->t('The character to use as the thousands separator.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_DECIMALS => $form_state->getValue(self::SETTING_DECIMALS),
      self::SETTING_DEC_POINT => $form_state->getValue(self::SETTING_DEC_POINT),
      self::SETTING_THOUSANDS_SEP => $form_state->getValue(self::SETTING_THOUSANDS_SEP),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_numeric($data)) {
      throw new TamperException('Input should be numeric.');
    }
    $decimals = $this->getSetting(self::SETTING_DECIMALS);
    $dec_point = $this->getSetting(self::SETTING_DEC_POINT);
    $thousands_sep = $this->getSetting(self::SETTING_THOUSANDS_SEP);

    return number_format($data, $decimals, $dec_point, $thousands_sep);
  }

}
