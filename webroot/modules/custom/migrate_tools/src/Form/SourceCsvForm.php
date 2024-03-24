<?php

namespace Drupal\migrate_tools\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\Core\Url;
use Drupal\migrate_plus\Entity\MigrationInterface;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * Provides an edit form for CSV source plugin column_names configuration.
 *
 * This means you can tell the migration which columns your data is in and no
 * longer edit the CSV to fit the column order set in the migration or edit the
 * migration yml itself.
 *
 * Changes made to the column configuration, or aliases, are stored in the
 * private migrate_tools private store keyed by the migration plugin id. The
 * data stored for each migrations consists of two arrays, the 'original' column
 * aliases and the 'updated' column aliases.
 *
 * An additional list of all changed migration id is kept in the store, in the
 * key 'migrations_changed'
 *
 * Private Store Usage:
 *   migrations_changed: An array of the ids of the migrations that have been
 * changed:
 *   [migration_id]: The original and changed values for this column assignments
 *
 * Format of the source configuration saved in the store.
 * migration_id
 *   original
 *     column_index1
 *       property 1 => label 1
 *     column_index2
 *       property 2 => label 2
 *   updated
 *     column_index1
 *       property 2 => label 2
 *     column_index2
 *       property 1 => label 1
 *
 * Example source configuration:
 * custom_migration
 *   original
 *     2
 *       title => title
 *     3
 *       body => foo
 *   updated
 *     8
 *       title => new_title
 *     9
 *       body => new_body
 */
class SourceCsvForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Temporary store for column assignment changes.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $store;

  /**
   * The file object that reads the CSV file.
   *
   * @var \SplFileObject
   */
  protected $file = NULL;

  /**
   * The migration being examined.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The migration plugin id.
   *
   * @var string
   */
  protected $id;

  /**
   * The array of columns names from the CSV source plugin.
   *
   * @var array
   */
  protected $columnNames;

  /**
   * An array of options for the column select form field..
   *
   * @var array
   */
  protected $options;

  /**
   * An array of modified and original column_name source plugin configuration.
   *
   * @var array
   */
  protected $sourceConfiguration;

  /**
   * Constructs new SourceCsvForm object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The plugin manager for config entity-based migrations.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $private_store
   *   The private store.
   */
  public function __construct(Connection $connection, MigrationPluginManagerInterface $migration_plugin_manager, MessengerInterface $messenger, PrivateTempStoreFactory $private_store) {
    $this->connection = $connection;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->messenger = $messenger;
    $this->store = $private_store->get('migrate_tools');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('plugin.manager.migration'),
      $container->get('messenger'),
      $container->get('tempstore.private')
    );
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\migrate_plus\Entity\MigrationInterface $migration
   *   The $migration.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed or forbidden, neutral if tempstore is empty.
   */
  public function access(AccountInterface $account, MigrationInterface $migration) {
    try {
      $this->migration = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());
    }
    catch (PluginException $e) {
      return AccessResult::forbidden();
    }

    if ($this->migration) {
      if ($source = $this->migration->getSourcePlugin()) {
        if (is_a($source, CSV::class)) {
          return AccessResult::allowed();
        }
      }
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, MigrationInterface $migration = NULL) {
    try {
      // @TODO: remove this horrible config work around after
      // https://www.drupal.org/project/drupal/issues/2986665 is fixed.
      $this->migration = $this->migrationPluginManager->createInstance($migration->id(), $migration->toArray());
      /** @var \Drupal\migrate_source_csv\Plugin\migrate\source\CSV $source */
      $source = $this->migration->getSourcePlugin();
      $source->setConfiguration($migration->toArray()['source']);
    }
    catch (PluginException $e) {
      return AccessResult::forbidden();
    }

    // Get the source file after the properties are initialized.
    $source->initializeIterator();
    $this->file = $source->getFile();

    // Set the input field options to the header row values or, if there are
    // no such values, use an indexed array.
    if ($this->file->getHeaderRowCount() > 0) {
      $this->options = $this->getHeaderColumnNames();
    }
    else {
      for ($i = 0; $i < $this->getFileColumnCount(); $i++) {
        $this->options[$i] = $i;
      }
    }

    // Set the store key to the migration id.
    $this->id = $this->migration->getPluginId();

    // Get the column names from the file or from the store, if updated
    // values are in the store.
    $this->sourceConfiguration = $this->store->get($this->id);
    if (isset($this->sourceConfiguration['changed'])) {
      if ($config = $this->sourceConfiguration['changed']) {
        $this->columnNames = $config;
      }
    }
    else {
      // Get the calculated column names. This is either the header rows or
      // the configuration column_name value.
      $this->columnNames = $this->file->getColumnNames();
      if (!isset($this->sourceConfiguration['original'])) {
        // Save as the original values.
        $this->sourceConfiguration['original'] = $this->columnNames;
        $this->store->set($this->id, $this->sourceConfiguration);
      }
    }
    $form['#title'] = $this->t('Column Aliases');

    $form['heading'] = [
      '#type' => 'item',
      '#title' => $this->t(':label', [':label' => $this->migration->label()]),
      '#description' => '<p>' . $this->t('You can change the columns to be used by this migration for each source property.') . '</p>',
    ];
    // Create a form field for each column in this migration.
    foreach ($this->columnNames as $index => $data) {
      $property_name = key($data);
      $default_value = $index;
      $label = $this->getLabel($this->sourceConfiguration['original'], $property_name);

      $description = $this->t('Select the column where the data for <em>:label</em>, property <em>:property</em>, will be found.', [
        ':label' => $label,
        ':property' => $property_name,
      ]);
      $form['aliases'][$property_name] = [
        '#type' => 'select',
        '#title' => $label,
        '#description' => $description,
        '#options' => $this->options,
        '#default_value' => $default_value,
      ];
    }
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Submit'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Display an error message if two properties have the same source column.
    $values = [];
    foreach ($this->columnNames as $index => $data) {
      $property_name = key($data);
      $value = $form_state->getValue($property_name);
      if (in_array($value, $values)) {
        $form_state->setErrorByName($property_name, $this->t('Source properties can not share the same source column.'));
      }
      $values[] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create a new column_names configuration.
    $new_column_names = [];
    foreach ($this->columnNames as $index => $data) {
      // Keep the property name as it is used in the process pipeline.
      $property_name = key($data);
      // Get the new column number from the form alias field for this property.
      $new_index = $form_state->getValue($property_name);
      // Get the new label from the options array.
      $new_label = $this->options[$new_index];
      // Save using the new column number and new label.
      $new_column_names[$new_index] = [$property_name => $new_label];
    }
    // Update the file columns.
    $this->file->setColumnNames($new_column_names);
    // Save as updated in the store.
    $this->sourceConfiguration['changed'] = $new_column_names;
    $this->store->set($this->id, $this->sourceConfiguration);

    $changed = ($this->store->get('migrations_changed')) ? $this->store->get('migrations_changed') : [];
    if (!in_array($this->id, $changed)) {
      $changed[] = $this->id;
      $this->store->set('migrations_changed', $changed);
    }
  }

  /**
   * Form submission handler for the 'cancel' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array $form, FormStateInterface $form_state) {
    // Restore the file columns to the original settings.
    $this->file->setColumnNames($this->sourceConfiguration['original']);
    // Remove this migration from the store.
    try {
      $this->store->delete($this->id);
    }
    catch (TempStoreException $e) {
      $this->messenger->addError($e->getMessage());
    }

    $migrationsChanged = $this->store->get('migrations_changed');
    unset($migrationsChanged[$this->id]);
    try {
      $this->store->set('migrations_changed', $migrationsChanged);
    }
    catch (TempStoreException $e) {
      $this->messenger->addError($e->getMessage());
    }
    $form_state->setRedirect('entity.migration_group.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_tools_source_csv';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {}

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.migration_group.list');
  }

  /**
   * Returns the header row.
   *
   * Use a new file handle so that CSVFileObject::current() is not executed.
   *
   * @return array
   *   The header row.
   */
  public function getHeaderColumnNames() {
    $row = [];
    $fname = $this->file->getPathname();
    $handle = fopen($fname, 'r');
    if ($handle) {
      fseek($handle, $this->file->getHeaderRowCount() - 1);
      $row = fgetcsv($handle);
      fclose($handle);
    }
    return $row;
  }

  /**
   * Returns the count of fields in the header row.
   *
   * Use a new file handle so that CSVFileObject::current() is not executed.
   *
   * @return int
   *   The number of fields in the header row.
   */
  public function getFileColumnCount() {
    $count = 0;
    $fname = $this->file->getPathname();
    $handle = fopen($fname, 'r');
    if ($handle) {
      $row = fgetcsv($handle);
      $count = count($row);
      fclose($handle);
    }
    return $count;
  }

  /**
   * Gets the label for a given property from a column_names array.
   *
   * @param array $column_names
   *   An array of column_names.
   * @param string $property_name
   *   The property name to find a label for.
   *
   * @return string
   *   The label for this property.
   */
  protected function getLabel(array $column_names, $property_name) {
    $label = '';
    foreach ($column_names as $column) {
      foreach ($column as $key => $value) {
        if ($key === $property_name) {
          $label = $value;
          break;
        }
      }
    }
    return $label;
  }

}
