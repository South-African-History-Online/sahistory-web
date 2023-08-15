<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Define entity import callback process.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_callback",
 *   label = @Translation("Callback")
 * )
 */
class EntityImportCallback extends ProcessPluginBase implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfigurations() {
    return [
      'callback' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $callable = $this->getFormStateValue('callable', $form_state);

    $form['callable'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callable'),
      '#required' => TRUE,
      '#default_value' => is_array($callable)
        ? implode('::', $callable)
        : $callable,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $callable = $form_state->getValue('callable');

    if (!is_callable($callable)) {
      $elements = NestedArray::getValue(
        $form_state->getCompleteForm(), $form['#parents']
      );
      $form_state->setError(
        $elements['callable'],
        $this->t('The inputted callback needs to be callable.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['callable'] = $this->getFormatCallback(
      $form_state->getValue('callable')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform(
    $value,
    MigrateExecutableInterface $migrate_executable,
    Row $row,
    $destination_property
  ) {
    $configuration = $this->getConfiguration();

    if (!isset($configuration['callable'])) {
      throw new \InvalidArgumentException('The "callable" must be set.');
    }
    elseif (!is_callable($configuration['callable'])) {
      throw new \InvalidArgumentException('The "callable" must be a valid function or method.');
    }

    return call_user_func($this->configuration['callable'], $value);
  }

  /**
   * Format callback.
   *
   * @param $callback
   *   The string callback.
   *
   * @return array|string
   *   An array with class and method; otherwise a single callback string.
   */
  protected function getFormatCallback($callback) {
    $callback = trim($callback);

    if (strpos($callback, '::') !== FALSE) {
      return explode('::', $callback);
    }

    return $callback;
  }
}
