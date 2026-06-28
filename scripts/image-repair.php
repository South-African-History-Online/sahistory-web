<?php

/**
 * @file
 * Phase C - Image repair executor (dry-run by default).
 *
 * Consumes the Phase B recovery-plan CSV and, for every file with an on-disk
 * recovery source (source_method = duplicate or derivative), copies the healthy
 * bytes onto the broken original path, flushes that file's style derivatives,
 * and corrects file_managed.filesize / filemime.
 *
 * SAFE BY DEFAULT: with no "apply" argument it only reports what it WOULD do
 * and writes a categorized action CSV - it changes nothing. Pass "apply" to
 * write the repairs.
 * Every applied repair first copies the broken original into a manifest backup
 * dir, so any change is reversible.
 *
 * Quality guards (because "same basename" can be a junk or unrelated match):
 *   - min dimension: a source smaller than min(W,H) is skipped (default 200).
 *   - self-match and missing-source are skipped.
 *   - post-write getimagesize() verification; a failed verify is rolled back.
 *
 * Usage (drush, NOT ./):
 *   # dry-run report (writes nothing):
 *   drush php:script scripts/image-repair.php <recovery-plan.csv>
 *   # tune the minimum acceptable dimension and cap the batch:
 *   drush php:script scripts/image-repair.php <plan.csv> min=300 limit=200
 *   # actually write the repairs:
 *   drush php:script scripts/image-repair.php <recovery-plan.csv> apply
 *   # include files with no usage (default = referenced files only):
 *   drush php:script scripts/image-repair.php <plan.csv> apply unreferenced
 *
 * Tokens are positional (no leading dashes) to avoid drush option parsing:
 *   apply | unreferenced | min=<int> | limit=<int>
 *
 * See docs/IMAGE-CORRUPTION-RESTORATION-PLAN.md.
 */

use Drupal\Core\Database\Database;
use Drupal\image\Entity\ImageStyle;

$tokens = $extra ?? ($args ?? []);
$plan_csv = NULL;
$apply = FALSE;
$include_unreferenced = FALSE;
$min_dim = 200;
$limit = 0;
foreach ($tokens as $t) {
  if ($t === 'apply') {
    $apply = TRUE;
  }
  elseif ($t === 'unreferenced') {
    $include_unreferenced = TRUE;
  }
  elseif (strpos($t, 'min=') === 0) {
    $min_dim = (int) substr($t, 4);
  }
  elseif (strpos($t, 'limit=') === 0) {
    $limit = (int) substr($t, 6);
  }
  elseif ($t !== '' && $t[0] !== '-') {
    $plan_csv = $t;
  }
}

if (!$plan_csv || !file_exists($plan_csv)) {
  echo "ERROR: pass the Phase B recovery-plan CSV path as the first argument.\n";
  echo "  drush php:script scripts/image-repair.php /path/to/image-recovery-plan-*.csv\n";
  return;
}

$file_system = \Drupal::service('file_system');
$connection = Database::getConnection();

$public_root = rtrim($file_system->realpath('public://'), '/');
$private_root = $file_system->realpath('private://');
$private_root = $private_root ? rtrim($private_root, '/') : NULL;

// Manifest + backup live next to the plan so they travel together.
$run_id = date('Ymd-His');
$backup_dir = dirname($plan_csv) . '/image-repair-backup-' . $run_id;
$manifest_path = dirname($plan_csv) . '/image-repair-manifest-' . $run_id . '.csv';
$action_path = dirname($plan_csv) . '/image-repair-actions-' . $run_id . '.csv';

if ($apply && !is_dir($backup_dir)) {
  mkdir($backup_dir, 0775, TRUE);
}

$action = fopen($action_path, 'w');
fputcsv($action, ['fid', 'uri', 'source_path', 'source_dimensions', 'referenced', 'decision', 'note']);

$manifest = NULL;
if ($apply) {
  $manifest = fopen($manifest_path, 'w');
  fputcsv($manifest, [
    'fid', 'uri', 'target_abs', 'backup_abs', 'source_path',
    'old_filesize', 'new_filesize', 'new_filemime',
  ]);
}

/**
 * Maps a public://|private:// uri to an absolute path.
 *
 * Does not require the file to exist (realpath returns FALSE for missing
 * files, which we must still be able to repair).
 */
$to_abs = function (string $uri) use ($public_root, $private_root) {
  if (strpos($uri, 'public://') === 0) {
    return $public_root . '/' . substr($uri, strlen('public://'));
  }
  if (strpos($uri, 'private://') === 0 && $private_root) {
    return $private_root . '/' . substr($uri, strlen('private://'));
  }
  return NULL;
};

$counts = [
  'repaired' => 0,
  'would_repair' => 0,
  'skip_too_small' => 0,
  'skip_unreferenced' => 0,
  'skip_no_source' => 0,
  'skip_source_missing' => 0,
  'skip_self_match' => 0,
  'failed_verify' => 0,
  'failed_write' => 0,
];

$in = fopen($plan_csv, 'r');
$header = fgetcsv($in);
$col = array_flip($header);
$processed = 0;

