# Open Record — Phase 0 audit + Phase 1+ backlog

Playwright visual audit of the live local site (Phase 0 shipped) against the
2027 "Open Record" mocks (`handoff_screenshots/`, `ui_kits/`, `Wireframes.html`).
Date: 2026-07-01. Viewport: 1440 desktop (+ 390 mobile spot-checks).

Pages audited: Home (`/`), Biography (`/people/thabo-marcus-motaung`), Search
(`/search`), Classroom (`/classroom`). The long-form detail types
(article/event/place/archive) share the Biography **record template family**;
content-type landings share the Search **card-grid family**.

> Note: detail pages fatalled on a Drupal 11.4 `file_entity` typed-property bug
> (`FileImageResponsiveFormatter::$currentUser`) — the same issue another agent
> is hot-fixing on `hotfix/file-entity-drupal11.4-typed-properties`. Applied that
> fix to vendor locally to complete the audit; the durable fix is that branch.

---

## Verdict

**Phase 0 (tokens) is working sitewide and looks right.** Every page renders on
warm newsprint paper, cool ink text, Caslon titles, Archivo UI, mono metadata,
square corners, hairline borders, oxblood links, no drop shadows. The palette /
type / shape transformation is real and consistent.

**Everything structural is still pending** — the record components, the index
tables, the new header, the alignment system, duotone imagery. That is Phases
1–5, exactly as planned. Below is the ranked backlog.

---

## A. Phase 0 completeness gaps (finish the reskin first — cheap, high impact)

These should already look Open Record but slipped through the token pass:

1. **Rounded pills survive on conditionally-loaded pages.** `_open-record.scss`
   is imported only into `main.css`; the route-specific bundles
   (`landing-pages.css`, `search-results.css`, `article-layout.css`) don't
   include it, so their literal radii persist. Seen: Classroom grade "pills"
   (`History Classroom Grade Eight` …) are still pill-shaped; some
   landing/search chips too. **Fix:** import the override (or a shared squaring
   partial) into each page bundle, or move the squaring into `_design-tokens`
   consumers. Severity: **high** (visible rounding breaks the discipline).
2. **Duotone not applied to imagery.** `--img-duotone` exists but nothing
   consumes it, so search/card thumbnails show full-colour photos (orange/blue
   backgrounds) instead of the unifying archival duotone. **Fix:** apply
   `.saho-duotone` / the filter to card + record imagery. Severity: **medium**.
2b. **Over-boxed cards.** Classroom/landing cards wrap each field (title, date,
   excerpt, read-more) in its own hairline box — too many rules. DS ArchiveCard
   is one frame + a content-type edge. Severity: **medium**.

---

## B. Global chrome (every page) — Phase 1

3. **Header / nav is still the legacy bar.** Current: white-ish bar, red→gold
   gradient underline, nav = Politics & Society · Africa · Art & Culture ·
   Biographies · Classroom · Places · Timelines · Archives · About Us, kebab +
   Donate. Mock: SAHO wordmark + mono tagline `THE OPEN RECORD · EST. 2000`,
   slim nav (Biographies · Timeline · Topics · Places · Archive · Classroom),
   single search glyph; **no gradient bar** (thin oxblood/ochre rule only).
   Rebuild `components/page-navigation` + `_header.scss`. Severity: **high**.
4. **Footer** is close (paper, square, links, Donate/Champion/PayPal/Snapscan,
   social) — light polish only. Severity: **low**.
5. **Alignment system missing.** Blocks still set their own widths/gutters
   (the "nothing lines up" problem the README calls out). Introduce the
   `.saho-page` container + shared gutter/rhythm + the 4 LB layouts. Severity:
   **high** (prerequisite for the front page + all record pages).

---

## C. Front page (`/`) — Phase 1 · ref `HomeScreen` / `wf01`

6. Missing the catalogue-home structure: full-width **SearchField**, the mono
   **ArchiveStatusBar** (record counts), the **6-col browse index**, the
   **recently-added IndexTable**. Current home is the legacy TDIH + Most-Read +
   Featured-biographies/places + Publications stack (now Open-Record-skinned but
   not restructured). Add the **"From the classroom"** clip strip (Phase 1b).
   Severity: **high**.

