<?php

namespace Drupal\entity_import\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_import\Entity\EntityImporterInterface;

/**
 * Define entity importer bundle form base.
 */
abstract class EntityImporterBundleFormBase extends FormBase {

  /**
   * @var string
   */
  protected $bundle;

  /**
   * @var \Drupal\entity_import\Entity\EntityImporterInterface
   */
  protected $entityImporter;

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    EntityImporterInterface $entity_importer = NULL
  ) {
    $this->entityImporter = $entity_importer;

    $form['#prefix'] = '<div id="entity-importer-bundle-form">';
    $form['#suffix'] = '</div>';

    $this->bundle = $entity_importer->getFirstBundle();

    $form['bundle'] = [
      '#type' => 'value',
      '#value' => $this->bundle,
    ];

    if ($entity_importer->hasMultipleBundles()) {
      $this->bundle = $this->getFormStateValue('bundle', $form_state);
      $form['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Import Bundle'),
        '#description' => $this->t('Select the import bundle type.'),
        '#options' => $entity_importer->getImporterBundles(),
        '#required' => TRUE,
        '#default_value' => $this->bundle,
        '#ajax' => [
          'event' => 'change',
          'method' => 'replace',
          'wrapper' => "entity-importer-bundle-form",
          'callback' => [$this, 'ajaxReplaceCallback'],
        ],
      ];
    }

    return $form;
  }

  /**
   * Entity bundle name.
   *
   * @return string
   *   The bundle name.
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   *   The form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   An array of the form elements to return.
   */
  public function ajaxReplaceCallback(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Set importer batch process.
   *
   * @param array $operations
   *   The entity importer batch operations.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param array $default_batch
   *   An array of default batch definitions properties.
   */
  protected function setImporterBatchProcess(
    array $operations,
    FormStateInterface $form_state,
    array $default_batch = []
  ) {
    if (empty($operations)) {
      return;
    }
    $form_state->setRedirectUrl(
      $this->entityImporter->createUrl(
        'entity_import.importer.page.status_form'
      )
    );

    $batch = [
      'operations' => $operations,
      'init_message' => $this->t('Processing...'),
      'finished' => '\Drupal\entity_import\Form\EntityImporterBatchProcess::finished'
    ] + $default_batch;

    batch_set($batch);
  }

  /**
   * Get property value from the form state.
   *
   * @param $property
   *   The form property name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   * @param null $default
   *   The default value.
   *
   * @return mixed|null
   *   The property form state value.
   */
  protected function getFormStateValue($property, FormStateInterface $form_state, $default = NULL) {
    if (!is_array($property)) {
      $property = [$property];
    }
    $states = [$form_state->getValues(), $form_state->getUserInput()];

    // Try to retrieve the property value from the form state array, otherwise
    // use the user input array.
    foreach ($states as $array) {
      $value = NestedArray::getValue($array, $property);

      if (!isset($value) || empty($value)) {
        continue;
      }

      return $value;
    }

    return $default;
  }
}