while (($r = fgetcsv($in)) !== FALSE) {
  $method = $r[$col['source_method']] ?? '';
  if ($method !== 'duplicate' && $method !== 'derivative') {
    // needs_backup_or_reharvest - nothing to do here.
    continue;
  }

  $fid = $r[$col['fid']];
  $uri = $r[$col['uri']];
  $ref = (int) ($r[$col['referenced']] ?? 0);
  $source = $r[$col['source_path']] ?? '';
  $dims = $r[$col['source_dimensions']] ?? '';

  if ($limit && $processed >= $limit) {
    break;
  }

  // Referenced-only by default (those are what users see / what spams the log).
  if ($ref === 0 && !$include_unreferenced) {
    fputcsv($action, [$fid, $uri, $source, $dims, $ref, 'skip_unreferenced', 'use "unreferenced" to include']);
    $counts['skip_unreferenced']++;
    continue;
  }

  if ($source === '') {
    fputcsv($action, [$fid, $uri, $source, $dims, $ref, 'skip_no_source', '']);
    $counts['skip_no_source']++;
    continue;
  }
  if (!is_file($source)) {
    fputcsv($action, [$fid, $uri, $source, $dims, $ref, 'skip_source_missing', 'source gone since Phase B']);
    $counts['skip_source_missing']++;
    continue;
  }

  // Dimension guard.
  [$sw, $sh] = array_map('intval', explode('x', $dims . 'x0'));
  if ($sw < $min_dim || $sh < $min_dim) {
    fputcsv($action, [$fid, $uri, $source, $dims, $ref, 'skip_too_small', "below min=$min_dim"]);
    $counts['skip_too_small']++;
    continue;
  }

  $target = $to_abs($uri);
  if ($target === NULL) {
    fputcsv($action, [$fid, $uri, $source, $dims, $ref, 'skip_no_source', 'unmappable uri scheme']);
    $counts['skip_no_source']++;
    continue;
  }
  if (realpath($source) !== FALSE && realpath($source) === realpath($target)) {
    fputcsv($action, [$fid, $uri, $source, $dims, $ref, 'skip_self_match', '']);
    $counts['skip_self_match']++;
    continue;
  }

  $processed++;

  if (!$apply) {
    fputcsv($action, [$fid, $uri, $source, $dims, $ref, 'would_repair', '']);
    $counts['would_repair']++;
    continue;
  }

  // --- APPLY ---
  $old_size = is_file($target) ? filesize($target) : 0;

  // 1. Back up the broken original (if present) for rollback.
  $backup_abs = '';
  if (is_file($target)) {
    $backup_abs = $backup_dir . '/' . $fid . '-' . basename($target);
    @copy($target, $backup_abs);
  }

  // 2. Ensure the target directory exists, then copy healthy bytes in.
  $tdir = dirname($target);
  if (!is_dir($tdir)) {
    mkdir($tdir, 0775, TRUE);
  }
  if (!@copy($source, $target)) {
    fputcsv($action, [$fid, $uri, $source, $dims, $ref, 'failed_write', 'copy failed']);
    $counts['failed_write']++;
    continue;
  }

  // 3. Verify the written file is a decodable image.
  $info = @getimagesize($target);
  if ($info === FALSE) {
    // Roll back from backup if we had one.
    if ($backup_abs && is_file($backup_abs)) {
      @copy($backup_abs, $target);
    }
    fputcsv($action, [$fid, $uri, $source, $dims, $ref, 'failed_verify', 'rolled back']);
    $counts['failed_verify']++;
    continue;
  }

  $new_size = filesize($target);
  $new_mime = $info['mime'] ?? 'image/jpeg';

  // 4. Correct the file_managed metadata.
  $connection->update('file_managed')
    ->fields(['filesize' => $new_size, 'filemime' => $new_mime])
    ->condition('fid', $fid)
    ->execute();

  // 5. Flush stale style derivatives for this uri so new ones regenerate.
  try {
    foreach (ImageStyle::loadMultiple() as $style) {
      $style->flush($uri);
    }
  }
  catch (\Throwable $e) {
    // Non-fatal: derivatives will regenerate on next request regardless.
  }

  fputcsv($manifest, [$fid, $uri, $target, $backup_abs, $source, $old_size, $new_size, $new_mime]);
  fputcsv($action, [$fid, $uri, $source, $dims, $ref, 'repaired', $info[0] . 'x' . $info[1]]);
  $counts['repaired']++;

  if ($counts['repaired'] % 500 === 0) {
    echo '  repaired ' . $counts['repaired'] . " ...\n";
  }
}

fclose($in);
fclose($action);
if ($manifest) {
  fclose($manifest);
}

echo "\n=== Image repair " . ($apply ? 'APPLY' : 'DRY-RUN') . " complete ===\n";
echo "Mode: " . ($apply ? 'APPLY (files written)' : 'DRY-RUN (nothing changed)') . "\n";
echo "min dimension: $min_dim   referenced-only: " . ($include_unreferenced ? 'no' : 'yes');
echo ($limit ? "   limit: $limit" : '') . "\n";
foreach ($counts as $k => $v) {
  if ($v > 0 || in_array($k, ['repaired', 'would_repair'], TRUE)) {
    echo sprintf("  %-20s %d\n", $k, $v);
  }
}
echo "Action report: $action_path\n";
if ($apply) {
  echo "Rollback manifest: $manifest_path\n";
  echo "Backups of originals: $backup_dir\n";
  echo "(to roll back: copy each backup_abs back over target_abs from the manifest)\n";
}
else {
  echo "Re-run with 'apply' appended to write these repairs.\n";
}
