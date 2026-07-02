<?php

/**
 * @file
 * Post update functions for saho_frontpage.
 */

declare(strict_types=1);

use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\node\Entity\Node;

/**
 * Rebuilds the front page (node 144647) as the Open Record catalogue home.
 *
 * The home layout is a Layout Builder DB override (not config), so this
 * rewrite runs here rather than through config import. The previous layout is
 * snapshotted once into the state key saho_frontpage.node_144647_layout_backup
 * and can be restored by uninstalling saho_frontpage, or manually:
 *
 * @code
 * drush php:eval '
 *   $sections = array_map([\Drupal\layout_builder\Section::class, "fromArray"],
 *     \Drupal::state()->get("saho_frontpage.node_144647_layout_backup"));
 *   \Drupal\node\Entity\Node::load(144647)
 *     ->set("layout_builder__layout", $sections)->save();'
 * @endcode
 */
function saho_frontpage_post_update_rebuild_node_144647_home(&$sandbox = NULL): string {
  $node = Node::load(144647);
  if ($node === NULL || !$node->hasField('layout_builder__layout')) {
    return 'Node 144647 not found or has no layout override; nothing to do.';
  }
  /** @var \Drupal\layout_builder\Field\LayoutSectionItemList $layout_field */
  $layout_field = $node->get('layout_builder__layout');
  $sections = $layout_field->getSections();

  // Idempotent guard: the rewrite starts with the front-door search on the
  // saho_standard layout, so its presence means we have already run.
  if (isset($sections[0]) && $sections[0]->getLayoutId() === 'saho_standard') {
    foreach ($sections[0]->getComponents() as $component) {
      if ($component->getPluginId() === 'saho_search_front') {
        return 'Open Record home layout already applied; nothing to do.';
      }
    }
  }

  // Snapshot the current layout exactly once; never overwrite a backup.
  $state = \Drupal::state();
  if ($state->get('saho_frontpage.node_144647_layout_backup') === NULL) {
    $state->set(
      'saho_frontpage.node_144647_layout_backup',
      array_map(static fn (Section $section): array => $section->toArray(), $sections),
    );
  }

  // Carry forward the stored configuration of the blocks we keep, so their
  // settings survive the rewrite. TDIH is NOT carried: both legacy instances
  // had show_today_history disabled; the wf01 aside wants today's chronology
  // entry rendered server-side, so it gets explicit configuration below.
  $keep = [
    'saho_top_read_content' => NULL,
    'featured_biography_block' => NULL,
    'saho_upcoming_events_block' => NULL,
    'tdih_interactive_block' => [
      'label' => 'This day in history',
      'label_display' => '0',
      'provider' => 'tdih',
      'display_mode' => 'compact',
      'show_date_picker' => FALSE,
      'date_picker_mode' => 'day_month',
      'show_today_history' => TRUE,
      'show_details_button' => TRUE,
      'show_explore_button' => TRUE,
      'show_header_title' => TRUE,
      'use_todays_date' => TRUE,
      'context_mapping' => [],
    ],
  ];
  foreach ($sections as $section) {
    foreach ($section->getComponents() as $component) {
      $plugin_id = $component->getPluginId();
      if (array_key_exists($plugin_id, $keep) && $keep[$plugin_id] === NULL) {
        $keep[$plugin_id] = $component->get('configuration');
      }
    }
  }

  $uuid = \Drupal::service('uuid');
  $make = static function (string $plugin_id, string $region, int $weight) use ($uuid, $keep): SectionComponent {
    $configuration = $keep[$plugin_id] ?? [
      'label' => '',
      'label_display' => '0',
      'context_mapping' => [],
    ];
    $configuration['id'] = $plugin_id;
    $component = new SectionComponent($uuid->generate(), $region, $configuration);
    $component->setWeight($weight);
    return $component;
  };

  // The wf01 vertical order: search, status bar, editorial row (+ this day),
  // browse index, recently added, keep-strip, classroom placeholder.
  $new_sections = [
    new Section('saho_standard', [], [$make('saho_search_front', 'content', 0)]),
    new Section('saho_standard', [], [$make('saho_archive_status_bar', 'content', 0)]),
    new Section('saho_editorial', [], [
      $make('saho_editorial_feature', 'lead', 0),
      $make('tdih_interactive_block', 'aside', 0),
    ]),
    new Section('saho_standard', [], [$make('saho_browse_index', 'content', 0)]),
    new Section('saho_standard', [], [$make('saho_recently_added', 'content', 0)]),
    new Section('saho_standard', [], [
      $make('saho_top_read_content', 'content', 0),
      $make('featured_biography_block', 'content', 1),
      $make('saho_upcoming_events_block', 'content', 2),
    ]),
    new Section('saho_standard', [], [$make('saho_classroom_strip', 'content', 0)]),
  ];

  $node->set('layout_builder__layout', $new_sections);
  $node->save();

  return sprintf(
    'Rebuilt node 144647 home layout: %d sections replaced by %d (backup in state).',
    count($sections),
    count($new_sections),
  );
}
