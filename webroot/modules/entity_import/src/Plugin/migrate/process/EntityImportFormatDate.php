<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\Plugin\migrate\process\FormatDate;

/**
 * Define the entity import format date migration process.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_format_date",
 *   label = @Translation("Format Date")
 * )
 */
class EntityImportFormatDate extends FormatDate implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfigurations() {
    return [
      'from_format' => NULL,
      'from_timezone' => NULL,
      'to_format' => NULL,
      'from_timezone' => NULL,
      'settings' => [
        'validate_format' => FALSE
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['from_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From Format'),
      '#description' => $this->t('The source format.'),
      '#default_value' => $configuration['from_format'],
      '#required' => TRUE,
    ];
    $form['from_timezone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('From Timezone'),
      '#description' => $this->t('String identifying the required source time zone.'),
      '#default_value' => $configuration['from_timezone']
    ];
    $form['to_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To Format'),
      '#description' => $this->t('The destination format.'),
      '#default_value' => $configuration['to_format'],
      '#required' => TRUE,
    ];
    $form['to_timezone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To Timezone'),
      '#description' => $this->t('String identifying the required source time zone.'),
      '#default_value' => $configuration['to_timezone']
    ];
    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
      '#tree' => TRUE,
    ];
    $form['settings']['validate_format'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Validate Format'),
      '#default_value' => $configuration['settings']['validate_format']
    ];

    return $form;
  }
}
