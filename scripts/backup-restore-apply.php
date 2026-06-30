<?php

/**
 * @file
 * Backup restore - PROD apply step (dry-run by default).
 *
 * Consumes a staging tree produced by backup-restore-stage.php (transported
 * to prod) and copies each staged original onto its live target path,
 * validates it, flushes that file's style derivatives, and corrects
 * file_managed.filesize / filemime.
 *
 * SAFE BY DEFAULT: with no "apply" token it only reports - changes nothing.
 * Every applied restore first backs up the existing file for rollback.
 *
 *   drush php:script scripts/backup-restore-apply.php <staging-root>
 *   drush php:script scripts/backup-restore-apply.php <staging-root> apply
 *   drush php:script scripts/backup-restore-apply.php <staging-root> apply limit=200
 *
 * <staging-root> must contain restore-manifest.csv and the staged files laid
 * out by their target relative path.
 *
 * See docs/IMAGE-CORRUPTION-RESTORATION-PLAN.md.
 */

use Drupal\Core\Database\Database;
use Drupal\image\Entity\ImageStyle;

$tokens = $extra ?? ($args ?? []);
$staging = NULL;
$apply = FALSE;
$limit = 0;
foreach ($tokens as $t) {
  if ($t === 'apply') {
    $apply = TRUE;
  }
  elseif (strpos($t, 'limit=') === 0) {
    $limit = (int) substr($t, 6);
  }
  elseif ($t !== '' && $t[0] !== '-') {
    $staging = rtrim($t, '/');
  }
}

$manifest_csv = $staging ? $staging . '/restore-manifest.csv' : NULL;
if (!$staging || !is_dir($staging) || !is_file($manifest_csv)) {
  echo "ERROR: pass the staging-root dir (must contain restore-manifest.csv).\n";
  echo "  drush php:script scripts/backup-restore-apply.php /path/to/staging\n";
  return;
}

$file_system = \Drupal::service('file_system');
$connection = Database::getConnection();
$public_root = rtrim($file_system->realpath('public://'), '/');

$run_id = date('Ymd-His');
$backup_dir = $staging . '/applied-backup-' . $run_id;
$applied_manifest = $staging . '/applied-manifest-' . $run_id . '.csv';
$action_path = $staging . '/apply-actions-' . $run_id . '.csv';

if ($apply && !is_dir($backup_dir)) {
  mkdir($backup_dir, 0775, TRUE);
}

$action = fopen($action_path, 'w');
fputcsv($action, ['target_uri', 'staged_file', 'match_type', 'decision', 'note']);

$man = NULL;
if ($apply) {
  $man = fopen($applied_manifest, 'w');
  fputcsv($man, [
    'target_uri', 'target_abs', 'backup_abs', 'staged_file',
    'old_filesize', 'new_filesize', 'new_filemime',
  ]);
}

$counts = [
  'restored' => 0,
  'would_restore' => 0,
  'skip_staged_missing' => 0,
  'skip_staged_invalid' => 0,
  'failed_write' => 0,
  'failed_verify' => 0,
  'skip_no_fid' => 0,
];

$in = fopen($manifest_csv, 'r');
$header = fgetcsv($in);
$col = array_flip($header);
$processed = 0;

while (($r = fgetcsv($in)) !== FALSE) {
  if ($limit && $processed >= $limit) {
    break;
  }
  $uri = $r[$col['target_uri']];
  $rel = $r[$col['target_rel']];
  $match_type = $r[$col['match_type']] ?? '';
  $staged_file = $staging . '/' . $rel;

  if (!is_file($staged_file)) {
    fputcsv($action, [$uri, $staged_file, $match_type, 'skip_staged_missing', '']);
    $counts['skip_staged_missing']++;
    continue;
  }
  $info = @getimagesize($staged_file);
  if ($info === FALSE) {
    fputcsv($action, [$uri, $staged_file, $match_type, 'skip_staged_invalid', '']);
    $counts['skip_staged_invalid']++;
    continue;
  }

  // Confirm a file_managed row exists for this uri.
  $fid = $connection->query('SELECT fid FROM {file_managed} WHERE uri = :u', [':u' => $uri])->fetchField();
  if (!$fid) {
    fputcsv($action, [$uri, $staged_file, $match_type, 'skip_no_fid', 'no file_managed row']);
    $counts['skip_no_fid']++;
    continue;
  }

  $processed++;
  $target = $public_root . '/' . $rel;

  if (!$apply) {
    fputcsv($action, [$uri, $staged_file, $match_type, 'would_restore', $info[0] . 'x' . $info[1]]);
    $counts['would_restore']++;
    continue;
  }

  // --- APPLY ---
  $old_size = is_file($target) ? filesize($target) : 0;
  $backup_abs = '';
  if (is_file($target)) {
    $backup_abs = $backup_dir . '/' . $fid . '-' . basename($target);
    @copy($target, $backup_abs);
  }
  $tdir = dirname($target);
  if (!is_dir($tdir)) {
    mkdir($tdir, 0775, TRUE);
  }
  if (!@copy($staged_file, $target)) {
    fputcsv($action, [$uri, $staged_file, $match_type, 'failed_write', '']);
    $counts['failed_write']++;
    continue;
  }
  $verify = @getimagesize($target);
  if ($verify === FALSE) {
    if ($backup_abs && is_file($backup_abs)) {
      @copy($backup_abs, $target);
    }
    fputcsv($action, [$uri, $staged_file, $match_type, 'failed_verify', 'rolled back']);
    $counts['failed_verify']++;
    continue;
  }

  $new_size = filesize($target);
  $new_mime = $verify['mime'] ?? 'image/jpeg';
  $connection->update('file_managed')
    ->fields(['filesize' => $new_size, 'filemime' => $new_mime])
    ->condition('fid', $fid)
    ->execute();

  try {
    foreach (ImageStyle::loadMultiple() as $style) {
      $style->flush($uri);
    }
  }
  catch (\Throwable $e) {
    // Non-fatal: derivatives regenerate on next request.
  }

  fputcsv($man, [$uri, $target, $backup_abs, $staged_file, $old_size, $new_size, $new_mime]);
  fputcsv($action, [$uri, $staged_file, $match_type, 'restored', $verify[0] . 'x' . $verify[1]]);
  $counts['restored']++;
  if ($counts['restored'] % 500 === 0) {
    echo '  restored ' . $counts['restored'] . " ...\n";
  }
}

fclose($in);
fclose($action);
if ($man) {
  fclose($man);
}

echo "\n=== Backup restore " . ($apply ? 'APPLY' : 'DRY-RUN') . " complete ===\n";
foreach ($counts as $k => $v) {
  if ($v > 0 || in_array($k, ['restored', 'would_restore'], TRUE)) {
    echo sprintf("  %-20s %d\n", $k, $v);
  }
}
echo "Action report: $action_path\n";
if ($apply) {
  echo "Rollback manifest: $applied_manifest\n";
  echo "Backups: $backup_dir\n";
}
else {
  echo "Re-run with 'apply' appended to write these restores.\n";
}
