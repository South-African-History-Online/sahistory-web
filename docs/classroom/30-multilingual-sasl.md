# SAHO Classroom - Multilingual + South African Sign Language (SASL)

Design for issue **#437** (child of classroom epic **#433**). Defines how the
classroom resource set is translated into South Africa's official languages,
how the 12th language - **South African Sign Language (SASL)** - is delivered as
a signed-video / caption / transcript track rather than machine text, and the
Drupal content-translation model, fallback rules, glossary base, review gate,
and rollout sequencing that make it work.

Read alongside:
- `10-content-model.md` (#435) - the classroom entities this doc translates.
- `20-html-framework.md` (#436) - the delivery templates the switcher plugs into.
- Agent specs #438-443 - the translation agent referenced in the review gate.

---

## 0. Hard context (read first)

- **Translation is currently OFF.** `core.extension` enables only the base
  `language` module (`language: 0`). `content_translation`, `locale`, and
  `config_translation` are **not** enabled. Only three langcodes exist
  (`en`, `und`, `zxx`) and `en` is the site default with an empty URL prefix.
  Every classroom bundle ships `default_langcode: site_default` and
  `language_alterable: false`, i.e. content is single-language today.
- **`field_language` is legacy metadata, NOT translation.** The
  `field_language` taxonomy vocabulary (referenced from e.g.
  `field.field.node.archive.field_language`) is a freeform "what language is
  this source document in" tag. It has nothing to do with Drupal's translation
  system and must never be conflated with it. Real translation uses langcodes
  and `content_translation` entity translations. Leave `field_language` alone;
  do not repurpose it as a switcher signal.
- **Scope is the classroom resource bundles**, not the whole 200k-node site.
  Translating everything is out of scope and unaffordable to review. Target
  bundles: `article` (classroom-tagged), `archive` (classroom-tagged),
  `activity`, `presentation`, `quiz`, `worksheet`, and `classroom_clip`
  (video). SASL specifically attaches to `classroom_clip` and to any resource
  that carries a video learning object.
- **SASL is a language, not an accessibility afterthought.** SASL became SA's
  12th official language via the 18th Constitutional Amendment (July 2023). In
  this design it is a first-class language variant delivered as **signed video +
  synchronised captions + text transcript**. It is never produced by machine
  text translation.

---

## 1. The 12 official languages

Eleven written languages get full text translations. SASL gets a signed-media
track. Langcodes below are the standard Drupal/ISO 639 codes used for the
`configurable_language` entities and URL prefixes.

| # | Language | Langcode | Native label | Delivery |
|---|----------|----------|--------------|----------|
| 1 | English | `en` | English | Text (source of truth) |
| 2 | Afrikaans | `af` | Afrikaans | Text translation |
| 3 | isiZulu | `zu` | isiZulu | Text translation |
| 4 | isiXhosa | `xh` | isiXhosa | Text translation |
| 5 | Sepedi (Northern Sotho) | `nso` | Sepedi | Text translation |
| 6 | Setswana | `tn` | Setswana | Text translation |
| 7 | Sesotho | `st` | Sesotho | Text translation |
| 8 | Xitsonga | `ts` | Xitsonga | Text translation |
| 9 | siSwati | `ss` | siSwati | Text translation |
| 10 | Tshivenda | `ve` | Tshivenda | Text translation |
| 11 | isiNdebele | `nr` | isiNdebele | Text translation |
| 12 | **South African Sign Language** | `sgn-ZA` | SASL | **Signed video + captions + transcript (no machine text)** |

Notes:
- Use `sgn-ZA` as the SASL langcode - a custom `configurable_language` entity
  (Drupal has no predefined entry). Direction `ltr`; the visual medium is video,
  so text direction is only relevant to its caption/transcript companion.
- All 11 written languages are LTR; no RTL handling is required.
- `nso`, `tn`, `st`, `ss`, `ve`, `nr` are the codes Drupal ships in its
  predefined list under those native labels; confirm the exact predefined
  entries at install and override the label to the native spelling above.

---

## 2. Content-translation model

### 2.1 Modules to enable

Enable, in this order, then export config:

1. `language` (already on).
2. `content_translation` - entity (node/media/term) translations.
3. `locale` - interface (UI string) translations + `.po` import.
4. `config_translation` - translate view titles, field labels, menu links,
   and the classroom landing-page copy.

### 2.2 Which entities become translatable

Translation is enabled **per bundle**, editing the existing
`language.content_settings.node.<bundle>.yml` files (flip
`default_langcode: site_default` stays, add the bundle to
`content_translation.settings`, and set the bundle + shared fields
translatable). Target bundles and the fields that are marked translatable
(text/label) vs shared (references, media, numbers):

| Bundle | Translatable fields | Shared (untranslated) fields |
|--------|---------------------|------------------------------|
| `article` (classroom) | title, body, summary, SEO/meta | taxonomy refs (`field_classroom_*`), images |
| `archive` (classroom) | title, body/description | media refs, `field_language` (legacy tag), classroom refs |
| `activity` | title, body, instructions | grade/subject/resource-type refs |
| `presentation` | title, slide text, notes | slide images, refs |
| `quiz` | title, questions, options, feedback | scoring config, refs |
| `worksheet` | title, body, downloadable label | attached file, refs |
| `classroom_clip` | title, body, caption/transcript text | `field_clip_media`, `field_poster`, `field_source_url`, refs |

Rules:
- **Media and files are shared, not translated.** A worksheet PDF or a poster
  image is the same asset across languages unless a language has its own
  localised file, in which case attach a language-specific media entity rather
  than duplicating the node.
- **Taxonomy terms translate once, centrally.** The `classroom`,
  `classroom_grade`, `classroom_subject`, `classroom_resource_type`, and
  `field_classroom_categories` vocabularies get term-level translations so
  facets, filters, and the switcher render in-language. Enable
  content-translation on these vocabularies (some already have
  `language.content_settings.taxonomy_term.*` entries - extend, do not replace).
- **Views + landing copy translate via `config_translation`**, not per-node.

### 2.3 SASL as a media track (the 12th language)

SASL does not get a text node translation. Instead it is a **signed-video
learning object** bound to the same source node:

- Add a `saho_classroom_i18n`-owned field `field_sasl_track` (media reference,
  cardinality 1) to the translatable classroom bundles. It points to a media
  entity of a new bundle **`sasl_video`** carrying:
  - the signed-interpretation video file (or embed URL),
  - a synchronised captions file (WebVTT, `en` and ideally the resource's own
    language),
  - a plain-text transcript field.
- For `classroom_clip`, SASL reuses the existing video plumbing:
  `field_clip_media` holds the primary video; `field_sasl_track` holds the
  signed-interpretation companion; `field_poster` supplies the still.
- The language switcher exposes SASL as a distinct option **only when a
  `field_sasl_track` value exists** for that resource (see 3.2). Selecting SASL
  swaps the media player to the signed video + caption/transcript panel; it does
  not route to a `sgn-ZA` text translation, because none exists by design.
- The `sgn-ZA` `configurable_language` still exists so SASL is a real, selectable
  language in the negotiation layer and analytics, and so a future SASL landing
  page (`/sgn-ZA/classroom`) can aggregate all signed resources.

---

## 3. Per-resource variants + language switcher

### 3.1 URL + negotiation

- Switch `language.negotiation` to **URL path_prefix for every non-default
  language**: `en` keeps the empty prefix; the other ten written languages plus
  `sgn-ZA` get their langcode as the prefix (`/af/...`, `/zu/...`,
  `/sgn-ZA/...`). This keeps English URLs stable (no redirects on the existing
  200k nodes) and is SEO-clean with `hreflang` alternates.
- Add **`hreflang` + `x-default`** alternates on every translated resource so
  Google serves the right variant. `x-default` -> English.
- Keep interface negotiation URL-driven too, so a `/zu/` page also renders
  menus, facets, and chrome in isiZulu (via `locale`).

### 3.2 The switcher (classroom-scoped)

Ship a `saho_classroom_i18n` block, `saho_classroom_language_switcher`, rather
than reusing the core language block, because the classroom switcher has
resource-aware behaviour the core block lacks:

- Lists **only languages that actually have a translation of the current
  resource** (queried from `$node->getTranslationLanguages()`), plus SASL when
  `field_sasl_track` is populated. No dead links to non-existent variants.
- Each option shows the **native label** (isiZulu, not "Zulu") and, for SASL, a
  signing-hands glyph + "SASL video".
- Untranslated languages are shown in a secondary "Not yet available" group that
  routes to the English original with the translation-pending notice (section 4)
  - discoverability without broken UX.
- Cache: contexts `languages:language_interface` + `url`, tag the node, so the
  switcher invalidates when a translation is added.

### 3.3 Per-resource variant lifecycle

Each language variant is a `content_translation` translation of the same node
(same nid, distinct langcode), so:
- taxonomy tagging, grade/subject facets, and canonical URL identity are shared;
- editorial workflow (moderation state) is tracked per translation, letting a
  variant sit in "translation pending / in review" while English is published;
- adding a language never forks the node or breaks inbound links.

---

## 4. Fallback rules

Default rule: **untranslated resource -> English source + a visible
"translation pending" notice**, never a 404 and never silent machine output.

| Situation | Behaviour |
|-----------|-----------|
| Requested language variant is published | Serve it. |
| Variant does not exist | Serve English; render a dismissible banner: "This resource is not yet available in {native language}. You are reading the English version." Switcher keeps the target language highlighted as "pending". |
| Variant exists but is unpublished / in review | Serve English + "translation in review" banner (only content editors see the draft). |
| SASL requested, `field_sasl_track` empty | Serve English text + "A SASL video for this resource is coming soon." No auto-generated signing. |
| Interface strings untranslated for a language | Fall back to English UI (core `locale` fallback) - acceptable and expected early in rollout. |

Implementation:
- Core `language-content` negotiation with the **language fallback** chain
  ending at `en`. Do not enable a machine-translation fallback.
- The banner is a `saho_classroom_i18n` element attached in a bundle-scoped
  `hook_node_view` / preprocess, driven by comparing the requested langcode with
  `$node->getTranslationLanguages()`. Copy is translatable via `locale`.
- Never present fallback English as if it were the requested language - the
  banner + the switcher state together make the gap explicit and honest.

---

## 5. Per-language terminology / glossary base

Historical terms, proper nouns, and place names are the highest-risk part of any
SAHO translation (e.g. "Union of South Africa", "pass laws", "homelands /
Bantustans", "uMkhonto weSizwe", place names that changed at 1994/2004). A
shared glossary keeps the translation agent and human reviewers consistent and
prevents anachronistic or politically loaded renderings.

**Model it as a dedicated taxonomy vocabulary** `saho_glossary` (owned by
`saho_classroom_i18n`), content-translation enabled, one term per canonical
English concept:

- `name` = canonical English term.
- `field_glossary_definition` = short English gloss (context + era).
- Per-language **term translations** carry the approved rendering in that
  language; where a term should stay untranslated (many proper nouns,
  organisation names), the translation records "keep English" explicitly so
  reviewers do not re-litigate it.
- `field_glossary_do_not_translate` (boolean) flags terms that must pass through
  verbatim in all languages (person names, MK, ANC, etc.).
- `field_glossary_place_authority` (link/text) cites the source for a place-name
  decision (pre/post-1994 name, official gazette) so the choice is auditable.

Usage:
- The translation agent (#438-443) receives the glossary as a **term base /
  constraint list** for the resource's detected terms before it drafts, so
  renderings are consistent across the whole corpus, not per-node guesses.
- Native-speaker reviewers (section 6) approve glossary entries **once**, then
  every resource reuses them - review cost drops sharply after the first grade
  band per language.
- A public-facing `/classroom/glossary/{langcode}` page can later surface the
  glossary as a learner resource; that is a bonus, not a requirement of #437.

---

## 6. Translation agent + native-speaker review gate

Translation is **agent-drafted, human-approved**. No language variant reaches
learners without a native-speaker sign-off. This is a governance gate, not an
optional QA step.

Flow per resource per language:

1. **Draft (agent).** The translation agent (#438-443) pulls the English source,
   applies the `saho_glossary` term base + do-not-translate list, and writes a
   `content_translation` translation in moderation state `translation_draft`.
   The agent records model + prompt version in a revision log field for
   auditability. The agent never publishes.
2. **Native-speaker review (human gate).** A reviewer fluent in the target
   language and literate in SA history checks: factual fidelity, term/place-name
   correctness against the glossary, grade-appropriate register, and cultural
   sensitivity. They either correct in place -> `translation_review_passed`, or
   reject -> back to `translation_draft` with notes.
3. **Publish.** Only a passed translation moves to `published` and becomes
   selectable in the switcher. Until then, fallback rules (section 4) apply.
4. **Glossary feedback loop.** Any term the reviewer corrects is pushed back into
   `saho_glossary` so the agent gets it right everywhere next time.

SASL variant of the flow:
- There is **no agent draft** for SASL. A qualified SASL interpreter produces the
  signed video; a Deaf-community reviewer confirms accuracy and register. The
  captions/transcript are human-authored (they may be seeded from the English
  transcript but are human-verified). SASL therefore skips step 1 and enters at a
  media-production + review workflow, ending in the same `published` gate.

Moderation states to add (via `content_moderation`, classroom workflow):
`translation_draft` -> `translation_review_passed` -> `published`, with a
`translation_rejected` side-state. English source states are unchanged.

---

## 7. Recommended language sequencing

Do not attempt 11 languages at once - review capacity, not translation compute,
is the bottleneck. Sequence by learner reach and pilot the pipeline on a small,
high-value slice first.

**Phase 0 - Pipeline pilot (one language, one grade band).**
Afrikaans first: largest existing SAHO editorial/reviewer capacity, LTR, and
lowest orthographic risk - proves the content-translation config, switcher,
fallback banner, glossary, and review gate end to end on ~1 grade band of
resources before scaling.

**Phase 1 - Highest-reach Nguni + English baseline.**
isiZulu (`zu`) and isiXhosa (`xh`) - the two largest home-language groups. This
is where translated classroom material has the most learner impact and where the
glossary earns its keep across shared Nguni vocabulary (siSwati, isiNdebele
benefit later).

**Phase 2 - Sotho-Tswana cluster.**
Sepedi (`nso`), Setswana (`tn`), Sesotho (`st`) - shared roots let glossary
entries and reviewer knowledge transfer across the three.

**Phase 3 - Remaining written languages.**
Xitsonga (`ts`), siSwati (`ss`), Tshivenda (`ve`), isiNdebele (`nr`) - smaller
speaker bases; sequence by available reviewer capacity.

**Phase 4 - SASL track.**
Runs on its own media-production track and can start in parallel once Phase 0
proves the switcher + `field_sasl_track` wiring. Prioritise the most-used
`classroom_clip` videos and flagship worksheets/activities. SASL depth follows
interpreter + Deaf-reviewer availability, independent of the text phases.

Within every phase, translate **by usage**: start from the most-viewed / most
curriculum-central resources (CAPS core topics) rather than translating the
catalogue alphabetically.

---

## 8. Drupal implementation checklist

Ordered, config-first. Each numbered step is one reviewable change; export
config after each (`ddev drush cex -y`).

**A. Language + module foundation**
- [ ] Enable `content_translation`, `locale`, `config_translation` (keep
      `language`). `ddev drush en content_translation locale config_translation -y`.
- [ ] Create the 11 `configurable_language` entities: `af`, `zu`, `xh`, `nso`,
      `tn`, `st`, `ts`, `ss`, `ve`, `nr`, and the custom `sgn-ZA` (SASL) with a
      native label. Set native labels (isiZulu, Sepedi, etc.).
- [ ] Update `language.negotiation`: URL path_prefix per language, `en` = empty
      prefix; enable URL + fallback for `language_content` and
      `language_interface`.
- [ ] Confirm `language.types` uses the fallback chain terminating at `en`; do
      **not** wire any machine-translation fallback.

**B. Make classroom entities translatable**
- [ ] For each target bundle (`article`, `archive`, `activity`, `presentation`,
      `quiz`, `worksheet`, `classroom_clip`): enable content translation and set
      title/body/text fields translatable, references/media/files shared
      (section 2.2). Edit the existing
      `language.content_settings.node.<bundle>.yml` - do not delete them.
- [ ] Enable term translation on `classroom`, `classroom_grade`,
      `classroom_subject`, `classroom_resource_type`,
      `field_classroom_categories`. Extend existing
      `language.content_settings.taxonomy_term.*` entries.
- [ ] Leave `field_language` (legacy source-language tag) untouched and
      non-translatable.

**C. `saho_classroom_i18n` module (new glue module)**
- [ ] Scaffold `webroot/modules/custom/saho_classroom_i18n` (saho_ prefix,
      Drupal 11 standards, doc comments, ASCII hyphens).
- [ ] Add `field_sasl_track` (media reference) to the translatable bundles and
      create the `sasl_video` media bundle (video file/embed + WebVTT captions +
      transcript text).
- [ ] Provide the `saho_classroom_language_switcher` block (section 3.2):
      resource-aware, native labels, SASL only when `field_sasl_track` set,
      "not yet available" group, correct cache contexts/tags.
- [ ] Attach the translation-pending / in-review / SASL-coming-soon banners via
      a bundle-scoped preprocess or `hook_node_view` (section 4); strings via
      `locale`.
- [ ] Emit `hreflang` + `x-default` alternates for translated resources.

**D. Glossary base**
- [ ] Create `saho_glossary` vocabulary + fields (`field_glossary_definition`,
      `field_glossary_do_not_translate`, `field_glossary_place_authority`),
      content-translation enabled (section 5).
- [ ] Seed the do-not-translate list (person names, MK, ANC, organisation names)
      and the first CAPS-topic term set for the Phase 0 language.
- [ ] Expose the glossary to the translation agent as a term base / constraint
      input (#438-443 integration point).

**E. Review workflow**
- [ ] Add `content_moderation` states `translation_draft`,
      `translation_review_passed`, `translation_rejected` and the classroom
      translation workflow (section 6); grant transitions per language-reviewer
      role.
- [ ] Configure the translation agent to write `translation_draft` only (never
      publish) and log model/prompt version to a revision field.
- [ ] Define the SASL media-production + Deaf-reviewer sub-workflow ending in the
      same `published` gate (no agent draft step).

**F. Interface + config strings**
- [ ] Import `.po` interface translations for the phase's language(s) via
      `locale`; translate classroom view titles, facet labels, menu links, and
      landing copy via `config_translation`.

**G. Verify (per phase, before rollout)**
- [ ] Playwright: load a resource under `/af/`, `/zu/`, `/sgn-ZA/`; assert the
      switcher lists only real variants, the fallback banner renders for
      untranslated languages, and SASL swaps the media player rather than routing
      to text.
- [ ] Confirm `hreflang`/`x-default` in page head and that English URLs are
      unchanged (no redirects on existing nodes).
- [ ] `ddev drush cex -y`; run PHP + frontend quality gates per CLAUDE.md before
      committing (include `dist/` for any theme assets).
