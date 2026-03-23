<?php

namespace Drupal\saho_donate\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
      '#attached' => [
        'library' => ['saho_donate/donate-page'],
      ],
      '#cache' => [
        'max-age' => 86400,
      ],
    ];
  }

}
