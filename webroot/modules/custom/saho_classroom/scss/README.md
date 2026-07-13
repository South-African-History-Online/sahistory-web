# Classroom presentations browse (#444) - theme drop-in guide

This directory holds the **presentation BROWSE** front-end proposal for Classroom
2.0. It renders the view `classroom_presentations` at `/classroom/presentations`:
published `presentation` decks grouped by grade, with BEF Grade + CAPS topic
facets, on the Open Record design language.

Everything here is authored in `saho_classroom` so the module can ship it as a
self-contained proposal **without editing the live theme rework**
(`feature/open-record-wireframes`). Nothing here is imported or compiled on its
own - a theme maintainer drops the three pieces in when ready.

## Pieces

| File (in this module) | Role | Theme destination |
| --- | --- | --- |
| `config/draft/views.view.classroom_presentations.yml` | The browse View (page + facets + grouping + pager) | move body to `config/sync/`, `drush cim` |
| `templates/views/views-view-grouping--classroom-presentations.html.twig` | Grade group section (heading + card grid wrapper) | `themes/custom/saho/templates/views/` |
| `templates/views/views-view-fields--classroom-presentations.html.twig` | One presentation card (row) | `themes/custom/saho/templates/views/` |
| `scss/_classroom-presentations.scss` | Browse + card styling (Open Record) | `themes/custom/saho/src/scss/components/` |

## How to drop it into the theme

1. **View config.** Copy
   `config/draft/views.view.classroom_presentations.yml` to
   `config/sync/views.view.classroom_presentations.yml`, delete the leading `#`
   comment header (keep the `uuid:` line down), then `ddev drush cim -y`. Every
   dependency it names (`field.storage.node.field_classroom_grade`,
   `field.storage.node.field_caps_topic`, the `classroom_grade` / `caps_topic`
   vocabularies, `node.type.presentation`, `better_exposed_filters`) already
   lives in `config/sync`, so it imports cleanly once the presentation bundle is
   installed.

2. **Templates.** Copy both `templates/views/*.html.twig` into
   `themes/custom/saho/templates/views/`. Their names
   (`views-view-grouping--classroom-presentations`,
   `views-view-fields--classroom-presentations`) are Views' own suggestions, so
   they bind to this view only and touch nothing else. `ddev drush cr`.

3. **SCSS.** Copy `scss/_classroom-presentations.scss` to
   `themes/custom/saho/src/scss/components/_classroom-presentations.scss` and add
   one line to the theme's SCSS entrypoint (next to the other component
   imports, e.g. after `_classroom-hub`):

   ```scss
   @import 'components/classroom-presentations';
   ```

   Then rebuild theme CSS (`cd themes/custom/saho && npm run production`) and
   commit `dist/` (no Node on prod). The partial only consumes tokens already
   defined in `base/_variables.scss` and `base/_open-record-scale.scss`
   (`--space-*`, `--bw-hair`, `--bw-accent`, `--type-article`, `--surface-card`,
   `--border-default`, `--text-*`, `--font-mono`, `--font-serif`, `--ls-*`,
   `--fw-semibold`, `--t-fast`, `--accent`, `--saho-paper*`) - it introduces no
   new custom properties.

## Design notes

- **Grouping** is done by the view's default (unformatted) style with a
  `grouping` on `field_classroom_grade`; each grade renders through the grouping
  template as a `<section>` with a mono grade heading and a CSS-grid of cards.
  Rows sort by grade then title so each group is contiguous. Grade terms sort by
  term id; if a strict pedagogical order (Grade 4 -> 12) is wanted, give the
  `classroom_grade` terms an explicit weight and add a term-weight sort.
- **Cards** are text-forward on purpose: the `presentation` bundle has no image
  field, so the CAPS-topic eyebrow (mono) + Caslon title + trimmed body summary
  + subject/type meta row carry the card. The whole card is one hit target via a
  stretched title link (`::after { inset: 0 }`).
- **Facets** reuse the exact BEF-links chip treatment from
  `components/_classroom-hub.scss` so the browse and the `/classroom` hub read as
  one system. Grade and CAPS topic are exposed; status + bundle are locked
  filters (published presentations only).
- **Open Record discipline:** square corners, hairline frame with an oxblood
  accent edge, borders (not shadows) for structure, hover selects (border -> ink,
  ground -> sunk paper) and never lifts.

## Optional: page wrapper markup

The view page renders the grade sections directly. If the theme wants the header
(accent edge title + intro + result count) and the `.saho-presentation-browse`
frame the SCSS targets, wrap the view output in a
`views-view--classroom-presentations.html.twig` in the theme:

```twig
<div class="saho-presentation-browse">
  <header class="saho-presentation-browse__head">
    <h1 class="saho-presentation-browse__title">{{ 'Classroom presentations'|t }}</h1>
    <p class="saho-presentation-browse__intro">{{ 'CAPS-aligned presentation decks, grouped by grade.'|t }}</p>
  </header>
  {% if exposed %}<div class="saho-presentation-browse__filters">{{ exposed }}</div>{% endif %}
  {{ rows }}
  {% if pager %}<div class="saho-presentation-browse__pager">{{ pager }}</div>{% endif %}
  {{ empty }}
</div>
```

Without this wrapper the grade groups + cards still render and stay styled; only
the outer page chrome/padding is skipped.
