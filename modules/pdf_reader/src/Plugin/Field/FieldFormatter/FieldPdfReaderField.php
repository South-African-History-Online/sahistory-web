<?php

namespace Drupal\pdf_reader\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;

/**
 * Plugin implementation of the 'FieldPdfReaderFields' formatter.
 *
 * @FieldFormatter(
 *  id = "FieldPdfReaderFields",
 *  label = @Translation("PDF Reader"),
 *  field_types = {"string","file"}
 * )
 */
class FieldPdfReaderField extends FormatterBase {

  const GOOGLE_VIEWER = '//docs.google.com/viewer';
  const MICROSOFT_VIEWER = 'https://view.officeapps.live.com/op/embed.aspx';

  public $displayOptions = [];
  public $isColorboxInstalled = FALSE;

  /**
   * Checks if the colorbox and the libraries module are enabled.
   *
   * @return bool
   *   TRUE, if colorbox and libraries module are enabled,
   *   FALSE otherwise.
   */
  public function isColorboxInstalled() {
    if (\Drupal::moduleHandler()->moduleExists('colorbox') && \Drupal::moduleHandler()->moduleExists('libraries')) {
      $this->isColorboxInstalled = TRUE;
      return $this->isColorboxInstalled;
    }
  }

  /**
   * Callback function to return PDF display options.
   */
  public function getPdfDisplayOptions() {
    $this->displayOptions = [
      'google' => $this->t('Google Viewer'),
      'ms' => $this->t('MS Viewer'),
      'embed' => $this->t('Direct Embed'),
      'pdf-js' => $this->t('pdf.js'),
    ];
    if ($this->isColorboxInstalled()) {
      $this->displayOptions['colorbox'] = $this->t('Colorbox');
    }
    return $this->displayOptions;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'pdf_width' => 600,
      'pdf_height' => 780,
      'renderer' => 'google',
      'download' => FALSE,
      'link_placement' => 'top',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['pdf_width'] = [
      '#title' => $this->t('Width'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('pdf_width'),
    ];

    $element['pdf_height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('pdf_height'),
    ];

    $element['renderer'] = [
      '#title' => $this->t('Renderer'),
      '#type' => 'select',
      '#options' => $this->getPdfDisplayOptions(),
      '#default_value' => $this->getSetting('renderer'),
    ];

    $element['download'] = [
      '#title' => $this->t('Show download link'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('download'),
    ];

    $element['link_placement'] = [
      '#title' => $this->t('Show Link'),
      '#type' => 'select',
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $this->getSetting('link_placement'),
      '#states' => [
        'invisible' => [
          'input[name="fields[field_file_to_test_ha][settings_edit_form][settings][download]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $displayoptions = $this->getPdfDisplayOptions();
    $summary[] = $this->t('Size:') . $this->getSetting('pdf_width') . 'x' . $this->getSetting('pdf_height');
    $summary[] = $this->t('Using:') . $displayoptions[$this->getSetting('renderer')];
    $is_downloadable = $this->getSetting('download') ? $this->t('YES') : $this->t('NO');
    $summary[] = $this->t('Download Link:') . $is_downloadable;
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $field_display_type = $this->getSetting('renderer');
    $width = $this->getSetting('pdf_width');
    $height = $this->getSetting('pdf_height');
    $download_placement = $this->getSetting('link_placement');
    foreach ($items as $delta => $item) {
      if ($values = $item->getValue('values')) {
        if (isset($values['target_id']) && !empty($values['target_id']) && is_numeric($values['target_id'])) {
          $file = \Drupal::entityTypeManager()->getStorage('file')->load($values['target_id']);
          if (isset($file) and is_object($file)) {
            $file_url = file_create_url($file->getFileUri());
            $file_name = $file->getFileName();
          }
        }
        elseif (isset($values['value']) && !empty($values['value'])) {
          if (UrlHelper::isValid($values['value'])) {
            $file_url = file_create_url($values['value']);
            $file_name = $file_url;
          }
        }
        if (isset($file_url)) {
          switch ($field_display_type) {
            case 'google':
            case 'ms':
              ($field_display_type == 'google') ? $file_path = self::GOOGLE_VIEWER . '?embedded=true&url=' . urlencode($file_url) : $file_path = self::MICROSOFT_VIEWER . '?src=' . urlencode($file_url);
              $elements[$delta] = [
                '#theme' => 'pdf_reader',
                '#service' => $field_display_type,
                '#file_url' => $file_path,
                '#width' => $width,
                '#height' => $height,
              ];
              break;

            case 'embed':
              $elements[$delta] = [
                '#theme' => 'pdf_reader_embed',
                '#service' => $field_display_type,
                '#width' => $width,
                '#height' => $height,
                '#file_url' => $file_url . '#view=Fit',
                '#text' => $this->t('It appears your Web browser is not configured to display PDF files.')
                . Link::fromTextAndUrl($this->t('Download adobe Acrobat'), Url::fromUri('http://www.adobe.com/products/reader.html'))->toString()
                . ' ' . $this->t('or') . ' ' . Link::fromTextAndUrl($this->t('Click here to download the PDF file.'), Url::fromUri($file_url))->toString() ,
              ];
              break;

            case 'pdf-js':
              $module_path = base_path() . drupal_get_path('module', 'pdf_reader');
              $elements[$delta] = [
                '#theme' => 'pdf_reader_js',
                '#service' => $field_display_type,
                '#attached' => [
                  'drupalSettings' => [
                    'pdf_reader' => [
                      'file_url' => $file_url,
                      'path_pdf_reader' => "$module_path/js/pdf.js",
                    ],
                  ],
                  'library' => [
                    "pdf_reader/global-styling",
                  ],
                ],
              ];
              break;

            case 'colorbox':
              $elements[$delta] = [
                '#theme' => 'pdf_reader_colorbox',
                '#service' => $field_display_type,
                '#file_url' => $file_url,
                '#file_name' => $file_name,
                '#width' => $width,
                '#height' => $height,
              ];
              break;
          }
          if ($this->getSetting('download')) {
            $elements[$delta]['#download_link'] = $file_url;
            if (!empty($download_placement) && $download_placement == 'top') {
              $elements[$delta]['#top'] = 'top';
            }
            else {
              $elements[$delta]['#bottom'] = 'bottom';
            }
            $elements[$delta]['#attached']['library'][] = 'pdf_reader/download-link-css';
          }
          if ($this->isColorboxInstalled()) {
            $elements[$delta]['#attached']['library'][] = 'pdf_reader/colorbox';
          }
        }
      }
    }
    return $elements;
  }

}
