<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\Plugin\migrate\process\MachineName;

/**
 * Define the entity import machine name plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_machine_name",
 *   label = @Translation("Machine Name")
 * )
 */
class EntityImportMachineName extends MachineName implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#markup'] = $this->t('The "@label" process plugin has no configurations.', [
      '@label' => $this->getLabel()
    ]);
    return $form;
  }
}
