# SAHO Classroom - SASL track for presentation decks

Design + spec for issue **#437** (child of the Classroom 2.0 epic **#433**),
scoped to the **41 presentation decks** rendered from slide-schema JSON by the
`presentation_deck` SDC. It defines a signed-video companion for every deck: a
`field_sasl_track` on the `presentation` bundle, a `sasl_video` media bundle,
per-slide signed-video segments with captions/transcripts, the "Watch in SASL"
viewer UX, the accessibility bar, and how a new **`sasl` block in the
slide-schema** flows through **DeckSync**.

This is a scaffold: design and draft config only. No modules are enabled, no
config is imported, no existing schema/theme/JSON is edited.

Read alongside:
- `30-multilingual-sasl.md` (#437) - the corpus-wide language + SASL model. This
  doc is the **deck-specific realisation** of the `field_sasl_track` /
  `sasl_video` idea sketched there (section 2.3), applied to the presentation
  slide schema and its renderer.
- `20-html-format.md` (#436) - the slide-schema contract this extends.
- `presentation/schema/slide-schema.json` - the canonical deck schema.
- `src/DeckSync.php` - the reproducible JSON -> node sync this hooks into.

---

## 0. Principles (read first)

- **SASL is video, never text.** South African Sign Language became SA's 12th
  official language (18th Constitutional Amendment, 2023). A deck's SASL variant
  is a **signed-language video interpretation** plus synchronised captions and a
  text transcript. It is **never** machine-translated text and never an
  auto-generated avatar. There is no `sgn-ZA` text translation of a slide.
- **The written deck is the source of truth; SASL is an additive companion.** A
  deck renders and teaches with no SASL track. Adding a track never rewrites the
  slides, never forks the node, and never blocks the existing English deck.
- **Slide-synchronised, not a single detached video.** The signed video is
  chaptered per slide so that advancing a slide advances the interpretation, and
  jumping to slide 4 seeks the interpreter to slide 4. This is the whole point:
  a Deaf learner navigates the deck, not a separate 20-minute clip.
- **Reproducible like the decks themselves.** The slide-schema JSON stays the
  durable source of truth. The `sasl` block references media by **stable UUID**,
  so `DeckSync` resolves it to the right media entity on every environment,
  exactly as it already resolves taxonomy by name (never by tid).
- **Self-containment is relaxed only for video.** A rendered offline deck inlines
  all CSS/JS/images and makes zero network requests. Signed video is too large
  to inline, so the **in-Drupal** deck streams it from a media entity; the
  **offline** self-contained export degrades gracefully to a "SASL available
  online" note plus the per-slide transcript (which *is* inlinable). Captions
  (WebVTT) are small and are inlined as a `data:` URI in the offline export.

---

## 1. Content model

### 1.1 `field_sasl_track` on the `presentation` bundle

A single-value entity-reference field on the `presentation` node type, pointing
at one `sasl_video` media entity - the full signed interpretation of that deck.

- `field_name`: `field_sasl_track`
- `type`: `entity_reference` -> `media`, target bundle `sasl_video`
- `cardinality`: 1
- `translatable`: false (the media *is* the SASL "translation"; it is not
  re-translated per written language)
- Attaches to `presentation` now; the same field can later extend to
  `worksheet`, `activity`, `quiz`, and `classroom_clip` (the other classroom
  bundles in `30-multilingual-sasl.md`) with no schema change.

Owned by a **new** glue module `saho_classroom_sasl` (saho_ prefix, Drupal 11
standards). It must not live in `saho_classroom` core so the deck engine keeps
working with the field absent.

### 1.2 The `sasl_video` media bundle

One media entity per deck, carrying the interpretation and its accessibility
companions:

| Field | Type | Purpose |
| --- | --- | --- |
| `field_media_sasl_file` | file (video/mp4, webm) **or** `field_media_oembed_sasl` (string, oEmbed) | The signed-language video. Local file for archival decks; oEmbed for large/streamed decks. |
| `field_sasl_captions` | file (`.vtt`) | WebVTT caption track in the deck's own written language (English by default). Also carries the `chapters` cues that map to slide ids (see 2.3). |
| `field_sasl_transcript` | text_long (basic HTML) | Full plain-text transcript, per-slide addressable via `data-slide` anchors. Human-verified; feeds search + the offline fallback. |
| `field_sasl_interpreter` | string | Credit line for the interpreter / studio (rights + accountability). |
| `field_sasl_poster` | image | Still frame / poster for the video (first-frame or interpreter portrait). |
| `field_sasl_review_state` | list_string | `pending`, `interpreter_recorded`, `deaf_review_passed`, `published`. Mirrors the Deaf-community review gate (section 5). |

Rules:
- **One continuous track per deck is the recommended model** (section 2.2); the
  per-slide segmentation is expressed as WebVTT chapter cues + schema
  timecodes, not as 41 separate files. This keeps production and hosting simple.
- Captions and transcript are in the deck's **written** language and are for
  accessibility of the video (Deaf learners who read, search indexing,
  the offline fallback). They are **not** a substitute for the signing and are
  never auto-generated from it.

---

## 2. The slide-schema `sasl` block

The slide schema (`presentation/schema/slide-schema.json`) is extended
**additively** - every existing deck keeps validating because the new
properties are optional and `sasl` is a new key, not a changed one. Do not edit
the shipped schema in this scaffold; the shapes below are the proposed
`schema_version: 1.1` additions, to be applied when #437 lands.

### 2.1 Deck-level: `meta.sasl`

Binds the deck to its signed-video media entity by stable UUID and declares the
delivery mode.

```json
"meta": {
  "title": "The Group Areas Act",
  "grade": "10",
  "subject": "History",
  "sasl": {
    "media": "b7e4c1a0-8f2d-5c3e-9a1b-2d4f6e8a0c11",
    "mode": "continuous",
    "language": "sgn-ZA",
    "interpreter": "SAHO Classroom - SASL studio",
    "duration_seconds": 612
  }
}
```

- `media` - **UUID of the `sasl_video` media entity**. DeckSync resolves this to
  a media id per environment (section 4). Env-independent, exactly like the
  deterministic deck UUID and name-matched taxonomy already in `DeckSync.php`.
- `mode` - `continuous` (one chaptered video, recommended) or `segmented` (one
  clip per slide, referenced from each slide's `sasl.media`).
- `language` - always `sgn-ZA` (SASL). Present for analytics and the language
  negotiation layer; it does **not** imply a text translation.

### 2.2 Per-slide: `slide.sasl` (continuous mode)

Each slide carries a **cue window** into the single deck track plus its own
transcript fragment. No new media per slide - just timecodes and text.

```json
{
  "id": "s3",
  "type": "cards",
  "heading": "What the Group Areas Act actually did",
  "sasl": {
    "start": 84.0,
    "end": 132.5,
    "transcript": "The Act did four things. First, it divided the map ...",
    "captions_from": "chapter"
  }
}
```

- `start` / `end` - seconds into `meta.sasl.media`. Advancing to slide `s3`
  seeks the player to `84.0`; the segment auto-pauses (or loops, see UX) at
  `132.5`. These map 1:1 to WebVTT `chapter` cues named by slide id.
- `transcript` - the signed content of *this slide* in the written language;
  rendered in the transcript panel and inlined into the offline export.
- `captions_from` - `chapter` reuses the deck WebVTT filtered to this window;
  `inline` allows a per-slide `captions` data-URI override.

### 2.3 Per-slide: `slide.sasl` (segmented mode)

For decks assembled from discrete clips (e.g. reused interpreter takes), each
slide references its own media by UUID:

```json
{
  "id": "s3",
  "sasl": {
    "media": "c9a1...-slide-3-clip-uuid",
    "transcript": "The Act did four things ...",
    "captions": "data:text/vtt;base64,V0VCVl..."
  }
}
```

DeckSync collects every per-slide `sasl.media` UUID, resolves each to a media
id, and stores the resolved map on the node (section 4) so the renderer never
performs a lookup at display time.

### 2.4 Schema draft (additive `$defs`)

Proposed additions to `slide-schema.json` (`schema_version` bumped to `1.1`),
shown here for review only:

```jsonc
// meta.properties.sasl
"sasl": {
  "type": "object",
  "description": "Deck-level SASL signed-video binding. Video, not text.",
  "additionalProperties": false,
  "required": ["media"],
  "properties": {
    "media": { "type": "string", "description": "UUID of the sasl_video media entity; resolved by DeckSync." },
    "mode": { "type": "string", "enum": ["continuous", "segmented"], "default": "continuous" },
    "language": { "type": "string", "const": "sgn-ZA" },
    "interpreter": { "type": "string" },
    "duration_seconds": { "type": "number", "minimum": 0 }
  }
},

// $defs.slide.properties.sasl
"sasl": {
  "type": "object",
  "description": "Per-slide signed-video segment. Cue window (continuous) or clip ref (segmented).",
  "additionalProperties": false,
  "properties": {
    "media": { "type": "string", "description": "UUID of a per-slide clip (segmented mode only)." },
    "start": { "type": "number", "minimum": 0, "description": "Seconds into meta.sasl.media (continuous mode)." },
    "end": { "type": "number", "minimum": 0 },
    "transcript": { "type": "string", "description": "Written-language transcript of this slide's signing." },
    "captions": { "type": "string", "description": "Optional inline WebVTT data: URI." },
    "captions_from": { "type": "string", "enum": ["chapter", "inline"], "default": "chapter" }
  }
}
```

Both are optional, so the 41 existing decks validate unchanged.

---

## 3. Viewer UX - "Watch in SASL"

### 3.1 The affordance beside the deck (Drupal chrome, open-record tokens)

On the presentation node page, beside the rendered `presentation_deck`, ship a
**"Watch in SASL"** control. It appears **only when `field_sasl_track` is
populated** (never a dead affordance), matching the resource-aware rule in
`30-multilingual-sasl.md` section 3.2.

- Placement: in the deck toolbar / header rail, adjacent to Present / Notes /
  Print, so it reads as a peer navigation mode.
- Styling uses the theme's **Open Record** tokens (the `feature/open-record-wireframes`
  design language - do not edit those files, consume them): mono label
  (`--font-mono`), hairline frame (`--bw-hair solid --border-default`), a
  content-type accent edge (`--bw-accent`) in `--saho-gold` to mark it as the
  language/access affordance, `--focus-ring` for keyboard focus. Square by
  discipline (`--saho-radius-md` is `0`), no lift on hover - it **selects**
  (border + sunk background), consistent with the record cards.
- Label: a signing-hands glyph + `WATCH IN SASL`. When no track exists the
  control is **absent**, not disabled.

### 3.2 The in-deck SASL panel (deck engine tokens, self-contained contract)

Activating "Watch in SASL" opens a docked video panel bound to the deck's slide
state. This panel lives inside the deck component, so it uses the **deck engine
tokens** (`--saho-red`, `--saho-gold`, `--ink`, `--focus` from
`presentation/engine/deck.css`) rather than the page chrome - keeping the deck a
portable unit.

- **Slide-synced playback.** The panel listens to the same slide-change events
  the deck already fires (`deck.js`). On slide N it seeks to that slide's
  `start` and plays the cue window; on manual video scrub it does **not** hijack
  slide nav (the two stay loosely coupled - video follows slides, slides do not
  follow video, to avoid fighting the teacher).
- **Segment end behaviour** is user-selectable: `pause` (default, teacher-led) or
  `advance` (auto-next for self-study). Persisted per user via `localStorage`.
- **Captions on by default** in the panel, toggleable, rendered from the WebVTT
  chapter cues (`::cue` styled with deck tokens for contrast).
- **Transcript drawer.** A collapsible panel shows the current slide's
  `sasl.transcript`, scroll-synced and printable as a handout beneath the slide
  (reusing the existing notes/print pipeline).
- **Layout.** Side-by-side on >= 992px (deck left, signer right, roughly 70/30);
  stacked with the signer pinned above the slide on mobile. The signer video is
  never smaller than 240px on its short edge (legibility of handshapes).
- **Docked, resizable, dismissable.** Closing returns to the standard deck; the
  choice persists so a Deaf learner's default is SASL-on.

### 3.3 A SASL landing affordance (optional, later)

A future `/sgn-ZA/classroom` view can aggregate every deck with a populated
`field_sasl_track`, giving Deaf learners a single entry point. Out of scope for
the first #437 slice; the `sgn-ZA` langcode + the populated-field query make it
free to add later.

---

## 4. DeckSync integration

`src/DeckSync.php` already reads each `*.slides.json`, derives a deterministic
node UUID from the deck id, writes `field_slide_schema`, and resolves taxonomy
by name. The SASL track hooks in **additively**, preserving reproducibility:

1. **Resolve `meta.sasl.media`** (and every per-slide `sasl.media` in segmented
   mode) from **media UUID -> media id**, via
   `entityTypeManager->getStorage('media')->loadByProperties(['uuid' => ...])`.
   UUID-based resolution mirrors the existing env-independent approach (never
   store a raw id in JSON). If the media entity is absent on this environment,
   log a warning and leave `field_sasl_track` unset - the deck still syncs and
   renders, SASL simply is not offered (graceful, like an unmatched term today).
2. **Set `field_sasl_track`** on the presentation node to the resolved deck-level
   media id. Additive: only set when a UUID resolves; never clear an
   editor-attached track when the JSON omits `sasl`.
3. **Persist the per-slide resolved map** (segmented mode) so the renderer does
   no runtime lookups - either as a computed cache on the node or folded into
   the schema passed to the SDC. Continuous mode needs no map (timecodes only).
4. **Respect the existing publish gate.** SASL never changes a deck's publish
   state; `{"review":{"approved":true}}` still governs the node. The signed
   video has its **own** gate (`field_sasl_review_state`, section 5) so an
   approved deck can ship while its SASL track is still in Deaf review - the
   "Watch in SASL" control simply stays hidden until the track is published.
5. **Media entities are synced by a companion step, not invented by DeckSync.**
   DeckSync only *references* media by UUID. Creating the `sasl_video` media
   (uploading the video + VTT) is an editorial / import task; a later
   `SaslMediaSync` (parallel to `TermSeeder`) can reproducibly create them from a
   manifest keyed by the same UUIDs if we choose to track them in-repo.

Because the `sasl` block is optional and UUID-resolved, running the updated
DeckSync over the **current** 41 decks is a no-op until decks start carrying
`meta.sasl` - the pipeline stays green throughout rollout.

---

## 5. Accessibility requirements

The SASL track is itself an accessibility feature, so it must clear the bar
rather than merely gesture at it (WCAG 2.1 AA, SANS/SA context):

- **Signed video is a first-class alternative, not a caption.** The signer is
  large, well-lit, upper-body framed, with clear handshape contrast; minimum
  240px short edge; never letterboxed below legibility.
- **Captions (WebVTT) on all SASL video** in the deck's written language,
  toggleable, AA-contrast via `::cue`, synced to the signing.
- **Text transcript** for every slide, programmatically associated with the
  slide (`aria-describedby` / `data-slide`), printable, and machine-readable for
  search.
- **Keyboard operable end to end.** "Watch in SASL", panel open/close, play,
  pause, caption toggle, transcript toggle, and per-slide seek are all reachable
  and operable by keyboard, with visible `--focus-ring` focus states. No
  pointer-only interactions.
- **Screen-reader semantics.** The control announces state ("Watch in SASL,
  signed video available"); the panel is a labelled region; slide changes are
  announced via the deck's existing `aria-live` region, extended to note "SASL
  segment for slide N".
- **`prefers-reduced-motion`** respected: no autoplay on load, no motion-driven
  transitions into the panel; the deck engine already honours this.
- **No auto-generated signing.** Avatar / synthetic SASL is explicitly excluded;
  only human interpreter video ships. This is a correctness and dignity
  requirement, not a stylistic one.
- **Colour-independent status.** The "Watch in SASL" affordance's presence, not
  colour alone, signals availability; its accent edge is supplementary.
- **Offline dignity.** The self-contained export cannot stream video, so it
  inlines the transcript + WebVTT and states plainly that the signed video is
  available online, with the deck URL - never a broken player.

---

## 6. How this fits `30-multilingual-sasl.md`

This doc **realises**, for the deck bundle, the `field_sasl_track` / `sasl_video`
design that `30-multilingual-sasl.md` (section 2.3) specified corpus-wide:

- Same field name (`field_sasl_track`), same media bundle idea (`sasl_video`),
  same "show SASL only when the track exists" switcher rule, same "no machine
  text, human interpreter + Deaf reviewer" governance.
- This doc **adds** the deck-specific pieces the corpus doc did not cover: the
  slide-schema `sasl` block, slide-synced playback, DeckSync UUID resolution, and
  the in-deck panel vs page-chrome token split.
- The `sgn-ZA` `configurable_language` and the Deaf-community review gate are
  defined once in `30-multilingual-sasl.md` (sections 1, 6) and reused here - not
  redefined.

---

## 7. Implementation checklist

Config-first, additive, reproducible. Each item is one reviewable change; export
config after each (`ddev drush cex -y`).

**A. New glue module**
- [ ] Scaffold `webroot/modules/custom/saho_classroom_sasl` (saho_ prefix, D11
      standards, doc comments, ASCII hyphens). Depends on `saho_classroom`,
      `media`. Deck engine must keep working with this module absent.

**B. Media + field config (draft in `config/draft/`, promote when reviewed)**
- [ ] `media.type.sasl_video` + its fields (`field_media_sasl_file` or oEmbed,
      `field_sasl_captions` [.vtt], `field_sasl_transcript`,
      `field_sasl_interpreter`, `field_sasl_poster`, `field_sasl_review_state`).
- [ ] `field.storage.node.field_sasl_track` + `field.field.node.presentation.field_sasl_track`
      (entity_reference -> media:sasl_video, cardinality 1, non-translatable).

**C. Slide-schema extension**
- [ ] Bump `slide-schema.json` to `schema_version: 1.1`; add optional
      `meta.sasl` + `$defs.slide.properties.sasl` (section 2.4). Verify all 41
      existing decks still validate (they must - additive only).

**D. DeckSync**
- [ ] Extend `DeckSync::syncFile()` to resolve `meta.sasl.media` (+ per-slide
      `sasl.media` in segmented mode) by media UUID and set `field_sasl_track`
      additively; log-and-continue when media is absent. Preserve the existing
      deck publish gate untouched. Cover with a kernel test (deck with/without
      SASL, missing media UUID, segmented map).

**E. Renderer + SDC**
- [ ] Extend the `presentation_deck` component + `deck.js` to open a slide-synced
      SASL panel from the schema's `sasl` data (in-deck engine tokens). Keep the
      self-contained export path: inline transcript + WebVTT, print handout,
      "available online" note instead of a player.
- [ ] Add the "Watch in SASL" affordance in the deck toolbar (open-record
      tokens), rendered only when `field_sasl_track` is set; correct cache
      contexts/tags (tag the node + the media).

**F. Review workflow**
- [ ] Wire `field_sasl_review_state` to the Deaf-community review gate from
      `30-multilingual-sasl.md` section 6 (interpreter records -> Deaf review ->
      published). "Watch in SASL" appears only at `published`.

**G. Verify (Playwright, per CLAUDE.md)**
- [ ] On a deck **with** a published track: affordance present, panel opens,
      seeking a slide seeks the signer, captions + transcript render, keyboard +
      screen-reader semantics hold, `prefers-reduced-motion` respected.
- [ ] On a deck **without** a track: affordance absent, deck unchanged.
- [ ] Offline export: no player, transcript + VTT inlined, no external requests
      (`grep -Eio '(src|href)="https?://[^"]+"' out.html` returns nothing except
      the documented online-SASL note URL).
- [ ] Run PHP + frontend quality gates and commit built `dist/` assets.
