# SAHO Classroom - presentation engine

Issue: #436 (child of epic #433 - Classroom 2.0)

A tiny, dependency-free HTML presentation engine and a pure-PHP renderer that
turns a structured slide schema into a single **self-contained** `.html` deck.
The rendered file inlines all CSS and JS, embeds every image as inline SVG or a
`data:` URI, and makes **zero external requests** - so it opens from `file://`
fully offline, prints cleanly to PDF, and meets the accessibility bar proven by
the prototype (`../prototype/group-areas-act.html`).

This engine is factored out of that prototype and hardened for reuse. It does
**not** modify the prototype.

## Layout

```
presentation/
  schema/
    slide-schema.json     Canonical JSON Schema for a deck (draft 2020-12)
  engine/
    deck.css              Standalone deck stylesheet (inlined into output)
    deck.js               Standalone deck controller (inlined into output)
  render.php              Pure-PHP CLI + library: schema JSON -> self-contained HTML
  examples/
    group-areas-act.json  Worked example deck
  README.md               This file
```

## Rendering a deck

`render.php` needs only PHP 8.1+ on the CLI. No Drupal bootstrap, no Composer
autoloader, no network.

```bash
cd webroot/modules/custom/saho_classroom/presentation

# To stdout (redirect to a file):
php render.php examples/group-areas-act.json > group-areas-act.html

# Or pass an explicit output path:
php render.php examples/group-areas-act.json group-areas-act.html
```

Open the resulting `group-areas-act.html` in any browser, or double-click it
from the file manager - no server required.

### As a library

```php
require_once __DIR__ . '/render.php';

// From a file path:
$html = saho_classroom_render_deck_file('examples/group-areas-act.json');

// From an already-decoded array (for example, built from a Drupal entity):
$html = saho_classroom_render_deck($deckArray);
```

The same routine can run in a CI validation step, a Drush command, or a build
script. When Drupal renders the interactive deck itself (Twig + the entity), it
attaches `engine/deck.css` and `engine/deck.js` as a library and reuses the same
markup contract described below - the schema is the durable asset, the renderer
is swappable.

## The schema

`schema/slide-schema.json` is the canonical contract. It reconciles the earlier
proposal in `docs/classroom/20-html-format.md`:

- Lesson metadata is nested under **`meta`** (`title`, `grade`, `subject`,
  `caps_topic`, `language`, `phase`, `duration_minutes`, `attribution`,
  `sources[]`). These align with the `saho_classroom` fields
  (`field_classroom_grade`, `field_classroom_subject`, `field_caps_topic`).
- Each slide uses **`type`** (layout), **`heading`**, and **`image`**. The
  proposal's `layout`, `title`, and `media` names are still accepted as
  aliases, so existing prototype-shaped JSON keeps validating.

### Slide types

`title`, `content`, `two-column`, `cards`, `quote`, `image`, `timeline`,
`summary`.

### Content blocks

`paragraph`, `list`, `heading`, `card`, `quote`, `timeline`, `qa`, `image`.

Two-column slides can supply an explicit `columns` array (two block lists);
`cards`/`two-column` slides with `card` blocks are auto-wrapped in the responsive
grid.

### Imagery must stay self-contained

`image.data` accepts **inline SVG markup** (starts with `<svg`) or a **`data:`
URI**. Anything else (an `http(s)` URL) is dropped and a warning is written to
stderr, so a rendered deck can never reach out to the network. Use the
`timeline` block to have the renderer generate an accessible inline-SVG timeline
straight from `year`/`label` events.

### Minimal example

```json
{
  "meta": { "title": "The Group Areas Act", "grade": "10", "subject": "History",
            "caps_topic": "Apartheid legislation in the 1950s", "language": "en" },
  "slides": [
    { "type": "title", "heading": "The Group Areas Act",
      "lead": "How one 1950 law divided South Africa's cities.",
      "notes": "Open by asking what learners already know." },
    { "type": "content", "eyebrow": "The law", "heading": "What it did",
      "bullets": ["Divided the map", "Controlled ownership", "Forced removals"] }
  ]
}
```

Inline Markdown is supported in text fields: `**bold**`, `*italic*`, `` `code` ``
and `[label](https://...)` (safe schemes only).

## Controls (rendered deck)

| Action | Keys / controls |
| --- | --- |
| Next slide | Right, Down, Space, PageDown, on-screen arrow, next button |
| Previous slide | Left, Up, PageUp, on-screen arrow, prev button |
| First / last | Home / End |
| Present (fullscreen) | `F` or the Present button |
| Speaker notes | `S` or the Notes button |
| Print / save as PDF | `P` or the Print button |
| Jump to slide | Progress dots (keyboard reachable) |
| Touch | Swipe left / right |

Accessibility: skip link, `role="application"` deck with per-slide
`role="group"`, an `aria-live` region announcing the current slide, focus moved
to the active slide, keyboard-reachable dots, visible focus rings,
`prefers-reduced-motion` support, and a print stylesheet that emits one slide per
page with speaker notes as a handout.

## Self-containment guarantee

A rendered deck contains no `<link>`, no external `<script>`, no web fonts and no
remote image URLs. You can verify:

```bash
grep -Eio '(src|href)="https?://[^"]+"' out.html   # expect no matches
```
