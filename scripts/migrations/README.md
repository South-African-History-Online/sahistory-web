# SAHO migrations / one-off data scripts

Scripts in this directory are idempotent data migrations or enrichment passes
that are too small / too one-off to live as a Drupal migration plugin.

## Conventions

- Source data goes in `webroot/private/migration_csv/<name>_<date>.csv` (gitignored, sits with existing media migration CSVs)
- Apply scripts go here in `scripts/migrations/<name>.php` (tracked)
- Scripts run via `ddev drush --uri=<site> scr <path>`
- Add a `--dry` flag where possible so a stakeholder can preview the diff

## Current scripts

### `apply_publication_enrichment.php`

Reads `webroot/private/migration_csv/publications_enrichment_2026-05-13.csv` and either updates existing `commerce_product` entities of bundle `publication` (action=update) or creates new ones (action=create).

Source data assembled from a metadata list provided by Ravi/Leander on 2026-05-13 (see issue #139).

```bash
# Dry-run first
ddev drush --uri=https://shop.ddev.site scr scripts/migrations/apply_publication_enrichment.php -- --dry

# Apply
ddev drush --uri=https://shop.ddev.site scr scripts/migrations/apply_publication_enrichment.php
```

Behaviour:
- Empty CSV cells are skipped so prior manual edits in the admin UI are preserved (idempotent).
- `field_publisher` requires the publisher to already exist in the `publishers` / `publisher` taxonomy. Missing terms surface in the error summary; the field is left untouched in that case.
- New products are created with a default `publication` variation, SKU `PUB-<slug>-<id>` and ZAR price when listed.

What it touches:
- `title`, `field_subtitle`, `field_author`, `field_editor`, `field_isbn`, `field_year`, `field_publication_date` (synthesised to `YYYY-01-01T00:00:00`)
- Variation price (`commerce_price`, ZAR)

What it does not touch:
- Cover images / `field_images`
- Category taxonomy (`field_category`)
- Featured flag, language, page count

Follow-ups after apply:
1. Upload cover images for the 5 newly-created publications via admin UI.
2. Categorise the new publications (`field_category`).
3. Resolve the duplicate `Collected Poems` (product 9 vs 10) - one is Mafika Gwala, the other is Alfred Temba Qabula. Decide whether to keep both or merge.
4. Drive the front-page Publications section work in #139 with the enriched data.
