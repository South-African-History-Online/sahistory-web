<?php

namespace Drupal\entity_import\Plugin\migrate\process;

use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\migrate\process\Flatten;

/**
 * Define the entity import flatten process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_import_flatten",
 *   label = @Translation("Flatten")
 * )
 */
class EntityImportFlatten extends Flatten implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state
  ) {
    $form['#markup'] = $this->t(
      'The flatten process plugin has no configuration settings.'
    );

    return $form;
  }
}
