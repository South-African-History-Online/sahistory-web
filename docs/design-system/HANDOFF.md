# Handoff: SAHO 2027 — "The Open Record" design standard

## Overview
This package is the complete 2027 redesign system for **South African History Online** (sahistory.org.za): the visual language, reusable components, full-screen UI recreations, and wireframe blueprints for every template. It implements the *"open record"* brief — a serious public-institution archive whose **data structure is the design**: every surface is a view onto a typed, sourced, cross-linked record.

The goal of this handoff is to **recreate these designs in the production SAHO Drupal theme** (`webroot/themes/custom/saho`, Bootstrap 5 base + Single-Directory Components), updating the existing `--saho-*` token system and Twig/SDC templates — not to ship the HTML directly.

## About the design files
The files in this bundle (the SAHO design-system project) are **design references created in HTML/CSS/React-via-Babel** — prototypes that show the intended look, structure and behaviour. They are **not** production code to paste in. The React components are deliberately simple, mostly-cosmetic recreations; the real implementation should be **Twig + SDC** following the theme's established patterns (`components/<group>/<name>/`), with styling driven by the existing `src/scss/base/_variables.scss` token layer (updated per the migration table below).

Where a design references an image, it uses a placeholder (`assets/default-portrait.svg`) under a CSS duotone. Production should wire real archival media with the Media library and the existing image-style pipeline.

## Fidelity
This bundle is **mixed fidelity, by design**:

- **High-fidelity** — `design_system/ui_kits/sahistory/` (Home, Biography, Timeline, Search) and all `design_system/guidelines/*` specimen cards. These carry final colours, type, spacing, borders and interactions. **Recreate pixel-accurately** using the token values in this README. Screenshots: `screenshots/uikit/`.
- **Low/medium-fidelity** — `design_system/Wireframes.html` (11 template blueprints: front page, content-type landing, biography/detail, topic/event article, search, timeline, editorial feature, classroom, and three mobile layouts). These are **greyboxes on the real grid** that map each template to its components and Drupal regions. Use them as the **layout + IA plan**; apply the hi-fi tokens for styling. Screenshots: `screenshots/wireframes/`.

---

## The data model (what the design is built around)
The design treats SAHO as a **record store**. Content types and their fields drive every layout:

| Entity (content type) | Accent token | Key fields | Detail template |
|---|---|---|---|
| **Biography** | forest green `#2d5016` | full name, born, died, roles, office, affiliation, portrait, body, sources, related | Sheet 03 |
| **Event** | ink `#1b1c17` | date, place, body, sources, related, theme/era | Sheet 04 |
| **Place** | slate blue `#3a4a64` | location, coordinates, body, sources, related | Sheet 03 (variant) |
| **Article / Topic** | brick `#8b2331` / oxblood | body, chronology, sources, related, in-text links | Sheet 04 |
| **Archive (document)** | ochre `#8a6420` | origin date, holding ref, scan, transcript, sources | Sheet 03 (variant) |

Cross-cutting, on every record: an **accession reference** (e.g. `B-0427`), a **status** (Verified / Revised / New), **provenance** (a sourced "how we know this" + source list), a **citation** (Chicago/APA/MLA), and **typed relations** (entity-reference fields to other records). These are the brand. They are visible, never buried.

---

## Screens / views
References below point at the hi-fi prototypes (`ui_kits/sahistory/*.jsx`) and the wireframe sheets (`Wireframes.html#wfNN`).

### 1. Front page / Catalogue home — `HomeScreen.jsx`, `#wf01`
- **Purpose:** not a marketing splash; the catalogue front door.
- **Layout:** full-width `SearchField`; a ruled `ArchiveStatusBar` (record counts in mono); a 1.55fr/1fr row of the **editorial feature** (Archivo Black on ink) + *This day in history*; a 6-column **browse index** of content types; an `IndexTable` of recently-added records. Container `1200px`.
- **Drupal:** header/footer regions; feature = curated node queue; counts = cached Views aggregate; recent = Views table (ref, type, title, dates, status).

