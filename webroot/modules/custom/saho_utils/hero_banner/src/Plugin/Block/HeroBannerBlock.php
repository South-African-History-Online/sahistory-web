<?php

namespace Drupal\hero_banner\Plugin\Block;

use Drupal\file\Entity\File;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Hero Banner' block.
 *
 * @Block(
 *   id = "hero_banner_block",
 *   admin_label = @Translation("Hero Banner Block"),
 *   category = @Translation("All custom"),
 * )
 */
class HeroBannerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new HeroBannerBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => '',
      'title_color' => 'white',
      'subtitle' => '',
      'body' => [
        'value' => '',
        'format' => 'basic_html',
      ],
      'background_image' => NULL,
      'display_mode' => 'standard',
      'call_to_action' => [
        'title' => '',
        'uri' => '',
        'style' => 'solid',
      ],
      'overlay_opacity' => 50,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    // Display Mode selection (moved up for #states dependencies).
    $form['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display Mode'),
      '#default_value' => $config['display_mode'] ?? 'standard',
      '#options' => [
        'standard' => $this->t('Standard (with text and CTA button)'),
        'graphic' => $this->t('Graphic Mode (image only - clickable banner)'),
      ],
      '#description' => $this->t('<strong>Graphic Mode:</strong> For promotional banners from the design team. Hides all text fields - the graphic itself contains the messaging. The entire banner becomes a clickable link.'),
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config['title'],
      '#required' => TRUE,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[name="settings[display_mode]"]' => ['value' => 'standard'],
        ],
        'required' => [
          ':input[name="settings[display_mode]"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $form['title_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Title Color'),
      '#default_value' => $config['title_color'] ?? 'white',
      '#options' => [
        'white' => $this->t('White'),
        'red' => $this->t('SAHO Red'),
        'gold' => $this->t('SAHO Gold'),
        'green' => $this->t('SAHO Green'),
      ],
      '#description' => $this->t('Choose the color for the hero banner title.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[display_mode]"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $form['subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subtitle'),
      '#default_value' => $config['subtitle'],
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[name="settings[display_mode]"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#default_value' => $config['body']['value'],
      '#format' => $config['body']['format'],
      '#rows' => 5,
      '#states' => [
        'visible' => [
          ':input[name="settings[display_mode]"]' => ['value' => 'standard'],
        ],
      ],
    ];

    // Load media entity for default value if available.
    $default_media_entity = NULL;
    if (!empty($config['background_image'])) {
      $default_media_entity = $this->entityTypeManager->getStorage('media')->load($config['background_image']);
    }

    $form['background_image'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'media',
      '#selection_settings' => [
        'target_bundles' => ['image'],
      ],
      '#title' => $this->t('Hero Banner Image'),
      '#default_value' => $default_media_entity,
      '#description' => $this->t('Start typing to search for an image, or upload a new one through the media library.'),
      '#maxlength' => 1024,
    ];

    $form['overlay_opacity'] = [
      '#type' => 'range',
      '#title' => $this->t('Overlay Opacity'),
      '#default_value' => $config['overlay_opacity'],
      '#min' => 0,
      '#max' => 100,
      '#step' => 10,
      '#description' => $this->t('Set the overlay opacity for the background image (0 = transparent, 100 = opaque).'),
    ];

    $form['call_to_action'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Link / Call to Action'),
      '#description' => $this->t('<strong>Standard Mode:</strong> Shows as a button. <strong>Graphic Mode:</strong> Makes entire banner clickable.'),
    ];

    $form['call_to_action']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $config['call_to_action']['title'],
      '#maxlength' => 255,
    ];

    $form['call_to_action']['uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#default_value' => $config['call_to_action']['uri'] ?? '',
      '#description' => $this->t('Enter an internal path (e.g., /about-us), external URL (e.g., https://example.com), or node path (e.g., /node/123).'),
      '#maxlength' => 2048,
    ];

    $form['call_to_action']['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Button Style'),
      '#default_value' => $config['call_to_action']['style'] ?? 'solid',
      '#options' => [
        'solid' => $this->t('Solid Button'),
        'ghost' => $this->t('Ghost Button (Outline)'),
      ],
      '#description' => $this->t('Choose the button style.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['title'] = $values['title'];
    $this->configuration['title_color'] = $values['title_color'];
    $this->configuration['subtitle'] = $values['subtitle'];
    $this->configuration['body'] = $values['body'];
    // Entity autocomplete returns an entity object, store just the ID.
    if (!empty($values['background_image'])) {
      if (is_object($values['background_image'])) {
        $this->configuration['background_image'] = $values['background_image']->id();
      }
      else {
        $this->configuration['background_image'] = $values['background_image'];
      }
    }
    else {
      $this->configuration['background_image'] = NULL;
    }
    $this->configuration['overlay_opacity'] = $values['overlay_opacity'];
    $this->configuration['display_mode'] = $values['display_mode'];
    $this->configuration['call_to_action']['title'] = $values['call_to_action']['title'];
    $this->configuration['call_to_action']['style'] = $values['call_to_action']['style'];
    // Handle URI from form.
    $uri_input = $values['call_to_action']['uri'] ?? '';
    if (!empty($uri_input)) {
      // Convert to proper URI format.
      if (strpos($uri_input, 'http://') === 0 || strpos($uri_input, 'https://') === 0) {
        $this->configuration['call_to_action']['uri'] = $uri_input;
      }
      elseif (strpos($uri_input, 'internal:') === 0) {
        // URI already has internal: prefix, don't add it again.
        $this->configuration['call_to_action']['uri'] = $uri_input;
      }
      elseif (strpos($uri_input, '/') === 0) {
        $this->configuration['call_to_action']['uri'] = 'internal:' . $uri_input;
      }
      else {
        $this->configuration['call_to_action']['uri'] = 'internal:/' . $uri_input;
      }
    }
    else {
      $this->configuration['call_to_action']['uri'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $build = [];

    $background_image_url = NULL;
    $background_image_mobile_url = NULL;
    $image_width = NULL;
    $image_height = NULL;
    if (!empty($config['background_image'])) {
      $media_id = $config['background_image'];
      $media = $this->entityTypeManager->getStorage('media')->load($media_id);
      if ($media && $media->hasField('field_media_image')) {
        $image_field = $media->get('field_media_image');
        if (!$image_field->isEmpty()) {
          $file = $image_field->entity;
          if ($file && $file instanceof File) {
            $file_uri = $file->getFileUri();

            // Get image dimensions for CLS prevention.
            $image_values = $image_field->first()->getValue();
            $image_width = $image_values['width'] ?? NULL;
            $image_height = $image_values['height'] ?? NULL;

            // Generate image style derivatives for responsive images.
            /** @var \Drupal\image\ImageStyleInterface $desktop_style */
            $desktop_style = $this->entityTypeManager->getStorage('image_style')->load('saho_hero');
            /** @var \Drupal\image\ImageStyleInterface $mobile_style */
            $mobile_style = $this->entityTypeManager->getStorage('image_style')->load('saho_hero_mobile');

            if ($desktop_style) {
              $desktop_uri = $desktop_style->buildUri($file_uri);
              // Ensure the derivative exists.
              if (!file_exists($desktop_uri)) {
                $desktop_style->createDerivative($file_uri, $desktop_uri);
              }
              // Check for WebP version of the derivative.
              $webp_desktop_uri = preg_replace('/\.(jpe?g|png)$/i', '.webp', $desktop_uri);
              if ($webp_desktop_uri !== $desktop_uri && file_exists($webp_desktop_uri)) {
                $background_image_url = $this->fileUrlGenerator->generateAbsoluteString($webp_desktop_uri);
              }
              else {
                $background_image_url = $desktop_style->buildUrl($file_uri);
              }
              // Update dimensions for the styled image.
              $original_width = $image_values['width'] ?? 0;
              $image_width = 1920;
              $image_height = ($image_height && $original_width) ? (int) round(($image_height / $original_width) * 1920) : 800;
            }
            else {
              // Fallback: Check for WebP version of original.
              $webp_uri = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_uri);
              if ($webp_uri !== $file_uri && file_exists($webp_uri)) {
                $background_image_url = $this->fileUrlGenerator->generateAbsoluteString($webp_uri);
              }
              else {
                $background_image_url = $this->fileUrlGenerator->generateAbsoluteString($file_uri);
              }
            }

            // Generate mobile derivative.
            if ($mobile_style) {
              $mobile_uri = $mobile_style->buildUri($file_uri);
              // Ensure the derivative exists.
              if (!file_exists($mobile_uri)) {
                $mobile_style->createDerivative($file_uri, $mobile_uri);
              }
              // Check for WebP version of the mobile derivative.
              $webp_mobile_uri = preg_replace('/\.(jpe?g|png)$/i', '.webp', $mobile_uri);
              if ($webp_mobile_uri !== $mobile_uri && file_exists($webp_mobile_uri)) {
                $background_image_mobile_url = $this->fileUrlGenerator->generateAbsoluteString($webp_mobile_uri);
              }
              else {
                $background_image_mobile_url = $mobile_style->buildUrl($file_uri);
              }
            }
          }
        }
      }
    }

    $button_url = NULL;
    $button_target = NULL;
    $button_rel = NULL;
    if (!empty($config['call_to_action']['uri'])) {
      $uri = $config['call_to_action']['uri'];

      // Handle different URI formats.
      if (strpos($uri, 'internal:') === 0) {
        // Internal path.
        // Remove 'internal:' prefix.
        $path = substr($uri, 9);
        $button_url = $path;
      }
      elseif (strpos($uri, 'http://') === 0 || strpos($uri, 'https://') === 0) {
        // External URL.
        $button_url = $uri;
        $button_target = '_blank';
        $button_rel = 'noopener noreferrer';
      }
      elseif (strpos($uri, '/') === 0) {
        // Direct path.
        $button_url = $uri;
      }
      else {
        // Assume it's a path without leading slash.
        $button_url = '/' . $uri;
      }
    }

    // Check display mode to determine what content to show.
    $display_mode = $config['display_mode'] ?? 'standard';
    $is_graphic_mode = ($display_mode === 'graphic');

    // Process body through the administrator-configured text format
    // for XSS protection (only in standard mode).
    $body_value = '';
    if (!$is_graphic_mode && !empty($config['body']['value'])) {
      // Use the format selected by the administrator in the block config
      // form. The format is stored when the block is saved via text_format.
      // Fallback to 'basic_html' only if format is missing (e.g., legacy data).
      $format = !empty($config['body']['format']) ? $config['body']['format'] : 'basic_html';
      // Use check_markup to apply text format filtering, then wrap in Markup
      // so Twig knows it's already sanitized and won't double-escape.
      $filtered_body = check_markup($config['body']['value'], $format);
      $body_value = Markup::create($filtered_body);
    }

    // Use SDC component for rendering (Drupal 11 best practice).
    // Falls back to module template for backward compatibility.
    // In graphic mode, suppress title/subtitle/body.
    // The graphic itself contains the messaging.
    $build = [
      '#type' => 'component',
      '#component' => 'saho:saho-hero-banner',
      '#props' => [
        'title' => $is_graphic_mode ? '' : (string) ($config['title'] ?? ''),
        'title_color' => (string) ($config['title_color'] ?? 'white'),
        'subtitle' => $is_graphic_mode ? '' : (string) ($config['subtitle'] ?? ''),
        'body' => $is_graphic_mode ? '' : $body_value,
        'background_image' => (string) ($background_image_url ?? ''),
        'background_image_mobile' => (string) ($background_image_mobile_url ?? $background_image_url ?? ''),
        'overlay_opacity' => (float) (($config['overlay_opacity'] ?? 50) / 100),
        'display_mode' => (string) $display_mode,
        'button_text' => (string) ($config['call_to_action']['title'] ?? ''),
        'button_url' => (string) ($button_url ?? ''),
        'button_target' => (string) ($button_target ?? '_self'),
        'button_rel' => (string) ($button_rel ?? ''),
        'button_style' => (string) ($config['call_to_action']['style'] ?? 'solid'),
        'image_width' => $image_width,
        'image_height' => $image_height,
      ],
      '#cache' => [
        'contexts' => ['url'],
        'tags' => !empty($config['background_image']) ? ['media:' . $config['background_image']] : [],
        'max-age' => 3600,
      ],
      '#attached' => [
        'library' => [
          'saho/saho-hero-banner',
        ],
      ],
    ];

    return $build;
  }

}
