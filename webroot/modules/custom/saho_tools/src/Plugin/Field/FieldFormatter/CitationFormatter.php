<?php

namespace Drupal\saho_tools\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\saho_tools\Service\CitationService;

/**
 * Plugin implementation of the 'citation_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "citation_formatter",
 *   label = @Translation("Citation"),
 *   field_types = {
 *     "boolean",
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class CitationFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The citation service.
   *
   * @var \Drupal\saho_tools\Service\CitationService
   */
  protected $citationService;

  /**
   * Constructs a CitationFormatter instance.
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
   * @param \Drupal\saho_tools\Service\CitationService $citation_service
   *   The citation service.
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CitationService $citation_service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->citationService = $citation_service;
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
      $container->get('saho_tools.citation_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'citation_format' => 'harvard',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['citation_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Citation Format'),
      '#options' => [
        'harvard' => $this->t('Harvard'),
        'apa' => $this->t('APA'),
        'oxford' => $this->t('Oxford (Footnote style)'),
        'all' => $this->t('All Formats'),
      ],
      '#default_value' => $this->getSetting('citation_format'),
      '#description' => $this->t('Select the citation format to display.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $citation_format = $this->getSetting('citation_format');
    if ($citation_format == 'all') {
      $summary[] = $this->t('Display all citation formats');
    }
    else {
      $formats = [
        'harvard' => $this->t('Harvard'),
        'apa' => $this->t('APA'),
        'oxford' => $this->t('Oxford (Footnote style)'),
      ];
      $summary[] = $this->t('Display @format citation format', ['@format' => $formats[$citation_format]]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity = $items->getEntity();

    // Only proceed if this is a node entity.
    if ($entity->getEntityTypeId() !== 'node') {
      return $elements;
    }

    // Get the citation format setting.
    $citation_format = $this->getSetting('citation_format');

    // Generate citations using the citation service.
    $citations = $this->citationService->generateCitations($entity);

    // Build the render array.
    if ($citation_format == 'all') {
      // Display all citation formats with tabs.
      $elements[0] = [
        '#theme' => 'citation_formatter_all',
        '#citations' => $citations,
        '#attached' => [
          'library' => ['saho_tools/citation'],
        ],
      ];
    }
    else {
      // Display a single citation format.
      $elements[0] = [
        '#theme' => 'citation_formatter_single',
        '#citation' => $citations[$citation_format],
        '#format' => $citation_format,
        '#attached' => [
          'library' => ['saho_tools/citation'],
        ],
      ];
    }

    return $elements;
  }

}