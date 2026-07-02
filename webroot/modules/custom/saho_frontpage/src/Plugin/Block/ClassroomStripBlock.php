<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Placeholder "From the classroom" strip.
 *
 * Doors into the existing educational landings, styled as archive cards.
 *
 * @todo S6 (issue #428): replace with the curriculum-aligned collections +
 *   classroom_clip strip once the classroom hub lands.
 *
 * @Block(
 *   id = "saho_classroom_strip",
 *   admin_label = @Translation("SAHO Classroom strip (placeholder)"),
 *   category = @Translation("SAHO Front page"),
 * )
 */
final class ClassroomStripBlock extends BlockBase {

  /**
   * The classroom doors: label, landing URL and one-line description.
   */
  private const DOORS = [
    [
      'title' => 'CAPS Documents',
      'href' => '/caps-documents',
      'excerpt' => 'Curriculum Assessment Policy Statements for history teaching.',
    ],
    [
      'title' => 'Aids & Resources',
      'href' => '/aids-resources',
      'excerpt' => 'Educational aids, teaching tools and classroom resources.',
    ],
    [
      'title' => 'Debates in history education',
      'href' => '/debates-history-education',
      'excerpt' => 'Perspectives and debates shaping the history classroom.',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $cards = [];
    foreach (self::DOORS as $door) {
      $cards[] = [
        '#type' => 'component',
        '#component' => 'saho:saho-archive-card',
        '#props' => [
          'type' => 'article',
          'title' => $door['title'],
          'href' => $door['href'],
          'excerpt' => $door['excerpt'],
        ],
      ];
    }
    return [
      'heading' => [
        '#type' => 'component',
        '#component' => 'saho:saho-section-heading',
        '#props' => [
          'title' => 'From the classroom',
          'level' => 'h2',
          'href' => '/classroom',
          'link_label' => 'Open the classroom',
        ],
      ],
      // Plain container with the card-grid classes: the saho-card-grid twig
      // expects include()-style slots, which render arrays cannot fill.
      'grid' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'saho-card-grid',
            'saho-card-grid--columns-three',
            'saho-card-grid--gap-normal',
            'saho-card-grid--align-start',
          ],
        ],
        '#attached' => [
          'library' => ['saho/saho-card-grid'],
        ],
        'cards' => $cards,
      ],
    ];
  }

}
