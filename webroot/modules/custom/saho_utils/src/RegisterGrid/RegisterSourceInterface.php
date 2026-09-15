<?php

declare(strict_types=1);

namespace Drupal\saho_utils\RegisterGrid;

/**
 * A register: the data behind one configuration of the Register grid block.
 *
 * Each source turns a fixed catalogue (SA provinces, African regions,
 * classroom grades, educational resource types) into archive-card items so a
 * single block plugin and template can render all of them. Item shape:
 *   title, href, type (saho-archive-card type), badge_label, kicker (the
 *   card's mono "dates" line), excerpt, meta, image, image_alt.
 */
interface RegisterSourceInterface {

  /**
   * Machine name stored in the block configuration (e.g. classroom_grades).
   */
  public function id(): string;

  /**
   * Admin label for the register select and the default block title.
   */
  public function label(): string;

  /**
   * Subset options (value => label), or [] when the register has none.
   *
   * The 'all' key must exist when options are returned.
   */
  public function subsetOptions(): array;

  /**
   * Builds the card items.
   *
   * @param string $subset
   *   The chosen subset key ('all' when the register has none).
   * @param bool $show_count
   *   Whether to include the record count line.
   * @param bool $show_featured
   *   Whether to include the featured record (newest / most populated).
   *
   * @return array[]
   *   Archive-card items, presentation order.
   */
  public function items(string $subset, bool $show_count, bool $show_featured): array;

  /**
   * Cache tags that invalidate the items.
   *
   * @return string[]
   *   Cache tags.
   */
  public function cacheTags(): array;

  /**
   * Text shown when the register yields no items.
   */
  public function emptyText(): string;

}
