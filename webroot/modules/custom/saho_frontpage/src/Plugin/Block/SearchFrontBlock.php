<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Renders the front-door search field, full width with scope chips.
 *
 * @Block(
 *   id = "saho_search_front",
 *   admin_label = @Translation("SAHO Front-door search"),
 *   category = @Translation("SAHO Front page"),
 * )
 */
final class SearchFrontBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#type' => 'component',
      '#component' => 'saho:saho-search-field',
      '#props' => [
        'placeholder' => 'Search people, events, places, dates…',
        'action' => '/search',
        'size' => 'lg',
        'scopes' => ['All', 'People', 'Events', 'Places', 'Archive'],
      ],
    ];
  }

}
