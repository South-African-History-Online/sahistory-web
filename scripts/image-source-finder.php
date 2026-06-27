<?php

/**
 * @file
 * Phase B - Image recovery source finder (read-only).
 *
 * Takes the Phase A audit CSV and, for every corrupt file, looks for a usable
 * recovery source - in priority order:
 *   1. A surviving image-style derivative on disk
 *      (styles/<style>/public/<path>) that is itself a valid image. Picks the
 *      largest by pixel area.
 *   2. A healthy duplicate elsewhere in the files tree with the same basename
 *      that is a valid image (dimensions reported so collisions can be judged).
 * Backups and re-harvest (sources 3 and 4 in the plan) are out of scope for
 * this script - the remainder are flagged "needs_backup_or_reharvest".
 *
 * Safe on production - reads files only, writes one CSV.
 *
 * Usage:
 *   drush php:script scripts/image-source-finder.php <path-to-audit.csv>
 *
 * Output columns:
 *   fid,uri,verdict,referenced,source_method,source_path,source_dimensions
 * source_method: derivative | duplicate | needs_backup_or_reharvest
 *
 * See docs/IMAGE-CORRUPTION-RESTORATION-PLAN.md.
 */

use Drupal\Core\Database\Database;

$audit_csv = $extra[0] ?? ($args[0] ?? NULL);
if (!$audit_csv || !file_exists($audit_csv)) {
  echo "ERROR: pass the Phase A audit CSV path as an argument.\n";
  echo "  drush php:script scripts/image-source-finder.php /path/to/image-audit-*.csv\n";
  return;
}

$file_system = \Drupal::service('file_system');
$connection = Database::getConnection();

$files_root = $file_system->realpath('public://');
$styles_root = $files_root . '/styles';

// Enumerate available style machine names once.
$styles = [];
if (is_dir($styles_root)) {
  foreach (scandir($styles_root) as $s) {
    if ($s !== '.' && $s !== '..' && is_dir("$styles_root/$s/public")) {
      $styles[] = $s;
    }
  }
}
echo 'Found ' . count($styles) . " style directories on disk.\n";

// Output next to the audit file.
$out_abs = dirname($audit_csv) . '/image-recovery-plan-' . date('Ymd-His') . '.csv';
$out = fopen($out_abs, 'w');
fputcsv($out, ['fid', 'uri', 'verdict', 'referenced', 'source_method', 'source_path', 'source_dimensions']);

/**
 * Return [width, height, abs_path] for the largest valid image in the set.
 */
$best_image = function (array $candidates) {
  $best = NULL;
  foreach ($candidates as $path) {
    if (!is_file($path)) {
      continue;
    }
    $info = @getimagesize($path);
    if ($info === FALSE) {
      continue;
    }
    $area = $info[0] * $info[1];
    if ($best === NULL || $area > $best['area']) {
      $best = ['area' => $area, 'w' => $info[0], 'h' => $info[1], 'path' => $path];
    }
  }
  return $best;
};

$in = fopen($audit_csv, 'r');
$header = fgetcsv($in);
$col = array_flip($header);

$counts = ['derivative' => 0, 'duplicate' => 0, 'needs_backup_or_reharvest' => 0];
$rows = 0;

while (($r = fgetcsv($in)) !== FALSE) {
  $verdict = $r[$col['verdict']];
  // Only chase recovery for genuinely broken files.
  if (in_array($verdict, ['ok'], TRUE)) {
    continue;
  }
  $rows++;
  $fid = $r[$col['fid']];
  $uri = $r[$col['uri']];
  $ref = $r[$col['referenced']];

  // Relative path inside the public scheme, e.g. "images_new/foo.jpg".
  $rel = preg_replace('#^public://#', '', $uri);
  $rel = ltrim($rel, '/');
  $basename = basename($rel);

  // 1. Surviving derivatives for this exact relative path.
  $deriv_candidates = [];
  foreach ($styles as $s) {
    $deriv_candidates[] = "$styles_root/$s/public/$rel";
  }
  $best = $best_image($deriv_candidates);

  if ($best) {
    fputcsv($out, [$fid, $uri, $verdict, $ref, 'derivative', $best['path'], $best['w'] . 'x' . $best['h']]);
    $counts['derivative']++;
    continue;
  }

  // 2. Healthy duplicate by basename anywhere in file_managed (valid on disk).
  $dupe_paths = [];
  $q = $connection->query("SELECT uri FROM {file_managed} WHERE uri LIKE :p AND uri <> :self", [
    ':p' => '%/' . $basename,
    ':self' => $uri,
  ]);
  foreach ($q as $d) {
    $p = $file_system->realpath($d->uri);
    if ($p) {
      $dupe_paths[] = $p;
    }
  }
  $best = $best_image($dupe_paths);

  if ($best) {
    fputcsv($out, [$fid, $uri, $verdict, $ref, 'duplicate', $best['path'], $best['w'] . 'x' . $best['h']]);
    $counts['duplicate']++;
    continue;
  }

  // 3. Nothing usable on disk.
  fputcsv($out, [$fid, $uri, $verdict, $ref, 'needs_backup_or_reharvest', '', '']);
  $counts['needs_backup_or_reharvest']++;

  if ($rows % 2000 === 0) {
    echo "  processed $rows corrupt files ...\n";
  }
}

fclose($in);
fclose($out);

echo "\n=== Recovery source scan complete ===\n";
echo "Corrupt files examined: $rows\n";
foreach ($counts as $k => $v) {
  echo sprintf("  %-26s %d\n", $k, $v);
}
$recoverable = $counts['derivative'] + $counts['duplicate'];
echo "On-disk recoverable (no backup needed): $recoverable\n";
echo "Recovery plan written to: $out_abs\n";
