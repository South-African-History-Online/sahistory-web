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

    // The main site has no commerce data, so the champion prices cannot be
    // read from product variations here (the shop theme does that). They
    // come from settings.php instead - overridable without a code deploy.
    // Set $settings['saho_champion_prices'] to an array keyed by 'monthly',
    // 'annual' and 'patron' to override; otherwise the shop fallbacks
    // below are used. Keep them aligned with the shop's CHAMPION-* SKUs.
    $prices = Settings::get('saho_champion_prices', []) + [
      'monthly' => '100',
      'annual' => '1,000',
      'patron' => '2,500',
    ];

    return [
      '#theme' => 'saho_champion_tiers_block',
      '#monthly_url' => $shop . '/product/saho-champion-monthly-support',
      '#annual_url' => $shop . '/product/saho-champion-annual-support',
      '#patron_url' => $shop . '/product/saho-champion-patron-support',
      '#monthly_price' => $prices['monthly'],
      '#annual_price' => $prices['annual'],
      '#patron_price' => $prices['patron'],
      // Custom-amount escape hatch points back at the main site donate page.
      // Defaults to the prod URL but is overridable via settings.php so a
      // local DDEV env can point at sahistory-web.ddev.site instead of
      // exiting to the real prod site during testing. The #donate-form
      // fragment lands the visitor on the donation form with the "Other
      // amount" field selected and focused (see saho_donate/js/donate.js);
      // a URL fragment survives the main-site -> shop 302 redirect intact.
      '#donate_url' => Settings::get('saho_main_donate_url', 'https://sahistory.org.za/donate') . '#donate-form',
      '#attached' => [
        'library' => ['saho_donate/donate-page'],
      ],
      '#cache' => [
        'max-age' => 86400,
      ],
    ];
  }

}
