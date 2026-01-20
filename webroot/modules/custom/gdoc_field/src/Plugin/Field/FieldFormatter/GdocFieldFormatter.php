<?php

namespace Drupal\gdoc_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class GdocFieldFormatter extends FileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a GdocFieldFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    FileUrlGeneratorInterface $file_url_generator,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('file_url_generator'),
    );
  }

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

      // Ensure we're working with a FileInterface.
      if (!($file instanceof FileInterface)) {
        // Skip entities that don't implement FileInterface.
        continue;
      }

      // Now $file is guaranteed to be a FileInterface.
      /** @var \Drupal\file\FileInterface $file */
      $file_uri = $file->getFileUri();
      $filename = $file->getFileName();
      $uri_scheme = StreamWrapperManager::getScheme($file_uri);

      if ($uri_scheme == 'public') {
        $url = $this->fileUrlGenerator->generateAbsoluteString($file_uri);
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
