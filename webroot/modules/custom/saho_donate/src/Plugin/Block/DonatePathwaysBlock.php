<?php

namespace Drupal\saho_donate\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Site\Settings;

/**
 * Provides the Donation Pathways block for Layout Builder.
 *
 * @Block(
 *   id = "saho_donate_pathways",
 *   admin_label = @Translation("Donation Pathways"),
 *   category = @Translation("SAHO Donate"),
 * )
 */
class DonatePathwaysBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'saho_donate_pathways_block',
      '#champion_url' => Settings::get('saho_champion_url', 'https://shop.sahistory.org.za/champion'),
      '#snapscan_url' => Settings::get('saho_snapscan_url', 'https://pos.snapscan.io/qr/SAHO'),
      '#snapscan_qr_image' => Settings::get('saho_snapscan_qr_image', '/sites/default/files/saho_snapscan.gif'),
      '#attached' => [
        'library' => ['saho_donate/donate-page'],
      ],
      '#cache' => [
        'max-age' => 86400,
      ],
    ];
  }

}
