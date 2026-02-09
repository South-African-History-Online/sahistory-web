# SAHO Project Assessment: Security, Frontend & Refactoring Plan

**Date:** February 2026
**Scope:** Security audit, frontend improvements, DRY refactoring plan
**Drupal Version:** 11.1.7 | **PHP:** 8.3.10 | **Theme:** Radix + Bootstrap 5

---

## Table of Contents

1. [Security Assessment](#1-security-assessment)
2. [Frontend Improvements](#2-frontend-improvements)
3. [DRY Refactoring Plan](#3-dry-refactoring-plan)
4. [Priority Matrix](#4-priority-matrix)

---

## 1. Security Assessment

### 1.1 Critical Issues (Fixed in This PR)

#### ✅ FIXED: Information Disclosure via Exception Messages
- **Files:** `saho_featured_articles/src/Controller/FeaturedArticlesController.php`
- **Issue:** Exception messages (`$e->getMessage()`) were exposed directly in JSON API responses, potentially leaking internal system paths, database structure, and stack trace information.
- **Fix:** Replaced with generic user-facing error messages; exceptions now logged server-side via `\Drupal::logger()`.

#### ✅ FIXED: Insecure Serialization of Filter Data
- **File:** `saho_statistics/src/EventSubscriber/SearchQueryTracker.php`
- **Issue:** `serialize()` was used to store search filter data containing user-supplied values. PHP deserialization of user-controlled data can lead to object injection attacks.
- **Fix:** Replaced `serialize()` with `json_encode()` which is safe from object injection.

#### ✅ FIXED: Debug Endpoint Access Control Bug
- **File:** `saho_featured_articles/src/Controller/FeaturedArticlesController.php`
- **Issue:** Operator precedence bug: `!$this->config(...)->get('error_level') === 'verbose'` — the `!` negates the config value to `false` before comparison, meaning the debug endpoint was **always accessible** regardless of logging level.
- **Fix:** Changed to `$this->config(...)->get('error_level') !== 'verbose'` for correct logic.

#### ✅ FIXED: XSS via Unsanitized HTML Rendering
- **Files:** `saho-timeline-svelte/src/lib/Timeline.svelte`, `ResearchTimeline.svelte`
- **Issue:** `{@html selectedEvent.body}` rendered API content without sanitization, allowing stored XSS if malicious HTML was present in event body content.
- **Fix:** Added DOMPurify sanitization: `{@html DOMPurify.sanitize(selectedEvent.body)}`.

#### ✅ FIXED: Wildcard CORS Configuration
- **File:** `saho-timeline-svelte/server.js`
- **Issue:** `Access-Control-Allow-Origin: *` allowed requests from any domain, and overly permissive methods (PUT, DELETE) were allowed.
- **Fix:** Restricted to configurable allowed origins (defaults to sahistory.org.za) and limited methods to GET/OPTIONS.

#### ✅ FIXED: Disabled Caching in Production Code
- **File:** `saho_timeline/src/Service/TimelineEventService.php`
- **Issue:** Cache was disabled via `if (FALSE && ...)` with debug comments left in. This caused all timeline API requests to bypass cache and hit the database, creating significant performance impact.
- **Fix:** Re-enabled caching by removing the debug bypass.

#### ✅ FIXED: Duplicate CSS Loading
- **File:** `webroot/themes/custom/saho/saho.libraries.yml`
- **Issue:** `url-truncation.css` was loaded both in the global `style` library and the specific `url.truncation` library, causing double loading.
- **Fix:** Removed from global `style` library; it is only loaded when the `url.truncation` library is attached.

### 1.2 Remaining Security Recommendations

These items require further discussion or larger refactoring effort:

| # | Issue | Severity | File | Recommendation |
|---|-------|----------|------|----------------|
| 1 | Raw SQL without parameterization | Medium | `db_fixes/db_fixes.install` | Use parameterized queries with `:placeholders` even for hardcoded values |
| 2 | Missing rate limiting on timeline API proxy | Medium | `saho-timeline-svelte/server.js` | Add `express-rate-limit` (e.g., 100 req/min per IP) |
| 3 | Hardcoded DDEV URLs in API client | Low | `saho-timeline-svelte/src/lib/api.js` | Use environment variables exclusively for all API URLs |
| 4 | No audit logging for admin destructive actions | Medium | `saho_timeline/Form/TimelineMigrationForm.php` | Add watchdog logging before DB truncate operations |
| 5 | sessionStorage access not error-wrapped | Low | `saho theme js/sidebar-tabs.js` | Wrap in try-catch for private browsing mode compatibility |
| 6 | Default Solr credentials in DDEV config | Low | `.ddev/docker-compose.solr.yaml` | Override credentials in production environment |

---

## 2. Frontend Improvements

### 2.1 CSS Architecture Issues

#### Hardcoded Colors (100+ instances)
The same color values are repeated throughout CSS files without CSS custom properties:

| Color | Usage | Count | Files |
|-------|-------|-------|-------|
| `#900` | SAHO primary red | 40+ | sidebar-tabs, sidebar-accordion, url-truncation, featured-content |
| `#600` | Darker red (hover) | 15+ | Same files |
| `#f8fafc` | Light gray background | 20+ | Same files |
| `#f1f5f9` | Lighter gray | 10+ | Same files |

**Recommendation:** Define CSS custom properties in the theme's `:root` or a dedicated variables file. Note that a `docs/COLOR-SYSTEM.md` and `docs/DESIGN-TOKENS.md` already exist — the implementation should follow these documented standards.

#### `!important` Overuse (11+ instances)
Found primarily in `sidebar-tabs.css` and `sidebar-accordion.css`, used defensively against Bootstrap cascade conflicts. These should be addressed by increasing selector specificity instead.

#### Inconsistent Naming Convention
CSS classes mix naming approaches: `.saho-accordion-button`, `.saho-tab-pane`, `.views-row`. Consider standardizing on BEM (`.saho-accordion__button--collapsed`) for custom components.

### 2.2 JavaScript Issues

#### Missing Error Handling
- `sessionStorage` access in `sidebar-tabs.js` can throw in private browsing mode
- No try-catch blocks around DOM operations in `featured-content.js`
- Silent failures in clipboard API fallback in `url-truncation.js`

#### Hardcoded Strings
- `table-scroll.js` contains `'← Swipe to see more →'` — should use `Drupal.t()` for translation

#### Dead Code
- `webpack.mix.js` still present alongside `vite.config.js` — remove the unused build config

### 2.3 Library Configuration
- Page-specific CSS bundles (`landing.pages`, `search.results`, `article.layout`) are declared in `saho.libraries.yml` but never conditionally attached in PHP code

---

## 3. DRY Refactoring Plan

### Phase 1: CSS Variables & Design Tokens (Effort: 4-6 hours)

Extract hardcoded values into CSS custom properties:

```css
/* Create/update: src/scss/abstracts/_variables.scss */
:root {
  --saho-color-primary: #900;
  --saho-color-primary-dark: #600;
  --saho-color-bg-light: #f8fafc;
  --saho-color-bg-lighter: #f1f5f9;
  --saho-color-text: #1e293b;
  --saho-color-text-muted: #64748b;
  --saho-color-border: #e2e8f0;
  --saho-transition-fast: 0.15s ease;
  --saho-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --saho-shadow-hover: 0 2px 4px rgba(0, 0, 0, 0.05);
}
```

Then replace all hardcoded values across CSS files with `var(--saho-color-primary)` etc.

### Phase 2: Shared CSS Patterns (Effort: 3-4 hours)

Extract duplicated patterns into reusable utility classes:

```css
/* Common hover effect (appears 8+ times) */
.saho-hover-lift {
  transition: transform var(--saho-transition-fast),
              box-shadow var(--saho-transition-fast);
}
.saho-hover-lift:hover {
  transform: translateY(-1px);
  box-shadow: var(--saho-shadow-hover);
}

/* Common focus outline (appears 6+ times) */
.saho-focus-ring:focus {
  outline: 2px solid var(--saho-color-primary);
  outline-offset: -2px;
}

/* Common .views-row hover (appears 3 times) */
.saho-content-row:hover {
  background-color: var(--saho-color-bg-lighter);
}
```

### Phase 3: JavaScript Utilities (Effort: 2-3 hours)

Create shared utilities to eliminate duplication:

```javascript
// src/js/utils/saho-utils.js
(function (Drupal) {
  'use strict';

  Drupal.sahoUtils = {
    /**
     * Debounce function (currently duplicated in sidebar-tabs.js).
     */
    debounce: function (func, wait) {
      let timeout;
      return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
      };
    },

    /**
     * Safe sessionStorage wrapper (handles private browsing).
     */
    storage: {
      get: function (key) {
        try {
          return sessionStorage.getItem(key);
        } catch (e) {
          return null;
        }
      },
      set: function (key, value) {
        try {
          sessionStorage.setItem(key, value);
        } catch (e) {
          // Silent fail for private browsing.
        }
      }
    }
  };

})(Drupal);
```

### Phase 4: Component Consolidation (Effort: 4-6 hours)

The sidebar tabs and sidebar accordion components share ~60% of their styling and behavior. Refactoring plan:

1. **Extract base styles** into `css/saho-sidebar-base.css`:
   - `.views-row` hover states
   - Content group headings
   - Focus/active state patterns
   - Responsive breakpoints

2. **Extract base behavior** into `js/saho-sidebar-base.js`:
   - Keyboard navigation (arrow keys, Home/End)
   - Content toggle logic
   - Session persistence

3. **Keep variant-specific styles** in their respective files

### Phase 5: Build System Cleanup (Effort: 1-2 hours)

1. Remove `webpack.mix.js` (Vite is the active build system)
2. Implement conditional library attachment for page-specific bundles:

```php
// In saho.theme - hook_preprocess_page()
function saho_preprocess_page(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  if (str_starts_with($route, 'view.search')) {
    $variables['#attached']['library'][] = 'saho/search.results';
  }
}
```

### Phase 6: Module Code Improvements (Effort: 3-4 hours)

1. **Standardize error handling** across all custom module controllers:
   - Always log server-side via `\Drupal::logger()`
   - Never expose internal messages to API consumers
   - Return consistent JSON error format

2. **Extract common patterns** in featured articles module:
   - The JSON response format is repeated in `sectionAjax()` and `mostReadAjax()` — extract to a helper method

---

## 4. Priority Matrix

| Phase | Effort | Impact | Priority | Dependencies |
|-------|--------|--------|----------|--------------|
| Security fixes (this PR) | ✅ Done | 🔴 Critical | P0 | None |
| Phase 1: CSS Variables | 4-6h | 🟡 High | P1 | None |
| Phase 3: JS Utilities | 2-3h | 🟡 High | P1 | None |
| Phase 2: Shared CSS | 3-4h | 🟡 Medium | P2 | Phase 1 |
| Phase 5: Build Cleanup | 1-2h | 🟢 Low | P2 | None |
| Phase 4: Component Merge | 4-6h | 🟡 Medium | P3 | Phase 1, 2, 3 |
| Phase 6: Module Code | 3-4h | 🟡 Medium | P3 | None |
| Rate limiting (server.js) | 1h | 🟡 Medium | P2 | None |
| Audit logging | 2h | 🟡 Medium | P3 | None |

**Total estimated refactoring effort: 20-30 hours across all phases.**

---

## Appendix: Files Changed in This PR

| File | Change |
|------|--------|
| `saho_featured_articles/Controller/FeaturedArticlesController.php` | Removed exception message exposure; fixed debug endpoint access control |
| `saho_statistics/EventSubscriber/SearchQueryTracker.php` | Replaced `serialize()` with `json_encode()` |
| `saho_timeline/Service/TimelineEventService.php` | Re-enabled production caching |
| `saho-timeline-svelte/server.js` | Restricted CORS to allowed origins |
| `saho-timeline-svelte/src/lib/Timeline.svelte` | Added DOMPurify XSS sanitization |
| `saho-timeline-svelte/src/lib/ResearchTimeline.svelte` | Added DOMPurify XSS sanitization |
| `saho-timeline-svelte/package.json` | Added `dompurify` dependency |
| `webroot/themes/custom/saho/saho.libraries.yml` | Removed duplicate CSS loading |
