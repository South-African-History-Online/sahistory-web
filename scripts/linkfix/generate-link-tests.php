<?php

/**
 * @file
 * Generate verifiable before/after test artifacts for the legacy link fix.
 *
 * Outputs (under scripts/linkfix/):
 *  - legacy_url_tests.csv   directly-hittable legacy URLs + expected target.
 *  - broken_link_pages.md   100+ live pages that contain broken in-body links.
 *
 * Run: ddev drush php:script scripts/linkfix/generate-link-tests.php
 */

$db = \Drupal::database();
$aliasManager = \Drupal::service('path_alias.manager');
$work = '/var/www/html/webroot/sites/default/files/saho_linkfix_work/candidates.json';
$out_dir = '/var/www/html/scripts/linkfix';

$candidates = json_decode(file_get_contents($work), TRUE);

$nid_of = function (string $uri): int {
  return (int) preg_replace('/\D/', '', $uri);
};
$alias_of = function (int $nid) use ($aliasManager): string {
  return $aliasManager->getAliasByPath('/node/' . $nid);
};
$title_of = function (int $nid) use ($db): string {
  return trim((string) $db->query('SELECT title FROM {node_field_data} WHERE nid=:n', [':n' => $nid])->fetchField());
};

// ---------------------------------------------------------------------------
// A. Directly-testable legacy URLs (absolute links), diverse across categories.
// ---------------------------------------------------------------------------
$by_cat = ['people' => [], 'organisations' => [], 'places' => [], 'events_topics' => []];
$seen = [];
foreach ($candidates as $c) {
  if ($c['kind'] !== 'absolute') {
    continue;
  }
  $src = $c['source_path'];
  if (isset($seen[$src])) {
    continue;
  }
  $seen[$src] = TRUE;
  $p = strtolower($src);
  $cat = match (TRUE) {
    str_contains($p, 'bios') || str_contains($p, 'people') => 'people',
    str_contains($p, 'organisation') => 'organisations',
    str_contains($p, 'places') => 'places',
    default => 'events_topics',
  };
  $nid = $nid_of($c['uri']);
  $by_cat[$cat][] = [
    'legacy_path' => $src,
    'expected_alias' => $alias_of($nid),
    'nid' => $nid,
    'title' => $title_of($nid),
    'category' => $cat,
  ];
}

// Build a balanced set of >= 150 tests (so prod always has 100+ even if some
// nodes were unpublished/moved since the import).
$tests = [];
$targets = ['people' => 60, 'organisations' => 40, 'places' => 20, 'events_topics' => 40];
foreach ($targets as $cat => $n) {
  $tests = array_merge($tests, array_slice($by_cat[$cat], 0, $n));
}

// Human-readable CSV (quoted) for spreadsheets.
$fh = fopen($out_dir . '/legacy_url_tests.csv', 'w');
fputcsv($fh, ['legacy_path', 'expected_alias', 'nid', 'category', 'title']);
foreach ($tests as $t) {
  fputcsv($fh, [$t['legacy_path'], $t['expected_alias'], $t['nid'], $t['category'], $t['title']]);
}
fclose($fh);

// Machine-readable TSV for the verifier - legacy paths contain commas
// (kotane,m.htm) but never tabs, so tab-separation parses unambiguously.
$tsv = fopen($out_dir . '/legacy_url_tests.tsv', 'w');
foreach ($tests as $t) {
  fwrite($tsv, $t['legacy_path'] . "\t" . $t['expected_alias'] . "\t" . $t['category'] . "\n");
}
fclose($tsv);
printf("Wrote %d directly-testable legacy URLs to legacy_url_tests.csv (+ .tsv)\n", count($tests));

// ---------------------------------------------------------------------------
// B. Live pages that contain broken IN-BODY legacy links (for visual testing).
// ---------------------------------------------------------------------------
$by_node = [];
foreach ($candidates as $c) {
  if ($c['kind'] === 'relative') {
    $by_node[$c['nid']][] = $c;
  }
}
uasort($by_node, fn($a, $b) => count($b) <=> count($a));

$md = "# Live pages with broken legacy links (in-body)\n\n";
$md .= "These published pages currently contain dead `.htm` links in their body text.\n";
$md .= "Open any of them on the live site, click a link in the list/table, and it 404s\n";
$md .= "(or bounces to a search guess). After the fix the same links resolve to the\n";
$md .= "exact page shown.\n\n";
$md .= "| # | Page (open this) | Broken links | Example broken link -> should become |\n";
$md .= "|---|---|---|---|\n";
$i = 0;
foreach ($by_node as $nid => $links) {
  $i++;
  $ex = $links[0];
  $target_nid = $nid_of($ex['uri']);
  $md .= sprintf(
    "| %d | %s | %d | `%s` -> %s |\n",
    $i,
    $alias_of((int) $nid),
    count($links),
    $ex['href'],
    $alias_of($target_nid),
  );
  if ($i >= 120) {
    break;
  }
}
file_put_contents($out_dir . '/broken_link_pages.md', $md);
printf("Wrote %d live pages with broken in-body links to broken_link_pages.md\n", $i);
