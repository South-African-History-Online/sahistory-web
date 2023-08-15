<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the explode plugin.
 *
 * @Tamper(
 *   id = "explode",
 *   label = @Translation("Explode"),
 *   description = @Translation("Break up sequenced data into an array"),
 *   category = "List"
 * )
 */
class Explode extends TamperBase {

  const SETTING_SEPARATOR = 'separator';
  const SETTING_LIMIT = 'limit';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_SEPARATOR] = ',';
    $config[self::SETTING_LIMIT] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_SEPARATOR] = [
      '#type' => 'textfield',
      '#title' => $this->t('String separator'),
      '#default_value' => $this->getSetting(self::SETTING_SEPARATOR),
      '#description' => $this->t('This will break up sequenced data into an
      array. For example, "a, b, c" would get broken up into the array(\'a\',
      \'b\', \'c\'). A space can be represented by %s, tabs by %t, and newlines
      by %n.'),
    ];

    $form[self::SETTING_LIMIT] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#default_value' => $this->getSetting(self::SETTING_LIMIT),
      '#description' => $this->t('If limit is set and positive, the returned
        items will contain a maximum of limit with the last item containing the
        rest of string. If limit is negative, all components except the last
        -limit are returned. If the limit parameter is zero, then this is
        treated as 1. If limit is not set, then there will be no limit on the
        number of items returned.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_SEPARATOR => $form_state->getValue(self::SETTING_SEPARATOR),
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
    $separator = str_replace(['%s', '%t', '%n'], [' ', "\t", "\n"], $this->getSetting(self::SETTING_SEPARATOR));
    $limit = is_numeric($this->getSetting(self::SETTING_LIMIT)) ? $this->getSetting(self::SETTING_LIMIT) : PHP_INT_MAX;
    return explode($separator, $data, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
