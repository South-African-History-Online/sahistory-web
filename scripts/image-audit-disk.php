<?php

/**
 * @file
 * Phase A - Image corruption ground-truth scanner (read-only).
 *
 * Scans every image/* file_managed entity and compares the DB-recorded size to
 * the ACTUAL bytes on disk, probing the real file type. This replaces the
 * stale file_managed.filesize proxy with reality before any repair.
 *
 * Safe to run on production - it only reads files and writes one CSV report.
 *
 * Usage:
 *   ddev drush php:script scripts/image-audit-disk.php
 *   # on prod:
 *   drush php:script scripts/image-audit-disk.php
 *
 * Output: scratch CSV path is printed at the end. Columns:
 *   fid,uri,db_size,disk_size,real_mime,verdict,referenced
 * Verdicts: ok | empty | html | truncated | missing
 *
 * Classification is driven by getimagesize() (reads image headers directly,
 * no libmagic dependency); finfo is optional enrichment only, since it is
 * unreliable on some production hosts.
 *
 * See docs/IMAGE-CORRUPTION-RESTORATION-PLAN.md.
 */

use Drupal\Core\Database\Database;

$file_system = \Drupal::service('file_system');
$connection = Database::getConnection();

// Where to write the report. Use the public files dir so it works everywhere.
$out_rel = 'private://image-audit-' . date('Ymd-His') . '.csv';
$out_abs = $file_system->realpath('private://') ? $file_system->realpath('private://') . '/image-audit-' . date('Ymd-His') . '.csv'
  : $file_system->realpath('public://') . '/image-audit-' . date('Ymd-His') . '.csv';

$fh = fopen($out_abs, 'w');
fputcsv($fh, ['fid', 'uri', 'db_size', 'disk_size', 'real_mime', 'verdict', 'referenced']);

// Pull usage counts once (fid => count) to avoid a query per file.
$usage = [];
foreach ($connection->query("SELECT fid, COUNT(*) c FROM {file_usage} GROUP BY fid") as $row) {
  $usage[$row->fid] = (int) $row->c;
}

$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : NULL;

$stats = [
  'ok' => 0,
  'empty' => 0,
  'html' => 0,
  'truncated' => 0,
  'missing' => 0,
];

// Iterate image entities in id order, streaming to keep memory flat.
$query = $connection->query("SELECT fid, uri, filesize FROM {file_managed} WHERE filemime LIKE 'image/%' ORDER BY fid");
$n = 0;
foreach ($query as $row) {
  $n++;
  $real = $file_system->realpath($row->uri);
  $db_size = (int) $row->filesize;
  $ref = $usage[$row->fid] ?? 0;

  if ($real === FALSE || !file_exists($real)) {
    fputcsv($fh, [$row->fid, $row->uri, $db_size, '', '', 'missing', $ref]);
    $stats['missing']++;
    continue;
  }

  $disk_size = filesize($real);

  if ($disk_size === 0) {
    fputcsv($fh, [$row->fid, $row->uri, $db_size, 0, '', 'empty', $ref]);
    $stats['empty']++;
    continue;
  }

  // Primary signal: getimagesize() reads the image header directly and does not
  // depend on libmagic/finfo (which is unreliable on some hosts). A valid,
  // decodable image returns [w, h, type, ...]; corrupt/truncated/non-image
  // data returns FALSE. SVG is the exception (getimagesize cannot read it), so
  // it is handled separately below.
  $info = @getimagesize($real);
  if ($info !== FALSE) {
    $mime = $info['mime'] ?? ('image/' . image_type_to_extension($info[2] ?? 0, FALSE));
    fputcsv($fh, [$row->fid, $row->uri, $db_size, $disk_size, $mime, 'ok', $ref]);
    $stats['ok']++;
    if ($n % 5000 === 0) {
      echo "  scanned $n ...\n";
    }
    continue;
  }

  // Not a decodable raster image. Read the header bytes to classify.
  $head = file_get_contents($real, FALSE, NULL, 0, 64);
  $trim = ltrim($head);

  // SVG is a valid image type that getimagesize cannot decode.
  if (stripos($trim, '<svg') !== FALSE || (stripos($trim, '<?xml') === 0 && stripos($head, 'svg') !== FALSE)) {
    fputcsv($fh, [$row->fid, $row->uri, $db_size, $disk_size, 'image/svg+xml', 'ok', $ref]);
    $stats['ok']++;
    continue;
  }

  // HTML masquerading as an image - the migration-corruption signature.
  if (stripos($trim, '<!') === 0 || stripos($trim, '<html') !== FALSE || stripos($trim, '<?php') === 0 || stripos($trim, '<?xml') === 0) {
    fputcsv($fh, [$row->fid, $row->uri, $db_size, $disk_size, 'text/html', 'html', $ref]);
    $stats['html']++;
    continue;
  }

  // Finfo is optional enrichment only - never used to gate classification.
  $real_mime = $finfo ? (finfo_file($finfo, $real) ?: 'unknown') : 'unknown';
  fputcsv($fh, [$row->fid, $row->uri, $db_size, $disk_size, $real_mime, 'truncated', $ref]);
  $stats['truncated']++;

  if ($n % 5000 === 0) {
    echo "  scanned $n ...\n";
  }
}

fclose($fh);
if ($finfo) {
  finfo_close($finfo);
}

$bad = $stats['empty'] + $stats['html'] + $stats['truncated'] + $stats['missing'];
echo "\n=== Image audit complete ===\n";
echo "Total image entities scanned: $n\n";
foreach ($stats as $k => $v) {
  echo sprintf("  %-10s %d\n", $k, $v);
}
echo "Corrupt/unusable total: $bad\n";
echo "Report written to: $out_abs\n";
