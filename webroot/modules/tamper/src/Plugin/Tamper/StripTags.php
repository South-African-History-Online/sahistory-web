<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for stripping tags.
 *
 * @Tamper(
 *   id = "strip_tags",
 *   label = @Translation("Strip tags"),
 *   description = @Translation("Strip tags."),
 *   category = "Text"
 * )
 */
class StripTags extends TamperBase {

  const SETTING_ALLOWED_TAGS = 'allowed_tags';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_ALLOWED_TAGS] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_ALLOWED_TAGS] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed tags'),
      '#default_value' => $this->getSetting(self::SETTING_ALLOWED_TAGS),
      '#description' => $this->t('A list of allowed tags such as %a%b', ['%a' => '<a>', '%b' => '<em>']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $allowed_tags = trim($form_state->getValue(self::SETTING_ALLOWED_TAGS));
    $this->setConfiguration([self::SETTING_ALLOWED_TAGS => $allowed_tags]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    $allowed_tags = $this->getSetting(self::SETTING_ALLOWED_TAGS);

    return strip_tags($data, $allowed_tags);
  }

}
