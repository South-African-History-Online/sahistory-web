<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the Truncate Text plugin.
 *
 * @Tamper(
 *   id = "truncate_text",
 *   label = @Translation("Truncate"),
 *   description = @Translation("Truncate"),
 *   category = "Text"
 * )
 */
class TruncateText extends TamperBase {

  const SETTING_NUM_CHAR = 'num_char';
  const SETTING_ELLIPSE = 'ellipses';
  const SETTING_WORDSAFE = 'wordsafe';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_NUM_CHAR] = 0;
    $config[self::SETTING_ELLIPSE] = FALSE;
    $config[self::SETTING_WORDSAFE] = FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_NUM_CHAR] = [
      '#type' => 'number',
      '#title' => $this->t('Number of characters'),
      '#default_value' => $this->getSetting(self::SETTING_NUM_CHAR),
      '#description' => $this->t('The number of characters the text will be limited to.'),
    ];
    $form[self::SETTING_ELLIPSE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ellipses'),
      '#default_value' => $this->getSetting(self::SETTING_ELLIPSE),
      '#description' => $this->t('Add ellipses (…) to the end of the truncated text.'),
    ];
    $form[self::SETTING_WORDSAFE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Truncate on a word boundary'),
      '#default_value' => $this->getSetting(self::SETTING_WORDSAFE),
      '#description' => $this->t('Attempt to truncate on a word boundary.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_NUM_CHAR => $form_state->getValue(self::SETTING_NUM_CHAR),
      self::SETTING_ELLIPSE => $form_state->getValue(self::SETTING_ELLIPSE),
      self::SETTING_WORDSAFE => $form_state->getValue(self::SETTING_WORDSAFE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    return Unicode::truncate(
      $data,
      $this->getSetting(self::SETTING_NUM_CHAR),
      $this->getSetting(self::SETTING_WORDSAFE),
      $this->getSetting(self::SETTING_ELLIPSE)
    );
  }

}
