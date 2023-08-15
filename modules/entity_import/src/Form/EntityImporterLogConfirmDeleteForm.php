<?php

namespace Drupal\entity_import\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_import\Entity\EntityImporterInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Define entity importer log confirm delete form.
 */
class EntityImporterLogConfirmDeleteForm extends ConfirmFormBase {

  /**
   * @var MigrationInterface
   */
  protected $migration;

  /**
   * @var EntityImporterInterface
   */
  protected $entityImporter;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_import_entity_importer_clear_log';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    MigrationInterface $migration = NULL,
    EntityImporterInterface $entity_importer = NULL
  ) {
    $this->migration = $migration;
    $this->entityImporter = $entity_importer;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to delete logs related to "@label"?',
      ['@label' => $this->migration->label()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute(
      'entity_import.importer.page.log_form',
      ['entity_importer' => $this->entityImporter->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->migration->getIdMap()->clearMessages();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
