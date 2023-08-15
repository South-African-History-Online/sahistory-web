<?php

namespace Drupal\entity_import\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_import\Entity\EntityImporterInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Define the entity importer log form.
 */
class EntityImporterLogForm extends EntityImporterBundleFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_import_importer_log';
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
    return $this->t('@label: Log', [
      '@label' => $entity_importer->label(),
    ]);
  }

  /**
   * {@inheritdoc}
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
    $migration_id = $this->getFormStateValue('migration', $form_state);
    $migration_options = $this->getImporterMigrationOptions($entity_importer, $bundle);

    if (count($migration_options) > 1) {
      $form['migration'] = [
        '#type' => 'select',
        '#title' => $this->t('Migration'),
        '#options' => $migration_options,
        '#required' => TRUE,
        '#default_value' => $migration_id,
        '#ajax' => [
          'event' => 'change',
          'method' => 'replace',
          'wrapper' => "entity-importer-bundle-form",
          'callback' => [$this, 'ajaxReplaceCallback'],
        ]
      ];
    }
    else {
      $migration_keys = array_keys($migration_options);
      $migration_id = reset($migration_keys);

      $form['migration'] = [
        '#type' => 'value',
        '#value' => $migration_id,
      ];
    }
    $message_rows = [];

    if (isset($migration_id) && isset($bundle)) {
      /** @var MigrationInterface $migration */
      $migration = $entity_importer->loadDependencyMigration($migration_id, $bundle);

      $form['filters'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Log Filters'),
        '#tree' => TRUE,
      ];
      $form['filters']['message_level'] = [
        '#type' => 'select',
        '#title' => $this->t('Message Level'),
        '#options' => [
          MigrationInterface::MESSAGE_ERROR => $this->t('Error'),
          MigrationInterface::MESSAGE_NOTICE => $this->t('Notice'),
          MigrationInterface::MESSAGE_WARNING => $this->t('Warning'),
          MigrationInterface::MESSAGE_INFORMATIONAL => $this->t('Information'),
        ],
        '#empty_option' => $this->t('- All -'),
        '#default_value' => NULL,
        '#ajax' => [
          'event' => 'change',
          'method' => 'replace',
          'wrapper' => "entity-importer-bundle-form",
          'callback' => [$this, 'ajaxReplaceCallback'],
        ]
      ];
      $message_level = $this->getFormStateValue(
        ['filters', 'message_level'], $form_state
      );
      $message_rows = $this->buildMigrationMessageRows($migration, $message_level);

      $form['logs'] = [
        '#type' => 'table',
        '#header' => [
          'hash' => $this->t('Hash ID'),
          'level' => $this->t('Level'),
          'message' => $this->t('Message'),
        ],
        '#rows' => $message_rows,
        '#empty' => $this->t('There are no logs for the "@label" importer.', ['@label' => $migration->label()]),
      ];
    }
    $form['entity_importer'] = [
      '#type' => 'value',
      '#value' => $entity_importer->id()
    ];
    $form['actions']['#type'] = 'actions';

    $form['actions']['execute'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete All'),
      '#disabled' => count($message_rows) === 0
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('migration')
      && $form_state->hasValue('entity_importer')) {

      $form_state->setRedirect(
        'entity_import.importer.page.log_delete_form', [
          'migration' => $form_state->getValue('migration'),
          'entity_importer' => $form_state->getValue('entity_importer')
        ]
      );
    }
  }

  /**
   * @param \Drupal\entity_import\Entity\EntityImporterInterface $entity_importer
   * @param $bundle
   *
   * @return array
   */
  protected function getImporterMigrationOptions(EntityImporterInterface $entity_importer, $bundle) {
    $options = [];

    /** @var  MigrationInterface $migration */
    foreach ($entity_importer->getDependencyMigrations($bundle) as $plugin_id => $migration) {
      $options[$plugin_id] = $migration->label();
    }

    return $options;
  }

  /**
   * Build migration message rows.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration instance.
   * @param null $level
   *   The message level indication.
   *
   * @return array
   *   An array of migration message rows.
   */
  protected function buildMigrationMessageRows(MigrationInterface $migration, $level = NULL) {
    $rows = [];

    foreach ($migration->getIdMap()->getMessages([], $level) as $message) {
      $rows[] = [
        'hash' => $message->source_ids_hash,
        'level' => $message->level,
        'message' => $message->message,
      ];
    }

    return $rows;
  }
}
