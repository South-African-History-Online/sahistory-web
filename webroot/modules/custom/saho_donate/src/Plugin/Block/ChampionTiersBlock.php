<?php

namespace Drupal\saho_donate\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Site\Settings;

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
    $shop = rtrim(Settings::get('saho_shop_url', 'https://shop.sahistory.org.za'), '/');

    return [
      '#theme' => 'saho_champion_tiers_block',
      '#monthly_url' => $shop . '/product/saho-champion-monthly-support-0',
      '#annual_url' => $shop . '/product/saho-champion-annual-support-0',
      '#patron_url' => $shop . '/champion#patron',
      '#attached' => [
        'library' => ['saho_donate/donate-page'],
      ],
      '#cache' => [
        'max-age' => 86400,
      ],
    ];
  }

}
