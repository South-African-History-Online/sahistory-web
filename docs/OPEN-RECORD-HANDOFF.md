# Open Record redesign — work handoff

Handoff for the next agent. Terse and operational. Read this, then the epic
issue **#431** and the per-slice specs on **#424–#428**.

## State
- **Branch:** `update/drupal-11.4.0` @ `65d1f2e7`, **11 commits ahead of origin/main** (main already has the Drupal 11.4 core bump via PR #422). **Not pushed. Not PR'd. Not tagged.**
- **Both sites green** (`sahistory-web.ddev.site` + `shop.ddev.site` = 200). DDEV up.
- **Plan:** `~/.claude/plans/declarative-doodling-hammock.md` (approved). **Design source:** `docs/design-system/` (HANDOFF.md, DATA-MODEL.md, Wireframes.html, `ui_kits/`, ported SDC) + newer canonical on claude.ai/design project `50e06254` via the **DesignSync MCP** (`get_file`).
- **Working tree:** shared with other agents (they have grabbed it before). Uncommitted `saho_shop/scss` + stray root files are **other people's** — do not commit them. `webroot/sites/default/settings.ddev.php` line 84 raised to `memory_limit=1024M` (gitignored, local-only — see #430).

## Done (committed, verified via Playwright + a 12-agent adversarial review)
| commit | what |
|---|---|
| `1d9aef9a` | Drupal 11.4 compat: classic front controller (`assets/index.php` + scaffold map) + relaxed SDC schemas |
| `1a83661d` `8ce05700` `c241dd76` | Phase 0: tokens/type/shape (paper/ink, Caslon+Archivo+Plex Mono, square, duotone, squared pills) |
| `217bd125` | Phase 0 audit → `docs/design-system/PHASE0-AUDIT.md` |
| `e9fd66ec` | **S0** alignment: scale tokens (`src/scss/base/_open-record-scale.scss`), `.saho-page` shell + `layout/_saho-layouts.scss`, `saho.layouts.yml` + `layouts/` (4 LB layouts), PurgeCSS safelist |
| `67f73ac0` + `9fd9e0d6` | **S1** shared SDC kit (13 components) + `saho_refs` module (`DisplayRefService` + `/ref/{ref}` route) + verification fixes |
| `2d2faddb` `93b40857` `65d1f2e7` | **S3 pt1/2**: record-header + detention-record on `node--biography`; header-landmine refactor |

The biography record page is live Open Record (record-header + banning/prison state-record doc). Verified on `/people/thabo-marcus-motaung` and `/people/ray-alexander-simons`.

## The queue (GitHub, label `open-record`)
Epic **#431**. Each slice issue has a **full execution spec posted as a comment** (22–28KB, read it before starting):
- **#424 S4 Front page** (node 144647 LB) — *recommended first* (approved sequencing). Reversible snapshot `post_update`; new `saho_frontpage` module.
- **#425 S3 Record family** — finish biography rail (related-tabs/metadata/provenance/citation/image-credit), then roll to article/event/place/archive. record-header + detention already in.
- **#426 S2 Site chrome** — `saho_chrome` module, in-code nav, single `/donate`. Parallelisable with S3.
- **#427 S5 Search + landings** — IndexTable + Facets (not yet enabled) + Solr reindex.
- **#428 S6 Classroom + classroom_clip** — deps S3+S4.
- **#429** container-width sign-off (1200 vs 1440); **#430** prod `memory_limit` + heavy-page perf.

## How to execute (proven pattern)
Slices mutate the **same theme + one DDEV tree** and each needs **live Playwright verification** → do them **one at a time, supervised**. Per slice: (1) read its issue spec; (2) use a background Workflow for the parallelisable bits (research/port isolated new files, adversarial verify) — NOT for shared-file mutation; (3) build `npm run production` (in `webroot/themes/custom/saho`) + **grep `dist/css/main.css`** to confirm PurgeCSS kept your classes/tokens; (4) `ddev drush cr` per site; (5) Playwright desktop **and** mobile; (6) `phpcs --standard=Drupal --extensions=php,module,inc,install,test,theme --warning-severity=0`; (7) commit (incl. `dist/`); (8) close the issue.

**Verify an unwired SDC:** render it via `ddev drush ev` `renderInIsolation` into a static HTML in `webroot/sites/default/files/` that `<link>`s `dist/css/main.css` + the component CSS, then Playwright-screenshot it. (Used for S1.)

## Locked decisions (do not re-litigate)
- **Refs/status:** `saho_refs` `DisplayRefService` is the single source. Ref = bundle-letter+7-digit nid (`archive=R`, not A); **"display reference", never "accession number"**; real-field-preferred (`field_accession_ref`); `/ref/{ref}` 301s (canonical only). Status = timestamp heuristic (content_moderation is OFF).
- **Sequencing:** front-page-first (#424), then S3+S2, then S5, then S6.
- **Chrome:** in-code nav + single `/donate`. **No UI Patterns 2.x** (native SDC + core LB layouts).

## Gotchas (hard-won — will bite you)
1. **SDC name collisions with legacy components.** The design's `card`/`badge`/`record` classes clash with legacy `saho-card`/`saho-badge`. Ported ones were namespaced: `archive-card`→`.saho-acard*`, badge→`saho:badge` w/ `.saho-tbadge*`. **grep for collisions before wiring any new component.**
2. **Stray `<header>` bg — FIXED at source** (`65d1f2e7`): critical CSS in `saho_performance.module` was `header{background:#990000}` (scoped to `header[role="banner"]` now). New `<header>` SDC are safe; don't re-add defensive bg hacks.
3. **Double-H1:** record-header owns the H1 → suppress the global page-title block per bundle (`.node-type--<bundle> .block-page-title-block{display:none}`) **and safelist `node-type--`/`block-page-title` in `vite.config.js`** (runtime classes get purged otherwise).
4. **PurgeCSS strips SDC classes only used via `{{ include() }}`** and any runtime class → safelist + **verify in a production build**, never dev.
5. **LB layouts for node 144647 / per-bundle displays are DB overrides, not config** → reversible **idempotent `post_update` that snapshots first**; `cim` won't carry them; enable new modules on **main only** (shop shares tokens/chrome + `media.type.video`).
6. **Free-text date fields** (`field_dob`/`field_dod`) are **strings** → render mono, never `|date`.
7. **Nullable SDC props:** Drupal 11.4 SDC validation is strict — optional object/number props need `type: [object, 'null']` (hit on hero-banner + detention-record).
8. **Spec-flagged, still open:** CitationService returns harvard/apa/oxford (not Chicago/MLA); `saho-search-field` posts `name="q"` but SAHO search wants `search_api_fulltext`; **Facets module is downloaded but not enabled**; `field_ref_str` carries inline markup (needs `Markup::create()` allow-list, not autoescape).

## Open questions for the human (blockers per slice)
S4: keep/drop of the 8 home blocks; count-label wording (Sources-cited ≈19,206 not 58,902; Topics/Classroom have no clean bundle). S2: footer legal (CC BY-NC-SA, WCAG badge) + Donate collapse. S4/#429: 1200 vs 1440 sign-off. S5: Solr reindex window. S6: clip moderation + rights review.

## Memory
`~/.claude/projects/.../memory/project_saho_open_record_redesign.md` + `[[gotcha-drupal-11-4-upgrade]]`, `[[gotcha-shared-tree-theme-rebuild]]`.
