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
      '#monthly_url' => $shop . '/product/saho-champion-monthly-support',
      '#annual_url' => $shop . '/product/saho-champion-annual-support',
      '#patron_url' => $shop . '/champion#patron',
      // Custom-amount escape hatch points back at the main site donate page.
      // Defaults to the prod URL but is overridable via settings.php so a
      // local DDEV env can point at sahistory-web.ddev.site instead of
      // exiting to the real prod site during testing.
      '#donate_url' => Settings::get('saho_main_donate_url', 'https://sahistory.org.za/donate'),
      '#attached' => [
        'library' => ['saho_donate/donate-page'],
      ],
      '#cache' => [
        'max-age' => 86400,
      ],
    ];
  }

}
