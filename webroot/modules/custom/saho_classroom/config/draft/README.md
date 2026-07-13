# Classroom 2.0 draft config (issue #435)

These YAML files are a **reviewer-facing reference**, not importable config.
They live in `config/draft/` on purpose:

- `config/install/` would install them when the module is enabled - we do not
  want that yet.
- The canonical, importable versions of most of these objects already exist in
  the site's `config/sync/` (the model is realised on the main site). The
  drafts here carry inline `#` comments explaining the design decisions behind
  each object, which real config YAML cannot keep.

Use them to see the concrete shape of the content model while reviewing #435,
then compare against `config/sync/` for the authoritative values.

## Content model at a glance

**Four taxonomies** (the facet spine every resource shares):

| Vocabulary | Purpose |
| --- | --- |
| `classroom_grade` | Grade 4-12, grouped under phase parents (Intermediate 4-6, Senior 7-9, FET 10-12). Powers the grade/phase selector. |
| `classroom_subject` | CAPS Social Sciences subjects carried by classroom content (History, Geography). |
| `caps_topic` | The curriculum topics. **This is the spine anchor** - one term per CAPS topic, children under a per-grade parent. |
| `classroom_resource_type` | Topic overview, Presentation, Worksheet, Activity, Quiz, Clip, Glossary, Source-based questions, Essay questions, Exam paper. Types content without changing its bundle and gives the spine its display order. |

**Five content types**: `presentation`, `worksheet`, `activity`, `quiz`,
`classroom_clip`. Each carries the same four classroom fields
(`field_classroom_grade`, `field_classroom_subject`, `field_caps_topic`,
`field_classroom_resource_type`). The legacy `article` and `archive` bundles
carry the same fields so existing classroom material joins the same facets
without a bundle migration. Only `node.type.presentation.yml` plus its four
field instances are drafted here as the worked example; the other four bundles
follow the identical field matrix.

## The topic spine

All resource types point at a `caps_topic` term through the single shared field
`field_caps_topic`. That shared reference is the "topic spine": from one CAPS
topic you can gather its overview, presentation, worksheets, activities, quiz
and clips as one ordered lesson unit. The read-only assembler is sketched in
`../src/TopicSpineInterface.php` and `../src/TopicSpineBuilder.php`.

## Migration from the current classroom tagging

Classroom content today is tagged with the legacy `classroom` vocabulary
(`field_classroom`) and `field_classroom_categories`, plus a shadow corpus of
article/archive nodes whose grade lives only in the title. The module's
`saho_classroom.post_update.php` retags that corpus onto the new taxonomies:

- **Additive-only and idempotent** - a new field is written only when empty, so
  re-running never overwrites editor work.
- Legacy grade terms and title grade markers map to `Grade N` terms.
- Subject defaults to History (per the content audit) unless the title names
  another subject.
- Resource type is derived from title keywords / the legacy CAPS Document term,
  falling back to "Topic overview".
- `field_caps_topic` uses a conservative positional + word-overlap match; a
  wrong topic is worse than none, so uncertain matches are left empty for
  editors.

## How translations attach

Every classroom field storage is `translatable: true` (see the
`field.storage.*` drafts). Translation is handled by core Content Translation on
the node, so a `presentation`/`worksheet`/etc. node gets an af/zu/xh/... entity
translation and the field values are per-language where it makes sense (labels,
body) while entity-reference targets are shared. The taxonomy terms themselves
are translated with Content Translation on `taxonomy_term`, so a `caps_topic` or
`classroom_grade` term shows its localized name in each language while the
spine keys off the language-independent term id. No custom translation layer is
introduced.
