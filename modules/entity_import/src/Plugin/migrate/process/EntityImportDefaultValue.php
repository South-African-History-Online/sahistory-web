<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\Plugin\migrate\process\DefaultValue;

/**
 * Define entity import migration lookup.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_default_value",
 *   label = @Translation("Default Value")
 * )
 */
class EntityImportDefaultValue extends DefaultValue implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfigurations() {
    return [
      'default_value' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['default_value'] = [
      '#type' => 'textfield',
      '#title'=> $this->t('Default Value'),
      '#required' => TRUE,
      '#default_value' => $configuration['default_value'],
      '#size' => 25
    ];

    return $form;
  }
}
