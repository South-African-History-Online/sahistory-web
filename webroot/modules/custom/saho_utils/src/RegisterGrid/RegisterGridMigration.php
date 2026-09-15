<?php

declare(strict_types=1);

namespace Drupal\saho_utils\RegisterGrid;

/**
 * Maps the four legacy register block configurations onto register_grid_block.
 *
 * Pure functions so the post-update that rewrites Layout Builder components
 * and its unit test share one definition of the mapping.
 */
final class RegisterGridMigration {

  /**
   * Legacy plugin id => register id, subset key, count key, featured key.
   */
  public const LEGACY = [
    'history_classroom_block' => [
      'register' => 'classroom_grades',
      'subset' => 'grades_to_show',
      'count' => 'show_content_count',
      'featured' => 'show_featured_topic',
    ],
    'africa_regions_block' => [
      'register' => 'africa_regions',
      'subset' => NULL,
      'count' => 'show_content_count',
      'featured' => 'show_featured_country',
    ],
    'sa_provinces_block' => [
      'register' => 'sa_provinces',
      'subset' => NULL,
      'count' => 'show_place_count',
      'featured' => 'show_featured_place',
    ],
    'educational_resources_block' => [
      'register' => 'educational_resources',
      'subset' => 'resources_to_show',
      'count' => 'show_content_count',
      'featured' => 'show_featured_item',
    ],
  ];

  /**
   * Whether a plugin id is one of the retired register blocks.
   */
  public static function isLegacy(string $plugin_id): bool {
    return isset(self::LEGACY[$plugin_id]);
  }

  /**
   * Converts a legacy component configuration to a register_grid_block one.
   *
   * @param array $configuration
   *   The stored Layout Builder component configuration (must carry 'id').
   *
   * @return array|null
   *   The new configuration, or NULL when the id is not a legacy block.
   */
  public static function map(array $configuration): ?array {
    $legacy = self::LEGACY[$configuration['id'] ?? ''] ?? NULL;
    if ($legacy === NULL) {
      return NULL;
    }
    $display_mode = (string) ($configuration['display_mode'] ?? 'grid');
    return [
      'id' => 'register_grid_block',
      'label' => (string) ($configuration['label'] ?? ''),
      'label_display' => $configuration['label_display'] ?? '0',
      'provider' => 'saho_utils',
      'context_mapping' => $configuration['context_mapping'] ?? [],
      'register' => $legacy['register'],
      'subset' => $legacy['subset'] !== NULL ? (string) ($configuration[$legacy['subset']] ?? 'all') : 'all',
      'block_title' => (string) ($configuration['block_title'] ?? ''),
      'intro_text' => (string) ($configuration['intro_text'] ?? ''),
      // The carousel mode was retired in #462; it always rendered as a grid.
      'display_mode' => $display_mode === 'list' ? 'list' : 'grid',
      'show_count' => (bool) ($configuration[$legacy['count']] ?? TRUE),
      'show_featured' => (bool) ($configuration[$legacy['featured']] ?? TRUE),
    ];
  }

}
