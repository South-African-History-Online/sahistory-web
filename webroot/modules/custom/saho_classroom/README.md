# SAHO Classroom

Classroom 2.0 (epic #433) content model for the main site.

## Model

Four vocabularies (installed with seeded terms via post_update):

- `classroom_grade` - hierarchical: Intermediate Phase (grades 4-6),
  Senior Phase (7-9), FET Phase (10-12). The phase level powers the wf08
  grade/phase selector.
- `classroom_subject` - History and Geography (CAPS Social Sciences).
- `caps_topic` - hierarchical: one parent per grade, CAPS term/topic
  children seeded from the official History curriculum.
- `classroom_resource_type` - Topic overview, Presentation, Worksheet,
  Activity, Quiz, Clip, Glossary, Source-based questions, Essay
  questions, Exam paper. Used to type legacy article/archive material
  without changing its bundle.

Five content types: `presentation`, `worksheet`, `activity`, `quiz`,
`classroom_clip` (self-hosted video + poster + mono source-credit line;
clips are unpublished by default - editors review before publishing;
own-content-only policy per #428 decisions).

The classroom fields (`field_classroom_grade`, `field_classroom_subject`,
`field_caps_topic`, `field_classroom_resource_type`) also attach to the
legacy `article` and `archive` bundles so existing classroom material
joins the same facets.

Enable on the MAIN site only:

```
ddev drush en saho_classroom -y
```

## Reserved learner-content patterns (R3 #479)

quiz / worksheet / activity have no published nodes yet (#428 deploys the
content). When they land, the design contract is: ArchiveCard with a mono
type kicker (`WORKSHEET - GRADE 6`) in listings, plus a `MATERIALS FOR THIS
LESSON` RelatedList slot in the deck-page rail. No dead UI ships before the
content exists.
