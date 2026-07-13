<?php

/**
 * @file
 * Build the UX-baseline capture matrix as JSON on stdout.
 *
 * One representative published node per content type, edge-case records
 * (longest article, imageless biography, DISA collection, chronology
 * timeline, translated classroom deck), plus the static landing routes.
 *
 * Run: ddev drush scr perf/scripts/ux-matrix.php > matrix.json
 */

use Drupal\views\Views;

$db = \Drupal::database();
$aliasManager = \Drupal::service('path_alias.manager');

$entries = [];
$add = function ($slug, $path, $note = '') use (&$entries) {
  $entries[$slug] = ['slug' => $slug, 'path' => $path, 'note' => $note];
};
$alias = function ($nid) use ($aliasManager) {
  return $aliasManager->getAliasByPath('/node/' . $nid);
};

// One latest published node per content type.
$types = array_keys(\Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple());
sort($types);
foreach ($types as $type) {
  $nid = $db->query("SELECT nid FROM {node_field_data} WHERE type = :t AND status = 1 AND default_langcode = 1 ORDER BY changed DESC LIMIT 1", [':t' => $type])->fetchField();
  if ($nid) {
    $add("type-$type", $alias($nid), "latest published $type (nid $nid)");
  }
}

// Edge: longest article body.
$nid = $db->query("SELECT b.entity_id FROM {node__body} b JOIN {node_field_data} n ON n.nid = b.entity_id AND n.status = 1 AND n.default_langcode = 1 WHERE b.bundle = 'article' ORDER BY LENGTH(b.body_value) DESC LIMIT 1")->fetchField();
if ($nid) {
  $add('edge-longest-article', $alias($nid), "longest article body (nid $nid)");
}

// Edge: biography without a portrait image.
$nid = $db->query("SELECT n.nid FROM {node_field_data} n LEFT JOIN {node__field_bio_pic} p ON p.entity_id = n.nid WHERE n.type = 'biography' AND n.status = 1 AND n.default_langcode = 1 AND p.entity_id IS NULL ORDER BY n.changed DESC LIMIT 1")->fetchField();
if ($nid) {
  $add('edge-imageless-biography', $alias($nid), "biography with no field_bio_pic (nid $nid)");
}

// Edge: the biggest collection (field_feature_parent = the collection facet)
// and the archive search narrowed to it - DISA Archive, ~20k members.
$row = $db->query("SELECT p.field_feature_parent_target_id nid, COUNT(*) c FROM {node__field_feature_parent} p GROUP BY 1 ORDER BY c DESC LIMIT 1")->fetchAssoc();
if ($row) {
  $add('edge-biggest-collection', $alias($row['nid']), "biggest collection node (nid {$row['nid']}, {$row['c']} members)");
  $add('edge-archives-collection-filter', '/archives?collection=' . $row['nid'], 'archive search narrowed to the biggest collection');
}

// Edge: timeline articles - the first register entry and one with a <dl> chronology.
$view = Views::getView('timelines');
if ($view) {
  $view->setDisplay('page_1');
  $view->setItemsPerPage(80);
  $view->execute();
  $first = NULL;
  $dl = NULL;
  foreach ($view->result as $viewRow) {
    $node = $viewRow->_entity;
    if (!$node) {
      continue;
    }
    if (!$first) {
      $first = $node;
    }
    $body = $node->hasField('body') ? ($node->get('body')->value ?? '') : '';
    if (!$dl && strpos($body, '<dl') !== FALSE) {
      $dl = $node;
    }
    if ($first && $dl) {
      break;
    }
  }
  if ($first) {
    $add('edge-timeline-article', $alias($first->id()), 'first timelines register entry (nid ' . $first->id() . ')');
  }
  if ($dl) {
    $add('edge-timeline-chronology-dl', $alias($dl->id()), 'timeline article with <dl> chronology (nid ' . $dl->id() . ')');
  }
}

// Edge: translated classroom deck (heritage-trail-provinces, Tshivenda).
$nid = $db->query("SELECT nid FROM {node_field_data} WHERE type = 'presentation' AND status = 1 AND default_langcode = 1 AND title LIKE '%Heritage Trail%' ORDER BY changed DESC LIMIT 1")->fetchField();
if ($nid) {
  $add('edge-classroom-deck', $alias($nid), "classroom deck (nid $nid)");
  // Translations carry their own per-language alias (translated slug).
  $veAlias = $aliasManager->getAliasByPath('/node/' . $nid, 've');
  if ($veAlias !== '/node/' . $nid) {
    $add('edge-classroom-deck-ve', '/ve' . $veAlias, 'same deck, Tshivenda translation');
  }
}

// Busiest taxonomy term page (term view page_2 is the live display).
$tid = $db->query("SELECT tid FROM {taxonomy_index} GROUP BY tid ORDER BY COUNT(*) DESC LIMIT 1")->fetchField();
if ($tid) {
  $add('term-busiest', $aliasManager->getAliasByPath('/taxonomy/term/' . $tid), "busiest taxonomy term (tid $tid)");
}

// Static landings and custom routes.
$static = [
  'home' => '/',
  'featured' => '/featured',
  'archives' => '/archives',
  'collections' => '/archive/collections',
  'biographies' => '/biographies',
  'timelines' => '/timelines',
  'timeline-app' => '/timeline',
  'classroom' => '/classroom',
  'africa' => '/africa',
  'history-through-pictures' => '/history-through-pictures',
  'search' => '/search',
  'search-keyword' => '/search?search_api_fulltext=mandela',
  'search-advanced' => '/search/advanced',
  'donate' => '/donate',
  'champions' => '/champion',
  'not-found' => '/this-page-does-not-exist',
];
foreach ($static as $slug => $path) {
  $add("route-$slug", $path, 'static route');
}

print json_encode(array_values($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
