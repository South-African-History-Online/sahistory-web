<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\saho_frontpage\ArchiveCountsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the archive status bar: live record counts in mono.
 *
 * @Block(
 *   id = "saho_archive_status_bar",
 *   admin_label = @Translation("SAHO Archive status bar"),
 *   category = @Translation("SAHO Front page"),
 * )
 */
final class ArchiveStatusBarBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
      '#component' => 'saho:saho-archive-status-bar',
      '#props' => [
        'stats' => $this->archiveCounts->getCounts(),
      ],
      '#cache' => [
        'tags' => ['node_list'],
        'max-age' => 3600,
      ],
    ];
  }

}
