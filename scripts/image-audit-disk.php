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
 * Verdicts: ok | empty | html | truncated | not_image | missing
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
  'not_image' => 0,
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

  // Read a small header to detect HTML masquerading as an image.
  $head = file_get_contents($real, FALSE, NULL, 0, 16);
  $real_mime = $finfo ? finfo_file($finfo, $real) : 'unknown';

  $is_html = (stripos($head, '<!') === 0) || (stripos($head, '<htm') !== FALSE) || (stripos($head, '<?xm') === 0 && stripos($real_mime, 'svg') === FALSE);

  if ($is_html || $real_mime === 'text/html') {
    fputcsv($fh, [$row->fid, $row->uri, $db_size, $disk_size, $real_mime, 'html', $ref]);
    $stats['html']++;
    continue;
  }

  if (strpos((string) $real_mime, 'image/') !== 0) {
    fputcsv($fh, [$row->fid, $row->uri, $db_size, $disk_size, $real_mime, 'not_image', $ref]);
    $stats['not_image']++;
    continue;
  }

  // Real image mime, but is it actually decodable? getimagesize returns FALSE
  // on truncated/corrupt image data.
  $info = @getimagesize($real);
  if ($info === FALSE) {
    fputcsv($fh, [$row->fid, $row->uri, $db_size, $disk_size, $real_mime, 'truncated', $ref]);
    $stats['truncated']++;
    continue;
  }

  fputcsv($fh, [$row->fid, $row->uri, $db_size, $disk_size, $real_mime, 'ok', $ref]);
  $stats['ok']++;

  if ($n % 5000 === 0) {
    echo "  scanned $n ...\n";
  }
}

fclose($fh);
if ($finfo) {
  finfo_close($finfo);
}

$bad = $stats['empty'] + $stats['html'] + $stats['truncated'] + $stats['not_image'] + $stats['missing'];
echo "\n=== Image audit complete ===\n";
echo "Total image entities scanned: $n\n";
foreach ($stats as $k => $v) {
  echo sprintf("  %-10s %d\n", $k, $v);
}
echo "Corrupt/unusable total: $bad\n";
echo "Report written to: $out_abs\n";
