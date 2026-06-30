# Image Backup Restore (local -> prod) + Media Migration Prep

Companion to `docs/IMAGE-CORRUPTION-RESTORATION-PLAN.md`. Covers two things:
1. Restoring the ~8,558 still-broken prod images from the Feb-2018 D7 backup,
   pushed from local over SSH.
2. Preparing for a proper migration of images into Drupal Media entities.

## Source of truth

- Pre-corruption backup: `~/000 SAHO WEBSITE 2018/sites/default/files`
  (27 GB, 103,268 image files, 48,623 distinct names; Feb-2018 D7 site).
- Prod still-broken set: the latest Phase A audit CSV on prod
  (`private/image-audit-*.csv`), verdicts html / empty / truncated / missing.
- Key fact: prod `file_managed.filesize` kept the ORIGINAL (pre-corruption)
  size, so a backup file whose byte size equals `db_size` is provably the same
  image - a collision-proof match.

## Part 1 - Restore pipeline (3 stages)

All staging/matching happens LOCAL (where the 27 GB backup lives). Only a small
matched subset is pushed to prod. Both scripts are dry-run / read-only first.

### Stage 1 (LOCAL): pull the prod audit CSV down

```bash
# from your local machine; fill in the SSH host
scp sahistrg878@PROD_HOST:/home/sahistrg878/public_html/sahistory.org.za/private/image-audit-20260630-164831.csv \
  ~/saho-image-audit.csv
```

### Stage 2 (LOCAL): match against the 2018 backup and stage originals

```bash
cd ~/ddev-projects/sahistory-web
php scripts/backup-restore-stage.php \
  ~/saho-image-audit.csv \
  "~/000 SAHO WEBSITE 2018/sites/default/files" \
  ~/saho-restore-staging \
  min=100
```

Output: `~/saho-restore-staging/` containing the matched originals laid out by
their target relative path, plus `restore-manifest.csv`. The summary prints
matched_exact / matched_largest / miss_no_name / miss_too_small - i.e. exactly
how many of the broken set the 2018 backup can recover.

### Stage 3a (TRANSPORT): push the staging tree to prod over SSH

```bash
# dry-run first to see what would transfer
rsync -avz --dry-run ~/saho-restore-staging/ \
  sahistrg878@PROD_HOST:/home/sahistrg878/public_html/sahistory.org.za/private/saho-restore-staging/

# then for real (drop --dry-run)
rsync -avz ~/saho-restore-staging/ \
  sahistrg878@PROD_HOST:/home/sahistrg878/public_html/sahistory.org.za/private/saho-restore-staging/
```

### Stage 3b (PROD over SSH): apply into place

```bash
ssh sahistrg878@PROD_HOST
cd ~/public_html/sahistory.org.za

# dry-run (writes nothing)
vendor/bin/drush php:script scripts/backup-restore-apply.php private/saho-restore-staging

# careful first batch, then full
vendor/bin/drush php:script scripts/backup-restore-apply.php private/saho-restore-staging apply limit=200
# spot-check a few image URLs in a browser, then:
vendor/bin/drush php:script scripts/backup-restore-apply.php private/saho-restore-staging apply
```

Each apply backs up the file it overwrites (rollback manifest in the staging
dir), validates with getimagesize, updates file_managed.filesize/filemime, and
flushes that file's style derivatives.

### Verify + close out

```bash
vendor/bin/drush php:script scripts/image-audit-disk.php   # corrupt count -> ~0
vendor/bin/drush watchdog:delete --type=image              # clear the log
```

## Part 2 - Media migration prep

DO THIS AFTER restoration. Migrating corrupt/placeholder files into Media would
just enshrine the breakage; the restore above is the prerequisite, and the audit
CSV is the clean inventory the migration consumes.

### Target state
- Each healthy image file -> a `media` entity of bundle `image`.
- Legacy file/image fields on nodes -> entity-reference (media) fields.
- Inline `<img src="/sites/default/files/...">` in body HTML -> `<drupal-media>`
  embeds (CKEditor 5 media library).
- The legacy redundant triple-path duplicates collapse to one reusable media
  entity (see the duplicate clusters surfaced during restoration).

### Modules
- Core: `media`, `media_library` (enabled?), `migrate`, `migrate_plus`,
  `migrate_tools`.
- Embeds: CKEditor 5 media embed; optionally `linkit`.

### Approach (staged, reversible)
1. INVENTORY: from the final audit CSV, list every `ok` image file_managed
   entity (post-restore) with its referencing entities/fields.
2. DEDUPE: group by content hash (sha1 of bytes) so the triple-path duplicates
   become one media entity; keep a canonical path per group.
3. CREATE MEDIA: batch-create one `media:image` per canonical file (idempotent;
   skip if a media entity already wraps that fid).
4. REWRITE REFERENCES:
   - file/image fields -> media reference fields (add new field, backfill,
     then switch display; keep old field until verified).
   - inline body `<img>` -> `<drupal-media data-entity-uuid=...>` via a guarded
     body rewriter (same pattern as saho_linkfix's BodyLinkRewriter:
     additive, tested, dry-run).
5. VERIFY: render sampled nodes (Playwright), confirm images + responsive styles.
6. CUTOVER: switch theme/templates to media render; retire legacy fields.

### Sequencing
restore (Part 1) -> dedupe inventory -> media create -> reference rewrite ->
verify -> cutover. Each step ships behind its own PR + tag, dry-run first.

## Safety
- Production is live. Every write step is dry-run by default and backs up what
  it overwrites.
- Matching/staging is fully local; only a vetted subset is transported.
- No legacy field is dropped until its media replacement is verified.
