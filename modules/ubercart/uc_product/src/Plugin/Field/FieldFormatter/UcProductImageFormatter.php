<?php

namespace Drupal\uc_product\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;

/**
 * Plugin implementation of the 'uc_product_image' formatter.
 *
 * @FieldFormatter(
 *   id = "uc_product_image",
 *   label = @Translation("Ubercart product images"),
 *   field_types = {
 *     "image"
 *   },
 *   settings = {
 *     "first_image_style" = "uc_product",
 *     "other_image_style" = "uc_thumbnail",
 *     "image_link" = "file"
 *   }
 * )
 */
class UcProductImageFormatter extends ImageFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = image_style_options(FALSE);
    $element['first_image_style'] = array(
      '#title' => $this->t('First image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('first_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
    );
    $element['other_image_style'] = array(
      '#title' => $this->t('Subsequent image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('other_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
    );

    $link_types = array(
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    );
    $element['image_link'] = array(
      '#title' => $this->t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = image_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $image_style_setting = $this->getSetting('first_image_style');
    if (!isset($image_styles[$image_style_setting])) {
      $image_styles[$image_style_setting] = $this->t('Original image');
    }
    $summary[] = $this->t('First image style: @style', ['@style' => $image_styles[$image_style_setting]]);

    $image_style_setting = $this->getSetting('other_image_style');
    if (!isset($image_styles[$image_style_setting])) {
      $image_styles[$image_style_setting] = $this->t('Original image');
    }
    $summary[] = $this->t('Subsequent image style: @style', ['@style' => $image_styles[$image_style_setting]]);

    $link_types = array(
      'content' => $this->t('Linked to content'),
      'file' => $this->t('Linked to file'),
    );
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $uri = $items->getEntity()->uri();
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $first_style = $this->getSetting('first_image_style');
    $other_style = $this->getSetting('other_image_style');
    foreach ($items as $delta => $item) {
      if ($item->entity) {
        if (isset($link_file)) {
          $image_uri = $item->entity->getFileUri();
          $uri = array(
            'path' => file_create_url($image_uri),
            'options' => [],
          );
        }
        $elements[$delta] = array(
          '#theme' => 'image_formatter',
          '#item' => $item->getValue(TRUE),
          '#image_style' => $delta == 0 ? $first_style : $other_style,
          '#path' => isset($uri) ? $uri : '',
        );
      }
    }

    return $elements;
  }

}
