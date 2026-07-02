<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\saho_frontpage\ArchiveCountsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the recently-added records as a ruled index table.
 *
 * @Block(
 *   id = "saho_recently_added",
 *   admin_label = @Translation("SAHO Recently added records"),
 *   category = @Translation("SAHO Front page"),
 * )
 */
final class RecentlyAddedBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  public function defaultConfiguration(): array {
    return ['limit' => 8];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $limit = (int) ($this->configuration['limit'] ?? 8);
    return [
      'heading' => [
        '#type' => 'component',
        '#component' => 'saho:saho-section-heading',
        '#props' => [
          'title' => 'Recently added to the archive',
          'level' => 'h2',
        ],
      ],
      'table' => [
        '#type' => 'component',
        '#component' => 'saho:saho-index-table',
        '#props' => [
          'columns' => [
            ['key' => 'ref', 'label' => 'Ref', 'mono' => TRUE, 'width' => '110px'],
            ['key' => 'type', 'label' => 'Type', 'width' => '150px'],
            ['key' => 'title', 'label' => 'Record', 'sortable' => TRUE],
            ['key' => 'dates', 'label' => 'Dates', 'mono' => TRUE, 'muted' => TRUE, 'width' => '150px'],
            ['key' => 'status', 'label' => 'Status', 'mono' => TRUE, 'muted' => TRUE, 'width' => '110px'],
          ],
          'rows' => $this->archiveCounts->getRecent($limit),
          'caption' => 'Recently added records',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list'],
        'max-age' => 3600,
      ],
    ];
  }

}
