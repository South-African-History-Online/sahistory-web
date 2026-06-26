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
| W0 | Restore GA4 measurement | TODO (ops: create property + swap ID in `google_analytics.settings.yml`) | - |
| W1 | Internal `page_cache` safety net | DONE - `core.extension` `page_cache:1` | #327 |
| W1 | `antibot` on forms (no CAPTCHA) | DONE - `core.extension` `antibot:1` | - |
| W1 | `allow_insecure_derivatives:false` | DEFERRED - site emits itok-less derivative URLs; needs a precursor that tokenises URLs + staging verification | - |
| W1 | Cloudflare URL-purge bridge | TODO | #327 |
| W1 | Confirm CF Bot Fight Mode + Brotli + cache rule | TODO (ops) | #327 |
| W2 | N+1 fix in `TopReadContentBlock` | DONE | - |
| W2 | Views `type:none` -> `tag` (12 views) | TODO | - |
| W2 | Cacheability-leak audit | TODO | - |
| W3 | Skip link + `aria-current` | DONE (verified live) | - |
| W3 | Speculation Rules (instant nav) | DONE - prefetch-only (no prerender, avoids GA double-count) | #280 |
| W3 | Revive dead LCP image preload | DONE - was in `hook_page_attachments()` which themes never run; moved to `_alter` | - |
| W3 | Wire `responsive_image` into image fields | TODO - styles exist (`responsive_image.styles.narrow/wide`), zero displays use them; biggest mobile-LCP win | - |
| W3 | axe/pa11y in CI | TODO | - |
| W3 | PWA (manifest + install SW) | DEFERRED to Phase 2 - Speculation Rules covers ~80% of perceived speed now | #280 |
| W4 | Interlink ~48k orphaned nodes / un-orphan 17.6k events | TODO - the documented #1 engagement lever | #139 |
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
