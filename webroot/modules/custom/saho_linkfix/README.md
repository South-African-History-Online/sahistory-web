# SAHO Link Fix (saho_linkfix)

Repairs dead legacy `.htm`/`.html` links left in node body text by the 2011-era
Drupal 7 site, using two guarded, reversible mechanisms:

1. **Precise redirects** for absolute legacy URLs
   (`www.sahistory.org.za/pages/.../x.htm`). A 301 to the exact current node,
   created only when no redirect already claims that source.
2. **In-body rewrites** for relative legacy links (`../../bios/x.htm`) that a
   redirect cannot catch, because relative hrefs resolve against the current
   page URL. Only the exact `href` is changed; surrounding prose is preserved.

## How targets are resolved

`LegacyLinkResolver` builds a legacy-path -> node map from two authoritative
sources the D7 migration left behind:

- `field_old_filename` - each node's own original legacy URL (highest
  confidence: the node *was* that page).
- the `redirect` table - existing curated source -> target pairs.

Resolution is layered: an exact normalised-path match first, then a
collision-free basename match (which recovers relative links whose file name is
unique across the corpus). Ambiguous basenames are never auto-resolved.

## Routing model (important)

Legacy `.htm` handling lives in **Drupal**, not Apache. The previous
`.htaccess.custom` "smart redirect to search" block was removed so that
`.htm` requests fall through to Drupal, where:

1. the `redirect` module serves the **precise** redirect (exact node), then
2. genuinely unmapped `.htm` requests reach
   `LegacyHtmFallbackSubscriber` (kernel EXCEPTION phase), which reproduces the
   old typed-search guess as a last resort.

Physical `.htm` files on disk (domain-verification files) are still served
directly by the one remaining Apache rule. Do **not** reinstate the removed
RewriteRules - they mask the precise redirects.

## Commands (all dry-run by default; `--apply` to write)

```bash
drush saho:linkfix-scan          # scan all bodies -> candidates.json + gaps.csv
drush saho:linkfix-redirects     # create precise redirects (absolute links)
drush saho:linkfix-rewrite       # rewrite relative links in body text
drush saho:linkfix-redirects-rollback   # delete redirects a run created
drush saho:linkfix-rewrite-rollback     # restore bodies a run changed
```

Work artifacts live in `public://saho_linkfix_work/` (resolved per-environment
via Drupal's stream wrapper, so the commands work on local and prod without a
path override). A deny-all `.htaccess` is written into that directory on
creation. Every `--apply` run writes a rollback file there.

## Safety guarantees (asserted by tests)

- Redirect writer is additive: never edits or deletes an existing redirect;
  idempotent; dedupes within a batch.
- Body rewriter only replaces the exact mapped `href`; snapshots the original
  body; creates a new revision; idempotent; revert restores only if untouched.
- 20 kernel tests, including a full HTTP-pipeline routing test
  (`LegacyLinkRoutingTest`) that handles real Request/Response cycles through
  the redirect module and the fallback subscriber.

## Deployment

The module is in exported config (`core.extension.yml`), so `drush cim` keeps it
enabled - it will not be silently disabled by a config import on deploy.

1. Deploy code; the `.htaccess.custom` change re-scaffolds to `webroot/.htaccess`
   via `composer drupal:scaffold` (the live file is gitignored). Run
   `drush cim` (or `drush en saho_linkfix -y`) in the same step so the module is
   enabled the moment the `.htaccess` search-block is removed - otherwise legacy
   `.htm` URLs hard-404 in the gap between deploy and enable.
2. `drush cr`, then dry-run `saho:linkfix-scan`/`-redirects`/`-rewrite` (default
   paths now resolve via `public://`, no override needed).
3. Apply redirects, verify, apply rewrites; archive the rollback files.
4. Confirm a sample legacy URL 301s to the exact node and an unmapped one falls
   back to typed search. Cloudflare caches the 301s.
   `BASE_URL=https://sahistory.org.za bash scripts/linkfix/verify-legacy-urls.sh`
