<?php

/**
 * @file
 * Post update functions for saho_classroom.
 */

declare(strict_types=1);

use Drupal\node\Entity\Node;

/**
 * Retags the legacy classroom corpus onto the Classroom 2.0 taxonomies.
 *
 * Additive-only and idempotent: a new field is only populated when it is
 * currently empty, so re-running the update (or running it after editors
 * have started tagging) never overwrites data. The corpus is the union of:
 * - nodes with a legacy field_classroom value (classroom vocabulary),
 * - nodes with a legacy field_classroom_categories value,
 * - archive/article nodes tagged with the "CAPS Document" media library
 *   type term (tid 34422),
 * - the "shadow corpus": article/archive nodes whose title carries a grade
 *   marker (e.g. "Grade 12 - Topic 2 - Independent Africa") but which have
 *   no classroom taxonomy at all.
 *
 * Mapping rules:
 * - field_classroom_grade: legacy grade terms from both legacy vocabularies
 *   map to the new "Grade N" terms; shadow nodes parse the grade from the
 *   title (grades 4-12 only).
 * - field_classroom_subject: History for the whole corpus (per the content
 *   audit), except titles that are explicitly another subject (e.g. the
 *   "English Home Language" exam papers), which are left empty because the
 *   classroom_subject vocabulary cannot represent them.
 * - field_classroom_resource_type: derived from title keywords and the
 *   legacy CAPS Document term, first match wins, falling back to
 *   "Topic overview".
 * - field_caps_topic: best-effort positional match only. A title matching
 *   "Grade N ... Term/Topic M" selects the Mth caps_topic child (by weight)
 *   under the "Grade N" parent. When the title also names a topic, the
 *   positional pick is vetoed unless at least one significant title word
 *   appears in the term name - a wrong topic is worse than none.
 */