### 2. Content-type landing — `#wf02`
- **Purpose:** one parameterized template for Biographies / Topics / Places / Events / Archive indexes.
- **Layout:** landing header (type tab + title + count + intro); filter bar of `Tag`s; an `IndexTable`↔`ArchiveCard`-grid toggle; facet sidebar + a featured record.
- **Drupal:** a single Views display with a contextual content-type filter; exposed filters via the Facets module; two row formats behind a view toggle.

### 3. Biography record (detail) — `BiographyScreen.jsx`, `#wf03`
- **Purpose:** the workhorse entity view; also covers Place / Event / Archive detail.
- **Layout:** breadcrumb (mono, ends on the accession ref); `RecordHeader` (typed tab + REF + status + Caslon title + ruled key-field strip + actions); then a `1fr / 288px` rail grid — article body (Caslon, in-text cross-links, pull-quote) + `ProvenanceBlock` + `Citation` in the main column; `ImageCredit` portrait, `MetadataBlock` fields, life `Timeline`, and `RelatedList` in the rail.
- **Drupal:** one node template per content type using SDC `record-header`, `metadata-block`, `provenance-block`, `citation`; relations from entity-reference fields; chronology from referenced Event nodes.

### 4. Topic / Event article — `#wf04`
- **Purpose:** long-form reference with strong cross-linking and a sticky chronology rail.
- **Layout:** `RecordHeader` (dateline); `1fr / 288px` with body + inline full-measure `ImageCredit` + `ProvenanceBlock`/`Citation`; sticky rail with `Timeline`, an "On this page" anchor list, and `RelatedList`.

### 5. Search & browse — `SearchScreen.jsx`, `#wf05`
- **Purpose:** fast, forgiving, prominent. A query returns a ruled index, not cards.
- **Layout:** large `SearchField` + scope chips; `240px / 1fr` with a facet checklist + a sortable `IndexTable` (ref, type, record, dates, src) + pagination.
- **Drupal:** Search API + Views; Facets module; fuzzy/typo tolerance configured in Search API.

### 6. Timeline — `TimelineScreen.jsx`, `#wf06`
- **Purpose:** chronology as a first-class navigational surface.
- **Layout:** title + intro; `1fr / 240px` with a filterable `Timeline` (spine + **square** nodes + theme toggles) + era/place `Tag` facets.

### 7. Editorial feature / campaign — `#wf07`
- **Purpose:** the expressive register for commemorations (e.g. Soweto, Sharpeville).
- **Layout:** full-bleed duotone hero with the **Archivo Black** masthead (`.saho-editorial-title`); standfirst + opinionated body; rail with `RelatedList` + `ProvenanceBlock`. Editorial **still shows sources**.
- **Drupal:** a "Feature" content type or Layout Builder layout (hero + flexible sections).

### 8. Classroom / educator landing — `#wf08`
- **Purpose:** grade-aligned doors into the reference material.
- **Layout:** header; a grade/phase `Tag` selector; curriculum-aligned collections as an `ArchiveCard` grid; "how to use" + downloadable lesson packs.
- **Drupal:** CAPS grade/phase taxonomy; collections = curated node queues; packs = media/file fields.

### 9–11. Mobile (home / record / search) — `#wf09`–`#wf11`
- **Purpose:** the majority case (mid-range Android, metered data).
- **Behaviour:** tables collapse to stacked rows; rails move below content; facets become a bottom sheet; hit targets ≥ 44px; status counts scroll-x; provenance/related become collapsible.

---

## Interactions & behaviour
- **Navigation:** hash routing in the prototype stands in for normal Drupal page routing. In-page `data-nav` links and content-type cells link to records.
- **IndexTable:** clickable sortable headers (a mono `↓` marks the active sort); whole-row hover sets the **sunk** background (a selection cue), rows are links.
- **Citation:** Chicago/APA/MLA segmented toggle swaps the mono citation string; a "Copy" affordance.
- **ContentWarning:** difficult imagery/topics sit behind a calm opt-in panel; `defaultRevealed` controls initial state.
- **Timeline / facets:** theme/era/place filters narrow the set in place.
- **Hover:** links darken oxblood → oxblood-deep and thicken their underline (1→2px); cards/records darken the border to ink and drop to the sunk ground — **no lift, no glow, no animation**. An archive is still.
- **Focus:** always-visible `2px` oxblood outline + `3px` oxblood-25 ring on every interactive element.
- **Motion:** 120–260ms standard-eased transitions only; fully disabled under `prefers-reduced-motion`. No parallax, bounce, or decorative loops.

