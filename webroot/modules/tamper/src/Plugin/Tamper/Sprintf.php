<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the Sprintf plugin.
 *
 * @Tamper(
 *   id = "sprintf",
 *   label = @Translation("Format string"),
 *   description = @Translation("Format string"),
 *   category = "Text"
 * )
 */
class Sprintf extends TamperBase {

  const SETTING_TEXT_FORMAT = 'format';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_TEXT_FORMAT] = '%s';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_TEXT_FORMAT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format'),
      '#default_value' => $this->getSetting(self::SETTING_TEXT_FORMAT),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#description' => $this->t('See the <a href="@url">sprintf</a> documentation for more details.', ['@url' => 'http://www.php.net/manual/en/function.sprintf.php']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_TEXT_FORMAT => $form_state->getValue(self::SETTING_TEXT_FORMAT),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }
    return sprintf($this->getSetting(self::SETTING_TEXT_FORMAT), $data);
  }

}
