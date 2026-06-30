<?php

/**
 * @file
 * Backup restore - LOCAL staging step (read-only against prod; standalone PHP).
 *
 * Matches the still-broken production images (from a Phase A audit CSV) against
 * a pre-corruption files backup tree (e.g. the Feb-2018 D7 files tree), and
 * copies the matched ORIGINALS into a staging directory laid out by their
 * target relative path - ready to transport to prod and copy into place.
 *
 * Runs with plain PHP (no Drupal needed): the audit CSV already carries the uri
 * and the original db_size, which is all the matcher needs.
 *
 *   php scripts/backup-restore-stage.php \
 *     <audit.csv> <backup-files-root> <staging-out> [min=100]
 *
 * Match priority (per broken file, by basename):
 *   1. EXACT db_size match - a backup file whose byte size equals the size
 *      Drupal recorded before corruption. Provably the same image, collision
 *      proof. (db_size must be > 0.)
 *   2. Largest valid image >= min dimension, when no exact-size match exists.
 * Files with no same-name candidate, or only sub-min candidates, are misses.
 *
 * Only public:// images are handled. Writes a manifest CSV next to the staging
 * dir: target_uri,target_rel,source_path,matched_size,match_type,dimensions.
 *
 * See docs/IMAGE-CORRUPTION-RESTORATION-PLAN.md.
 */

$audit = $argv[1] ?? NULL;
$backup_root = isset($argv[2]) ? rtrim($argv[2], '/') : NULL;
$staging = isset($argv[3]) ? rtrim($argv[3], '/') : NULL;
$min_dim = 100;
foreach (array_slice($argv, 4) as $a) {
  if (strpos($a, 'min=') === 0) {
    $min_dim = (int) substr($a, 4);
  }
}

if (!$audit || !is_file($audit) || !$backup_root || !is_dir($backup_root) || !$staging) {
  fwrite(STDERR, "Usage: php scripts/backup-restore-stage.php <audit.csv> <backup-files-root> <staging-out> [min=100]\n");
  exit(1);
}

@mkdir($staging, 0775, TRUE);
$manifest_path = $staging . '/restore-manifest.csv';

// 1. Index the backup tree by basename -> list of absolute paths.
fwrite(STDERR, "Indexing backup tree under $backup_root ...\n");
$index = [];
$exts = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'gif' => 1];
$it = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($backup_root, FilesystemIterator::SKIP_DOTS),
  RecursiveIteratorIterator::LEAVES_ONLY
);
$indexed = 0;
foreach ($it as $file) {
  if (!$file->isFile()) {
    continue;
  }
  $ext = strtolower($file->getExtension());
  if (!isset($exts[$ext])) {
    continue;
  }
  $bn = strtolower($file->getFilename());
  $index[$bn][] = $file->getPathname();
  $indexed++;
}
fwrite(STDERR, "Indexed $indexed backup image files (" . count($index) . " distinct names).\n");

// 2. Walk the audit CSV broken rows and match.
$broken_verdicts = ['html' => 1, 'empty' => 1, 'truncated' => 1, 'missing' => 1];

$in = fopen($audit, 'r');
$header = fgetcsv($in);
$col = array_flip($header);

$out = fopen($manifest_path, 'w');
fputcsv($out, ['target_uri', 'target_rel', 'source_path', 'matched_size', 'match_type', 'dimensions']);

$stats = [
  'matched_exact' => 0,
  'matched_largest' => 0,
  'miss_no_name' => 0,
  'miss_too_small' => 0,
  'skip_nonpublic' => 0,
];

/**
 * Picks the largest valid image >= min dimension from candidate paths.
 */
$best_valid = function (array $paths) use ($min_dim) {
  $best = NULL;
  foreach ($paths as $p) {
    $info = @getimagesize($p);
    if ($info === FALSE) {
      continue;
    }
    if ($info[0] < $min_dim || $info[1] < $min_dim) {
      continue;
    }
    $area = $info[0] * $info[1];
    if ($best === NULL || $area > $best['area']) {
      $best = ['area' => $area, 'w' => $info[0], 'h' => $info[1], 'path' => $p];
    }
  }
  return $best;
};

while (($r = fgetcsv($in)) !== FALSE) {
  $verdict = $r[$col['verdict']] ?? '';
  if (!isset($broken_verdicts[$verdict])) {
    continue;
  }
  $uri = $r[$col['uri']];
  $db_size = (int) ($r[$col['db_size']] ?? 0);

  if (strpos($uri, 'public://') !== 0) {
    $stats['skip_nonpublic']++;
    continue;
  }
  $rel = substr($uri, strlen('public://'));
  $bn = strtolower(basename($rel));

  $candidates = $index[$bn] ?? [];
  if (!$candidates) {
    $stats['miss_no_name']++;
    continue;
  }

  // Priority 1: exact recorded-size match (collision proof).
  $chosen = NULL;
  $match_type = '';
  $dims = '';
  if ($db_size > 0) {
    foreach ($candidates as $p) {
      if (filesize($p) === $db_size) {
        $info = @getimagesize($p);
        if ($info !== FALSE) {
          $chosen = $p;
          $match_type = 'exact_size';
          $dims = $info[0] . 'x' . $info[1];
          break;
        }
      }
    }
  }

  // Priority 2: largest valid image >= min dimension.
  if ($chosen === NULL) {
    $best = $best_valid($candidates);
    if ($best === NULL) {
      $stats['miss_too_small']++;
      continue;
    }
    $chosen = $best['path'];
    $match_type = 'largest';
    $dims = $best['w'] . 'x' . $best['h'];
  }

  // Stage the chosen original under its TARGET relative path.
  $dest = $staging . '/' . $rel;
  @mkdir(dirname($dest), 0775, TRUE);
  if (@copy($chosen, $dest)) {
    fputcsv($out, [$uri, $rel, $chosen, filesize($chosen), $match_type, $dims]);
    $stats[$match_type === 'exact_size' ? 'matched_exact' : 'matched_largest']++;
  }
}

fclose($in);
fclose($out);

$matched = $stats['matched_exact'] + $stats['matched_largest'];
echo "\n=== Backup restore staging complete ===\n";
echo "min dimension: $min_dim\n";
foreach ($stats as $k => $v) {
  echo sprintf("  %-18s %d\n", $k, $v);
}
echo "TOTAL staged for transport: $matched\n";
echo "Staging dir: $staging\n";
echo "Manifest: $manifest_path\n";
echo "Next: rsync the staging dir to prod, then apply with the prod-side copy step.\n";
