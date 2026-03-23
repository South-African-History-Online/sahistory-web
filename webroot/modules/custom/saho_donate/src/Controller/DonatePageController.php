<?php

namespace Drupal\saho_donate\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the /donate landing page.
 */
class DonatePageController extends ControllerBase {

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected BlockManagerInterface $blockManager;

  /**
   * Constructs a DonatePageController.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block plugin manager.
   */
  public function __construct(BlockManagerInterface $block_manager) {
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('plugin.manager.block')
    );
  }

  /**
   * Renders the /donate landing page.
   *
   * @return array
   *   A render array.
   */
  public function page(): array {
    $wall_of_champions = [];
    if ($this->blockManager->hasDefinition('wall_of_champions_block')) {
      $block = $this->blockManager->createInstance('wall_of_champions_block', []);
      $wall_of_champions = $block->build();
    }

    return [
      '#theme' => 'saho_donate_page',
      '#snapscan_url' => 'https://pos.snapscan.io/qr/SAHO',
      '#wall_of_champions' => $wall_of_champions,
      '#attached' => [
        'library' => ['saho_donate/donate-page'],
      ],
    ];
  }

}
