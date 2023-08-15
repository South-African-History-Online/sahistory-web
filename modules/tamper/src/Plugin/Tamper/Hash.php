<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation of the hash plugin.
 *
 * @Tamper(
 *   id = "hash",
 *   label = @Translation("Hash"),
 *   description = @Translation("Makes the value a hash of the values of item being tampered."),
 *   category = "Other"
 * )
 */
class Hash extends TamperBase {

  const SETTING_OVERRIDE = 'override';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_OVERRIDE] = FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_OVERRIDE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override set value'),
      '#description' => $this->t('If checked, the existing value of this field will be overridden.'),
      '#default_value' => $this->getSetting(self::SETTING_OVERRIDE),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_OVERRIDE => $form_state->getValue(self::SETTING_OVERRIDE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    if (NULL === $item) {
      throw new TamperException('Tamperable item can not be null.');
    }

    if (empty($data) || $this->getSetting(self::SETTING_OVERRIDE)) {
      $values = $item->getSource();
      return md5(serialize($values));
    }

    return $data;
  }

}
