<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for url encode.
 *
 * @Tamper(
 *   id = "url_encode",
 *   label = @Translation("URL Encode"),
 *   description = @Translation("Run values through the <a href='http://us3.php.net/urlencode'>urlencode()</a> function."),
 *   category = "Text"
 * )
 */
class UrlEncode extends TamperBase {

  const SETTING_METHOD = 'method';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_METHOD] = 'rawurlencode';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_METHOD] = [
      '#type' => 'radios',
      '#title' => $this->t('Encode method'),
      '#options' => $this->getOptions(),
      '#default_value' => $this->getSetting(self::SETTING_METHOD),
      '#description' => $this->t('Run values through the <a href="http://us3.php.net/urlencode">urlencode()</a> function.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([self::SETTING_METHOD => $form_state->getValue(self::SETTING_METHOD)]);
  }

  /**
   * Get the urlencode options.
   *
   * @return array
   *   List of options, keyed by url encode function.
   */
  protected function getOptions() {
    return [
      'rawurlencode' => $this->t('Raw'),
      'urlencode' => $this->t('Legacy: Encodes spaces into + symbols.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }
    $operation = $this->getSetting(self::SETTING_METHOD);
    return call_user_func($operation, $data);
  }

}
