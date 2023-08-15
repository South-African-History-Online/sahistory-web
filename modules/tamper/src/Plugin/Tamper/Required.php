<?php

namespace Drupal\tamper\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\SkipTamperItemException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;

/**
 * Plugin implementation for required values.
 *
 * @Tamper(
 *   id = "required",
 *   label = @Translation("Required"),
 *   description = @Translation("Make this field required. If it is empty, the item will not be processed."),
 *   category = "Filter",
 *   handle_multiples = TRUE
 * )
 */
class Required extends TamperBase {

  const SETTING_INVERT = 'invert';

  /**
   * Flag indicating whether there are multiple values.
   *
   * @var bool
   */
  protected $multiple = FALSE;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_INVERT] = FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SETTING_INVERT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Invert filter'),
      '#default_value' => $this->getSetting(self::SETTING_INVERT),
      '#description' => $this->t('Inverting the filter will save items only if the field is empty.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([self::SETTING_INVERT => (bool) $form_state->getValue(self::SETTING_INVERT)]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    $this->multiple = is_array($data);

    $invert = $this->getSetting(self::SETTING_INVERT);

    if (!$invert && empty($data)) {
      throw new SkipTamperItemException('Item is empty.');
    }

    if ($invert && !empty($data)) {
      throw new SkipTamperItemException('Item is not empty.');
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

}
