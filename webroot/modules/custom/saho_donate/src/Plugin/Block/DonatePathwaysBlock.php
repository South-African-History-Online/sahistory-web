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
      // Default ships with the module so the QR renders even when no public
      // file has been uploaded. Operators can override via settings.php with
      // `$settings['saho_snapscan_qr_image'] = '/sites/default/files/...';`.
      '#snapscan_qr_image' => Settings::get('saho_snapscan_qr_image', '/modules/custom/saho_donate/images/snapscan-qr.gif'),
      // PayPal donate-button URL (hosted button). Pulls from settings so the
      // button id can be rotated without a code change.
      '#paypal_url' => Settings::get('saho_paypal_url', 'https://www.paypal.com/donate?hosted_button_id=CZPNF5PNPW8SL'),
      '#attached' => [
        'library' => ['saho_donate/donate-page'],
      ],
      '#cache' => [
        'max-age' => 86400,
      ],
    ];
  }

}
