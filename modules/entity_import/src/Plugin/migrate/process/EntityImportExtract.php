<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\migrate\Plugin\migrate\process\Extract;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define entity import migration extract plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_extract",
 *   label = @Translation("Extract"),
 *   handle_multiples = TRUE
 * )
 */
class EntityImportExtract extends Extract implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfigurations() {
    return [
      'index' => [],
      'default'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['index'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Index'),
      '#required' => TRUE,
      '#default_value' => implode("\n", $configuration['index']),
      '#description' => $this->t('The list of keys to access the value. If 
        multiple, list one key per line.'),
      '#size' => 255,
    ];
    $form['default'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default'),
      '#description' => $this->t('A default value to assign to the destination 
        if the key does not exist.'),
      '#default_value' => $configuration['default']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['index'] = explode("\n", $form_state->getValue('index'));
  }
}
