<?php

/**
 * @file
 * Phase D - Neutralize unrecoverable corrupt images with a placeholder.
 *
 * For corrupt-bytes files that have no recovery source (HTML error pages,
 * empty, or truncated files that still exist on disk), this replaces the bytes
 * with a valid "image unavailable" placeholder of the matching format. It can
 * then `identify` it successfully, so the recurring image-error log spam stops,
 * and pages show a clean placeholder instead of a broken image.
 *
 * Only the present-but-corrupt verdicts are targeted (html, empty, truncated).
 * `missing` files are NOT touched - a non-existent file cannot spam the
 * ImageMagick log, and writing a placeholder for it would fabricate content.
 *
 * SAFE BY DEFAULT: with no "apply" token it only reports - changes nothing.
 * Every replaced file is first backed up (the corrupt original is preserved)
 * and recorded in a lost-media report, so a real image can be restored later.
 *
 *   drush php:script scripts/image-neutralize-placeholder.php <audit.csv>
 *   drush php:script scripts/image-neutralize-placeholder.php <audit.csv> apply
 *   # cap the batch, or restrict verdicts (default html,empty,truncated):
 *   ... <audit.csv> apply limit=200
 *   ... <audit.csv> apply verdicts=html
 *
 * See docs/IMAGE-CORRUPTION-RESTORATION-PLAN.md.
 */

use Drupal\Core\Database\Database;
use Drupal\image\Entity\ImageStyle;

$tokens = $extra ?? ($args ?? []);
$audit = NULL;
$apply = FALSE;
$limit = 0;
$verdicts = ['html' => 1, 'empty' => 1, 'truncated' => 1];
foreach ($tokens as $t) {
  if ($t === 'apply') {
    $apply = TRUE;
  }
  elseif (strpos($t, 'limit=') === 0) {
    $limit = (int) substr($t, 6);
  }
  elseif (strpos($t, 'verdicts=') === 0) {
    $verdicts = array_fill_keys(array_filter(explode(',', substr($t, 9))), 1);
  }
  elseif ($t !== '' && $t[0] !== '-') {
    $audit = $t;
  }
}

if (!$audit || !is_file($audit)) {
  echo "ERROR: pass the Phase A audit CSV path as the first argument.\n";
  echo "  drush php:script scripts/image-neutralize-placeholder.php /path/to/image-audit-*.csv\n";
  return;
}
if (!function_exists('imagecreatetruecolor')) {
  echo "ERROR: PHP GD is required to generate placeholders.\n";
  return;
}

$file_system = \Drupal::service('file_system');
$connection = Database::getConnection();
$public_root = rtrim($file_system->realpath('public://'), '/');

$run_id = date('Ymd-His');
$dir = dirname($audit);
$backup_dir = $dir . '/placeholder-backup-' . $run_id;
$manifest_path = $dir . '/placeholder-manifest-' . $run_id . '.csv';
$lostmedia_path = $dir . '/lost-media-' . $run_id . '.csv';
$action_path = $dir . '/placeholder-actions-' . $run_id . '.csv';

if ($apply && !is_dir($backup_dir)) {
  mkdir($backup_dir, 0775, TRUE);
}

/**
 * Builds a valid placeholder image in the given format, cached per format.
 */
$placeholder_cache = [];
$make_placeholder = function (string $fmt) use (&$placeholder_cache) {
  if (isset($placeholder_cache[$fmt])) {
    return $placeholder_cache[$fmt];
  }
  $w = 320;
  $h = 240;
  $img = imagecreatetruecolor($w, $h);
  $bg = imagecolorallocate($img, 238, 238, 238);
  $border = imagecolorallocate($img, 170, 170, 170);
  $ink = imagecolorallocate($img, 120, 120, 120);
  imagefilledrectangle($img, 0, 0, $w - 1, $h - 1, $bg);
  imagerectangle($img, 4, 4, $w - 5, $h - 5, $border);
  $msg = 'image unavailable';
  $fw = imagefontwidth(5);
  $fh = imagefontheight(5);
  imagestring($img, 5, (int) (($w - strlen($msg) * $fw) / 2), (int) (($h - $fh) / 2), $msg, $ink);
  ob_start();
  switch ($fmt) {
    case 'png':
      imagepng($img);
      break;

    case 'gif':
      imagegif($img);
      break;

    default:
      imagejpeg($img, NULL, 85);
  }
  $bytes = ob_get_clean();
  imagedestroy($img);
  $placeholder_cache[$fmt] = $bytes;
  return $bytes;
};

/**
 * Maps a file extension to a placeholder format + mime.
 */
$fmt_for = function (string $path) {
  $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
  if ($ext === 'png') {
    return ['png', 'image/png'];
  }
  if ($ext === 'gif') {
    return ['gif', 'image/gif'];
  }
  return ['jpg', 'image/jpeg'];
};

