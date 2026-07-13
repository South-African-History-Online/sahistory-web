# SAHO Design System, "The Open Record" (2027 Standard)

A design system for **South African History Online** ([sahistory.org.za](https://www.sahistory.org.za)), the largest independent, non-partisan history archive in South Africa. An encyclopedia of South African and broader African history, a classroom resource, and an instrument of public history and activism. **Anti-paywall by principle:** knowledge here is free, sourced, and accessible.

This system implements the **2027 redesign brief** ("the open record"), which deliberately moves SAHO's interface away from the generic AI/SaaS house style toward the authority of a serious public institution, closer to the Smithsonian, Europeana, NYPL Digital Collections and Wikipedia-at-its-most-credible than to a product looking for a market.

> **North star concept, the open record.** SAHO is a record of how a country was made, unmade, and remade, kept in the open and free to read. Two ideas drive every decision: **provenance is visible** (sources, citations and "how we know this" are part of the visual language, not buried in footers) and **the archive comes first** (search, browse, timeline and biography lead; self-description is secondary).

> **Design approach, "The Record."** The system is built from SAHO's actual data structure, not decorated around it. The archive is a store of typed entities (biography, event, place, article, archive document) with fields (dates, roles, sources), accession references, and typed cross-links between records. So every surface is a *view onto structured data*: a catalogue record, a ruled field table, an index you can query. The aesthetic follows from that, square corners, ruled hairlines, mono accession numbers, borders instead of shadows, the finding-aid and the ledger rather than the magazine and the app.

---

## Sources used to build this system

- **GitHub, `South-African-History-Online/sahistory-web`** (`docs/` subtree), the production Drupal theme's design documentation. Read in full: `COLOR-SYSTEM.md`, `TYPOGRAPHY-SYSTEM.md`, `SPACING-SYSTEM.md`, `DESIGN-TOKENS.md`, `COMPONENT-PATTERNS.md`. Explore it here: <https://github.com/South-African-History-Online/sahistory-web/tree/main/docs>
  - The heritage colour anchors (Deep Heritage Red `#990000`, Muted Gold, Slate Blue, Forest Green, Faded Brick) and the content-type colour mapping are lifted directly from the production `COLOR-SYSTEM.md`.
  - The 8px spacing grid and fluid `clamp()` type scale follow the production `SPACING-SYSTEM.md` / `TYPOGRAPHY-SYSTEM.md`.
- **Logo**, `assets/saho-logo.svg`, the SAHO heritage emblem, imported from the same repo (`webroot/themes/custom/saho/logo.svg`).
- **Fonts**, self-hosted, libre, imported from the official upstreams for digital sovereignty:
  - **Libre Caslon Text** (variable) + **Libre Caslon Display**, `google/fonts` (OFL)
  - **Archivo** (variable, width axis), `Omnibus-Type/Archivo` (OFL)
  - **IBM Plex Mono**, `IBM/plex` (OFL)
- Related SAHO repositories worth exploring for deeper context: the org's [`franco-frescura`](https://github.com/South-African-History-Online/franco-frescura) digital archive and [`saho-ai-project`](https://github.com/South-African-History-Online/saho-ai-project). Browse the GitHub org for more: <https://github.com/South-African-History-Online>

> **What changed from production to 2027.** Production SAHO ships **Inter** as its primary face. The 2027 brief forbids the default system/geometric sans and the generative house style that converges on warm "wonky" editorial serifs. This system instead grounds itself in the actual South African documentary record: **Libre Caslon** (the typeface lineage of gazettes, declarations and official printing) for the reference register, and **Archivo**, a sturdy grotesque from a global-south foundry, for the editorial register (the visual register of Drum magazine, Medu struggle posters and the liberation press). The palette is reframed as **warm ink on warm paper** (archival, not clinical white). The heritage hues and accessibility discipline are retained.

---

## Content fundamentals

How SAHO writes. The voice is **direct, plain, dignified and unsentimental.** SAHO does not market itself and does not hedge: it states what is known, shows where it knows it from, and respects the reader as a serious person.

- **Person:** mostly impersonal/third-person and declarative ("Police opened fire on a crowd protesting pass laws, killing 69 people."). Addresses the reader as "you" only in functional UI ("Search people, events, places, dates…"). Never "we"-as-brand cheerleading.
- **Tone:** factual, sourced, restrained. Every strong claim is attributable. Difficult history is handled with gravity, never sensationalism.
- **Casing:** Sentence case for headings and UI. Mono **UPPERCASE with letter-spacing** is reserved for metadata, eyebrows and provenance labels (`BIOGRAPHY · 14 SOURCES`).
- **No exclamation marks. No emoji. No growth-hacking nudges. No false enthusiasm.** Interface copy matches the editorial voice.
- **Dates & names:** rendered with dignity and correctness, full diacritics and click-letter spellings; date spans in mono (`1918–2013`). South Africa has eleven official languages; type and layout must render names correctly (`lang` attributes on multilingual content).
- **Examples of voice:**
  - Eyebrow: `CURRENT FEATURE · COMMEMORATION`
  - Provenance: "This biography is compiled from the Nelson Mandela Foundation archive… and cross-checked against the Truth & Reconciliation Commission report (1998)."
  - CTA: "Read the feature" / "View the chronology", verbs, no hype.

---

## Visual foundations

**The governing idea is a finding aid / well-set reference book, not a magazine or an app.** Density is treated as a feature handled well, not a problem hidden behind whitespace.

### Colour
- **Material palette.** Cool aged **paper** grounds (`--saho-paper #e7e4d8` newsprint oat, records `--saho-paper-raised #f1efe7`, wells `--saho-paper-sunk #dad6c7`, a `--saho-manila` folder-tab tone) and cool printer's-**ink** text (`--saho-ink #1b1c17`, grey-leaning soft/muted). Not clinical white, and deliberately cooler/greyer than a warm cream, so it reads as archival newsprint, not a soft lifestyle background.
- **One emphasis accent:** **oxblood** (`#990000`, AAA on paper) for links, emphasis and gravity in struggle-history contexts; `oxblood-deep #6e0000` for pressed/hover. Used **sparingly**.
- **One warm secondary:** **ochre** (`#b88a2e`; text-safe `#8a6420`) for editorial warmth and section differentiation.
- **Content-type hues (meaning-bearing):** Article = oxblood, Biography = forest green, Place = slate blue, Archive = ochre, Event = ink, Topic = brick. Colour is **never the sole carrier of meaning**, type is always also labelled.
- The SA flag palette is deliberately avoided, it reads as state branding, which SAHO is not. High contrast throughout (WCAG 2.2 AA floor, AAA on body text), which also serves low-bandwidth clarity.

### Type
Type carries the authority, and it maps onto SAHO's two registers rather than being decoration.
- **Libre Caslon** (libre), the reference register's voice: headlines, article titles and long-form body. Caslon is the typeface lineage of the printed official record, declarations, gazettes and the documentary tradition the archive lives in. **Libre Caslon Display** sets the largest mastheads at regular weight; **Libre Caslon Text** carries weighted titles and long-form reading.
- **Archivo** (variable grotesque, global-south foundry), the editorial register and all interface chrome. Set heavy and tight (often uppercase) it evokes Drum mastheads and struggle-poster lettering; at text weights it is a calm, neutral UI face with strong multilingual coverage. Its width axis drives gazette-style compression.
- **IBM Plex Mono**, the **scholarly apparatus** and the typed record (card catalogues, TRC transcripts): tabular metadata, provenance labels, citations, dates. This is a deliberate differentiator, the apparatus is the brand.
- Fluid `clamp()` scale; generous reading **measure (68ch)**; long-form readability is the single most important typographic outcome.

### Layout
- **The record is the unit.** Every page is a view onto a structured archive entity. The composition is a catalogue/finding-aid: a typed `RecordHeader` (folder tab + accession reference + ruled key fields), ruled field tables, and the archive surfaced as a sortable `IndexTable` (rows are records, columns are fields). Browse and search are queries that return index tables, not magazine grids.
- Editorial, grid-based, **asymmetric where it earns it**. Print-reference devices throughout: **hairline rules**, clear column structure, visible metadata blocks, accession numbers in mono.
- Recurring structural motifs: the **timeline** (chronology spine with dot markers) and the **index** (cross-reference / "related" lists). A right-hand **rail** (`--rail-width 18rem`) carries metadata, chronology and cross-refs alongside long-form text.
- Containers: prose `42rem`, standard `75rem`, wide `90rem`. 8px baseline grid; 32px standard grid gap.

### Imagery
- Archival photography is central and mixed in source/quality. A unifying **warm duotone** (`--img-duotone`, applied via `.saho-duotone` or `ImageCredit duotone`) makes disparate images sit together.
- **Every image carries a rigorous, visible credit + provenance line** (mono). Struggle/apartheid-era imagery is handled with restraint, no sensational cropping, no decorative use of trauma. **Dignity is the governing word.** Difficult content sits behind a calm `ContentWarning` opt-in.
- Image vibe: warm, sepia-leaning monochrome (duotone maps darks → ink, lights → paper).

### Backgrounds, borders, cards, shadows, radii
### Backgrounds, borders, cards, shadows, radii
- **Backgrounds:** flat cool newsprint paper. **No gradients as decoration** (the only gradient is the thin oxblood/ochre accent rule in the header/footer). No glassmorphism, no blur, no texture images.
- **Corners are square.** Every radius token is `0` (cards, buttons, inputs, badges, panels). The one exception is `--radius-full`, reserved for genuine circles: avatars and status dots. Content-type markers and timeline nodes are **squares**, not dots, reinforcing the catalogue/ledger language. This is a deliberate reversal of the earlier rounded draft.
- **Borders, not shadows, do the structural work.** The catalogue hairline is the core device: `--bw-hair (1px)` field rules, `--bw-rule (2px)` section/record-top rules, `--bw-accent (3px)` content-type edges. Shadows are near-absent (`--shadow-xs` is `none`); a faint `--shadow-sm/md` exists only for true overlays (modals, popovers).
- **Cards/records:** cool paper-raised surface, a `1px` hairline frame, a content-type accent on the top or left edge, square corners. Hover **selects** the record (border darkens to ink, ground drops to the sunk tone), it does **not** lift, glow, or animate, an archive is still.
- **Tables are first-class.** Ruled field tables (`MetadataBlock`), the record header (`RecordHeader`) and the archive index (`IndexTable`) are the dominant compositional units, mono labels, sunk header rows, hairline-ruled rows.

### Motion & states
- **Calm and purposeful.** Subtle fades and short (120–260ms) standard-eased transitions. No bounce, no parallax, no decorative loops. Motion aids orientation, not entertainment; fully disabled under `prefers-reduced-motion`.
- **Hover:** links darken oxblood to oxblood-deep and thicken their underline; cards/records darken their border to ink and drop to the sunk ground (a selection, not a lift); the title shifts to `link-hover`.
- **Press/active:** oxblood-deep fills.
- **Focus:** always-visible `2px` oxblood outline + `3px` oxblood-25 ring. Full keyboard navigation and correct semantic structure are first-class.
- **Transparency/blur:** essentially unused, opacity only for on-image badges (`~92%`) and the duotone. No backdrop blur.

---

## Iconography

SAHO is **icon-light by discipline**, a reference institution, not an app dashboard. The system favours **type, rules and colour dots** over a heavy icon set:

- **Content-type dots** (a coloured `--radius-full` dot beside a labelled type) are the primary "iconography", they encode meaning while keeping colour non-load-bearing.
- **Unicode glyphs** are used sparingly for the few functional affordances: search (`⌕`), arrows/chevrons (`→`), the sensitivity marker (`▲`). These avoid shipping an icon font on metered connections.
- **No emoji**, ever (the voice forbids it). No decorative iconography.
- **Logo:** `assets/saho-logo.svg`, the SAHO heritage emblem (red roundel). Works on paper and on ink; pair with the Caslon wordmark + mono tagline lockup (see `guidelines/brand-logo.html`).
- **If a richer icon set becomes necessary** for production (e.g. share, download, print actions), the recommendation is a **thin-stroke, square-cut line set** (e.g. Lucide / Phosphor light) self-hosted as an SVG sprite, sized ≥20px, never filled/playful. Flag any such addition, none is bundled here, to keep page weight low for mid-range Android on metered data.

Assets present in `assets/`: `saho-logo.svg`, `default-portrait.svg` (portrait placeholder), `placeholder-card.svg`, and the self-hosted font binaries in `assets/fonts/`.

---

## Accessibility & performance (as design, not cleanup)
- **WCAG 2.2 AA floor, AAA on body-text contrast.** Warm ink on warm paper clears AAA; oxblood on paper is AAA for text.
- Colour is never the only signal; full keyboard nav; visible focus; semantic structure; `lang` attributes for multilingual names.
- **Performance budget for mid-range Android on metered data:** self-hosted, subsettable fonts; restrained imagery; flat backgrounds; no heavy effects. A page that costs a learner half a megabyte they cannot spare is a failed page.

---

## Index / manifest

**Root**
- `styles.css`, the single entry point consumers link (imports-only).
- `tokens/`, `fonts.css` (@font-face), `colors.css`, `typography.css`, `spacing.css`, `effects.css`, `base.css`.
- `assets/`, logo, placeholders, and self-hosted font binaries (`assets/fonts/`).
- `SKILL.md`, Agent-Skills-compatible entry point.

**Foundation specimen cards** (`guidelines/`, shown in the Design System tab)
- Type: Libre Caslon · Two registers · Long-form & interface · Provenance (Plex Mono) · Type scale
- Colors: Paper & ink · Oxblood & ochre · Content-type hues · Rules & feedback
- Spacing: Baseline scale · Measure & the rail
- Brand: Catalogue structure · Logo · Imagery & dignity · The open record

**Components** (`components/`, React primitives, `window.SAHODesignSystem_…`)
- `record/`, **RecordHeader** (catalogue record header with accession ref), **IndexTable** (the archive as a ruled, sortable index), *the data-structure surfaces*
- `core/`, **Button**, **Badge** (content-type), **Tag** (filter/cross-ref)
- `content/`, **ArchiveCard** (workhorse index card), **MetadataBlock** (tabular finding-aid block)
- `provenance/`, **ProvenanceBlock** ("How we know this"), **Citation** ("Cite this entry"), **ImageCredit**, **ContentWarning**, *the differentiators*
- `navigation/`, **Timeline** (filterable chronology), **RelatedList** (cross-reference), **SearchField** (front-door search)

**UI kit** (`ui_kits/sahistory/`), interactive recreation of sahistory.org.za: Home, Biography (north star), Timeline, Search, plus shared chrome.

**Starting points**, Button, ProvenanceBlock, MetadataBlock, ArchiveCard, Timeline, SearchField, RecordHeader and IndexTable are tagged as seeds for consuming projects.

---

**Status:** 2027 "Open Record" standard · v1.0 · Reference + editorial registers, one system.
