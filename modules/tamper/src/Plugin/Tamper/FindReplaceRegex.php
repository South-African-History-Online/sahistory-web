<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the Find replace REGEX plugin.
 *
 * @Tamper(
 *   id = "find_replace_regex",
 *   label = @Translation("Find replace REGEX"),
 *   description = @Translation("Find replace REGEX"),
 *   category = "Text"
 * )
 */
class FindReplaceRegex extends TamperBase {

  const SETTING_FIND = 'find';
  const SETTING_REPLACE = 'replace';
  const SETTING_LIMIT = 'limit';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_FIND] = '';
    $config[self::SETTING_REPLACE] = '';
    $config[self::SETTING_LIMIT] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_FIND] = [
      '#type' => 'textfield',
      '#title' => $this->t('REGEX to find'),
      '#default_value' => $this->getSetting(self::SETTING_FIND),
      '#description' => $this->t('A regular expression in the form: /<your regex here>/'),
      '#maxlength' => 1024,
    ];

    $form[self::SETTING_REPLACE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replacement pattern'),
      '#default_value' => $this->getSetting(self::SETTING_REPLACE),
      '#description' => $this->t('The replacement pattern.'),
      '#maxlength' => 1024,
    ];

    $form[self::SETTING_LIMIT] = [
      '#type' => 'number',
      '#title' => $this->t('Limit number of replacements'),
      '#default_value' => $this->getSetting(self::SETTING_LIMIT),
      '#description' => $this->t('This sets an optional limit. Leave it blank for no limit.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Test the regex.
    $test = @preg_replace($form_state->getValue(self::SETTING_FIND), '', 'asdfsadf');
    if ($test === NULL) {
      $form_state->setErrorByName(self::SETTING_FIND, $this->t('Invalid regular expression.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_FIND => $form_state->getValue(self::SETTING_FIND),
      self::SETTING_REPLACE => $form_state->getValue(self::SETTING_REPLACE),
      self::SETTING_LIMIT => $form_state->getValue(self::SETTING_LIMIT),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }
    $find = $this->getSetting(self::SETTING_FIND);
    $replace = $this->getSetting(self::SETTING_REPLACE);
    $limit = $this->getSetting(self::SETTING_LIMIT);
    if (empty($limit)) {
      $limit = '-1';
    }
    return preg_replace($find, $replace, $data, $limit);
  }

}