$action = fopen($action_path, 'w');
fputcsv($action, ['fid', 'uri', 'verdict', 'referenced', 'decision', 'note']);
$man = NULL;
$lost = NULL;
if ($apply) {
  $man = fopen($manifest_path, 'w');
  fputcsv($man, ['fid', 'uri', 'target_abs', 'backup_abs', 'old_size', 'new_size', 'new_mime']);
  $lost = fopen($lostmedia_path, 'w');
  fputcsv($lost, ['fid', 'uri', 'original_verdict', 'original_db_size', 'referenced']);
}

$counts = [
  'neutralized' => 0,
  'would_neutralize' => 0,
  'skip_not_on_disk' => 0,
  'skip_no_fid' => 0,
  'failed' => 0,
];

$in = fopen($audit, 'r');
$header = fgetcsv($in);
$col = array_flip($header);
$processed = 0;

while (($r = fgetcsv($in)) !== FALSE) {
  $verdict = $r[$col['verdict']] ?? '';
  if (!isset($verdicts[$verdict])) {
    continue;
  }
  if ($limit && $processed >= $limit) {
    break;
  }
  $uri = $r[$col['uri']];
  $ref = (int) ($r[$col['referenced']] ?? 0);
  $db_size = (int) ($r[$col['db_size']] ?? 0);

  if (strpos($uri, 'public://') !== 0) {
    continue;
  }
  $rel = substr($uri, strlen('public://'));
  $target = $public_root . '/' . $rel;

  // Only present-but-corrupt files (the ones that actually fail identify).
  if (!is_file($target)) {
    fputcsv($action, [$r[$col['fid']], $uri, $verdict, $ref, 'skip_not_on_disk', '']);
    $counts['skip_not_on_disk']++;
    continue;
  }

  $fid = $connection->query('SELECT fid FROM {file_managed} WHERE uri = :u', [':u' => $uri])->fetchField();
  if (!$fid) {
    fputcsv($action, [$r[$col['fid']], $uri, $verdict, $ref, 'skip_no_fid', '']);
    $counts['skip_no_fid']++;
    continue;
  }

  $processed++;

  if (!$apply) {
    fputcsv($action, [$fid, $uri, $verdict, $ref, 'would_neutralize', '']);
    $counts['would_neutralize']++;
    continue;
  }

  // --- APPLY ---
  [$fmt, $mime] = $fmt_for($target);
  $bytes = $make_placeholder($fmt);

  $old_size = filesize($target);
  $backup_abs = $backup_dir . '/' . $fid . '-' . basename($target);
  @copy($target, $backup_abs);

  if (@file_put_contents($target, $bytes) === FALSE) {
    fputcsv($action, [$fid, $uri, $verdict, $ref, 'failed', 'write failed']);
    $counts['failed']++;
    continue;
  }
  if (@getimagesize($target) === FALSE) {
    if (is_file($backup_abs)) {
      @copy($backup_abs, $target);
    }
    fputcsv($action, [$fid, $uri, $verdict, $ref, 'failed', 'placeholder invalid - rolled back']);
    $counts['failed']++;
    continue;
  }

  $new_size = filesize($target);
  $connection->update('file_managed')
    ->fields(['filesize' => $new_size, 'filemime' => $mime])
    ->condition('fid', $fid)
    ->execute();

  try {
    foreach (ImageStyle::loadMultiple() as $style) {
      $style->flush($uri);
    }
  }
  catch (\Throwable $e) {
    // Non-fatal.
  }

  fputcsv($man, [$fid, $uri, $target, $backup_abs, $old_size, $new_size, $mime]);
  fputcsv($lost, [$fid, $uri, $verdict, $db_size, $ref]);
  fputcsv($action, [$fid, $uri, $verdict, $ref, 'neutralized', '']);
  $counts['neutralized']++;
  if ($counts['neutralized'] % 500 === 0) {
    echo '  neutralized ' . $counts['neutralized'] . " ...\n";
  }
}

fclose($in);
fclose($action);
if ($man) {
  fclose($man);
  fclose($lost);
}

echo "\n=== Placeholder neutralize " . ($apply ? 'APPLY' : 'DRY-RUN') . " complete ===\n";
echo 'verdicts: ' . implode(',', array_keys($verdicts)) . "\n";
foreach ($counts as $k => $v) {
  if ($v > 0 || in_array($k, ['neutralized', 'would_neutralize'], TRUE)) {
    echo sprintf("  %-20s %d\n", $k, $v);
  }
}
echo "Action report: $action_path\n";
if ($apply) {
  echo "Rollback manifest: $manifest_path\n";
  echo "Lost-media report: $lostmedia_path\n";
  echo "Backups (corrupt originals): $backup_dir\n";
}
else {
  echo "Re-run with 'apply' appended to write placeholders.\n";
}
