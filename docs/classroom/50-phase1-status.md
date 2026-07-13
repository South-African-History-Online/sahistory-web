# Classroom 2.0 - Phase 1 status: schema -> HTML pipeline assembled & verified

Epic: #433. Phase 0 delivered the POC deck, the slide-schema proposal
(`docs/classroom/20-html-format.md`), the content model in `config/sync`
(5 content types incl. `presentation` + 50-term `caps_topic` taxonomy, 0 nodes),
and the `src/` topic-spine sketches. Phase 1 wires the pieces into a working,
Drupal-free renderer and produces the first real pilot deck.

Status: **PIPELINE WORKING AND VERIFIED. Ready for SME content review.**
The Drupal module remains **disabled** - enabling is a gated, human-go-ahead step
(checklist at the end).

## What was built (Phase 1)

Pure-PHP presentation engine + pilot content, all additive new files. The Phase-0
prototype (`prototype/group-areas-act.html`) was not touched.

- `presentation/render.php` - pure PHP renderer (no Drupal bootstrap). CLI +
  library. HTML-escapes all text, applies a safe inline-Markdown subset, builds
  accessible inline-SVG timelines, and **drops any external-URL image** so output
  is guaranteed self-contained. Inlines `deck.css` + `deck.js`.
- `presentation/engine/deck.css` - standalone deck stylesheet (SAHO tokens,
  responsive `clamp()` type, print/PDF one-slide-per-page, reduced-motion, focus).
- `presentation/engine/deck.js` - standalone controller (keyboard/arrow nav,
  fullscreen present mode, speaker-notes toggle, print, touch/swipe, aria-live).
- `presentation/schema/slide-schema.json` - canonical JSON Schema (draft 2020-12).
- `presentation/examples/group-areas-act.json`, `presentation/README.md`.
- `content/pilot/grade9-turning-points.slides.json` - the real pilot deck source:
  14 slides, schema_version 1.0, Grade 9 CAPS topic "Turning points in South
  African history: 1948 and the 1950s", 20 SAHO sources + provenance.
- `content/pilot/grade9-turning-points.sources.md` - provenance + claim-to-source
  map + SME-flagged uncertainties.

The Drupal render path (SDC `saho_classroom:presentation_deck`, field formatter,
`TopicSpineBuilderImpl`, `saho_classroom.services.yml`, kernel test) also exists
but is inert until the module is enabled.

## Exact render command

Run from the module root
(`webroot/modules/custom/saho_classroom/`), host `php` (PHP 8.3):

```bash
php presentation/render.php \
  content/pilot/grade9-turning-points.slides.json \
  > content/pilot/grade9-turning-points.html
```

Render exit code 0, no stderr warnings (no external images were dropped).

## Verification results

Rendered deck: **`webroot/modules/custom/saho_classroom/content/pilot/grade9-turning-points.html`**

| Check | Result |
|---|---|
| Single self-contained file | YES |
| File size | 49,459 bytes (~48 KB) |
| Slide count in JSON | 14 |
| Slide count in rendered HTML (`.slide`) | 14 - matches |
| Inline `<style>` blocks | 1 (deck.css inlined) |
| Inline `<script>` blocks | 1 (deck.js inlined) |
| Inline `<svg>` (timelines/media) | 12 |
| External stylesheets (`<link rel=stylesheet>`) | 0 |
| External `<script src>` | 0 |
| Auto-fetch tags (link/img/script/iframe/audio/video/source) with external URL | 0 |
| `src`/`href` = `http(s)://` asset | 0 |
| Protocol-relative `//cdn` refs | 0 |
| External asset URLs (`.js/.css/.png/.jpg/.svg/.woff...`) | 0 |
| CSS `url()` fetches | 0 (only mention is inside a CSS comment) |
| HTML structure | valid: `<!doctype html>`, single `<html>/<head>`; DOMDocument parse OK, 0 fatal errors (137 non-fatal warnings are libxml not recognising HTML5/ARIA elements - expected) |

The only `http(s)://` strings in the file are: (a) 9x the SVG `xmlns`
namespace `http://www.w3.org/2000/svg` (a declaration, never fetched), and
(b) 20 SAHO source-citation URLs rendered as **plain text** (not in any
fetchable attribute). **Net external requests when opening the file: 0.**

No engine/renderer fixes were needed - the deck rendered self-contained on the
first pass.

## Ready for SME review

The pilot deck is ready for subject-matter-expert content review:

- Deck: `content/pilot/grade9-turning-points.html` (open directly in any browser,
  works fully offline).
- Source + provenance: `content/pilot/grade9-turning-points.sources.md`, including
  the AI-drafted banner and the **[SME]**-flagged uncertain claims (1948 seat/vote
  mechanics, Verwoerd quotation, Defiance/Sophiatown/march numbers, Act numbers,
  reading level, sensitive terminology).
- Note surfaced in Phase 0 pilot-content: existing node nid 65089
  ("Grade 9 - Term 4: Turning points...") covers the *next* CAPS topic
  (Sharpeville/Soweto/Mandela), not 1948-1950s; the deck draws on the distinct
  1948-1950s pages instead. Documented in the sources file.

SME review is a content-accuracy pass only; it does not require enabling the module.

## Gated next step - enable in Drupal (needs explicit human go-ahead)

NOT done in Phase 1. The module is disabled and no config/DB was mutated.
When a human approves, the enablement sequence is:

1. **Enable the module**
   `ddev drush en saho_classroom -y`
2. **Resolve the two flagged conflicts first** (see build summaries):
   - Pick ONE topic-spine class - the design-sketch `src/TopicSpineBuilder.php`
     stub OR `src/TopicSpineBuilderImpl.php` - and drop the other; only one may
     own the `saho_classroom.topic_spine` service.
   - If another agent also added a `saho_classroom.services.yml`, merge the two
     `services:` maps (the two Impl entries are additive).
3. **Import config**
   `ddev drush cim -y` (brings in the `presentation` type + `caps_topic` taxonomy;
   currently 0 nodes).
4. **Add a slide-schema field to the `presentation` type.** The bundle currently
   has `body` (text_with_summary) + `field_file_upload` and no dedicated deck
   field. Either assign the `PresentationDeckFormatter` to `body`, or add a
   `field_deck_schema` (long text) and assign the formatter to it. Export:
   `ddev drush cex -y`.
5. **Create a `presentation` node**, paste the contents of
   `content/pilot/grade9-turning-points.slides.json` into the slide-schema field,
   set `field_caps_topic` = tid 35821, `field_classroom_grade` = Grade 9
   (tid 35786), `field_classroom_subject` = History (tid 35791).
6. **Verify in-browser** via Playwright (per project convention: screenshot +
   getComputedStyle; DDEV serves the main checkout).

### Security gate for step 4/5
The deck's inline-SVG media (`media.data`, type `svg`) is emitted with `|raw` in
both the Twig SDC and the field formatter. Before any non-trusted user can store
deck JSON, SVG payloads MUST be sanitised upstream. For the SME-reviewed pilot
(trusted authoring) this is acceptable; it becomes mandatory before opening
authoring more widely.

## Rules honoured
Additive only. Read-only `php` CLI used to render + verify. No git/composer/drush
mutation; module not enabled; no config imported.
