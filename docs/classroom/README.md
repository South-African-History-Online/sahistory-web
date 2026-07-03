# SAHO Classroom 2.0 - Phase 0 (Foundation)

Index of the Phase 0 discovery and design deliverables for the Classroom 2.0
epic ([#433](https://github.com/South-African-History-Online/sahistory-web/issues/433)).

Phase 0 is design and audit only. Nothing here is wired to production. No
classroom content types are enabled, no translation modules are turned on, and
the `saho_classroom` module ships disabled. This phase answers: what do we have,
what should we build, and how do we build it safely - so that Phase 1
(implementation) starts from evidence, not assumptions.

Context: CAPS places History inside Social Sciences for grades 4-9 and as a
standalone subject for grades 10-12. Every design below is organised around that
curriculum spine.

---

## 1. Deliverables

| # | Deliverable | Issue | Location |
| --- | --- | --- | --- |
| 1 | Content and CAPS coverage audit | #434 | `docs/classroom/00-content-audit.md` |
| 2 | Content model (spine + bundles + migration) | #435 | `webroot/modules/custom/saho_classroom/` (README, `config/draft/`, `src/`, `saho_classroom.post_update.php`) |
| 3 | HTML presentation format + prototype | #436 | `docs/classroom/20-html-format.md` + `webroot/modules/custom/saho_classroom/prototype/group-areas-act.html` |
| 4 | Multilingual + SASL design | #437 | `docs/classroom/30-multilingual-sasl.md` |
| 5 | AI agent pipeline design | #438-#443 | `docs/classroom/40-agent-pipeline.md` |

### 1.1 Content and CAPS coverage audit (#434)

A read-only, live-database audit of the existing Classroom section. Headline
finding: the content model already exists in `config/sync` (five dedicated
content types plus a genuinely CAPS-aligned `caps_topic` taxonomy of ~50 terms),
but all five new content types hold **zero nodes**. All 122 live classroom items
(111 published, 11 unpublished) are legacy `article`/`archive` nodes carrying the
classroom fields. The coverage matrix (grade x resource type x CAPS topic x
language) shows the section is 65% "Topic overview": Worksheet, Presentation,
Quiz and Clip are empty across every grade; assessment exists only for FET
(grades 10-12); Senior Phase (grades 7-9) is bare; and there are zero non-English
translations. In short, the section is an encyclopaedia, not a classroom.

### 1.2 Content model (#435)

Realised as module artifacts rather than a standalone doc. Defines the four
shared taxonomies (`classroom_grade` with phase parents, `classroom_subject`,
`caps_topic`, `classroom_resource_type`) and five content types (`presentation`,
`worksheet`, `activity`, `quiz`, `classroom_clip`). The organising idea is the
**topic spine**: every resource type carries one shared `field_caps_topic`
reference, so a single CAPS topic assembles its overview, deck, worksheets,
activities, quiz and clips into one ordered lesson unit. The legacy
`article`/`archive` bundles carry the same four fields so existing material joins
the same facets without a bundle migration. An additive-only, idempotent
`post_update` retags the legacy corpus onto the new taxonomies. A read-only
`TopicSpine` API is sketched in `src/` (interface + value object + a stub builder
that currently throws "not yet implemented"). The module stays disabled; net-new
draft config lives in `config/draft/` (reviewer-facing, non-importable).

### 1.3 HTML presentation format (#436)

Proposal plus a working, self-contained proof-of-concept deck
(`group-areas-act.html`, ~30 KB, all CSS/JS inline, imagery as inline SVG, zero
external requests, opens from `file://`). It demonstrates keyboard/arrow/swipe
nav, fullscreen present mode, speaker-notes toggle, clean print-to-PDF, offline
use, responsive layout and WCAG 2.1 AA a11y. The recommendation is a lightweight
custom HTML engine (~200 LOC vanilla JS) over reveal.js (~350 KB+ dependency) or
PowerPoint/PDF attachments. Crucially, generation agents emit a
**renderer-agnostic structured slide schema** (JSON, or Markdown front-matter for
human authors), not final HTML; Drupal stores it and a Twig template renders both
an interactive deck and a plain semantic fallback for SEO / no-JS / screen
readers.

### 1.4 Multilingual + SASL (#437)

Design for translating the classroom resource bundles into South Africa's 12
official languages: 11 written-text translations plus South African Sign Language
(SASL) delivered as a `sgn-ZA` signed-video + captions + transcript track, never
as machine text. Two load-bearing findings shaped it: translation is currently
**OFF** (only the base `language` module is on; `content_translation`, `locale`,
`config_translation` are not enabled), and the existing `field_language` taxonomy
is legacy source-language metadata that must not be confused with real
translation. The design covers the per-bundle content-translation model, a new
`saho_classroom_i18n` glue module (`field_sasl_track` + `sasl_video` media
bundle), a resource-aware language switcher, a `saho_glossary` terminology/
place-name base with a do-not-translate list, honest fallback (untranslated ->
English + visible "translation pending" banner, never a 404, never silent machine
output), an agent-draft / native-speaker-review gate, and phased language
sequencing (Afrikaans pilot -> Nguni -> Sotho-Tswana -> rest -> SASL in parallel).

### 1.5 AI agent pipeline (#438-#443)

The design spec for the six-agent generation pipeline: research/modernisation
(#438), presentation generation (#439), translation/terminology (#440), CAPS/
pedagogical QA (#441), orchestration (#442), and blocking guardrails (#443). The
framing constraint throughout is that generation is cheap and **human review
capacity is the bottleneck**, so every design choice optimises reviewer
throughput (batch by topic spine, pre-score and pre-flag, gate English before
translation fans out, target regeneration on reject). Stages communicate through
an append-only "topic spine" JSON data contract keyed by
`topic_id x grade x language`, where claims are the atomic unit of truth and every
rendered fact traces to a cited SAHO source node. Guardrails (citation coverage,
provenance monotonicity, licensing) run automatically before every human gate,
and only human sign-off moves Drupal content-moderation state to published.

---

## 2. Key decisions and recommendations

1. **Build on the existing model; do not rebuild it.** The taxonomies and content
   types already exist in `config/sync`. Phase 1 is about filling them, not
   re-scaffolding. The `saho_classroom` module and its `config/draft/` document
   the intended shape without re-installing it.
2. **The topic spine is the organising primitive.** One shared `field_caps_topic`
   reference across all bundles (new and legacy) is what turns five disconnected
   node lists into a teachable unit.
3. **Custom lightweight HTML for presentations**, dependency-free, with a
   renderer-agnostic slide schema as the durable asset. Agents emit the schema,
   not HTML.
4. **Retag legacy content in place** (additive, idempotent) rather than migrating
   `article`/`archive` nodes to new bundles.
5. **Translation is a from-scratch build**, not a top-up. Enable
   `content_translation`/`locale`/`config_translation`, keep `field_language`
   untouched, and deliver SASL as media (not text). Sequence by learner reach,
   Afrikaans first as the pipeline pilot.
6. **Optimise the pipeline for reviewers, not models.** Guardrails are blocking
   and pre-gate; only humans publish; provenance is stamped and monotonic.
7. **Recommended pilot slice:** Grade 9 "Turning points in South African history:
   1948 and the 1950s" (fills the weakest band, leverages SAHO's deepest
   apartheid content), with Grade 5/6 "Hunter-gatherers and herders" as a
   lower-risk Intermediate-Phase control so the pilot spans two CAPS phases.

---

## 3. Open questions (need human / SME input)

Ranked by how much they block Phase 1.

1. **Authoring policy: which bundle does new content land in?** Legacy content is
   all `article`/`archive`; the five new types are empty. Someone must decide the
   migration/authoring rule before content creation starts. (#435)
2. **Named reviewers and their capacity.** The whole pipeline is throttled by
   human review. Who is the SME (historical fact) and who is the CAPS-qualified
   educator? What is their weekly review-slot capacity, and what is the escalation
   path for a contested historical claim? Without this, the pipeline throughput is
   unknown. (#438-#443, #448-#451)
3. **Native-speaker reviewer availability per language.** Language sequencing is
   gated by reviewer capacity, not translation compute. Confirm who can sign off
   Afrikaans (pilot), then Nguni, etc. (#437)
4. **View drift.** The per-grade views named in the epic brief
   (`historyclassroom_grade4`, `classroom_history_grade10`, `latest_in_classroom`)
   are not present in `config/sync`. Were they removed, renamed, or do they live
   unexported in the DB? Resolve before the delivery layer work. (#434, #435, #444)
5. **Presentation storage model.** Single JSON field vs Paragraph-per-slide
   (agent-output simplicity vs editor-friendliness). (#436)
6. **QA thresholds.** The readability metric, the CAPS rubric scoring method, and
   the approve/review/reject and confidence thresholds are all placeholders that
   need pedagogical input. (#441, #438)
7. **Local vs production content drift.** All audit counts are from the local
   DDEV database on 2026-07-03; production may differ. Confirm against prod before
   committing to the coverage numbers. (#434)
8. **Doc-set housekeeping.** The four docs are numbered 00/20/30/40 but their
   internal cross-references assume a 10/20/30/50/60/70 scheme, and #435 has no
   standalone `docs/classroom/` file (it lives in the module). Reconcile the
   numbering and decide whether #435 needs a companion doc for reviewers who do
   not read module code.

---

## 4. Definition of Done for Phase 0

Phase 0 is complete when:

- [x] Read-only content + CAPS coverage audit exists with live counts, a coverage
      matrix, ranked gaps and a recommended pilot (#434).
- [x] Content model is documented (bundles, four taxonomies, topic spine,
      migration policy, translation attach) with a read-only spine API sketch
      (#435).
- [x] HTML presentation format is decided, justified against alternatives, and
      proven with a self-contained working prototype + a structured slide schema
      (#436).
- [x] Multilingual + SASL model is designed: languages, translation config,
      switcher, glossary, fallback rules, review gate, sequencing (#437).
- [x] AI agent pipeline is specified: six agents, the topic-spine data contract,
      guardrails, and moderation integration (#438-#443).
- [x] All deliverables are indexed here with decisions, open questions and a
      next-steps plan.
- [ ] **Human review of this synthesis** and sign-off on the pilot slice and the
      open questions in section 3 (specifically #1, #2, #4). This is the one item
      Phase 0 cannot close on its own.

Phase 0 output is design and documentation only. No modules enabled, no config
imported, no content created, no production changes. That is the intended state.

---

## 5. Next two weeks (action list)

Mapped to issues. Items marked (human) need a person, not an agent.

**Week 1 - unblock and validate**

1. (human) Review this index and the four docs; sign off or amend the pilot slice
   (Grade 9 turning points + Grade 5/6 hunter-gatherers control). [#433, #449]
2. (human) Name the SME and educator reviewers and confirm weekly review-slot
   capacity; this sets pipeline throughput. [#448-#451, #438-#443]
3. Resolve view drift: locate or rebuild `historyclassroom_grade4`,
   `classroom_history_grade10`, `latest_in_classroom`; export to `config/sync`.
   [#434, #435]
4. Decide the authoring/migration policy (which bundle new content lands in) and
   record it on #435. [#435]
5. Re-run the audit counts against production to confirm the coverage matrix
   before committing to targets. [#434]

**Week 2 - stand up the vertical slice**

6. Enable `saho_classroom` on the main site in a branch; dry-run the
   additive/idempotent `post_update` retag against a DB copy and verify no editor
   data is overwritten. [#435]
7. Move the prototype deck's CSS/JS into a versioned `saho_classroom` library and
   render one real slide-schema deck through Twig (interactive + no-JS fallback).
   [#436]
8. Author the JSON Schema for the slide format so agent output can be validated in
   CI. [#436, #439]
9. Scaffold the pilot language pipeline: enable
   `content_translation`/`locale`/`config_translation` in a branch, create the
   Afrikaans `configurable_language`, and stand up the `saho_glossary` vocabulary
   seeded with the do-not-translate list. [#437]
10. Produce the pilot topic end to end by hand (research brief -> deck ->
    worksheet -> quiz -> source-based questions) to validate the topic-spine data
    contract before automating any agent. [#438-#443, #449]

Priority for the queue once agents come online: high-traffic topics and
exam-weighted grades first, informed by the #434 audit and site analytics, so the
scarce reviewer hours land on the cells that reach the most learners.