---

## D. Record / detail family (biography · article · event · place · archive) — Phase 2 · ref `wf03`/`wf04`

Biography is the north star; the others share the template. Gaps vs mock:

7. **No RecordHeader.** Missing the folder tab (`BIOGRAPHY`), the accession
   **REF** (`B-0427`), and the **status** (`● VERIFIED`). Breadcrumb should end
   on the accession ref. This is the signature "record" device. Severity: **high**.
8. **No mono key-field strip.** Mock: a ruled horizontal strip BORN · DIED ·
   LIVED · SOURCES · UPDATED (mono labels). Live: a vertical "Personal
   Information" card with Born/Died only — no LIVED/SOURCES count, not ruled/mono.
   Severity: **high**.
9. **Thin, underused rail.** Live rail = portrait + References. Mock rail =
   duotone portrait + **ImageCredit** + vital-record **MetadataBlock** + life
   **Timeline** + **RelatedList**. Severity: **high**.
10. **No ProvenanceBlock.** "How we know this" framed statement is absent
    (References exist as a start). Add `provenance-block` + `citation`
    (Chicago/APA/MLA toggle). Severity: **medium**.
11. **Related content pattern.** Bottom "Related Content & Topics / Popular
    Content" is a big card grid; DS puts cross-refs in a rail **RelatedList**.
    Severity: **medium**.
12. **Record actions.** "Cite This Page" / "Share" → DS "Cite this record"
    (oxblood filled) + "Download sources". Severity: **low**.
13. Body is Caslon (good); enforce the 68ch measure + pull-quote + in-text
    cross-link styling. Severity: **low**.

---

## E. Search + content landings — Phase 3 · ref `wf05` / Phase 4 · ref `wf02`

14. **Results are a card grid, not a ruled IndexTable.** Core DS rule: "a query
    returns a ruled index, not cards." Build the sortable IndexTable
    (REF · TYPE · RECORD · DATES · SRC) with content-type square swatches.
    Severity: **high**.
15. **No prominent search field / scope chips on results.** Mock: big
    "Search the record" field + All/People/Events/Places/Archive/Topics chips.
    Live: a small "Fulltext search" input in the sidebar. Severity: **high**.
16. **Facets are minimal.** Only a "Content type" `- Any -` dropdown; mock has a
    faceted REFINE checklist with counts (Facets module). Severity: **medium**.
17. **No result count / sort control** ("RESULTS 1–7 OF 1,284 · Sort"). Severity:
    **medium**.
18. Landings (Biographies/Places/… lists) share the card-grid pattern → wf02
    parameterized display with an IndexTable↔ArchiveCard toggle + facets.
    Severity: **medium**.

---

## F. Classroom — Phase 1/4 · ref `wf08`

19. Currently grade-tagged **articles** + a grade sidebar (rounded pills, see A1).
    Mock: grade/phase selector + curriculum-aligned **collections** + lesson
    packs + "how to use". Add the native **`classroom_clip`** short-form content
    (Phase 1b) and rebuild the hub. Severity: **high** (ties to the Phoenix
    social-content goal).

---

## Suggested execution order

1. **Finish Phase 0** (A1–A2b: square the page bundles, apply duotone, de-box
   cards) — small, sitewide, immediate.
2. **Phase 1**: alignment system (B5) → header/nav (B3) → front page (C6) →
   `classroom_clip` + Classroom hub (F19).
3. **Phase 2**: record template family (D7–D13) via the SDC in
   `docs/design-system/saho/components/`.
4. **Phase 3/4**: search IndexTable + facets (E14–E18).
5. **Phase 5**: editorial/campaign register.

Accelerator: `docs/design-system/saho/` already contains production-ready SDC
(`record-header`, `index-table`, `metadata-block`, `provenance-block`,
`citation`, `archive-card`, `search-field`, `timeline`, `related-list` …) + the
4 Layout Builder layouts to port into the Vite/SCSS theme.
