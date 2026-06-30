# Image Corruption Restoration Plan

Status: PHASE C EXECUTED on prod 2026-06-30 (8,357 repaired); backup/re-harvest
        track + log clear + re-audit remain
Author: investigation 2026-06-27
Trigger: dblog flooded with `image` errors - "ImageMagick error 1: identify: Not a JPEG file"

## Execution log

- 2026-06-27: Phase A + B run on prod. 119,617 image entities: 2,555
  corrupt-bytes (html/empty/truncated), 14,360 missing-from-disk. Of 16,915
  broken, 8,435 have a healthy on-disk twin, 8,480 need backup/re-harvest.
- 2026-06-30: Phase C repair run on prod with `apply unreferenced min=100`.
  Result: **8,357 repaired**, 78 skip_too_small. Two repaired files verified
  on the live domain via Playwright (chris-hani.jpg 678x829; deklerk-fw3.jpg
  renders the real portrait). Rollback manifests + per-file backups in prod
  `private/` (image-repair-manifest-*.csv, image-repair-backup-*/).
- PIVOT recorded: `file_usage` is the wrong usage filter here. The corrupt
  files are legacy-path orphans (file_usage = 0) whose healthy twin carries the
  usage record; the orphan path is still requested and spams the log. Hence the
  repair must run with the `unreferenced` token (referenced-only fixed just 73).

## Remaining

1. Clear the log: `drush watchdog:delete --type=image` (prod).
2. Re-run Phase A; corrupt-bytes count should fall from 2,555 to ~0 (only the
   ~78 too-small still present-but-broken).
3. The 8,480 needs_backup_or_reharvest (+ 78 too-small) have no on-disk twin:
   pre-2019 files backup, else Archive Factory / Wayback re-harvest.
4. The 14,360 `missing` entries (path absent on disk) are a separate data-loss
   track, partly overlapping #3.

## 0. Authoritative production numbers (Phase A + B, 2026-06-28)

Phase A on-disk scan of all 119,617 image entities:

| verdict   | count   | meaning                                            |
|-----------|---------|----------------------------------------------------|
| ok        | 102,702 | valid, decodable images                            |
| html      | 2,493   | HTML error pages with image extension (log cause)  |
| empty     | 58      | 0-byte files                                        |
| truncated | 4       | corrupt/incomplete image bytes                      |
| missing   | 14,360  | file_managed points to a path absent from disk     |

Two distinct problems:
- LOG SPAM = the 2,555 files that exist-but-are-bad-bytes (html+empty+truncated).
  ImageMagick can only fail `identify` on a present file, so this exact set
  drives the dblog clutter. (Much smaller than the 7,324 DB-filesize estimate.)
- DATA LOSS = 14,360 missing files (~12% of refs point at nothing). Separate
  track; does not cause ImageMagick errors.

Phase B recovery-source scan of the 16,915 broken files:

| source_method             | count | meaning                                  |
|---------------------------|-------|------------------------------------------|
| duplicate                 | 8,435 | healthy same-name image on disk - fix now |
| needs_backup_or_reharvest | 8,480 | no on-disk copy - backup / re-harvest     |
| derivative                | 0     | none (cannot derive from a corrupt source)|

So ~half are recoverable from on-disk twins (the legacy/modern path redundancy),
half are genuinely lost and need backups or Archive Factory re-harvest.

## 1. Summary

The recurring ImageMagick errors in the admin log are the visible symptom of a
large-scale image corruption introduced during the D7 -> D11 migration. A batch
of image files had their real bytes replaced by tiny placeholders (about 320
bytes of HTML "page not found" markup, or 0 bytes). Every time a visitor or
crawler requests an image style derivative of one of these files, ImageMagick's
`identify` fails and Drupal logs an error.

Because dblog self-caps at 1,000 rows (`dblog.settings:row_limit = 1000`), these
errors evict genuinely useful log entries. That is the "clutter".

This document is the plan to fix the root cause (restore the images), not just
clear the log.

## 2. Confirmed findings (from local DDEV against a prod DB mirror)

