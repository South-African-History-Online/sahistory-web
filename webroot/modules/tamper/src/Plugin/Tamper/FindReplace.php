<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the find_replace plugin.
 *
 * @Tamper(
 *   id = "find_replace",
 *   label = @Translation("Find replace"),
 *   description = @Translation("Find and replace text"),
 *   category = "Text"
 * )
 */
class FindReplace extends TamperBase {

  const SETTING_FIND = 'find';
  const SETTING_REPLACE = 'replace';
  const SETTING_CASE_SENSITIVE = 'case_sensitive';
  const SETTING_WORD_BOUNDARIES = 'word_boundaries';
  const SETTING_WHOLE = 'whole';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_FIND] = '';
    $config[self::SETTING_REPLACE] = '';
    $config[self::SETTING_CASE_SENSITIVE] = FALSE;
    $config[self::SETTING_CASE_SENSITIVE] = FALSE;
    $config[self::SETTING_WHOLE] = FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_FIND] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to find'),
      '#default_value' => $this->getSetting(self::SETTING_FIND),
    ];

    $form[self::SETTING_REPLACE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to replace'),
      '#default_value' => $this->getSetting(self::SETTING_REPLACE),
    ];

    $form[self::SETTING_CASE_SENSITIVE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Case sensitive'),
      '#default_value' => $this->getSetting(self::SETTING_CASE_SENSITIVE),
      '#description' => $this->t('If checked, "book" will match "book" but not "Book" or "BOOK".'),
    ];

    $form[self::SETTING_WORD_BOUNDARIES] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Respect word boundaries'),
      '#default_value' => $this->getSetting(self::SETTING_WORD_BOUNDARIES),
      '#description' => $this->t('If checked, "book" will match "book" but not "bookcase".'),
    ];

    $form[self::SETTING_WHOLE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Match whole word/phrase'),
      '#default_value' => $this->getSetting(self::SETTING_WHOLE),
      '#description' => $this->t('If checked, then the whole word or phrase will be matched, e.g. "book" will match "book" but not "the book". If this option is selected then "Respect word boundaries" above will be ignored.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_FIND => $form_state->getValue(self::SETTING_FIND),
      self::SETTING_REPLACE => $form_state->getValue(self::SETTING_REPLACE),
      self::SETTING_CASE_SENSITIVE => $form_state->getValue(self::SETTING_CASE_SENSITIVE),
      self::SETTING_WORD_BOUNDARIES => $form_state->getValue(self::SETTING_WORD_BOUNDARIES),
      self::SETTING_WHOLE => $form_state->getValue(self::SETTING_WHOLE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    $function = $this->getFunction();
    $find = $this->useRegex() ? $this->getRegexPattern() : $this->getSetting(self::SETTING_FIND);
    $replace = $this->getSetting(self::SETTING_REPLACE);

    return $function($find, $replace, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }

  /**
   * Check if we are using the regex callback.
   *
   * @return bool
   *   TRUE when regex will be used.
   */
  protected function useRegex() {
    return $this->getSetting(self::SETTING_WORD_BOUNDARIES) || $this->getSetting(self::SETTING_WHOLE);
  }

  /**
   * Get the function to use for the find and replace.
   *
   * @return string
   *   Function name to call.
   */
  protected function getFunction() {
    if ($this->useRegex()) {
      return 'preg_replace';
    }
    return $this->getSetting(self::SETTING_CASE_SENSITIVE) ? 'str_replace' : 'str_ireplace';
  }

  /**
   * Get the regex pattern.
   *
   * @return string
   *   Regex pattern to use.
   */
  protected function getRegexPattern() {
    $regex = $this->getSetting(self::SETTING_WHOLE) ?
      '/^' . preg_quote($this->getSetting(self::SETTING_FIND), '/') . '$/u' :
      '/\b' . preg_quote($this->getSetting(self::SETTING_FIND), '/') . '\b/u';;

    if (!$this->getSetting(self::SETTING_CASE_SENSITIVE)) {
      $regex .= 'i';
    }
    return $regex;
  }

}
