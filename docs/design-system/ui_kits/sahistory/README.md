# SAHO Website — UI Kit

A high-fidelity recreation of **sahistory.org.za** under the **2027 "Open Record" standard**. These screens compose the design-system primitives (no re-implementation) on the warm paper / warm ink material palette with Fraunces + IBM Plex.

## Screens
- **`index.html`** — interactive shell. Click the header nav or in-page links to move between screens (hash-routed).
- **`HomeScreen.jsx`** — a curated index into the living archive (current feature, *This day in history*, a browse index, recent & significant additions). Not a marketing splash.
- **`BiographyScreen.jsx`** — the workhorse template: portrait + masthead, long sourced article with cross-links and a pull-quote, a vital-record metadata block, a life-chronology rail, related cross-references, and a visible **ProvenanceBlock + Citation**.
- **`TimelineScreen.jsx`** — the filterable chronology as a first-class navigational surface.
- **`SearchScreen.jsx`** — the forgiving, prominent front door with facets and result list.
- **`Chrome.jsx`** — shared `SiteHeader`, `SiteFooter`, `Wordmark`, `AccentBar`.

## Registers
The kit demonstrates both registers from one system: the **reference register** (Biography, Timeline, Search — calm, dense, authoritative) and the **editorial register** (the Home feature — more expressive, image-led) drawing from the same type and colour discipline.

## Notes / honest gaps
- Portraits use the bundled `default-portrait.svg` placeholder under the archival **duotone** treatment. Drop real archival photography (with credit + source) into the `ImageCredit` components for production fidelity.
- These are cosmetic recreations for design exploration, not production Drupal/Twig templates.
