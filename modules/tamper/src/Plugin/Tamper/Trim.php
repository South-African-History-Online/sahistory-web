<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for trimming text.
 *
 * Removes text and whitespace from the beginning, middle or both sides of text.
 *
 * @Tamper(
 *   id = "trim",
 *   label = @Translation("Characters to trim"),
 *   description = @Translation("Characters to trim."),
 *   category = "Text"
 * )
 */
class Trim extends TamperBase {

  const SETTING_CHARACTER = 'character';
  const SETTING_SIDE = 'side';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_CHARACTER] = '';
    $config[self::SETTING_SIDE] = 'trim';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_CHARACTER] = [
      '#type' => 'textfield',
      '#title' => $this->t('Characters to trim'),
      '#default_value' => $this->getSetting(self::SETTING_CHARACTER),
      '#description' => $this->t('The characters to remove from the string. If blank, then whitespace will be removed.'),
    ];
    $form[self::SETTING_SIDE] = [
      '#type' => 'select',
      '#title' => $this->t('Side'),
      '#options' => $this->getOptions(),
      '#default_value' => $this->getSetting(self::SETTING_SIDE),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([self::SETTING_CHARACTER => $form_state->getValue(self::SETTING_CHARACTER)]);
    $this->setConfiguration([self::SETTING_SIDE => $form_state->getValue(self::SETTING_SIDE)]);
  }

  /**
   * Get the trim options.
   *
   * @return array
   *   List of options, keyed by trim function.
   */
  protected function getOptions() {
    return [
      'trim' => $this->t('Both'),
      'ltrim' => $this->t('Left'),
      'rtrim' => $this->t('Right'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }
    $operation = $this->getSetting(self::SETTING_SIDE);

    $mask = $this->getSetting(self::SETTING_CHARACTER);

    if (!empty($mask)) {
      return call_user_func($operation, $data, $mask);
    }
    else {
      return call_user_func($operation, $data);
    }

  }

}
