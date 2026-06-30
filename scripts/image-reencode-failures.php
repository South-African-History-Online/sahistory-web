<?php

/**
 * @file
 * Phase E - Re-encode images that ImageMagick rejects but GD can still read.
 *
 * The Phase A audit used PHP getimagesize(), which is more lenient than the
 * ImageMagick toolkit the site actually uses. A class of files - notably GIFs
 * with an "invalid colormap index" - pass getimagesize() (so they were marked
 * "ok") yet fail `identify`, so every derivative request still logs an
 * ImageMagick error. These files DO contain a real picture, so the fix is to
 * re-encode them (load via GD, re-save with a clean palette), producing a file
 * ImageMagick accepts while keeping the actual image. If GD cannot read it
 * either, it falls back to a valid "image unavailable" placeholder.
 *
 * Detection runs the real `identify` binary (the same tool that fails), so it
 * finds exactly the files that spam the log - not a getimagesize approximation.
 *
 * SAFE BY DEFAULT: with no "apply" token it only reports. Every changed file is
 * backed up first; reversible via the manifest.
 *
 *   drush php:script scripts/image-reencode-failures.php          # dry-run gifs
 *   drush php:script scripts/image-reencode-failures.php apply
 *   drush php:script scripts/image-reencode-failures.php apply limit=500
 *   drush php:script scripts/image-reencode-failures.php apply ext=all
 *
 * Tokens: apply | limit=<int> | ext=gif|all (default gif - the colormap issue
 * is GIF-specific; ext=all scans every image format, slower).
 *
 * See docs/IMAGE-CORRUPTION-RESTORATION-PLAN.md.
 */

use Drupal\Core\Database\Database;
use Drupal\image\Entity\ImageStyle;

$tokens = $extra ?? ($args ?? []);
$apply = FALSE;
$limit = 0;
$ext_mode = 'gif';
foreach ($tokens as $t) {
  if ($t === 'apply') {
    $apply = TRUE;
  }
  elseif (strpos($t, 'limit=') === 0) {
    $limit = (int) substr($t, 6);
  }
  elseif (strpos($t, 'ext=') === 0) {
    $ext_mode = substr($t, 4) === 'all' ? 'all' : 'gif';
  }
}

if (!function_exists('imagecreatefromgif')) {
  echo "ERROR: PHP GD is required.\n";
  return;
}

$file_system = \Drupal::service('file_system');
$connection = Database::getConnection();
$public_root = rtrim($file_system->realpath('public://'), '/');

$bin = rtrim((string) \Drupal::config('imagemagick.settings')->get('path_to_binaries'), '/');
$identify = ($bin ? $bin . '/' : '') . 'identify';

$run_id = date('Ymd-His');
$out_dir = $file_system->realpath('private://') ?: $public_root;
$backup_dir = $out_dir . '/reencode-backup-' . $run_id;
$manifest_path = $out_dir . '/reencode-manifest-' . $run_id . '.csv';
$action_path = $out_dir . '/reencode-actions-' . $run_id . '.csv';
if ($apply && !is_dir($backup_dir)) {
  mkdir($backup_dir, 0775, TRUE);
}

/**
 * Returns TRUE if ImageMagick identify accepts the file (exit code 0).
 */
$identify_ok = function (string $path) use ($identify) {
  $cmd = escapeshellarg($identify) . ' ' . escapeshellarg($path) . ' > /dev/null 2>&1';
  $rc = 1;
  $out = [];
  exec($cmd, $out, $rc);
  return $rc === 0;
};

/**
 * Re-encodes a file via GD, preserving its format. Returns bytes or NULL.
 */
$reencode = function (string $path) {
  $info = @getimagesize($path);
  if ($info === FALSE) {
    return NULL;
  }
  $img = match ($info[2]) {
    IMAGETYPE_GIF => @imagecreatefromgif($path),
    IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
    IMAGETYPE_PNG => @imagecreatefrompng($path),
    IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : FALSE,
    default => FALSE,
  };
  if ($img === FALSE) {
    return NULL;
  }
  if ($info[2] === IMAGETYPE_PNG) {
    imagealphablending($img, FALSE);
    imagesavealpha($img, TRUE);
  }
  ob_start();
  switch ($info[2]) {
    case IMAGETYPE_GIF:
      imagegif($img);
      break;

    case IMAGETYPE_PNG:
      imagepng($img);
      break;

    case IMAGETYPE_WEBP:
      imagewebp($img);
      break;

    default:
      imagejpeg($img, NULL, 90);
  }
  $bytes = ob_get_clean();
  imagedestroy($img);
  return $bytes;
};

