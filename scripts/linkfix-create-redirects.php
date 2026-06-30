<?php

/**
 * @file
 * Create redirects from a vetted source->target TSV (dry-run by default).
 *
 * Input: a TSV with two columns per line - the legacy source path (no domain,
 * leading slash optional) and the target (an alias path like /people/x or a
 * node path). Produced by the linkfix-gap-resolver workflow + the local pair
 * generator. Each confirmed legacy URL becomes a 301 redirect to the current
 * node, so the recurring 404s for famous-person bios and org/place pages stop.
 *
 * SAFE BY DEFAULT: with no "apply" token it only reports. Idempotent - skips a
 * source that already has a redirect. Created redirect ids are written to a
 * rollback file so they can be deleted if needed.
 *
 *   drush php:script scripts/linkfix-create-redirects.php <pairs.tsv>
 *   drush php:script scripts/linkfix-create-redirects.php <pairs.tsv> apply
 *
 * See docs/IMAGE-CORRUPTION-RESTORATION-PLAN.md (linkfix track).
 */

use Drupal\redirect\Entity\Redirect;

$tokens = $extra ?? ($args ?? []);
$tsv = NULL;
$apply = FALSE;
foreach ($tokens as $t) {
  if ($t === 'apply') {
    $apply = TRUE;
  }
  elseif ($t !== '' && $t[0] !== '-') {
    $tsv = $t;
  }
}

if (!$tsv || !is_file($tsv)) {
  echo "ERROR: pass the redirect pairs TSV (source<TAB>target) as the first argument.\n";
  return;
}
if (!\Drupal::moduleHandler()->moduleExists('redirect')) {
  echo "ERROR: the redirect module is not enabled.\n";
  return;
}

$storage = \Drupal::entityTypeManager()->getStorage('redirect');
$rollback = $apply ? fopen(dirname($tsv) . '/linkfix-redirects-created-' . date('Ymd-His') . '.txt', 'w') : NULL;

$counts = ['created' => 0, 'would_create' => 0, 'skip_existing' => 0, 'skip_bad' => 0];

$fh = fopen($tsv, 'r');
while (($line = fgets($fh)) !== FALSE) {
  $line = rtrim($line, "\r\n");
  if ($line === '') {
    continue;
  }
  $parts = explode("\t", $line);
  if (count($parts) < 2) {
    $counts['skip_bad']++;
    continue;
  }
  $src = ltrim(trim($parts[0]), '/');
  $target = trim($parts[1]);
  if ($src === '' || $target === '') {
    $counts['skip_bad']++;
    continue;
  }
  // Redirect source must not carry a query string here; strip if present.
  $src = explode('?', $src)[0];
  $uri = strpos($target, '/') === 0 ? 'internal:' . $target : $target;

  // Skip if a redirect already exists for this exact source path.
  $existing = $storage->loadByProperties(['redirect_source__path' => $src]);
  if ($existing) {
    $counts['skip_existing']++;
    continue;
  }

  if (!$apply) {
    $counts['would_create']++;
    continue;
  }

  try {
    $redirect = Redirect::create([
      'redirect_source' => ['path' => $src, 'query' => []],
      'redirect_redirect' => ['uri' => $uri],
      'status_code' => 301,
      'language' => 'und',
    ]);
    $redirect->save();
    fwrite($rollback, $redirect->id() . "\n");
    $counts['created']++;
    if ($counts['created'] % 200 === 0) {
      echo '  created ' . $counts['created'] . " ...\n";
    }
  }
  catch (\Throwable $e) {
    $counts['skip_bad']++;
  }
}
fclose($fh);
if ($rollback) {
  fclose($rollback);
}

echo "\n=== Linkfix redirect creation " . ($apply ? 'APPLY' : 'DRY-RUN') . " complete ===\n";
foreach ($counts as $k => $v) {
  if ($v > 0 || in_array($k, ['created', 'would_create'], TRUE)) {
    echo sprintf("  %-16s %d\n", $k, $v);
  }
}
if (!$apply) {
  echo "Re-run with 'apply' appended to create these redirects.\n";
}