## State management
- **Search/landing:** query string, active scope, facet selections (multi), sort key + direction, page. (Drupal: exposed-filter state in the URL.)
- **Timeline:** active theme/era/place filter.
- **Citation:** selected format. **ContentWarning:** revealed boolean. **IndexTable:** sort key.
- Data is read-only reference content; no auth flows in scope beyond standard Drupal.

---

## Design tokens
Authoritative source: `design_system/tokens/*.css` (shipped via `design_system/styles.css`). Summary:

### Colour
```
Paper (cool newsprint)   --saho-paper #e7e4d8 · raised #f1efe7 · sunk #dad6c7 · manila #d9d2bd
Ink (cool printer's)     --saho-ink #1b1c17 · soft #41433a · muted #6b6c5f · faint #92907f
Rules                    --saho-rule #bdb9a6 · strong #8e8a73 · faint #d3cfbf
Accent (oxblood)         #990000 · deep #6e0000 · bright #b22222
Secondary (ochre)        #b88a2e · deep (text-safe) #8a6420
Content types            article #990000 · biography #2d5016 · place #3a4a64 · archive #8a6420 · event #1b1c17 · topic #8b2331
Semantic                 success #2e6b34 · warning #9a6a00 · danger #b3261e · info #2b5a8c
```
Contrast: ink-on-paper and oxblood-on-paper both clear **WCAG AAA**; muted text clears AA. Colour is never the sole signal (always paired with a label).

### Type
```
Reference (Caslon)   --font-masthead "Libre Caslon Display" (heroes, 400)
                     --font-display/--font-serif "Libre Caslon Text" (titles 700, body 400)
Editorial + UI       --font-sans "Archivo" (variable wght+wdth; .saho-editorial-title = 900, wdth ~75-88%, uppercase)
Apparatus            --font-mono "IBM Plex Mono" 400/500/600 (refs, metadata, citations, dates)
Scale (fluid clamp)  --fs-xs … --fs-5xl ; body --fs-base ≈ 17–19px ; reading measure 68ch
Line height          tight 1.12 · snug 1.25 · normal 1.5 · relaxed 1.62 (body)
```
Fonts are **self-hosted** in `assets/fonts/` (OFL). Do **not** load from a CDN — SAHO self-hosts for digital sovereignty and to subset for metered connections.

### Spacing / layout
```
8px grid   --space-1..10 (4,8,12,16,24,32,48,64,96,128)
Containers prose 42rem · narrow 52rem · standard 75rem · wide 90rem
Rail       --rail-width 18rem · grid gap 32px
```

### Effects (the hard-edged catalogue)
```
Radius     ALL 0 (square) — exception --radius-full 9999px for genuine circles (avatars, status dots)
Borders    --bw-hair 1px (field rules) · --bw-rule 2px (section/record-top) · --bw-accent 3px (content-type edge)
Shadows    near-absent — borders do the structural work; faint --shadow-sm/md only for true overlays
Duotone    --img-duotone (unifies archival imagery); apply via .saho-duotone or ImageCredit duotone
Transitions 120/180/260ms, standard easing; reduced-motion → 0ms
```

### Token migration (production `_variables.scss` → 2027)
The production theme already ships a `--saho-*` token layer. Update these values:

