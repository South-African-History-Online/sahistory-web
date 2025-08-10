<?php

namespace Drupal\hero_banner\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Hero Banner Inline Block' plugin.
 *
 * @Block(
 *   id = "hero_banner_inline_block",
 *   admin_label = @Translation("Hero Banner Inline Block"),
 *   category = @Translation("All custom"),
 *   deriver = "Drupal\hero_banner\Plugin\Derivative\HeroBannerInlineBlockDeriver"
 * )
 */
class HeroBannerInlineBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new HeroBannerInlineBlock instance.
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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FileUrlGeneratorInterface $file_url_generator, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
    $this->renderer = $renderer;
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
      $container->get('file_url_generator'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_content_id = $this->getDerivativeId();

    if (!$block_content_id) {
      return [];
    }

    $block_content = $this->entityTypeManager
      ->getStorage('block_content')
      ->load($block_content_id);

    if (!$block_content || $block_content->bundle() !== 'hero_banner') {
      return [];
    }

    // Get the title from info field (block label).
    $title = $block_content->label() ?: '';

    // Get the body content.
    $body = '';
    if ($block_content->hasField('body') && !$block_content->get('body')->isEmpty()) {
      $body_field = $block_content->get('body')->first();
      $body = check_markup($body_field->value, $body_field->format);
    }

    // Get the background image URL.
    $background_image_url = NULL;
    if ($block_content->hasField('field_hero_banner') && !$block_content->get('field_hero_banner')->isEmpty()) {
      $media = $block_content->get('field_hero_banner')->entity;
      if ($media && $media->hasField('field_media_image')) {
        $image_field = $media->get('field_media_image');
        if (!$image_field->isEmpty()) {
          $file = $image_field->entity;
          if ($file) {
            $background_image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
          }
        }
      }
    }

    // Get the CTA button data.
    $button_text = '';
    $button_url = NULL;
    $button_attributes = ['class' => ['hero-banner__button', 'btn', 'btn-primary']];

    if ($block_content->hasField('field_call_to_action_button') && !$block_content->get('field_call_to_action_button')->isEmpty()) {
      $link_field = $block_content->get('field_call_to_action_button')->first();

      // Get the button text.
      $button_text = $link_field->title ?: '';

      // Get the button URL.
      if (!empty($link_field->uri)) {
        try {
          $url = Url::fromUri($link_field->uri);
          $button_url = $url->toString();

          // Add target="_blank" for external links.
          if ($url->isExternal()) {
            $button_attributes['target'] = '_blank';
            $button_attributes['rel'] = 'noopener noreferrer';
          }
        }
        catch (\Exception $e) {
          // Log the error but continue rendering.
          \Drupal::logger('hero_banner')->error('Invalid URL in hero banner: @error', ['@error' => $e->getMessage()]);
        }
      }
    }

    // Build the render array.
    $build = [
      '#theme' => 'hero_banner_block',
      '#title' => $title,
      '#subtitle' => '',
      '#body' => $body,
      '#background_image' => $background_image_url,
      '#button_text' => $button_text,
      '#button_url' => $button_url,
      '#button_attributes' => $button_attributes,
      '#overlay_opacity' => 0.5,
      '#attached' => [
        'library' => [
          'hero_banner/hero_banner_modern',
        ],
      ],
      '#cache' => [
        'tags' => $block_content->getCacheTags(),
      ],
    ];

    return $build;
  }

}
