# SAHO 2027 — Single Directory Components for Drupal Layout Builder

**The Open Record design standard, as a production Drupal theme.** Every SAHO
component is a [Single Directory Component](https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components)
(SDC): one folder holding a schema (`*.component.yml`), a template (`*.twig`)
and its own scoped CSS. Editors place them with **Layout Builder**; the shared
layout system keeps every page **aligned to one grid** — the fix for the
current front page, where nothing lines up.

> Mobile is **more than 50% of SAHO's traffic** (mid-range Android, often on
> metered data). Every component is authored **mobile-first**: single column by
> default, enhancements added at `min-width` breakpoints, self-hosted subsettable
> fonts, square/ruled styling that costs no shadows or images to render.

Open **`PREVIEW.html`** in a browser to see the whole library assembled as a
real, aligned page (it links the exact CSS the theme ships).

---

## Requirements

- **Drupal 10.3+ or 11** (SDC is stable in core from 10.3).
- **Layout Builder** (core, enable it) for page composition.
- **[UI Patterns 2.x](https://www.drupal.org/project/ui_patterns)** *(recommended)* —
  exposes every SDC as a **block** and as a **Layout Builder source**, so editors
  can drop components into regions and map fields to props without code. Core SDC
  alone renders components from Twig (`{% include 'saho:button' %}`); UI Patterns
  is what makes them point-and-click in Layout Builder.

No build step, no npm. CSS is hand-authored against design tokens; JS is a single
progressive-enhancement file using `Drupal.behaviors` + `core/once`.

---

## Install

1. Copy the `saho/` folder to `web/themes/custom/saho`.
2. `drush theme:enable saho` and set it as default (or use it as a base/subtheme).
3. Enable Layout Builder and (recommended) UI Patterns 2.x:
   `drush en layout_builder ui_patterns ui_patterns_layout_builder`.
4. Clear caches: `drush cr`. The components appear under the **SAHO** categories
   in the block/section pickers; the four **SAHO layouts** appear in the layout list.

---

## File structure

```
saho/
  saho.info.yml            Theme definition + regions + global library
  saho.libraries.yml       global-styling (fonts, tokens, base, layout) + behaviours JS
  saho.layouts.yml         Four Layout Builder layouts (the alignment system)
  css/
    saho.fonts.css         @font-face — self-hosted Libre Caslon / Archivo / Plex Mono
    saho.tokens.css        ALL design tokens (colour, type, spacing, effects)
    saho.base.css          Element defaults + shared utilities (.saho-eyebrow, .saho-meta…)
    saho.layouts.css       Container, gutters, vertical rhythm, the 4 layout grids
  js/
    saho.behaviours.js     Citation switch, content-warning reveal, timeline filter,
                           search scope, index-table sort — all progressive enhancement
  fonts/                   Self-hosted webfont binaries (OFL)
  images/  logo.svg        Brand assets
  layouts/                 One Twig template per Layout Builder layout
    standard/  record/  index/  editorial/
  components/              THE SDC LIBRARY (16 components)
    button/ badge/ tag/ section-heading/
    archive-card/ metadata-block/ record-header/ index-table/
    provenance-block/ citation/ image-credit/ content-warning/
    timeline/ related-list/ search-field/
```

---

## The alignment system (why the front page will finally line up)

The current site drifts because blocks set their own widths and gutters. This
theme removes that freedom: **one container, one gutter, one rhythm.**

- **`.saho-page`** — every region/section is capped at `--container-standard`
  (1200px; `--wide` 1440 for index grids, `--prose` 832 for long-form) and uses
  the **same fluid gutter** `--gutter-page` (16→48px). Nothing sets its own margin.
- **Vertical rhythm** — stacked sections are spaced by shared `--space-*` tokens,
  so gaps are identical down the page.
- **Four Layout Builder layouts** (in `saho.layouts.yml`), each mobile-first
  single-column, each snapping content to the shared grid:

  | Layout | Use | Desktop grid |
  |---|---|---|
  | **SAHO — Standard column** | reading pages | one aligned column |
  | **SAHO — Record + rail** | biography, article, event | `1fr` + 288px sticky rail |
  | **SAHO — Index grid** | browse, search, home | 1 → 2 → 3 cols (auto/dense variants) |
  | **SAHO — Editorial feature** | features, campaigns | asymmetric `1.55fr / 1fr` |

Because layouts own the geometry and components never set outer width, editors
can drop any component into any region and the page **stays aligned**.

---

## Mobile-first, by contract

- Base styles target the narrow viewport; grids/rails appear only at
  `min-width: 40rem / 48rem / 64rem`. The record rail stacks under the article on
  mobile; the index table scrolls horizontally like a ledger; the search submit
  goes full width under ~30rem.
- Type scales fluidly with `clamp()` (already mobile-tuned in the tokens).
- Self-hosted, subsettable fonts; **no** decorative gradients, shadows or images
  doing structural work — borders and hairlines do it, which is cheap to paint.
- Respects `prefers-reduced-motion` globally.
- Targets **WCAG 2.2 AA** (AAA on body-text contrast): warm ink on newsprint
  clears AAA; content type is never colour-only (always a label too); full
  keyboard support and visible focus rings.

---

## Component catalogue (16 SDC)

**Core** — `button` · `badge` (content-type) · `tag` (filter/cross-ref) ·
`section-heading` (the ruled page-alignment device).
**Content** — `archive-card` (workhorse index card) · `metadata-block`
(tabular finding-aid) · `record-header` (every page is a catalogue record) ·
`index-table` (the archive as a sortable ledger).
**Provenance (the differentiators)** — `provenance-block` ("How we know this") ·
`citation` ("Cite this entry", Chicago/APA/MLA) · `image-credit` (always-visible
credit + duotone) · `content-warning` (dignified sensitivity gate).
**Navigation** — `timeline` (filterable chronology spine) · `related-list`
(cross-reference connective tissue) · `search-field` (the front door).

### Using a component

In Twig (any template):

```twig
{% include 'saho:archive-card' with {
  type: 'biography',
  title: 'Nelson Rolihlahla Mandela',
  href: node.toUrl,
  dates: '1918–2013',
  excerpt: content.field_summary|render|striptags|trim,
  meta: 'Biography · 12 min read',
  image: content.field_portrait|render,
} only %}
```

With **UI Patterns 2.x** in Layout Builder: add a **Component block**, pick
*Archive card*, and map each node field to the matching prop in the UI — no code.

Each `*.component.yml` documents every prop (type, enum, default) and slot, so
the props show up as labelled, validated fields.

---

## Design tokens

All values live in `css/saho.tokens.css` (generated from the SAHO design
system). Author against the **semantic aliases**, not raw hues:

- Surfaces `--surface-page / -card / -sunk`; text `--text-primary / -secondary / -muted`;
  rules `--border-default / -strong / -faint`.
- Accent `--accent` (oxblood #990000) · warm secondary `--accent-warm` (ochre).
- **Content-type map** `--type-article|biography|place|archive|event|topic` — the
  meaning-bearing hues used by badges, cards, record tabs and swatches.
- Type: `--font-masthead` (Libre Caslon Display), `--font-display/-serif`
  (Libre Caslon Text), `--font-sans` (Archivo — interface + the heavy editorial
  register), `--font-mono` (IBM Plex Mono — provenance & metadata).
- Square by discipline: `--radius-*` are `0` except genuine circles.

---

## Two registers, one system

- **Reference register** (biographies, articles, events, the archive): calm,
  dense, authoritative. Caslon titles, ruled metadata, the record header.
- **Editorial register** (features, campaigns, commemorations): expressive,
  image-led. Heavy condensed **Archivo** headlines (`.saho-editorial-title`),
  oxblood + ochre. Use the **Editorial feature** layout.

Both draw from the same tokens, so the site reads as one institution in two voices.

---

## Notes / honest gaps

- Imagery in the preview uses the bundled silhouette placeholder under the
  archival duotone. Wire real archival media (with credit + source) via
  `image-credit` / `archive-card`.
- `index-table` is shown with static rows; in production feed it from a **View**
  (or a UI Patterns "table" source). Client-side sort is enhancement only — the
  server order is authoritative.
- Source design system, tokens, React reference implementations and UI kit live
  in the parent project; this `saho/` theme is the production translation. Build
  input repos are credited in the project `readme.md`.
```
```
