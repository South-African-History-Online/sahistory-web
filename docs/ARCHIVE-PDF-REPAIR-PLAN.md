# Archive PDF repair plan

Dead and broken DISA-era archive PDFs: what is wrong, what we fix, how we
prove it. Tooling lives in `saho_linkfix`.

## The problem (measured 2026-10-12, local copy of prod)

41,039 managed PDFs under `public://`:

| State | Count | Notes |
|---|---|---|
| ok (real PDF) | 38,436 | |
| not_pdf (HTML page saved as .pdf) | 2,265 | May-2019 migration, mostly `2019-05/` |
| missing on disk | 317 | dead dirs `archivefiles/`, dangling `DC/` symlinks, etc. |
| empty (0 bytes) | 21 | |

Separately, dblog fills with `page not found` for `/archive/NAME.pdf`, the old
site's bare archive path still requested by search engines and inbound links.
Every one of the 48 sampled names exists on disk under `archive-files*`.

Our own content does not link to `/archive/*.pdf` (4 body links, none DISA),
so the 404s are external traffic and need a redirect, not a content edit.

## Goals and success metrics

| # | Goal | Metric | Target |
|---|---|---|---|
| G1 | Old `/archive/NAME.pdf` URLs reach the document | 48 dblog sample URLs: 301 then a body starting `%PDF-` | 48/48 |
| G2 | No content shows a broken PDF that has a genuine copy | `saho:pdf-twin-scan` `action:swap` + `action:repoint` | 0 |
| G3 | Repair is idempotent | second `saho:pdf-twin-repair` run: `files` | 0 |
| G4 | Repair is reversible | trial apply then `saho:pdf-twin-rollback --apply` restores the original fids | exact restore |
| G5 | No collateral change | no new revisions, `changed` dates untouched, `errors` | 0 errors |
| G6 | Truly lost files are listed for restore | `action:missing` rows referenced by content (`uses > 0`) | list exported |
| G7 | Code quality | phpunit `saho_linkfix`, phpcs (CI flags), drupal-check | all green |

Local results (2026-10-12): G1 48/48 · G2 1,952 -> 0 (3,181 field items on
1,421 nodes + 1,760 media) · G3 0 · G4 5/5 reverted exactly · G5 0 errors ·
G6 529 referenced files with no genuine copy (476 HTML-as-.pdf in `2019-05/`)
· G7 58 tests pass, phpcs + drupal-check clean.

## What the code does

- `ArchivePdfLocator` - finds the genuine copy of a PDF by name across
  `archive-files`, `archive-files2..5`, `archive_files`, `2019-05` (in that
  order). Genuine = non-empty and starts with `%PDF-`. Case-insensitive
  fallback through `file_managed.filename`.
- `ArchivePdfFallbackSubscriber` - on a 404 for `/archive/NAME.pdf` or
  `/sites/default/files/{DC/<dir>|archivefiles|archive}/NAME.pdf`, 301 to the
  genuine copy. Unknown names keep their 404.
- `PdfTwinRepair` + drush commands - for each broken managed PDF referenced by
  content, either swaps the file-field reference to the twin's file entity
  (`swap`) or, when the twin has no file entity, repoints the broken entity's
  uri (`repoint`). No bytes are copied (prod disk is at 87%). Saves in place:
  no new revision, no changed-date bump. Writes a rollback JSON.

## Verify locally

Prereq: DDEV running, branch checked out, `ddev drush cr`.

Prod's `DC/` is a tree of symlinks with absolute prod paths. To make local
behave like prod (lasts until the next `ddev restart`):

```bash
ddev exec "sudo mkdir -p /home/sahistrg878/public_html/sahistory.org.za && \
  sudo ln -sfn /var/www/html/webroot /home/sahistrg878/public_html/sahistory.org.za/webroot"
```

1. Redirects (G1) - each should print `301` and a target under `archive-files*`:

   ```bash
   for n in LaSep80.pdf crmay87.1684.8101.000.000.may1987.8.pdf \
            NuApr93.1684.4890.000.000.Apr1993.7.pdf SeMay87.pdf; do
     curl -sk -o /dev/null -w "$n %{http_code} %{redirect_url}\n" \
       "https://sahistory-web.ddev.site/archive/$n"
   done
   ```

   Expected: `LaSep80` -> `archive-files4/`, `crmay87` -> `archive-files/CrMay87...`
   (case fixed), `NuApr93` -> `archive-files/`, `SeMay87` -> `404` (truly lost).

2. Scan (read-only):

   ```bash
   ddev drush saho:pdf-twin-scan
   ```

   Before repair expect roughly `action:swap 1952`, `action:missing 545`.

3. Trial on one known record (G4). Node 100406 "Night-watchmen" shows an HTML
   page as its PDF (fid 299988, twin fid 307260):

   ```bash
   ddev drush saho:pdf-twin-repair --fids=299988 --apply \
     --rollback-out=public://saho_linkfix_work/trial.json
   curl -skL https://sahistory-web.ddev.site/archive/night-watchmen \
     | grep -o '/sites/default/files/[^"#]*\.pdf' | sort -u
   # -> archive-files/AbJun73.1684.9728.000.003.Jun1973.8.pdf (was 2019-05/abjun73...)
   ddev drush saho:pdf-twin-rollback --in=public://saho_linkfix_work/trial.json --apply
   ```

4. Full run (G2, G3, G5):

   ```bash
   ddev drush saho:pdf-twin-repair            # dry run, read the stats
   ddev drush saho:pdf-twin-repair --apply    # errors must be 0
   ddev drush saho:pdf-twin-scan              # action:swap/repoint gone
   ddev drush saho:pdf-twin-repair            # files 0
   ```

5. Visual: open `/archive/night-watchmen` - the Document Preview renders the
   scanned page and "Updated" still reads 27 Aug 2019.

6. Tests (G7):

   ```bash
   ddev exec -d /var/www/html/webroot/modules/custom phpunit saho_linkfix/tests
   ./vendor/bin/phpcs --standard=Drupal,DrupalPractice \
     --extensions=php,module,inc,install,test,theme --warning-severity=0 \
     webroot/modules/custom/saho_linkfix
   ```

## Prod runbook (after deploy)

1. `drush cr`; check G1 with three `/archive/*.pdf` URLs on the live domain.
2. `drush saho:pdf-twin-scan` - compare counts to the local numbers above;
   investigate any large difference before applying.
3. `drush saho:pdf-twin-repair --fids=299988 --apply` and check node 100406.
4. `drush saho:pdf-twin-repair --apply` (about 3 minutes locally). Keep the
   rollback JSON from `public://saho_linkfix_work/`.
5. Re-scan: swap/repoint 0. Purge Cloudflare for any spot-checked node pages.
6. Export the restore list: rows with `action=missing` and `uses > 0` in
   `pdf_twin_scan.csv` - these need DISA or a backup to recover.

## Out of scope

- Restoring the 529 lost files - needs the source documents (DISA).
- Deleting the orphaned HTML-as-.pdf files and stale file entities - leave
  them until the repair has been live for a while; they are harmless.
- The Apache `DC/` redirect chain in the untracked `.htaccess` keeps working;
  the subscriber only covers what it misses.
