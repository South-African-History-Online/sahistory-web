# SAHO SEO Release Blueprint — 2026-06-14

Merges the GSC Performance analysis (`2026-06-14-gsc-performance-findings.md`) with the
8-agent codebase audit. Source of audit: workflow `saho-seo-release-blueprint`.

## The core story (GSC + code reconcile perfectly)

GSC said: 297M impressions, ~1% blended CTR, head terms rank pos 6-9 but earn 0.1-0.3% CTR
(10-50x below benchmark), and `Search appearance.csv` shows **zero rich results**.

The audit found **why**: structured data is NOT missing (correction to the initial premise) —
a complete custom JSON-LD system (13 builders) is live in `saho_tools`. But the SERP-snippet
plumbing is **broken in exactly the ways that crater CTR**:

1. **Meta/OG descriptions are full untruncated body dumps** (bio ~15,000 chars) OR empty on ~58k
   nodes — so SERP snippets are garbage or absent across all 82k pages.
2. **BreadcrumbList JSON-LD points at 404/301 URLs** (/event, /biography → 404) — Google voids
   breadcrumb rich results on ~80k pages.
3. **og:image broken** (concatenated token chain renders empty; false hardcoded 1090x1090 dims);
   **Twitter Cards emit nothing** despite the submodule being enabled.
4. **Front page** shares as "Home Page", og:type=article, no image.

=> The fixes are mostly **S/M-effort config + small PHP/Twig edits**, not a rebuild, yet the blast
radius is the entire index. Lifting blended CTR 1% → 2% ~doubles organic traffic. This is the release.

## Phase 1 — Sitewide snippet + structured-data correctness (touches all ~82k pages)
| Item | Impact | Effort |
|---|---|---|
| Cap description/og length (tag_trim_maxlength 160/200); drop duplicated [node:body] | high | S |
| Fix BreadcrumbSchemaBuilder.php level-2 URLs → real 200 landing pages | high | S |
| Per-bundle metatag overrides using real description fields (fixes ~58k empty-desc nodes) | high | M |
| Fix og:image to first-non-empty per bundle; remove false 1090x1090 dims; banner fallback | high | M |
| Add Twitter Card tags (summary_large_image) to node + front defaults | high | S |
| Add WebSite + SearchAction schema (Google sitelinks search box) | medium | S |
| Fix front-page OG (title/type/image) | medium | S |

## Phase 2 — Activate dormant interlinking + image sitemap (engines already exist)
| Item | Impact | Effort |
|---|---|---|
| Extend saho_suggested_reading to biography/event/place/image (~48k orphaned nodes) | high | M |
| Fix node--event.html.twig to re-print stripped related fields (un-orphan 17.6k events) | high | S |
| Enable include_images on image-bearing bundles (expose 18k images to Google Images) | high | S |
| Make breadcrumb deterministic/crawler-safe (drop HTTP_REFERER variance) | medium | M |
| Give image nodes outbound interlinking via node--image.html.twig | medium | M |

## Phase 3 — Image-detail performance + image-SEO quality
| Item | Impact | Effort |
|---|---|---|
| Image-detail display: WebP image style + loading=eager (it's the LCP element, currently lazy original) | high | S |
| Junk-alt audit drush command + upload-time guard | medium | M |
| AI alt-text backfill for 97k media + ~15k junk alts via AIditor/Ollama vision (gated) | high | XL |

## Phase 4 — Thin-content / crawl-budget / canonicalization
| Item | Impact | Effort |
|---|---|---|
| Re-export robotstxt config (URGENT — cim would delete live AI-crawler allow-list) | high | S |
| Noindex + de-sitemap 14,503 orphan field_feature_tag term pages | high | M |
| Self-canonical + robots on view/listing routes (strip ?title=/?sort_by= dupes) | high | M |
| Populate redirect_domain (www→apex safety net under Cloudflare) | medium | S |

## Phase 5 — Performance round 3 + structured-data hardening
| Item | Impact | Effort |
|---|---|---|
| Defer GA + migrate off DEAD UA property (UA-10590438-8 stopped processing 2024-07-01) | high | M |
| Replace offsetHeight reflow (citation.js:604, sharing.js:266) with double rAF (~764ms INP) | medium | S |
| Featured Biography thumbnail 160px WebP instead of 400px | medium | S |
| PHPUnit kernel tests for the 13 schema builders | medium | M |
| BiographySchemaBuilder field_dob/field_dod fallback (triples birthDate coverage) | medium | S |
| Make seckit CSP enforcing; drop unsafe-inline/eval (fixes stuck Best Practices 77) | medium | L |

## Quick wins (S effort, ship this week)
- tag_trim_maxlength cap (config only)
- Re-export robotstxt config (prevents AI-rule deletion on next cim) — **do before any prod cim**
- Fix BreadcrumbSchemaBuilder URLs (one PHP edit, restores breadcrumb eligibility on 80k pages)
- Twitter Cards + front-page OG (config only)
- Fix node--event.html.twig (Twig only)
- Image-detail WebP style + eager (one config change)
- offsetHeight → double rAF
- Enable include_images on image bundles

## Blocked on GSC data still to pull
- **Page Indexing report** (indexed vs excluded of 82k; the 14.5k thin tag pages; gates Archive Factory)
- Rich Results / Enhancements (which schema types Google validated; confirms breadcrumb errors at scale)
- Sitemaps "last read" date (served lastmod is ~4 months stale → prod cron may not be regenerating)
- CWV field report + GA4 organic landing pages

## Open risks
- **robotstxt config drift** — a prod `drush cim` before re-export silently deletes the AI/LLM
  crawler allow-list. Most urgent latent regression.
- Stale sitemap lastmod (~4mo) → prod cron sitemap regen likely not completing for 82k nodes;
  enabling image sitemaps increases cost. Verify cron job / move to CLI batch.
- **Archive Factory transport is code-complete but UNVALIDATED end-to-end**: only dry-run against
  target=local, local JSON:API is read_only, service account unconfirmed, v1 mirrors scalar fields
  only (drops images/taxonomy/field_feature_parent). Pushing thin, imageless, orphaned, schema-less
  nodes to prod at 30k+ scale = index bloat + crawl-budget dilution. **Do NOT enable prod ship**
  until: staging validation + relationship-shipping (v2 writer) + a pre-publish SEO quality gate.
- AI alt-text backfill risks hallucinated alts polluting accessibility AND schema captions
  (ImageSchemaBuilder reuses field_image alt) — gate behind manual QA + node-title grounding.
- GA migration assumes G-W91HQEGETK is the correct GA4 property — confirm with GA admin first.
- saho_performance module disabled; its analytics-optimization.js has PHP-style TRUE/FALSE literals
  that throw ReferenceError — must fix JS before enabling as a GA lazy-loader.

## Archive Factory verdict (answers the user's original question)
The pipeline exists (harvester + AIditor + Ollama + a ship command), but it is NOT production-ready.
The right sequence is: **fix the SEO foundation first (Phases 1-4), THEN harden the transport with a
pre-publish quality gate that enforces description + og:image + interlinking + alt text BEFORE a node
is allowed to publish.** AIditor/Ollama is the correct automation layer for that gate. Scaling content
into the current broken-snippet foundation would multiply the CTR problem, not fix it.
