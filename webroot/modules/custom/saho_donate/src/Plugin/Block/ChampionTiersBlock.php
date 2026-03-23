<?php

namespace Drupal\saho_donate\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides the Champion Tiers block for Layout Builder.
 *
 * @Block(
 *   id = "saho_champion_tiers",
 *   admin_label = @Translation("Champion Tiers"),
 *   category = @Translation("SAHO Donate"),
 * )
 */
class ChampionTiersBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'saho_champion_tiers_block',
      '#attached' => [
        'library' => ['saho_donate/donate-page'],
      ],
      '#cache' => [
        'max-age' => 86400,
      ],
    ];
  }

}
