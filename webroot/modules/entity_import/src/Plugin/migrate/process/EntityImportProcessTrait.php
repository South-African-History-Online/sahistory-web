<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define entity import process.
 */
trait EntityImportProcessTrait {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfigurations() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Ajax process callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An array of the form state.
   */
  public function ajaxProcessCallback(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();

    return NestedArray::getValue(
      $form, array_splice($trigger['#array_parents'], 0, -1)
    );
  }

  /**
   * Get the form state value.
   *
   * @param $property
   *   The form property key, either a string or an array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param null $default_value
   *   The default value.
   *
   * @return mixed|null
   *   The form state value.
   */
  protected function getFormStateValue(
    $property,
    FormStateInterface $form_state,
    $default_value = NULL
  ) {
    if (!is_array($property)) {
      $property = [$property];
    }
    $config = $this->getConfiguration();
    $value = $form_state->hasValue($property)
      ? $form_state->getValue($property)
      : NestedArray::getValue($config, $property);

    if (!isset($value) || empty($value)) {
      return $default_value;
    }

    return $value;
  }

  /**
   * Get process configuration.
   *
   * @return array
   *   An array of process configuration.
   */
  public function getConfiguration() {
    return $this->configuration + $this->defaultConfigurations();
  }
}
