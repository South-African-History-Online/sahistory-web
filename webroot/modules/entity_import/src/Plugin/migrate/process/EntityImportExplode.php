<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Explode;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "entity_import_explode",
 *   label = @Translation("Explode")
 * )
 */
class EntityImportExplode extends Explode implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfigurations() {
    return [
      'limit' => NULL,
      'strict' => TRUE,
      'delimiter' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delimiter'),
      '#description' => $this->t('Input the boundary string.'),
      '#size' => 10,
      '#required' => TRUE,
      '#default_value' => $configuration['delimiter'],
    ];
    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#size' => 5,
      '#default_value' => $configuration['limit'],
    ];
    $form['strict'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict'),
      '#description' => $this->t('The source should be strictly a string.'),
      '#default_value' => $configuration['strict']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    return array_map('trim', $value);
  }
}