$action = fopen($action_path, 'w');
fputcsv($action, ['fid', 'uri', 'decision', 'note']);
$man = NULL;
if ($apply) {
  $man = fopen($manifest_path, 'w');
  fputcsv($man, ['fid', 'uri', 'target_abs', 'backup_abs', 'old_size', 'new_size', 'method']);
}

$counts = [
  'scanned' => 0,
  'identify_ok' => 0,
  'reencoded' => 0,
  'would_fix' => 0,
  'placeholdered' => 0,
  'failed' => 0,
];

$mime_cond = $ext_mode === 'gif' ? "filemime = 'image/gif'" : "filemime LIKE 'image/%'";
$query = $connection->query("SELECT fid, uri FROM {file_managed} WHERE $mime_cond ORDER BY fid");

foreach ($query as $row) {
  if ($limit && ($counts['reencoded'] + $counts['would_fix'] + $counts['placeholdered']) >= $limit) {
    break;
  }
  if (strpos($row->uri, 'public://') !== 0) {
    continue;
  }
  $target = $public_root . '/' . substr($row->uri, strlen('public://'));
  if (!is_file($target)) {
    continue;
  }

  $counts['scanned']++;
  if ($counts['scanned'] % 5000 === 0) {
    echo '  scanned ' . $counts['scanned'] . " ...\n";
  }

  // Only act on files that ImageMagick actually rejects.
  if ($identify_ok($target)) {
    $counts['identify_ok']++;
    continue;
  }

  if (!$apply) {
    fputcsv($action, [$row->fid, $row->uri, 'would_fix', '']);
    $counts['would_fix']++;
    continue;
  }

  // --- APPLY: try GD re-encode first (keeps the real picture) ---
  $backup_abs = $backup_dir . '/' . $row->fid . '-' . basename($target);
  @copy($target, $backup_abs);
  $old_size = filesize($target);

  $bytes = $reencode($target);
  $method = '';
  if ($bytes !== NULL) {
    @file_put_contents($target, $bytes);
    if ($identify_ok($target)) {
      $method = 'reencode';
    }
  }

  // Fallback: a valid placeholder if re-encode did not satisfy identify.
  if ($method === '') {
    $w = 320;
    $h = 240;
    $img = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($img, 238, 238, 238);
    $ink = imagecolorallocate($img, 120, 120, 120);
    imagefilledrectangle($img, 0, 0, $w - 1, $h - 1, $bg);
    imagestring($img, 5, 92, 112, 'image unavailable', $ink);
    ob_start();
    imagegif($img);
    $pbytes = ob_get_clean();
    imagedestroy($img);
    @file_put_contents($target, $pbytes);
    if ($identify_ok($target)) {
      $method = 'placeholder';
    }
  }

  if ($method === '') {
    // Could not fix - restore the original from backup.
    if (is_file($backup_abs)) {
      @copy($backup_abs, $target);
    }
    fputcsv($action, [$row->fid, $row->uri, 'failed', 'reencode + placeholder both failed identify']);
    $counts['failed']++;
    continue;
  }

  $new_size = filesize($target);
  $new_mime = @getimagesize($target)['mime'] ?? NULL;
  $fields = ['filesize' => $new_size];
  if ($new_mime) {
    $fields['filemime'] = $new_mime;
  }
  $connection->update('file_managed')->fields($fields)->condition('fid', $row->fid)->execute();

  try {
    foreach (ImageStyle::loadMultiple() as $style) {
      $style->flush($row->uri);
    }
  }
  catch (\Throwable $e) {
    // Non-fatal.
  }

  fputcsv($man, [$row->fid, $row->uri, $target, $backup_abs, $old_size, $new_size, $method]);
  fputcsv($action, [$row->fid, $row->uri, $method === 'reencode' ? 'reencoded' : 'placeholdered', '']);
  $counts[$method === 'reencode' ? 'reencoded' : 'placeholdered']++;
}

fclose($action);
if ($man) {
  fclose($man);
}

echo "\n=== Re-encode " . ($apply ? 'APPLY' : 'DRY-RUN') . " complete (ext=$ext_mode) ===\n";
foreach ($counts as $k => $v) {
  echo sprintf("  %-14s %d\n", $k, $v);
}
echo "Action report: $action_path\n";
if ($apply) {
  echo "Rollback manifest: $manifest_path\n";
  echo "Backups: $backup_dir\n";
}
else {
  echo "Re-run with 'apply' to fix the would_fix files.\n";
}
