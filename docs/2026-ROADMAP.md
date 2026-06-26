# SAHO 2026 Roadmap - Engagement, Speed, Caching, Bots & A11y

Single coordinating sheet for the 2026 engagement/performance push. Ties each
workstream to its GitHub issue and records hard sequencing gates. Source of the
full rationale (incl. what was deliberately rejected) is the approved plan.

## Hard gates (read first)
- **Analytics gates engagement.** GA4 is dead (still `UA-10590438-8`, broken since
  2024-07-01). Nothing "engagement" is measurable until GA4 is restored. The
  `drupal/google_analytics ^4.0` module is already installed + cron-wired - this is
  a property creation + config swap, not an integration.
- **Edge-purge gates safe caching.** Cloudflare proxy is ON, so anonymous HTML is
  edge-cached. Without a purge bridge, edits stay stale up to `s-maxage=3600`.
  Cloudflare *tag* purge is Enterprise-only; on the current plan the free pattern is
  *URL* purge on node save, with `s-maxage` as the bounded-staleness backstop.

## Status

| # | Workstream | Status | Issue |
|---|-----------|--------|-------|
| W0 | Restore GA4 measurement | DONE - `account: G-W91HQEGETK` (GA4 property 384995578); local gtag.js loader verified 200; confirm in GA4 Realtime after deploy | - |
| W1 | Internal `page_cache` safety net | DONE - `core.extension` `page_cache:1` | #327 |
| W1 | `antibot` on forms (no CAPTCHA) | DONE - `core.extension` `antibot:1` | - |
| W1 | `allow_insecure_derivatives:false` | DEFERRED - site emits itok-less derivative URLs; needs a precursor that tokenises URLs + staging verification | - |
| W1 | Cloudflare URL-purge bridge | TODO | #327 |
| W1 | Confirm CF Bot Fight Mode + Brotli + cache rule | TODO (ops) | #327 |
| W2 | N+1 fix in `TopReadContentBlock` | DONE | - |
| W2 | Views `type:none` -> `tag` | DONE - 5 entity-derived content listings flipped; per-request/referrer/system views left as none | - |
| W2 | Cacheability-leak audit | TODO | - |
| W3 | Skip link + `aria-current` | DONE (verified live) | - |
| W3 | Speculation Rules (instant nav) | DONE - prefetch-only (no prerender, avoids GA double-count) | #280 |
| W3 | Revive dead LCP image preload | DONE - was in `hook_page_attachments()` which themes never run; moved to `_alter` | - |
| W3 | Wire `responsive_image` into image fields | DEFERRED to staging - styles are sound, but would misalign the revived hero LCP preload and can't be verified on local image routing; apply to non-LCP in-page images | - |
| W3 | axe/pa11y in CI | TODO | - |
| W3 | PWA (manifest + install SW) | DEFERRED to Phase 2 - Speculation Rules covers ~80% of perceived speed now | #280 |
| W4 | Interlink orphaned image nodes | DONE - `image` bundle added to saho_suggested_reading + shared partial in `node--image`; verified 6 internal links on an orphan node | #139 |
| W4 | Extend contextual interlink quality + verify events render | TODO - many image nodes fall back to "Popular Content" (same links); add contextual relations; `event` already renders the block (verify data) | #139 |
| W5 | Docs + issue triage | IN PROGRESS | - |

## Killed (do not re-litigate)
Redis-in-prod (deprecation spam #327) · BigPipe (incompatible with full-page CDN
caching) · `dynamic_page_cache` priority (tiny authed audience) · CAPTCHA/reCAPTCHA
(a11y/payload/privacy) · spammaster-alongside-antibot · quicklink (Speculation Rules
supersedes) · Matomo/Plausible (reuse existing GA4) · "unused fonts" as a perf item
(repo hygiene only).

## Key gotcha discovered (2026-06)
`hook_page_attachments()` is **not invoked for themes** (modules only). The shipped
image-LCP preload lived there and silently never rendered. Theme `#attached` head
work must go in `hook_page_attachments_alter()`. Fixed for the LCP preload; applies
to any future theme-level head attachment.
