<?php

namespace Drupal\entity_import\Plugin\migrate\source;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\Row;
use League\Csv\Reader;

/**
 * @MigrateSource(
 *   id = "entity_import_csv",
 *   label = @Translation("CSV"),
 * )
 */
class EntityImportSourceCSV extends EntityImportSourceLimitIteratorBase implements RequirementsInterface {

  /**
   * @var \AppendIterator
   */
  protected $fileIterator;

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return (string) json_encode(iterator_to_array($this->fileIterator));
  }

  /**
   * {@inheritDoc}
   */
  public function isValid() {
    $config = $this->getConfiguration();
    return isset($config['file_id']) && !empty($config['file_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    /** @var \Drupal\migrate\Row $current_row */
    $current_row = parent::current();

    if ($this->hasEmptyRowData($current_row)) {
      $this->next();
      return $this->current();
    }

    return $current_row;
  }

  /**
   * {@inheritDoc}
   */
  public function runCleanup() {
    if ($this->skipCleanup === FALSE) {
      foreach ($this->getFiles() as $file) {
        $file->delete();
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function checkRequirements() {
    $config = $this->getConfiguration();

    if (!isset($config['file_id'])) {
      throw new RequirementsException(
        'Missing required source configuration.',
        ['file_id']
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildImportForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildImportForm($form, $form_state);

    $form['file_id'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload File'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#required' => $this->isRequired(),
      '#multiple' => $this->hasMultiple(),
      '#upload_location' => $this->getUploadTemporaryUri(),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateImportForm(array $form, FormStateInterface $form_state) {
    parent::validateImportForm($form, $form_state);

    if ($file_id = $form_state->getValue('file_id', [])) {
      $parents = $form['#parents'];
      $this->configuration['file_id'] = $file_id;

      $complete = $form_state->getCompleteForm();
      $element = NestedArray::getValue($complete, $parents);

      $headers = $this->buildFileIterator()->current();

      if ($missing = $this->getEntityImporter()->getMissingUniqueIdentifiers($headers)) {
        $form_state->setError(
          $element['file_id'],
          $this->t('These defined identifiers (@ids) are required in the header.', [
            '@ids' => implode(', ', $missing)
          ])
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['upload_multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Upload multiple files'),
      '#description' => $this->t('Allow multiple CSV files to be uploaded at once.'),
      '#default_value' => $this->hasMultiple(),
    ];
    $form['encoding'] = [
      '#type' => 'details',
      '#title' => $this->t('Encoding'),
      '#description' => $this->t('Specify the encoding on which to convert the CSV content to.'),
      '#tree' => TRUE,
    ];
    $form['encoding']['from'] = [
      '#type' => 'select',
      '#title' => $this->t('From'),
      '#options' => $this->getEncodingOptions(),
      '#empty_option' => $this->t('- Disable -'),
      '#default_value' => $this->getCsvFromEncoding(),
    ];
    $form['encoding']['to'] = [
      '#type' => 'select',
      '#title' => $this->t('To'),
      '#options' => $this->getEncodingOptions(),
      '#empty_option' => $this->t('- Disable -'),
      '#default_value' => $this->getCsvToEncoding(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function limitedIterator() {
    return $this->buildFileIterator();
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    return iterator_count($this->buildFileIterator());
  }

  /**
   * Get CSV encoding configurations.
   *
   * @return array
   *   An array of the to/from encoding options.
   */
  protected function getCsvEncoding() {
    return $this->getConfiguration()['encoding'];
  }

  /**
   * Get the temporary uri for uploading.
   *
   * @return mixed
   *   NULL if not set, or the overriden value.
   */
  protected function getUploadTemporaryUri() {
    $temporaryUri = $this->settings->get('temporary_uri');
    if (!empty($temporaryUri)) {
      return $temporaryUri;
    }
    return NULL;
  }

  /**
   * Get the TO encoding option.
   *
   * @return string
   *   The selected encoding that we're convert to.
   */
  protected function getCsvToEncoding() {
    return $this->getCsvEncoding()['to'];
  }

  /**
   * Get the FROM encoding option.
   *
   * @return string
   *   The selected encoding that we're convert from.
   */
  protected function getCsvFromEncoding() {
    return $this->getCsvEncoding()['from'];
  }

  /**
   * Determine if the CSV encoding have been set.
   *
   * @return bool
   *   Return TRUE if encoding is valid; otherwise FALSE.
   */
  protected function hasCsvEncoding() {
    $encoding = $this->getCsvEncoding();

    return isset($encoding['to'], $encoding['from'])
      && (string) $encoding['to'] !== (string) $encoding['from'];
  }

  /**
   * Get the available encoding options.
   *
   * @return array
   *   An array of encoding options.
   */
  protected function getEncodingOptions() {
    $encodings = mb_list_encodings();
    $options = array_combine($encodings, $encodings);

    unset($options['pass'], $options['auto']);

    return $options;
  }

  /**
   * File upload allows multiple.
   *
   * @return bool
   *   Return TRUE if uploading multiple files is allowed; otherwise FALSE.
   */
  protected function hasMultiple() {
    $config = $this->getConfiguration();
    return isset($config['upload_multiple']) && $config['upload_multiple'];
  }

  /**
   * Determine if the row data is empty.
   *
   * @param \Drupal\migrate\Row $row
   *   The migration row instance.
   *
   * @return bool
   *   Return TRUE if the row data is empty; otherwise FALSE.
   */
  protected function hasEmptyRowData(Row $row) {
    $row_data = array_intersect_key(
      $row->getSource(),
      $this->fields()
    );

    return count(array_filter($row_data)) === 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultConfiguration() {
    return [
        'file_id' => [],
        'encoding' => [
          'to' => NULL,
          'from' => NULL
        ],
        'upload_multiple' => FALSE,
      ] + parent::defaultConfiguration();
  }

  /**
   * Build the file iterator.
   *
   * @return \AppendIterator
   *   An iterator with all valid CSV records append.
   *
   * @throws \League\Csv\Exception
   */
  protected function buildFileIterator() {
    if (isset($this->fileIterator)) {
      return $this->fileIterator;
    }
    $iterator = new \AppendIterator();

    foreach ($this->getFiles() as $file) {
      /** @var \League\Csv\Reader $csv */
      $csv = Reader::createFromPath($file->getFileUri());
      $csv->setHeaderOffset(0);

      if ($this->hasCsvEncoding()) {
        $filter = "{$this->getCsvFromEncoding()}/{$this->getCsvToEncoding()}";
        try {
          $csv->addStreamFilter("convert.iconv.{$filter}");
        } catch (\Exception $exception) {
          watchdog_exception('entity_import', $exception);
        }
      }
      $records = $csv->getRecords();
      $records->rewind();

      if (!$records->valid()) {
        continue;
      }
      $iterator->append($records);
    }
    $this->fileIterator = $iterator;

    return $iterator;
  }

  /**
   * Get an array of uploaded files.
   *
   * @return \Drupal\file\Entity\File[]
   *   An array of instantiated files.
   */
  protected function getFiles() {
    $config = $this->getConfiguration();

    if (!isset($config['file_id'])) {
      return [];
    }

    return $this->loadFiles($config['file_id']);
  }

  /**
   * Load uploaded files.
   *
   * @param array $file_ids
   *   An array of file identifiers.
   *
   * @return \Drupal\file\Entity\File[]
   *   An array of instantiated file objects.
   */
  protected function loadFiles(array $file_ids) {
    $files = [];

    foreach ($file_ids as $fid) {
      /** @var \Drupal\file\Entity\File $file */
      $file = File::load($fid);

      if (!isset($file) || !$file instanceof FileInterface) {
        continue;
      }
      $files[] = $file;
    }

    return $files;
  }
}