function saho_classroom_post_update_retag_legacy_corpus(&$sandbox = NULL): string {
  $database = \Drupal::database();
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  if (!isset($sandbox['nids'])) {
    // Resolve the new-model term ids by name so the update is portable
    // across environments where tids may differ.
    $grade_tids = [];
    foreach ($term_storage->loadTree('classroom_grade') as $term) {
      if (preg_match('/^Grade (\d{1,2})$/', $term->name, $m)) {
        $grade_tids[(int) $m[1]] = (int) $term->tid;
      }
    }
    $type_tids = [];
    foreach ($term_storage->loadTree('classroom_resource_type') as $term) {
      $type_tids[$term->name] = (int) $term->tid;
    }
    $subject_tid = 0;
    foreach ($term_storage->loadTree('classroom_subject') as $term) {
      if ($term->name === 'History') {
        $subject_tid = (int) $term->tid;
      }
    }
    // caps_topic: ordered children (by weight) per "Grade N" parent.
    $caps_parents = [];
    $caps_children = [];
    $caps_names = [];
    foreach ($term_storage->loadTree('caps_topic') as $term) {
      $tid = (int) $term->tid;
      $caps_names[$tid] = $term->name;
      if ((int) $term->parents[0] === 0) {
        if (preg_match('/^Grade (\d{1,2})$/', $term->name, $m)) {
          $caps_parents[$tid] = (int) $m[1];
        }
      }
    }
    foreach ($term_storage->loadTree('caps_topic') as $term) {
      $parent = (int) $term->parents[0];
      if (isset($caps_parents[$parent])) {
        // loadTree() returns terms ordered by weight within each parent.
        $caps_children[$caps_parents[$parent]][] = (int) $term->tid;
      }
    }

    if (count($grade_tids) !== 9 || !$subject_tid || count($type_tids) < 11) {
      return 'saho_classroom retag: new-model vocabularies are incomplete, nothing migrated.';
    }

    // Legacy corpus: any node carrying legacy classroom taxonomy or the
    // CAPS Document media library type term.
    $legacy_nids = $database->query('SELECT DISTINCT entity_id FROM {node__field_classroom} UNION SELECT DISTINCT entity_id FROM {node__field_classroom_categories} UNION SELECT DISTINCT entity_id FROM {node__field_media_library_type} WHERE field_media_library_type_target_id = :caps', [
      ':caps' => 34422,
    ])->fetchCol();

    // Shadow corpus: grade-marker titles with no classroom taxonomy at all,
    // restricted to the bundles that carry the new fields.
    $shadow_nids = $database->query("SELECT DISTINCT n.nid FROM {node_field_data} n LEFT JOIN {node__field_classroom} fc ON fc.entity_id = n.nid LEFT JOIN {node__field_classroom_categories} fcc ON fcc.entity_id = n.nid WHERE n.type IN ('article', 'archive') AND LOWER(n.title) REGEXP :re AND fc.entity_id IS NULL AND fcc.entity_id IS NULL", [
      ':re' => 'grade[[:space:]]*[0-9]',
    ])->fetchCol();

    $nids = array_map('intval', array_unique(array_merge($legacy_nids, $shadow_nids)));
    sort($nids);

    $sandbox['nids'] = $nids;
    $sandbox['shadow'] = array_fill_keys(array_map('intval', $shadow_nids), TRUE);
    $sandbox['grade_tids'] = $grade_tids;
    $sandbox['type_tids'] = $type_tids;
    $sandbox['subject_tid'] = $subject_tid;
    $sandbox['caps_children'] = $caps_children;
    $sandbox['caps_names'] = $caps_names;
    $sandbox['total'] = count($nids);
    $sandbox['processed'] = 0;
    $sandbox['retagged'] = 0;
    $sandbox['skipped'] = 0;
    $sandbox['count_grades'] = 0;
    $sandbox['count_types'] = 0;
    $sandbox['count_topics'] = 0;
  }

  // Legacy tid => grade number, for both legacy vocabularies.
  $legacy_grade_map = [
    // Vocabulary classroom: "History Classroom Grade Four".."Twelve".
    35614 => 4,
    35615 => 5,
    35616 => 6,
    35617 => 7,
    35618 => 8,
    35619 => 9,
    35620 => 10,
    35621 => 11,
    35622 => 12,
    // field_classroom_categories vocabulary: "Grade 4".."Grade 12".
    60 => 4,
    61 => 5,
    62 => 6,
    63 => 7,
    64 => 8,
    65 => 9,
    66 => 10,
    67 => 11,
    68 => 12,
  ];

  // Words too generic to confirm a caps_topic name match.
  $stopwords = [
    'the', 'and', 'for', 'from', 'with', 'history', 'caps', 'grade', 'term',
    'topic', 'intermediate', 'senior', 'fet', 'phase', 'classroom',
    'timeline', 'timelines', 'activities', 'activity', 'ideas', 'home',
    'support', 'learner', 'teacher', 'questions', 'question', 'essay',
    'key', 'terms', 'definitions', 'national', 'certificate', 'paper',
    'memorandum', 'exam', 'feedback', 'archive', 'worksheet', 'quiz',
    'glossary', 'source', 'based', 'africa', 'african', 'south',
  ];

  $batch = array_slice($sandbox['nids'], $sandbox['processed'], 50);

  foreach ($batch as $nid) {
    $sandbox['processed']++;
    $node = Node::load($nid);
    if (!$node || !$node->hasField('field_classroom_grade')) {
      $sandbox['skipped']++;
      continue;
    }
    $title = $node->getTitle();
    $is_shadow = isset($sandbox['shadow'][$nid]);
    $has_caps_doc = FALSE;
    if ($node->hasField('field_media_library_type')) {
      foreach ($node->get('field_media_library_type') as $item) {
        if ((int) $item->target_id === 34422) {
          $has_caps_doc = TRUE;
        }
      }
    }
    $changed = FALSE;

    // 1. field_classroom_grade: legacy grade terms, or title parsing for
    // the shadow corpus.
    if ($node->get('field_classroom_grade')->isEmpty()) {
      $grades = [];
      foreach (['field_classroom', 'field_classroom_categories'] as $field) {
        if (!$node->hasField($field)) {
          continue;
        }
        foreach ($node->get($field) as $item) {
          $legacy_tid = (int) $item->target_id;
          if (isset($legacy_grade_map[$legacy_tid])) {
            $grades[$legacy_grade_map[$legacy_tid]] = TRUE;
          }
        }
      }
      if ($is_shadow && preg_match('/grade\s*(\d{1,2})/i', $title, $m)) {
        $number = (int) $m[1];
        if ($number >= 4 && $number <= 12) {
          $grades[$number] = TRUE;
        }
      }
      if ($grades) {
        ksort($grades);
        $targets = [];
        foreach (array_keys($grades) as $number) {
          $targets[] = ['target_id' => $sandbox['grade_tids'][$number]];
        }
        $node->set('field_classroom_grade', $targets);
        $sandbox['count_grades']++;
        $changed = TRUE;
      }
    }

    // 2. field_classroom_subject: History across the corpus, except titles
    // that are explicitly another subject (no matching term exists).
    if ($node->get('field_classroom_subject')->isEmpty()
      && !preg_match('/english home language|afrikaans|mathematic|life science|physical science/i', $title)
    ) {
      $node->set('field_classroom_subject', ['target_id' => $sandbox['subject_tid']]);
      $changed = TRUE;
    }

    // 3. field_classroom_resource_type: first matching rule wins.
    if ($node->get('field_classroom_resource_type')->isEmpty()) {
      if (stripos($title, 'glossary') !== FALSE) {
        $type = 'Glossary';
      }
      elseif (preg_match('/source.based/i', $title)) {
        $type = 'Source-based questions';
      }
      elseif (stripos($title, 'essay') !== FALSE) {
        $type = 'Essay questions';
      }
      elseif (preg_match('/paper \d|exam/i', $title)) {
        $type = 'Exam paper';
      }
      elseif ($has_caps_doc) {
        $type = 'Lesson pack';
      }
      elseif (stripos($title, 'activit') !== FALSE) {
        $type = 'Activity';
      }
      elseif (stripos($title, 'worksheet') !== FALSE) {
        $type = 'Worksheet';
      }
      elseif (stripos($title, 'quiz') !== FALSE) {
        $type = 'Quiz';
      }
      else {
        // Curriculum spine pages ("Term N"/"Topic N") and everything else.
        $type = 'Topic overview';
      }
      $node->set('field_classroom_resource_type', ['target_id' => $sandbox['type_tids'][$type]]);
      $sandbox['count_types']++;
      $changed = TRUE;
    }

    // 4. field_caps_topic: best-effort positional match with a name veto.
    if ($node->get('field_caps_topic')->isEmpty()
      && preg_match('/grade\s*(\d{1,2}).*?(?:term|topic)\s*(\d{1,2})/i', $title, $m)
    ) {
      $grade_number = (int) $m[1];
      $position = (int) $m[2];
      $candidate = $sandbox['caps_children'][$grade_number][$position - 1] ?? NULL;
      if ($candidate) {
        // Veto the positional pick when the title names a different topic:
        // strip grade/term markers and generic words, then require at least
        // one remaining word to appear in the candidate term name.
        $remainder = preg_replace('/grade\s*\d+(\.\d+)?|(term|topic)\s*\d+/i', ' ', $title);
        $remainder = preg_replace('/[^a-z]+/', ' ', strtolower($remainder));
        $words = array_diff(array_filter(explode(' ', $remainder), static function (string $word): bool {
          return strlen($word) > 2;
        }), $stopwords);
        $confident = TRUE;
        if ($words) {
          $confident = FALSE;
          $term_name = strtolower($sandbox['caps_names'][$candidate]);
          foreach ($words as $word) {
            if (strpos($term_name, $word) !== FALSE) {
              $confident = TRUE;
              break;
            }
          }
        }
        if ($confident) {
          $node->set('field_caps_topic', [['target_id' => $candidate]]);
          $sandbox['count_topics']++;
          $changed = TRUE;
        }
      }
    }

    if ($changed) {
      // Additive data migration: no new revision, and syncing mode so that
      // save-time side effects (pathauto, notifications) stay quiet.
      $node->setNewRevision(FALSE);
      $node->setSyncing(TRUE);
      $node->save();
      $sandbox['retagged']++;
    }
    else {
      $sandbox['skipped']++;
    }
  }

  $sandbox['#finished'] = $sandbox['total'] ? $sandbox['processed'] / $sandbox['total'] : 1;

  return sprintf('retagged %d nodes (grades: %d, resource types: %d, topics: %d), skipped %d already-tagged',
    $sandbox['retagged'],
    $sandbox['count_grades'],
    $sandbox['count_types'],
    $sandbox['count_topics'],
    $sandbox['skipped'],
  );
}