- Total image file entities: 119,617
- Corrupt (filesize < 1500 bytes, mime image/*): 7,324  (6.1%)
  - 0 bytes (empty): 50
  - 1-399 bytes (HTML error page): 3,310
  - 400-1499 bytes: 3,964
- Referenced in live content (file_usage > 0): 6,255  -> users see broken images
- Orphaned (no file_usage): 1,069
- Have a healthy same-name copy elsewhere in DB: only 177 (and generic names like
  `1.jpg` collide, so this is NOT a safe blanket repoint)
- Corruption window: created timestamps cluster in 2019-05 .. 2019-06
  (about 6,943 of 7,324). This was a single bad migration batch.
- Top affected directories: images_new (3,430), images (926), field (237),
  event_pics (108), archive-files (64), plus a public:/// root batch (238).
- Mime split: gif 4,771, jpeg 2,462, png 91.

### Why config cannot suppress it
The errors come from `ImagemagickExecManager::sendCommand()` line ~223, the
`$this->logger->error(...)` branch (return code != 0 AND stderr non-empty).
This path is NOT gated by `imagemagick.settings:log_warnings`. Toggling that
setting does nothing. The only ways to stop the errors are: remove/replace the
corrupt source files, or stop the requests (not controllable - crawlers).

## 3. Important caveat: DB filesize is a proxy

The 7,324 count is based on the `file_managed.filesize` column, recorded at
migration time. The authoritative truth is the actual bytes on the production
disk - a file may have been re-uploaded since, or the recorded size may be stale.
Phase A re-establishes ground truth before any destructive action.

## 4. Recovery sources (to be discovered - no backup inventory exists yet)

In priority order of quality:

1. Image-style derivatives already on disk.
   Drupal stores generated derivatives at
   `sites/default/files/styles/<style>/public/<original-relative-path>`.
   If a derivative was generated BEFORE the original was corrupted, it is a
   valid (if downscaled) copy we can promote back to the original path.
   This is the most promising no-backup source. Pick the largest-dimension
   style available per file.
2. Healthy duplicates within the current files tree (same content, different
   path; or a larger same-name file). Verify by image dimensions / content, not
   just name (names collide).
3. Pre-migration backups: D7 site files archive, hosting snapshots, or any
   `files` tarball from before 2019-05. Locate by auditing the production server
   and the hosting provider's backups.
4. Re-harvest from the live web: the SAHO Archive Factory pipeline
   (`~/saho-archive-import/`) and/or web.archive.org for the original article
   pages. Last resort, lower fidelity, but covers files with no other source.

## 5. Per-file recovery decision tree

For each confirmed-corrupt original:

```
is there a valid style derivative on disk?
  yes -> promote largest derivative to original path (note: upscaled-from-thumb
         quality; acceptable for icons/logos, flag larger images for review)
  no  -> is there a verified healthy duplicate (by dimensions/content)?
           yes -> copy duplicate bytes to original path
           no  -> is there a pre-2019-05 backup original?
                    yes -> restore from backup
                    no  -> re-harvest (Archive Factory / Wayback)
                             found    -> install
                             not found-> mark unrecoverable: replace with a
                                         neutral placeholder + record in a
                                         "lost-media" report; remove file_usage
                                         so no derivative is attempted
```

## 6. Execution phases

All work developed and dry-run on local DDEV first, then applied to prod with
explicit confirmation. Every write is additive and reversible; nothing is
deleted until its replacement is verified.

### Phase A - Ground truth (read-only, run on PROD)
- Scan every `image/*` file_managed entry: stat real disk size + probe real
  type (finfo / getimagesize).
- Output authoritative CSV: fid, uri, db_size, disk_size, real_mime, verdict
  (ok | empty | html | truncated | missing).
- This replaces the DB-filesize proxy with reality. Deliverable: the definitive
  corrupt list.

### Phase B - Source discovery (read-only, run on PROD)
- For each confirmed-corrupt file, locate recovery candidates per section 4:
  scan styles/ derivatives, find healthy duplicates, audit server + hosting for
  backups, queue the remainder for re-harvest.
- Output: recovery-plan CSV mapping each corrupt fid -> chosen source + method.

### Phase C - Repair (staged writes, dry-run local first)
- For each file with a chosen source: write recovered bytes to the original
  path, flush that file's style derivatives (`image_path_flush` /
  delete styles/*/<path>), update file_managed.filesize, run `identify` to
  confirm it now passes.
