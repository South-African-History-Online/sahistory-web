<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the Str Pad plugin.
 *
 * @Tamper(
 *   id = "str_pad",
 *   label = @Translation("Pad a string"),
 *   description = @Translation("Pad a string"),
 *   category = "Text"
 * )
 */
class StrPad extends TamperBase {

  const SETTING_PAD_LENGTH = 'pad_length';
  const SETTING_PAD_STRING = 'pad_string';
  const SETTING_PAD_TYPE = 'pad_type';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_PAD_LENGTH] = 10;
    $config[self::SETTING_PAD_STRING] = ' ';
    $config[self::SETTING_PAD_TYPE] = STR_PAD_RIGHT;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_PAD_LENGTH] = [
      '#type' => 'number',
      '#title' => $this->t('Pad length'),
      '#default_value' => $this->getSetting(self::SETTING_PAD_LENGTH),
      '#description' => $this->t('If the input value has a length less than this, it will use the string below to increase the length.'),
    ];
    $form[self::SETTING_PAD_STRING] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pad string'),
      '#default_value' => $this->getSetting(self::SETTING_PAD_STRING),
      '#description' => $this->t('The string to use for padding. If blank, a space will be used.'),
    ];
    $form[self::SETTING_PAD_TYPE] = [
      '#type' => 'radios',
      '#title' => $this->t('Pad type'),
      '#options' => $this->getOptions(),
      '#default_value' => $this->getSetting(self::SETTING_PAD_TYPE),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_PAD_LENGTH => $form_state->getValue(self::SETTING_PAD_LENGTH),
      self::SETTING_PAD_STRING => $form_state->getValue(self::SETTING_PAD_STRING),
      self::SETTING_PAD_TYPE => $form_state->getValue(self::SETTING_PAD_TYPE),
    ]);
  }

  /**
   * Get the Strpad options.
   *
   * @return array
   *   List of options, keyed by Strpad function.
   */
  protected function getOptions() {
    return [
      STR_PAD_RIGHT => $this->t('Right'),
      STR_PAD_LEFT => $this->t('Left'),
      STR_PAD_BOTH => $this->t('Both'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    return str_pad($data, $this->getSetting(self::SETTING_PAD_LENGTH), $this->getSetting(self::SETTING_PAD_STRING), $this->getSetting(self::SETTING_PAD_TYPE));
  }

}