/**
 * Redirects the retired per-grade classroom views to the wf08 hub.
 *
 * The legacy views (classroom_history_grade10, historyclassroom_grade4 and
 * its per-grade displays, the technical-skills duplicate and
 * latest_in_classroom) are deleted; their URLs 301 to /classroom with the
 * matching grade preselected so bookmarks and search results keep working.
 */
function saho_classroom_post_update_redirect_retired_views(&$sandbox = NULL): string {
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $grade_tid = function (string $name) use ($term_storage): ?int {
    $terms = $term_storage->loadByProperties(['vid' => 'classroom_grade', 'name' => $name]);
    return $terms ? (int) reset($terms)->id() : NULL;
  };
  $map = [
    'classroom-history-grade10' => 'Grade 10',
    'historyclassroom-grade4' => 'Grade 4',
    'historyclassroom-grade5' => 'Grade 5',
    'historyclassroom-grade6' => 'Grade 6',
    'historyclassroom-grade7' => 'Grade 7',
    'historyclassroom-grade8' => 'Grade 8',
    'historyclassroom-grade9' => 'Grade 9',
    'historyclassroom-grade12' => 'Grade 12',
  ];
  $redirect_storage = \Drupal::entityTypeManager()->getStorage('redirect');
  $created = 0;
  $ensure = function (string $source, string $target_uri) use ($redirect_storage, &$created): void {
    $existing = $redirect_storage->loadByProperties(['redirect_source__path' => $source]);
    if ($existing !== []) {
      return;
    }
    $redirect_storage->create([
      'redirect_source' => ['path' => $source, 'query' => []],
      'redirect_redirect' => ['uri' => $target_uri],
      'status_code' => 301,
      'language' => 'und',
    ])->save();
    $created++;
  };
  foreach ($map as $source => $grade_name) {
    $tid = $grade_tid($grade_name);
    $target = $tid !== NULL
      ? 'internal:/classroom?grade%5B' . $tid . '%5D=' . $tid
      : 'internal:/classroom';
    $ensure($source, $target);
  }
  $ensure('classroom-technical-skills-sans-footnote', 'internal:/classroom-technical-skills');
  $ensure('latest-in-classroom', 'internal:/classroom');
  return "Created $created classroom redirects for retired view URLs.";
}
