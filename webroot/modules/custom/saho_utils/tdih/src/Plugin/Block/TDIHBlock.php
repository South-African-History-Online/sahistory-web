<?php

namespace Drupal\tdih\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides a 'This Day in History' block.
 *
 * @Block(
 *   id = "tdih_block",
 *   admin_label = @Translation("This Day in History")
 * )
 */
class TDIHBlock extends BlockBase {

  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $render_array = [
      '#theme' => 'tdih',
      '#cache' => [
        'contexts' => ['user.permissions'],
        'tags' => ['node_list'],
        'max-age' => 3600,
      ],
      '#attached' => [
        'library' => [
          'tdih/tdih',
        ],
      ],
    ];

    // Debug log: Render array.
    $this->logger->debug('Render array: @data', ['@data' => json_encode($render_array)]);

    return $render_array;
  }
}