- Batch by directory/source; checkpoint after each batch.
- Keep a per-file before/after manifest for rollback.

### Phase D - Unrecoverable handling
- Files with no source: install a neutral placeholder so pages do not show a
  broken image, record them in `docs/lost-media-report.csv`, and detach
  file_usage so no further derivative attempts (no more log errors).

### Phase E - Stop the log bleeding (do early as interim relief)
- Clear current image errors: `drush watchdog:delete --type=image` (prod).
- Optional interim: a cron `drush watchdog:delete --type=image` until repair is
  complete, so the 1,000-row log stays usable. Remove once Phase C/D finish.

### Phase F - Validation
- Re-run an `identify` sweep across all originals: target zero failures.
- Confirm dblog `image` errors stop accruing over 48h.
- Spot-check a sample of restored pages in the browser (Playwright screenshots).

## 7. Safety

- Production is live. No destructive op runs on prod without a local dry-run and
  explicit confirmation.
- All repairs are additive (write bytes, then flush derivatives). The original
  corrupt bytes are preserved in the manifest until validation passes.
- Work in batches with checkpoints; never a single 7,000-file sweep.

## 8. Open questions

- Where do the most complete pre-2019-05 file backups live? (hosting snapshots,
  old D7 server, local archive) - audit needed in Phase B.
- For files recoverable only from low-res derivatives, is upscaled quality
  acceptable, or should those go to re-harvest for full resolution?

## 9. Scripts

- `scripts/image-audit-disk.php`    - Phase A ground-truth scanner (read-only)
  BUILT + validated locally. Run: `drush php:script scripts/image-audit-disk.php`
- `scripts/image-source-finder.php` - Phase B recovery-source mapper (read-only)
  BUILT + validated locally. Run:
  `drush php:script scripts/image-source-finder.php <audit.csv>`
- `scripts/image-repair.php`         - Phase C staged repair executor.
  BUILT + validated locally (corrupt->healthy copy, metadata corrected,
  derivative flush, reversible backup manifest). DRY-RUN by default; append
  `apply` to write. Positional tokens: `apply`, `unreferenced`, `min=<int>`,
  `limit=<int>`. Quality guards: min-dimension (default 200) rejects junk
  same-name matches; post-write getimagesize verify with rollback; referenced
  files only unless `unreferenced` given. Run:
  `drush php:script scripts/image-repair.php <recovery-plan.csv>` (dry-run),
  then `... <recovery-plan.csv> apply limit=200` for a careful first batch.

## 10. Validation status (2026-06-27, local DDEV)

- Phase A scanned all 119,617 image entities. Key discovery: the on-disk truth
  differs from the DB filesize proxy. Example: `event_pics/deklerk-fw3.jpg` has
  db_size 36,979 but is 0 bytes on disk and is referenced 5 times - the
  filesize<1500 query would have missed it. The real corrupt set must be
  measured on the PROD disk, not inferred from the DB.
- Phase B correctly located recovery sources for sample corrupt files, e.g.
  `u3/chris-hani.jpg` (empty) -> healthy duplicate `field/image/chris-hani.jpg`
  at 678x829. It also exposed the quality caveat: a `floods.jpg` duplicate was
  only 45x45, so the repair phase must enforce a minimum-dimension threshold and
  not blindly trust same-name duplicates.

## 11. Next action (requires PROD)

The local files dir is only a partial mirror, so the authoritative corrupt count
and the real recoverable-vs-needs-backup split can only come from production.
Run, on prod (both read-only):

```
drush php:script scripts/image-audit-disk.php
drush php:script scripts/image-source-finder.php <the-audit.csv-just-written>
```

Then we size Phase C/D from real numbers. Interim log relief is safe any time:
`drush watchdog:delete --type=image`.
