<?php

namespace Drupal\gdoc_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Plugin implementation of the 'gdoc_field' formatter.
 *
 * @FieldFormatter(
 *   id = "gdoc_field",
 *   label = @Translation("Embedded Google Documents Viewer"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class GdocFieldFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $entity = $this->fieldDefinition->getTargetEntityTypeId();
      $bundle = $this->fieldDefinition->getTargetBundle();
      $field_name = $this->fieldDefinition->getName();
      $field_type = $this->fieldDefinition->getType();
      $file_uri = $file->getFileUri();
      $filename = $file->getFileName();
      $uri_scheme = StreamWrapperManager::getScheme($file_uri);

      if ($uri_scheme == 'public') {
        $url = file_create_url($file->getFileUri());
        $elements[$delta] = [
          '#theme' => 'gdoc_field',
          '#url' => $url,
          '#filename' => $filename,
          '#delta' => $delta,
          '#entity' => $entity,
          '#bundle' => $bundle,
          '#field_name' => $field_name,
          '#field_type' => $field_type,
          '#attached' => [
            'library' => [
              'gdoc_field/gdoc-field',
            ],
          ],
        ];

      }
      else {
        $this->messenger()->addError(
          t('The file (%file) is not publicly accessible. It must be publicly available in order for the Google Docs viewer to be able to access it.',
          ['%file' => $filename]
          ),
          FALSE
        );
      }
    }

    return $elements;
  }

}
