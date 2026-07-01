---
name: saho-design
description: Use this skill to generate well-branded interfaces and assets for SAHO (South African History Online), either for production or throwaway prototypes/mocks/etc. Contains essential design guidelines, colors, type, fonts, assets, and UI kit components for prototyping the "Open Record" 2027 standard.
user-invocable: true
---

Read the `readme.md` file within this skill, and explore the other available files.

If creating visual artifacts (slides, mocks, throwaway prototypes, etc), copy assets out and create static HTML files for the user to view. Link `styles.css` for the full token system and self-hosted fonts. If working on production code, you can copy assets and read the rules here to become an expert in designing with this brand.

Key facts to anchor on:
- **Concept:** "the open record" and the "The Record" approach: every surface is a view onto structured archive data (typed entities, fields, accession refs, cross-links). A catalogue / finding aid / database, not a magazine or app. Square corners, ruled hairlines, borders not shadows, cool newsprint ground.
- **Type:** Libre Caslon (the official printed record, reference register: titles + long-form body) + Archivo (grotesque, editorial register + all interface; set heavy/uppercase for the struggle-poster voice) + IBM Plex Mono (provenance, citations, tabular metadata). Self-hosted in `assets/fonts/`.
- **Colour:** cool printer's ink (`#1b1c17`) on cool aged newsprint paper (`#e7e4d8`); oxblood (`#990000`) as the single emphasis accent; ochre (`#b88a2e`) as warm secondary; meaning-bearing content-type hues. Never clinical white, never the SA flag palette, no gradients-as-decoration, no rounded corners.
- **Voice:** direct, plain, dignified, unsentimental. No exclamation marks, no emoji, no hype.
- **Differentiators:** the record + provenance + chronology components: `RecordHeader`, `IndexTable`, `ProvenanceBlock`, `Citation`, `ImageCredit`, `ContentWarning`, `Timeline`, `RelatedList`, `MetadataBlock`.
- **Registers:** reference (calm, dense, authoritative) and editorial (expressive, image-led) from one system.
- Accessibility (WCAG 2.2 AA/AAA) and performance for mid-range Android on metered data are design requirements, not cleanup.

If the user invokes this skill without any other guidance, ask them what they want to build or design, ask some questions, and act as an expert designer who outputs HTML artifacts _or_ production code, depending on the need.
