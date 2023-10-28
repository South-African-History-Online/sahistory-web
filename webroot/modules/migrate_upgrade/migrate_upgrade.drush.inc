<?php

/**
 * @file
 * Command-line tools to aid performing and developing upgrade migrations.
 */

use Drupal\migrate_upgrade\MigrateUpgradeDrushRunner;
use Drush\Drush;

/**
 * Implements hook_drush_command().
 */
function migrate_upgrade_drush_command() {
  $items['migrate-upgrade'] = [
    'description' => 'Perform one or more upgrade processes.',
    'options' => [
      'legacy-db-url' => 'A Drupal 6 style database URL. Required if you do not set legacy-db-key.',
      'legacy-db-key' => 'A database connection key from settings.php. Use as an alternative to legacy-db-url',
      'legacy-db-prefix' => 'Prefix of the legacy Drupal installation.',
      'legacy-root' => 'Site address or root of the legacy Drupal installation',
      'configure-only' => 'Set up the appropriate upgrade processes but do not perform them',
      'migration-prefix' => 'With configure-only, a prefix to apply to generated migration ids. Defaults to \'upgrade_\'',
    ],
    'examples' => [
      'migrate-upgrade --legacy-db-url=\'mysql://root:pass@127.0.0.1/d6\'' => 'Upgrade a Drupal 6 database to Drupal 8',
      'migrate-upgrade --legacy-db-key=\'drupal_7\'' => 'Upgrade Drupal 7 database where the connection to Drupal 7 has already been created in settings.php ($databases[\'drupal_7\'])',
      'migrate-upgrade --legacy-db-url=\'mysql://root:pass@127.0.0.1/d7\' --configure-only --migration-prefix=d7_custom_' => 'Generate migrations for a custom migration from Drupal 7 to Drupal 8',
    ],
    'drupal dependencies' => ['migrate_upgrade'],
  ];
  $items['migrate-upgrade-rollback'] = [
    'description' => 'Rolls back and removes upgrade migrations.',
    'examples' => [
      'migrate-upgrade-rollback' => 'Rolls back a previously-run upgrade',
    ],
    'drupal dependencies' => ['migrate_upgrade'],
  ];

  return $items;
}

/**
 * Execute the upgrade command, configuring the necessary migrations.
 *
 * Optionally perform the imports.
 */
function drush_migrate_upgrade() {
  $runner = new MigrateUpgradeDrushRunner(Drush::logger());

  try {
    $runner->configure();
    if (drush_get_option('configure-only')) {
      $result = $runner->export();
      foreach ($result as $original => $prefixed_migration) {
        drush_print(dt('Exporting @migration as @new_migration',
          ['@migration' => $original, '@new_migration' => $prefixed_migration]
        ));
      }
    }
    else {
      $runner->import();
      \Drupal::state()->set('migrate_drupal_ui.performed', \Drupal::time()->getRequestTime());
    }
    // Remove the global database state.
    \Drupal::state()->delete('migrate.fallback_state_key');
  }
  catch (\Exception $e) {
    drush_log($e->getMessage(), 'error');
  }
}

/**
 * Rolls back any upgrade migrations that are present.
 */
function drush_migrate_upgrade_rollback() {
  if ($date_performed = \Drupal::state()->get('migrate_drupal_ui.performed')) {
    if (drush_confirm(dt('All migrations tagged as \'Drupal\' will be rolled back. Are you sure?'))) {
      $runner = new MigrateUpgradeDrushRunner(Drush::logger());

      try {
        drush_log(dt('Rolling back the upgrades performed @date',
          ['@date' => \Drupal::service('date.formatter')->format($date_performed)]));
        $runner->rollback();
        \Drupal::state()->delete('migrate_drupal_ui.performed');
        drush_log(dt('Rolled back upgrades'));
      }
      catch (\Exception $e) {
        drush_log($e->getMessage(), 'error');
      }
    }
    else {
      drush_user_abort();
    }
  }
  else {
    drush_log(dt('No upgrade operation has been performed.'), 'warning');
  }
}
