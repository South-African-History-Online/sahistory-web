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
 * Adds customized sort priority by Content Bundle.
 *
 * @SearchApiProcessor(
 *   id = "contentbundle",
 *   label = @Translation("Sort Priority by Content Bundle"),
 *   description = @Translation("Sort Priority by Content Bundle."),
 *   stages = {
 *     "add_properties" = 20,
 *     "pre_index_save" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class ContentBundle extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  protected $targetFieldId = 'contentbundle_weight';

  /**
   * Can only be enabled for an index that indexes the content bundle entity.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'node') {
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
        'label' => $this->t('Sort Priority by Content Bundle'),
        // TODO Come up with better description.
        'description' => $this->t('Sort Priority by Content Bundle.'),
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

    // TODO We are only working with nodes for now.
    if ($item->getDatasource()->getEntityTypeId() == 'node') {
      $bundle_type = $item->getDatasource()->getItemBundle($item->getOriginalObject());

      // Get the weight assigned to content type.
      if (isset($this->configuration['sorttable'][$bundle_type]) && $this->configuration['sorttable'][$bundle_type]['weight']) {
        $weight = $this->configuration['sorttable'][$bundle_type]['weight'];
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
        $this->t('Content Bundle'),
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
      // TODO Maybe this can be extended for non Node types?
      if ($datasource->getEntityTypeId() == 'node') {
        if ($bundles = $datasource->getBundles()) {
          // Make a dummy array to add custom weight.
          foreach ($bundles as $bundle_id => $bundle_name) {
            $weight = $this->configuration['weight'];
            if (isset($this->configuration['sorttable']) && isset($this->configuration['sorttable'][$bundle_id]['weight'])) {
              $weight = $this->configuration['sorttable'][$bundle_id]['weight'];
            }

            $bundle_weight[$bundle_id]['bundle_id'] = $bundle_id;
            $bundle_weight[$bundle_id]['bundle_name'] = $bundle_name;
            $bundle_weight[$bundle_id]['weight'] = $weight;
          }

          // Sort weights.
          uasort($bundle_weight, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

          // Loop over each bundle and create a form row.
          foreach ($bundle_weight as $bundle_id => $bundle) {
            $weight = $bundle['weight'];
            $bundle_name = $bundle['bundle_name'];

            // Add form with weights
            // Mark the table row as draggable.
            $form['sorttable'][$bundle_id]['#attributes']['class'][] = 'draggable';

            // Sort the table row according to its existing/configured weight.
            $form['sorttable'][$bundle_id]['#weight'] = $weight;

            // Table columns containing raw markup.
            $form['sorttable'][$bundle_id]['label']['#plain_text'] = $bundle_name;

            // Weight column element.
            $form['sorttable'][$bundle_id]['weight'] = [
              '#type' => 'weight',
              '#title' => t('Weight for @title', ['@title' => $bundle_name]),
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

}
