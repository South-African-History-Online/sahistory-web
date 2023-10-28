<?php

namespace Drupal\entity_import\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_import\Entity\EntityImporterInterface;

/**
 * Define entity importer status form.
 */
class EntityImporterStatusForm extends EntityImporterBundleFormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'entity_import_importer_status';
  }

  /**
   * Set the form title.
   *
   * @param \Drupal\entity_import\Entity\EntityImporterInterface $entity_importer
   *   The entity importer instance.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function setTitle(EntityImporterInterface $entity_importer = NULL) {
    return $this->t('@label: Status', [
      '@label' => $entity_importer->label(),
    ]);
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    EntityImporterInterface $entity_importer = NULL
  ) {
    $form = parent::buildForm($form, $form_state, $entity_importer);

    $bundle = $this->getBundle();

    if (!isset($bundle) || empty($bundle)) {
      return $form;
    }

    $form['overview'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Importer'),
        $this->t('Status'),
        $this->t('Processed'),
        $this->t('Error')
      ],
      '#empty' => $this->t(
        'Nothing has been imported for the @label importer.', [
          '@label' => $entity_importer->label()
        ]
      )
    ];

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    foreach ($entity_importer->getDependencyMigrations($bundle) as $migration_id => $migration) {
      $form['overview'][$migration_id]['importer']['#plain_text'] = $migration
        ->label();
      $form['overview'][$migration_id]['status']['#plain_text'] = $migration
        ->getStatusLabel();
      $form['overview'][$migration_id]['processed']['#plain_text'] = $migration
        ->getIdMap()
        ->importedCount();

      $error_count = $migration->getIdMap()->errorCount();
      $form['overview'][$migration_id]['error']['#plain_text'] = $error_count;

      if ($error_count > 0) {
        $form['overview'][$migration_id]['error'] = $entity_importer->createLink(
          $error_count, 'entity_import.importer.page.log_form'
        )->toRenderable();
      }
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
