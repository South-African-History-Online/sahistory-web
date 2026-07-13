<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_utils\Service\ImageExtractorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The "From the classroom" strip (Open Record S6, #428).
 *
 * Surfaces the newest published classroom clips as poster cards. Until
 * clips exist, the strip falls back to the three curriculum doors so the
 * band never renders empty.
 *
 * @Block(
 *   id = "saho_classroom_strip",
 *   admin_label = @Translation("SAHO Classroom strip"),
 *   category = @Translation("SAHO Front page"),
 * )
 */
final class ClassroomStripBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Fallback doors into the educational landings.
   */
  private const DOORS = [
    [
      'title' => 'CAPS Documents',
      'href' => '/caps-documents',
      'excerpt' => 'Curriculum Assessment Policy Statements for history teaching.',
    ],
    [
      'title' => 'Aids & Resources',
      'href' => '/aids-resources',
      'excerpt' => 'Educational aids, teaching tools and classroom resources.',
    ],
    [
      'title' => 'Debates in history education',
      'href' => '/debates-history-education',
      'excerpt' => 'Perspectives and debates shaping the history classroom.',
    ],
  ];

  /**
   * Constructs the block.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\saho_utils\Service\ImageExtractorService $imageExtractor
   *   The image extractor service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ImageExtractorService $imageExtractor,
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
      $container->get('entity_type.manager'),
      $container->get('saho_utils.image_extractor'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cards = [];
    foreach ($this->recentClips() as $clip) {
      $props = [
        'type' => 'archive',
        'badge_label' => 'Clip',
        'title' => (string) $clip->label(),
        'href' => $clip->toUrl()->toString(),
        'excerpt' => $this->clipExcerpt($clip),
      ];
      $poster = $this->posterUrl($clip);
      if ($poster !== NULL) {
        $props['image'] = $poster;
        $props['image_alt'] = (string) $clip->label();
      }
      $cards[] = [
        '#type' => 'component',
        '#component' => 'saho:saho-archive-card',
        '#props' => $props,
      ];
    }

    // Until clips exist, the doors keep the band populated.
    if ($cards === []) {
      foreach (self::DOORS as $door) {
        $cards[] = [
          '#type' => 'component',
          '#component' => 'saho:saho-archive-card',
          '#props' => [
            'type' => 'article',
            'title' => $door['title'],
            'href' => $door['href'],
            'excerpt' => $door['excerpt'],
          ],
        ];
      }
    }

    return [
      'heading' => [
        '#type' => 'component',
        '#component' => 'saho:saho-section-heading',
        '#props' => [
          'title' => 'From the classroom',
          'level' => 'h2',
          'href' => '/classroom',
          'link_label' => 'Open the classroom',
        ],
      ],
      // Plain container with the card-grid classes: the saho-card-grid twig
      // expects include()-style slots, which render arrays cannot fill.
      'grid' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'saho-card-grid',
            'saho-card-grid--columns-three',
            'saho-card-grid--gap-normal',
            'saho-card-grid--align-start',
          ],
        ],
        '#attached' => [
          'library' => ['saho/saho-card-grid'],
        ],
        'cards' => $cards,
      ],
      '#cache' => [
        'tags' => ['node_list:classroom_clip'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Loads the newest published clips.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Up to three clips, newest first.
   */
  private function recentClips(): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $nids = $storage->getQuery()
      ->condition('type', 'classroom_clip')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 3)
      ->execute();
    return $nids === [] ? [] : array_values($storage->loadMultiple($nids));
  }

  /**
   * Builds the poster derivative URL, skipping files missing on disk.
   */
  private function posterUrl(NodeInterface $clip): ?string {
    if (!$clip->hasField('field_poster') || $clip->get('field_poster')->isEmpty()) {
      return NULL;
    }
    $file = $clip->get('field_poster')->first()->entity ?? NULL;
    if (!$file instanceof FileInterface || !file_exists($file->getFileUri())) {
      return NULL;
    }
    return $this->imageExtractor->extractImageWithDerivatives($clip, 'max_650x650', 'field_poster');
  }

  /**
   * One plain-text summary line for the card.
   */
  private function clipExcerpt(NodeInterface $clip): string {
    if (!$clip->hasField('body') || $clip->get('body')->isEmpty()) {
      return '';
    }
    $item = $clip->get('body')->first();
    $text = trim((string) $item->get('summary')->getValue()) ?: trim((string) $item->get('value')->getValue());
    $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5));
    return mb_strlen($text) > 140 ? mb_substr($text, 0, 139) . "\u{2026}" : $text;
  }

}