| Production token | Was | Set to (2027) |
|---|---|---|
| `--saho-color-surface` | `#ffffff` | `#e7e4d8` (paper ground) |
| `--saho-color-surface-alt` | `#f7f7f7` | `#dad6c7` (sunk) / `#f1efe7` (raised) |
| `--saho-color-text-primary` | `#1e293b` | `#1b1c17` (cool ink) |
| `--saho-color-text-secondary` | `#475569` | `#41433a` |
| `--saho-color-text-muted` | `#94a3b8` | `#6b6c5f` |
| `--saho-color-border` | `#d9d9d9` | `#bdb9a6` |
| `--saho-color-primary` | `#990000` | `#990000` (unchanged — keep heritage red) |
| `--saho-color-accent` | `#b88a2e` | `#b88a2e` (ochre, unchanged; use `#8a6420` for text) |
| `--saho-radius-md` (cards) | `8px` | `0` |
| `--saho-button-radius-pill` | `25px` | `0` (no pills) |
| Font stack (Inter) | `"Inter", …` | Caslon (serif/headlines) + Archivo (UI) + Plex Mono (meta) |
| Card hover | `translateY(-4px)` + `shadow-xl` | border→ink + sunk bg, **no transform** |

The content-type colour map, the 8px grid and the fluid `clamp()` scale already exist in production and carry over with minor value tweaks.

---

## Assets
- `assets/saho-logo.svg` — SAHO heritage emblem (from the production theme `logo.svg`). Pair with a Caslon wordmark + mono tagline.
- `assets/default-portrait.svg`, `assets/placeholder-card.svg` — placeholders; replace with real Media.
- `assets/fonts/` — self-hosted OFL binaries: Libre Caslon Text (variable) + Display, Archivo (variable), IBM Plex Mono (400/500/600). Subset for the languages in use (incl. diacritics and click-letter spellings).
- **Iconography:** icon-light by discipline. Content-type **squares** + labels are the primary "iconography"; a few Unicode glyphs (`⌕ → ▲`) for functional affordances; **no emoji**. If a richer set is needed, self-host a thin square-cut SVG sprite — do not add a webfont on metered connections.

## Accessibility & performance (first-class requirements)
- **WCAG 2.2 AA floor, AAA on body text.** Full keyboard nav; visible focus; correct semantics; `lang` attributes on multilingual names and terms (eleven official languages; render diacritics/click letters correctly).
- **Performance budget for mid-range Android on metered data:** self-hosted subset fonts; restrained imagery; flat backgrounds; borders not shadows; progressive loading; tables re-layout to cards on mobile (no horizontal scroll). A page that costs a learner half a megabyte they cannot spare is a failed page.

## Voice & tone (for any copy you write)
Direct, plain, dignified, unsentimental. Sentence case; mono UPPERCASE only for metadata/eyebrows. **No exclamation marks, no emoji, no em dashes, no hype.** State what is known and show the source.

---

## Files
```
Project root (this bundle)
  HANDOFF.md                        ← this document
  styles.css                        ← single global entry point (imports tokens/*)
  tokens/                           ← colors, typography, spacing, effects, fonts, base
  assets/                           ← logo, placeholders, self-hosted fonts (OFL)
  _ds_bundle.js                     ← compiled component library (window.SAHODesignSystem_50e062)
  components/                       ← React reference impls + .d.ts props + .prompt.md usage
    record/    (RecordHeader, IndexTable)
    content/   (ArchiveCard, MetadataBlock)
    provenance/(ProvenanceBlock, Citation, ImageCredit, ContentWarning)
    navigation/(Timeline, RelatedList, SearchField)
    core/      (Button, Badge, Tag)
  ui_kits/sahistory/                ← hi-fi screens (index.html + *Screen.jsx + Chrome.jsx)
  guidelines/                       ← foundation specimen cards (Type/Colors/Spacing/Brand)
  Wireframes.html                   ← 11 template blueprints (open in a browser)
  readme.md                         ← the full design-system guide
  SKILL.md                          ← agent-skill entry point
  handoff_screenshots/
    uikit/      01 home · 02 biography · 03 timeline · 04 search
    wireframes/ 01 front · 02 biography · 03 article · 04 search · 05 editorial · 06 mobile
```
Open `ui_kits/sahistory/index.html` and `Wireframes.html` directly in a browser to explore. A developer who wasn't in this conversation can build the production theme from this README plus the token files alone.
