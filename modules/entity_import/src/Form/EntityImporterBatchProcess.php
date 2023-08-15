<?php

namespace Drupal\entity_import\Form;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_import\Plugin\migrate\source\EntityImportSourceLimitIteratorInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Define the importer batch process.
 */
class EntityImporterBatchProcess {

  /**
   * Batch migration import.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration object.
   * @param $update
   *   A boolean flag if the migration should update.
   * @param $status
   *   The migration status that should be initialized.
   * @param array $context
   *   An array of batch contexts.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public static function import(
    MigrationInterface $migration,
    $update,
    $status,
    array &$context
  ) {
    $action = 'import';
    /** @var \Drupal\entity_import\Plugin\migrate\source\EntityImportSourceInterface $source */
    $source = $migration->getSourcePlugin();

    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }

    if (!isset($context['results']['action'])) {
      $context['results']['action'] = $action;
    }

    if ($source instanceof EntityImportSourceLimitIteratorInterface) {
      $limit = $source->getLimitCount();
      $source->setBatch(TRUE);

      if (empty($context['sandbox'])) {
        $context['sandbox'] = [];
        $context['sandbox']['batch'] = 0;
        $context['sandbox']['iterations'] = abs(ceil($source->count() / $limit));
      }
      $batch = &$context['sandbox']['batch'];

      $offset = $batch * $limit;

      $source->setLimitOffset($offset);
      $batch++;

      if ($batch < $context['sandbox']['iterations']) {
        $source->skipCleanup();
      }
      $execute_status = static::executeMigration(
        $migration, $update, $status, $action
      );
      $source->resetBaseIterator();

      $context['results']['count'] += $source->getLimitIteratorCount();
      $context['results']['migrations'][$migration->id()]['status'][$batch] = $execute_status;

      if ($context['sandbox']['batch'] != $context['sandbox']['iterations']) {
        $context['finished'] = $context['sandbox']['batch'] / $context['sandbox']['iterations'];
      }
    } else {
      $execute_status = static::executeMigration(
        $migration, $update, $status, $action
      );
      $context['results']['count'] += $source->count();
      $context['results']['migrations'][$migration->id()]['status'] = $execute_status;
    }

    $context['message'] = new TranslatableMarkup(
      'Running the migration @action process for "@label".',
      [
        '@label' => $migration->label(),
        '@action' => $action,
      ]
    );
  }

  /**
   * Batch migration rollback.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration object.
   * @param $status
   *   The migration status that should be initialized.
   * @param $context
   *   An array of batch contexts.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public static function rollback(
    MigrationInterface $migration,
    $status,
    array &$context
  ) {
    $action = 'rollback';
    $context['message'] = new TranslatableMarkup(
      'Running the migration @action process for "@label".',
      [
        '@label' => $migration->label(),
        '@action' => $action,
      ]
    );
    if (!isset($context['results']['count'])) {
      $context['results']['count'] = 0;
    }
    $count = $migration->getIdMap()->processedCount();

    $execute_status = static::executeMigration(
      $migration, FALSE, $status, $action
    );

    $context['results']['action'] = $action;
    $context['results']['count'] += $count;
    $context['results']['migrations'][$migration->id()]['status'] = $execute_status;
  }

  /**
   * Batch finished callback.
   *
   * @param $success
   *   The batch success boolean.
   * @param $results
   *   The batch results.
   * @param $operations
   *   An array of operations that finished.
   */
  public static function finished($success, $results, $operations) {
    $action = $results['action'];
    $message = $success === TRUE
      ? new TranslatableMarkup(
        '<p>The system successfully ran the @action process for @count records.</p>', [
          '@action' => $action,
          '@count' => $results['count'],
        ]
      )
      : new TranslatableMarkup(
        '<p>The system experienced a problem when executing @action.</p>', [
          '@action' => $action,
        ]
      );

    static::messenger()->addMessage(
      $message,
      $success
        ? MessengerInterface::TYPE_STATUS
        : MessengerInterface::TYPE_WARNING
    );
  }

  /**
   * Execute migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   * @param $update
   *   A boolean flag if the migration should update.
   * @param $status
   *   The migration status that should be initialized.
   * @param $action
   *   The migration executable action to perform.
   *
   * @return int
   * @throws \Drupal\migrate\MigrateException
   */
  protected static function executeMigration(
    MigrationInterface $migration,
    $update,
    $status,
    $action
  ) {
    $migration->setStatus($status);

    if ($update == TRUE) {
      $migration->getIdMap()->prepareUpdate();
    }
    $executable = new MigrateExecutable($migration);

    if (!method_exists($executable, $action)) {
      return FALSE;
    }

    return call_user_func_array([$executable, $action], []);
  }

  /**
   * Get messenger instance.
   *
   * @return \Drupal\Core\Messenger\Messenger
   */
  protected static function messenger() {
    return \Drupal::service('messenger');;
  }
}
