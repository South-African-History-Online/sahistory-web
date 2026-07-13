# Classroom Content and CAPS Coverage Audit

Issue: [#434](https://github.com/South-African-History-Online/sahistory-web/issues/434)
(child of epic [#433](https://github.com/South-African-History-Online/sahistory-web/issues/433))
Scope: read-only current-state audit of the Classroom section.
Method: inspection of `config/sync` (taxonomies, fields, content types, views) plus
read-only `ddev drush sql:query` counts against the live local database.
Date: 2026-07-03. Drupal 11.4.0.

All figures below are live database counts, not config estimates.

---

## 1. Current-state summary

### 1.1 The content model is built; the content is not

The data model that epic #433 / issue #435 implies is already present in `config/sync`.
There are five dedicated classroom content types and a full CAPS-aligned taxonomy stack:

Content types (all present, all with `field_caps_topic`, `field_classroom_grade`,
`field_classroom_resource_type`, `field_classroom_subject`, `field_file_upload`):

| Content type | Purpose (from `node.type.*.yml`) | Published nodes | Total nodes |
| --- | --- | --- | --- |
| `worksheet` | Classroom worksheet | 0 | 0 |
| `activity` | Classroom activity | 0 | 0 |
| `presentation` | CAPS-aligned deck (HTML-native or file) | 0 | 0 |
| `quiz` | Classroom quiz | 0 | 0 |
| `classroom_clip` | Short self-hosted educational video | 0 | 0 |

**Every dedicated classroom content type is empty.** The HTML-native delivery vehicles that
issues #436 onward depend on exist only as scaffolding. 100% of live classroom content today
is legacy `article` (117 nodes) and `archive` (5 nodes) tagged with the classroom fields.

### 1.2 What content actually exists

| Metric | Value |
| --- | --- |
| Nodes tagged with `field_classroom_grade` | 122 (117 article + 5 archive) |
| Published / unpublished | 111 published / 11 unpublished |
| Nodes tagged with `field_caps_topic` | 71 |
| Nodes tagged with `field_classroom_resource_type` | 165 tag rows across ~122 nodes |
| Nodes with `field_classroom` (grade topic spine) | 49 |
| Nodes with `field_classroom_categories` (extras) | 99 |
| Subject split | History 153, Geography 1 (effectively History-only) |
| Language | English 34, language-neutral (`und`) 88 -> **0 non-English translations** |

### 1.3 Taxonomies (all populated)

| Vocabulary (`vid`) | Terms | Notes |
| --- | --- | --- |
| `caps_topic` | 50 | ~44 real CAPS topics + 6 "Grade N" placeholder terms |
| `classroom_grade` | 12 | Grades 4-12 plus Intermediate / Senior / FET Phase |
| `classroom_resource_type` | 11 | Activity, Clip, Essay questions, Exam paper, Glossary, Lesson pack, Presentation, Quiz, Source-based questions, Topic overview, Worksheet |
| `classroom_subject` | 2 | History, Geography |
| `classroom` | 9 | Legacy "History Classroom Grade Four..Twelve" spine |
| `field_classroom_categories` | 13 | Grade 4-12 + Aids & Resources, Technical skills, Debate, Nkosi Albert Luthuli Oral History |

The `caps_topic` vocabulary is genuinely CAPS-aligned (e.g. "Turning points in South African
history: 1948 and the 1950s", "The Mineral Revolution in South Africa", "Hunter-gatherers and
herders in Southern Africa"). This is the strongest asset in the section.

### 1.4 Delivery pages (views)

| View | Page path | Role |
| --- | --- | --- |
| `classroom` | `/classroom` | Main filterable landing (Grade, Resource type, Subject facets) |
| `saho_classroom_topic` | `/classroom/topic/%` | Per-topic spine page |
| `classroom_extras` | `/caps-documents`, `/aids-resources`, `/debates-history-education` | Extras |
| `classroom_technical_skills` | `/classroom-technical-skills` | Technical skills |

Note: the per-grade views named in the epic brief (`historyclassroom_grade4`,
`classroom_history_grade10`, `latest_in_classroom`) are **not present in `config/sync`** - only
`classroom_extras` and `classroom_technical_skills` are exported. Either they were removed, live
only in the DB unexported, or were renamed. Flagged for the #435/#444 work.

---

## 2. Coverage matrix

RAG key: **G** = adequate spread (>=5 nodes and 2+ resource types), **A** = present but thin
or single-type, **R** = missing / not started.

### 2.1 Grade x Resource type (History) - the core matrix

Counts are distinct nodes carrying both tags. Empty cell = 0.

| Grade (Phase) | Topic overview | Activity | Worksheet | Presentation | Quiz | Clip | Source-based Q | Essay Q | Exam paper | Glossary | Lesson pack | RAG |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | :---: |
| Grade 4 (Int) | 13 | 1 | - | - | - | - | - | - | - | - | - | A |
| Grade 5 (Int) | 12 | 1 | - | - | - | - | - | - | - | - | - | A |
| Grade 6 (Int) | 7 | 2 | - | - | - | - | - | - | - | - | - | A |
| Grade 7 (Sen) | 6 | - | - | - | - | - | - | - | - | - | - | R |
| Grade 8 (Sen) | 6 | - | - | - | - | - | - | - | - | - | - | R |
| Grade 9 (Sen) | 5 | - | - | - | - | - | - | - | - | - | - | R |
| Grade 10 (FET) | 19 | - | - | - | - | - | 4 | 4 | - | 4 | - | A |
| Grade 11 (FET) | 9 | - | - | - | - | - | 4 | 4 | - | 4 | - | A |
| Grade 12 (FET) | 11 | 1 | - | - | - | - | - | - | 5 | - | - | A |

Reading the matrix:
- **Four resource types are completely empty across every grade**: Worksheet, Presentation,
  Quiz, Clip (all 0). These are exactly the HTML-native vehicles the epic wants to build.
- The section is a **library of topic overviews**, not a set of teachable resources. 107 of 165
  resource-type tags (65%) are "Topic overview".
- Assessment scaffolding (source-based / essay / exam) exists **only for FET (Grades 10-12)**.
  Grades 4-9 have none.
- Senior Phase (Grades 7-9) is the weakest band: overviews only, no activities, no assessment.

### 2.2 CAPS topic coverage (History, real topics only)

37 of ~44 real CAPS topics carry at least one node; the long tail is one node each.

| Depth band | # of CAPS topics | Example topics | RAG |
| --- | ---: | --- | :---: |
| 5+ nodes | 3 | Hunter-gatherers and herders (6), The French Revolution (5), Local history (5) | A |
| 2-4 nodes | 12 | European expansion & slave trade (4), Nationalisms (4), Mapungubwe (3), Ideas of race (3), Communism in Russia (3), Capitalism in the USA (3), Mineral Revolution (2), SA War and Union (2) | A |
| 1 node | 22 | WWI, WWII, Cold War, Scramble for Africa, Turning points 1948/1950s, Turning points 1960/1976/1990, Democracy & citizenship, Transatlantic slave trade, etc. | R |
| 0 nodes | ~7 | Grade-placeholder terms + a handful of un-tagged CAPS topics | R |

No CAPS topic reaches "green": even the best-covered topic (6 nodes) is 6 overview articles with
no worksheet, presentation, quiz, or clip.

### 2.3 Language dimension

| Language | Classroom nodes | RAG |
| --- | ---: | :---: |
| English (`en` / `und`) | 122 | A (source language only) |
| Afrikaans, isiZulu, isiXhosa, Sesotho, Setswana, Sepedi, siSwati, Tshivenda, Xitsonga, isiNdebele | 0 | **R (all)** |

There are **zero** translations in any of the other 10 official South African languages -
site-wide, not just in Classroom. Multilingual delivery (issue #437) starts from nothing.

---

## 3. Top gaps (ranked)

1. **No teachable resources exist - only overviews.** Worksheet, Presentation, Quiz and Clip
   content types are 0 nodes; the matching resource-type terms have 0 tagged content. The entire
   value proposition of the epic (CAPS-aligned worksheets, decks, quizzes, clips) is greenfield.
2. **Senior Phase (Grades 7-9) is bare.** 17 overview nodes total, no activities, no assessment.
   This is the CAPS band where History becomes a distinct, assessed strand inside Social Sciences.
3. **Assessment only exists for FET.** Source-based questions, essay questions and exam papers
   are tagged only to Grades 10-12. Grades 4-9 have no assessment material at all.
4. **CAPS topic coverage is a mile wide and an inch deep.** 22 real CAPS topics have exactly one
   node; SAHO's flagship subjects (apartheid turning points, the Cold War, WWII) sit at 1 node
   despite huge underlying article depth elsewhere on the site that is simply not classroom-tagged.
5. **Zero multilingual content.** No non-English translations anywhere. Any multilingual goal is
   a from-scratch build, not a translation top-up.
6. **Legacy vs new model split.** All content lives on `article`/`archive`; the five new content
   types are empty. Migration/authoring policy (which type new content lands in) is undecided.
7. **View drift.** Per-grade views referenced in the brief are absent from `config/sync`; the
   delivery layer inventory does not match the config that ships.

---

## 4. Recommended pilot topic

### Primary pilot: "Turning points in South African history: 1948 and the 1950s" (Grade 9, Senior Phase)

CAPS topic `35821`. Recommended as the first end-to-end vertical slice.

Why this one:
- **Fills the worst gap.** Grade 9 / Senior Phase is the weakest band (RED) - a pilot here proves
  the model where coverage is thinnest, not where it is already least bad.
- **Plays to SAHO's deepest content domain.** Apartheid, the Defiance Campaign, the Freedom
  Charter and the 1950s liberation struggle are SAHO's single strongest and highest-traffic
  subject area. Source material for worksheets, source-based questions and a presentation already
  exists on the site (outside the classroom tag), so authoring is assembly, not research.
- **Clean single-grade CAPS mapping** with a natural bridge to the Grade 12 apartheid topic,
  letting the same source set seed a second grade later with minimal extra cost.
- **Demonstrates the full resource stack** the epic needs: one Topic overview (exists to anchor)
  + Worksheet + Presentation + Quiz + Source-based questions + optional Clip. Producing all five
  vehicles once, on a familiar subject, de-risks the HTML framework (#436) before scale-up.

Success criteria for the pilot: this one CAPS topic reaches **green** - >=1 published node of each
core resource type (overview, worksheet, presentation, quiz, source-based questions) on the new
content types, surfaced through `/classroom` and `/classroom/topic/%`.

### Secondary pilot (lowest authoring risk): "Hunter-gatherers and herders in Southern Africa" (Grade 5/6, Intermediate Phase)

CAPS topic `35799`. Best existing coverage (6 nodes) and a foundational Intermediate-Phase topic.
Use it as the Intermediate-Phase control case so the pilot spans two CAPS phases (Senior + Intermediate)
and validates the model for younger grades, where language, reading level and assessment style differ.

---

## 5. Notes and caveats

- All counts are read-only snapshots of the local DDEV database on 2026-07-03; production may
  differ (see MEMORY note on local vs prod bundle drift).
- `field_classroom_resource_type` is single-value (cardinality 1) while `field_classroom_grade`,
  `field_caps_topic` and `field_classroom` are multi-value - a node can serve several grades/topics
  but only one resource type. Cross-tabs above count distinct nodes per tag pair.
- "Topic overview" dominance (65% of resource tags) is the headline structural finding: the
  section is currently an encyclopaedia, not a classroom.
