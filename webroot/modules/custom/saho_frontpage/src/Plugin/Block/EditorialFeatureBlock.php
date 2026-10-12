<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_frontpage\CurrentFeatureService;
use Drupal\saho_refs\DisplayRefService;
use Drupal\saho_utils\Service\ImageExtractorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the current editorial feature: the lead of the front-page row.
 *
 * The feature node comes from the shared current-feature engine
 * (saho_frontpage.current_feature), which /featured also leads with.
 *
 * @Block(
 *   id = "saho_editorial_feature",
 *   admin_label = @Translation("SAHO Editorial feature"),
 *   category = @Translation("SAHO Front page"),
 * )
 */
final class EditorialFeatureBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Image styles for the srcset, smallest first.
   *
   * The feature is the front page's LCP candidate, so it ships WebP
   * derivatives sized to the column, never the raw upload.
   */
  private const IMAGE_STYLES = ['saho_hero_mobile', 'saho_feature'];

  /**
   * Minimum original width for a feature image.
   *
   * Narrower originals would be stretched blurry across the card (legacy
   * bio_pics are often 275px), so those features stay text-only.
   */
  private const MIN_IMAGE_WIDTH = 480;

  /**
   * Constructs the block.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\saho_frontpage\CurrentFeatureService $currentFeature
   *   The shared current-feature engine.
   * @param \Drupal\saho_refs\DisplayRefService $displayRef
   *   The display reference service.
   * @param \Drupal\saho_utils\Service\ImageExtractorService $imageExtractor
   *   Resolves a node's image field (image, file or media) to its file.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly CurrentFeatureService $currentFeature,
    private readonly DisplayRefService $displayRef,
    private readonly ImageExtractorService $imageExtractor,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('saho_frontpage.current_feature'),
      $container->get('saho_refs.display_ref'),
      $container->get('saho_utils.image_extractor'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->currentFeature->node();
    if (!$node instanceof NodeInterface) {
      // Publishing eligible content should restore the hero, so carry the
      // cache metadata even when nothing resolves yet.
      return [
        '#cache' => [
          'tags' => ['node_list'],
          'max-age' => 3600,
        ],
      ];
    }
    $image = $this->buildImage($node);
    return [
      '#type' => 'component',
      '#component' => 'saho:saho-home-feature',
      '#props' => $image + [
        'kicker' => $this->t('Current feature'),
        'title' => (string) $node->label(),
        'standfirst' => $this->currentFeature->standfirst($node),
        'href' => $node->toUrl()->toString(),
        'cta_label' => $this->t('Open the feature'),
        'secondary_href' => '/timelines',
        'secondary_label' => $this->t('View chronology'),
        'meta' => $this->t('Editorial register · @ref', ['@ref' => $this->displayRef->getRef($node)]),
        'heading_level' => 'h1',
      ],
      '#cache' => [
        'tags' => ['node_list', 'node:' . $node->id()],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Builds the image props for the feature node, or none without an image.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The feature node.
   *
   * @return array
   *   image_src, image_srcset, image_alt, image_width and image_height, or an
   *   empty array when the node has no usable image.
   */
  private function buildImage(NodeInterface $node): array {
    $field_name = $this->imageExtractor->findImageFieldForEntity($node);
    if ($field_name === NULL) {
      return [];
    }
    $file = $this->imageExtractor->resolveFile($node, $field_name);
    if (!$file instanceof FileInterface) {
      return [];
    }

    // Image items carry width/height/alt in their values; file items do not,
    // and media keeps them on its own source field.
    $item = $node->get($field_name)->first();
    $media = $item instanceof EntityReferenceItem ? $item->get('entity')->getValue() : NULL;
    if ($media instanceof MediaInterface) {
      $source = $media->getSource()->getConfiguration()['source_field'] ?? NULL;
      $item = $source && $media->hasField($source) ? $media->get($source)->first() : NULL;
    }
    $values = $item?->getValue() ?? [];
    $width = $values['width'] ?? NULL;
    $height = $values['height'] ?? NULL;
    $alt = $values['alt'] ?? NULL;
    if ($width !== NULL && (int) $width < self::MIN_IMAGE_WIDTH) {
      return [];
    }

    /** @var \Drupal\image\ImageStyleInterface[] $styles */
    $styles = $this->entityTypeManager->getStorage('image_style')
      ->loadMultiple(self::IMAGE_STYLES);
    $uri = $file->getFileUri();
    $srcset = [];
    $src = NULL;
    $dimensions = [];
    foreach (self::IMAGE_STYLES as $style_id) {
      if (!isset($styles[$style_id])) {
        continue;
      }
      $dimensions = ['width' => $width, 'height' => $height];
      $styles[$style_id]->transformDimensions($dimensions, $uri);
      // Scale styles never upscale, so a small original yields the same
      // width twice: list each real width once.
      $descriptor = (int) ($dimensions['width'] ?? 0);
      if ($descriptor > 0 && isset($srcset[$descriptor])) {
        continue;
      }
      // The largest style is the fallback src and sets the intrinsic ratio.
      $src = $styles[$style_id]->buildUrl($uri);
      $srcset[$descriptor] = $descriptor > 0 ? $src . ' ' . $descriptor . 'w' : $src;
    }
    if ($src === NULL) {
      return [];
    }

    return [
      'image_src' => $src,
      // Without known widths a srcset would be invalid; src alone is enough.
      'image_srcset' => isset($srcset[0]) ? NULL : implode(', ', $srcset),
      // Fall back to the title: the image is the feature's subject, not
      // decoration, so an empty alt would hide it from screen readers.
      'image_alt' => trim((string) $alt) !== '' ? (string) $alt : (string) $node->label(),
      'image_width' => !empty($dimensions['width']) ? (int) $dimensions['width'] : NULL,
      'image_height' => !empty($dimensions['height']) ? (int) $dimensions['height'] : NULL,
    ];
  }

}
