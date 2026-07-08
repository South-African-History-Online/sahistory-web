<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\node\NodeInterface;

/**
 * Builds and repairs the Open Record home layout on node 144647.
 *
 * The home layout is a Layout Builder DB override (not config), which makes
 * it editable - and clobberable - through the Layout tab. A stale per-user
 * Layout Builder draft saved after a rebuild republishes whatever layout the
 * draft was opened on, silently replacing the Open Record home. This class
 * is the single owner of the canonical layout: the post_update delegates to
 * it, the saho:frontpage-rebuild drush command re-applies it on demand, and
 * hook_node_presave() uses isOpenRecordLayout() to warn when a save is about
 * to replace it.
 */
class HomeLayoutRebuilder {

  /**
   * The node id of the home page.
   */
  public const HOME_NID = 144647;

  /**
   * State key holding the pre-rebuild layout snapshot.
   */
  public const BACKUP_STATE_KEY = 'saho_frontpage.node_144647_layout_backup';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly StateInterface $state,
    private readonly UuidInterface $uuid,
    private readonly KeyValueExpirableFactoryInterface $keyValueExpirable,
  ) {
  }

  /**
   * Checks whether sections carry the Open Record home signature.
   *
   * The rebuilt layout always starts with the front-door search block on the
   * saho_standard layout, so that pair identifies the Open Record home.
   *
   * @param \Drupal\layout_builder\Section[] $sections
   *   The layout sections to inspect.
   */
  public function isOpenRecordLayout(array $sections): bool {
    $first = $sections[0] ?? NULL;
    if (!$first instanceof Section || $first->getLayoutId() !== 'saho_standard') {
      return FALSE;
    }
    foreach ($first->getComponents() as $component) {
      if ($component->getPluginId() === 'saho_search_front') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Rebuilds node 144647 as the Open Record catalogue home.
   *
   * Idempotent: returns early when the Open Record signature is already in
   * place. The replaced layout is snapshotted once into state (never
   * overwritten), and any pending Layout Builder drafts for the node are
   * discarded so a stale draft cannot republish the old layout later.
   *
   * @return string
   *   A human-readable result message.
   */
  public function rebuild(): string {
    $node = $this->entityTypeManager->getStorage('node')->load(self::HOME_NID);
    if (!$node instanceof NodeInterface || !$node->hasField('layout_builder__layout')) {
      return 'Node 144647 not found or has no layout override; nothing to do.';
    }
    /** @var \Drupal\layout_builder\Field\LayoutSectionItemList $layout_field */
    $layout_field = $node->get('layout_builder__layout');
    $sections = $layout_field->getSections();

    if ($this->isOpenRecordLayout($sections)) {
      $this->discardPendingDrafts();
      return 'Open Record home layout already applied; nothing to do.';
    }

    // Snapshot the current layout exactly once; never overwrite a backup.
    if ($this->state->get(self::BACKUP_STATE_KEY) === NULL) {
      $this->state->set(
        self::BACKUP_STATE_KEY,
        array_map(static fn (Section $section): array => $section->toArray(), $sections),
      );
    }

    // Carry forward the stored configuration of the blocks we keep, so their
    // settings survive the rewrite. TDIH is NOT carried: both legacy
    // instances had show_today_history disabled; the wf01 aside wants today's
    // chronology entry rendered server-side, so it gets explicit
    // configuration below.
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

    $make = function (string $plugin_id, string $region, int $weight) use ($keep): SectionComponent {
      $configuration = $keep[$plugin_id] ?? [
        'label' => '',
        'label_display' => '0',
        'context_mapping' => [],
      ];
      $configuration['id'] = $plugin_id;
      $component = new SectionComponent($this->uuid->generate(), $region, $configuration);
      $component->setWeight($weight);
      return $component;
    };

    // The wf01 vertical order: search, status bar, editorial row (+ this
    // day), browse index, recently added, keep-strip, classroom placeholder.
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
    $this->discardPendingDrafts();

    return sprintf(
      'Rebuilt node 144647 home layout: %d sections replaced by %d (backup in state).',
      count($sections),
      count($new_sections),
    );
  }

  /**
   * Discards pending Layout Builder drafts for the home node.
   *
   * Layout Builder keeps per-user drafts in the shared tempstore until "Save
   * layout" publishes them; a draft opened before a rebuild still holds the
   * old layout and would republish it wholesale.
   */
  public function discardPendingDrafts(): void {
    $store = $this->keyValueExpirable->get('tempstore.shared.layout_builder.section_storage.overrides');
    foreach (array_keys($store->getAll()) as $key) {
      if (str_starts_with((string) $key, 'node.' . self::HOME_NID . '.')) {
        $store->delete($key);
      }
    }
  }

}
