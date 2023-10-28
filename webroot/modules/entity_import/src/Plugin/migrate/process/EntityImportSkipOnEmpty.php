<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\Plugin\migrate\process\SkipOnEmpty;

/**
 * Class EntityImportSkipOnEmpty
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_skip_on_empty",
 *   label = @Translation("Skip On Empty")
 * )
 */
class EntityImportSkipOnEmpty extends SkipOnEmpty implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfigurations() {
    return [
      'method' => NULL,
      'message' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#description' => $this->t('Select the action to do if the input value is empty.'),
      '#options' => [
        'row' => $this->t('Row'),
        'process' => $this->t('Process')
      ],
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $configuration['method'],
    ];
    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Input the message to be logged.'),
      '#default_value' => $configuration['message']
    ];

    return $form;
  }
}
