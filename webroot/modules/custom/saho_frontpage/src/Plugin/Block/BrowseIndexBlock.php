<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\saho_frontpage\ArchiveCountsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the browse index: six typed squares with accent edges and counts.
 *
 * @Block(
 *   id = "saho_browse_index",
 *   admin_label = @Translation("SAHO Browse index"),
 *   category = @Translation("SAHO Front page"),
 * )
 */
final class BrowseIndexBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the block.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\saho_frontpage\ArchiveCountsService $archiveCounts
   *   The archive counts service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly ArchiveCountsService $archiveCounts,
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
      $container->get('saho_frontpage.archive_counts'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#type' => 'component',
      '#component' => 'saho:saho-browse-index',
      '#props' => [
        'title' => 'Browse the archive',
        'items' => $this->archiveCounts->getBrowseTypes(),
      ],
      '#cache' => [
        'tags' => ['node_list'],
        'max-age' => 3600,
      ],
    ];
  }

}
