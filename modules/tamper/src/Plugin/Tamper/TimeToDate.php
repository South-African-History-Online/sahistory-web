<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the TimetoDate plugin.
 *
 * @Tamper(
 *   id = "timetodate",
 *   label = @Translation("Unix timestamp to Date"),
 *   description = @Translation("Unix timestamp to Date"),
 *   category = "Date/time"
 * )
 */
class TimeToDate extends TamperBase {

  const SETTING_DATE_FORMAT = 'date_format';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_DATE_FORMAT] = "\I\\t'\s g \o'\c\l\o\c\k \J\i\m\.";
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_DATE_FORMAT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date format'),
      '#default_value' => $this->getSetting(self::SETTING_DATE_FORMAT),
      '#description' => $this->t('A user-defined php date format string like "m-d-Y H:i". See the <a href="@link">PHP manual</a> for available options.', ['@link' => 'http://www.php.net/manual/function.date.php']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_DATE_FORMAT => $form_state->getValue(self::SETTING_DATE_FORMAT),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_numeric($data)) {
      throw new TamperException('Input should be numeric.');
    }
    return date($this->getSetting(self::SETTING_DATE_FORMAT), $data);
  }

}
