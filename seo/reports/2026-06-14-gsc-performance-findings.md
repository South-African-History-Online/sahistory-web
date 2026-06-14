# SAHO GSC Performance Findings — 2026-06-14

Source: GSC Performance export, Search type = Web, **Last 16 months**.
Files: `seo/inbox/gsc-performance/` (Chart, Countries, Devices, Filters, Pages, Queries, Search appearance).
Analyzer: `seo/scripts/analyze_gsc.py`.

## Headline numbers (16 months)
- **~297M impressions, ~2.9M clicks, blended CTR ~0.98%.**
- Mobile: 1,920,038 clicks / 137.3M impr / **1.4% CTR** / pos **6.71**.
- Desktop: 911,688 clicks / 152.3M impr / **0.6% CTR** / pos **11.17** (ranks much worse than mobile).
- Tablet: 64,088 / 7.2M / 0.9% / 8.23.
- Top market: South Africa (115M impr, 1.88% CTR, pos 7.85). US = 89M impr but 0.27% CTR.

## #1 finding: severe CTR anomaly on already-ranking head terms
SAHO ranks page 1 (pos 6-9) for huge head terms but earns 10-50x BELOW expected CTR:
- zulu — 561k impr, pos 8.3, **0.1% CTR**
- shaka zulu — 553k impr, pos 8.6, 0.3%
- winnie mandela — 488k impr, pos 6.7, 0.2%
- cecil rhodes — 333k impr, pos 6.6, 0.2%
- nelson mandela — 261k impr, pos 5.9, 0.1%
- 261 striking-distance queries total (pos 4-20, impr>=5k).

A pos-6 result should pull ~4-5% CTR; SAHO gets 0.1-0.3%. This is structural, not ranking decay.

### Root causes (high confidence)
1. **No rich results.** `Search appearance.csv`: only Translated results (3.4M impr), 80 video impr, 4 product snippets across 297M impressions. ZERO Article/Person/Event/breadcrumb/sitelinks enhancements. Confirms the no-schema.org gap. Competitors (Wikipedia/Britannica) outrank visually with rich snippets + knowledge panels.
2. **Weak/generic title tags + meta descriptions** not earning the click.

### Why it's the top lever
Impressions already exist. Blended CTR 1% -> 2% ~= DOUBLE organic traffic with no new content/rankings. Maps directly onto the structured-data + metatag release work.

## Content-type performance (top 1000 pages)
| type | clicks | impressions | CTR% |
|---|---|---|---|
| article | 1,108,626 | 102.7M | 1.08 |
| biography | 232,994 | 31.3M | **0.74** |
| sites/files (PDFs) | 286,874 | 23.1M | 1.24 |
| place | 86,320 | 14.8M | 0.58 |
| event | 110,043 | 6.1M | 1.81 |
| archive | 53,029 | 3.25M | 1.63 |

- **Biographies**: worst CTR of the big types AND most schema-ready (Person). Richest seam.
- `/sites/default/files/archive-files/*.pdf` ranking with 1M+ impressions each — raw PDFs in index (PDF SEO + possible canonical/leak review).

## Highest-value individual pages (imp>=50k, CTR<1%) — 423 of top 1000
Targets for title/meta + schema rewrite (impr / CTR / pos):
- /article/how-did-nazis-construct-aryan... 3.89M / 0.59% / 6.9
- /people/shaka-zulu 2.38M / 0.73% / 7.3
- /article/zulu 1.77M / 0.30% / 8.5
- /people/mohandas-karamchand-gandhi 1.64M / 0.24% / 10.9
- /people/nelson-rolihlahla-mandela 1.37M / 0.19% / 13.3
- /place/gqeberha-was-known-port-eliza... 1.37M / 0.20% / 6.3

## Still needed (not in this export)
- **Page Indexing report** (Indexing -> Pages) — how many of 82k nodes are indexed vs excluded, and why. Gates the Archive Factory decision.
- Core Web Vitals report (field data).
- GA4 organic landing pages (engagement/conversion side).
