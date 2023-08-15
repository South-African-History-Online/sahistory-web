<?php

namespace Drupal\search_api_sort_priority\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;

/**
 * Adds customized sort priority by File mime.
 *
 * @SearchApiProcessor(
 *   id = "filemime",
 *   label = @Translation("Sort Priority by File mime"),
 *   description = @Translation("Sort Priority by File mime."),
 *   stages = {
 *     "add_properties" = 20,
 *     "pre_index_save" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class FileMime extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  protected $targetFieldId = 'filemime_weight';

  /**
   * Can only be enabled for an index that indexes the file mime entity.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'file') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        // TODO Come up with better label.
        'label' => $this->t('Sort Priority by File mime'),
        // TODO Come up with better description.
        'description' => $this->t('Sort Priority by File mime.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
        // This will be a hidden field,
        // not something a user can add/remove manually.
        'hidden' => TRUE,
      ];
      $properties[$this->targetFieldId] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get default weight.
    $weight = $this->configuration['weight'];

    // We are only working with files for now.
    if ($item->getDatasource()->getEntityTypeId() == 'file') {
      $mimeType = $item->getOriginalObject()->getValue()->getMimeType();

      // Get the weight assigned to content type.
      if ($this->configuration['sorttable'][$mimeType]['weight']) {
        $weight = $this->configuration['sorttable'][$mimeType]['weight'];
      }

      // Set the weight on all the configured fields.
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, $this->targetFieldId);
      foreach ($fields as $field) {
        $field->addValue($weight);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'weight' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['sorttable'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('File mime'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'sorttable-order-weight',
        ],
      ],
    ];

    // Get a list of available bundle_types defined on this index.
    $datasources = $this->index->getDatasources();

    foreach ($datasources as $datasource) {
      if ($datasource->getEntityTypeId() == 'file') {

        $mimeTypes = $this->getAvailableMimes();
        if ($mimeTypes) {
          // Make a dummy array to add custom weight.
          foreach ($mimeTypes as $mimeType) {
            $weight = $this->configuration['weight'];
            if (isset($this->configuration['sorttable']) && isset($this->configuration['sorttable'][$mimeType]['weight'])) {
              $weight = $this->configuration['sorttable'][$mimeType]['weight'];
            }

            $mime_weight[$mimeType]['mime_type'] = $mimeType;
            $mime_weight[$mimeType]['weight'] = $weight;
          }

          // Sort weights.
          uasort($mime_weight, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

          // Loop over each mime and create a form row.
          foreach ($mime_weight as $mime) {
            $weight = $mime['weight'];
            $mimeType = $mime['mime_type'];

            // Add form with weights
            // Mark the table row as draggable.
            $form['sorttable'][$mimeType]['#attributes']['class'][] = 'draggable';

            // Sort the table row according to its existing/configured weight.
            $form['sorttable'][$mimeType]['#weight'] = $weight;

            // Table columns containing raw markup.
            $form['sorttable'][$mimeType]['label']['#plain_text'] = $mimeType;

            // Weight column element.
            $form['sorttable'][$mimeType]['weight'] = [
              '#type' => 'weight',
              '#title' => t('Weight for @title', ['@title' => $mimeType]),
              '#title_display' => 'invisible',
              '#default_value' => $weight,
              // Classify the weight element for #tabledrag.
              '#attributes' => ['class' => ['sorttable-order-weight']],
            ];
          }

        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    // Automatically add field to index if processor is enabled.
    $field = $this->ensureField(NULL, $this->targetFieldId, 'integer');
    // Hide the field.
    $field->setHidden();
  }

  /**
   * Get a list of mimes for all files.
   *
   * @return array
   *   Return a mime list.
   */
  private function getAvailableMimes() {
    $mimeTypes = [];

    $connection = \Drupal::database();
    $query = $connection->select('file_managed', 'f')
      ->condition('f.status', FILE_STATUS_PERMANENT)
      ->fields('f', ['filemime'])
      ->distinct()
      ->execute();
    $results = $query->fetchAll();

    foreach ($results as $result) {
      $mimeTypes[] = $result->filemime;
    }

    return $mimeTypes;
  }

}
