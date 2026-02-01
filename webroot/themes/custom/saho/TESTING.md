# SAHO Theme Testing Guide

Comprehensive testing documentation for the SAHO theme modernization project.

## Quick Test Commands

```bash
# Run all frontend tests
cd webroot/themes/custom/saho
npm run biome:check     # JavaScript linting
npm run stylint-fix     # SCSS linting
npm run production      # Production build

# Drupal cache clear
ddev drush cr

# Check configuration status
ddev drush config:status
```

## 1. Visual Regression Testing

### Critical Pages to Test

Test these pages after any component or styling changes:

#### Homepage & Landing Pages
- [ ] Homepage (`/`)
- [ ] Politics & Society (`/politics-society`)
- [ ] Struggle & Resistance (`/struggle-resistance`)
- [ ] Arts & Culture (`/arts-culture`)
- [ ] Economy & Infrastructure (`/economy-infrastructure`)
- [ ] Archives (`/archive`)

#### Content Pages
- [ ] Article page (any `/node/[id]` article)
- [ ] Biography page (any biography node)
- [ ] Collection page (any collection node)
- [ ] Timeline page (`/timeline`)
- [ ] This Day in History (`/tdih`)

#### Search & Listings
- [ ] Global search results (`/search/site/[query]`)
- [ ] Biography browse (`/biographies`)
- [ ] People browse (`/people`)
- [ ] Taxonomy term pages (any category)

#### Responsive Breakpoints
Test all critical pages at these viewport sizes:
- [ ] Mobile (320px × 568px) - iPhone SE
- [ ] Tablet (768px × 1024px) - iPad
- [ ] Desktop (1366px × 768px) - Standard laptop
- [ ] Large desktop (1920px × 1080px) - Full HD

### Manual Visual Testing Checklist

For each page, verify:

#### Layout
- [ ] Cards display in proper grid (2-3-4 columns responsive)
- [ ] Images load with correct aspect ratios
- [ ] No layout shift during page load (CLS)
- [ ] No horizontal scrolling on mobile
- [ ] Footer displays correctly

#### Components
- [ ] **saho-card**: Rounded corners, hover effects, consistent spacing
- [ ] **saho-button**: Proper styling, icon alignment, hover states
- [ ] **saho-badge**: Correct colors, positioning on cards
- [ ] **saho-metadata**: Icons display, proper separators
- [ ] **saho-section-header**: Accent bars, heading hierarchy

#### Interactive Elements
- [ ] Buttons change on hover
- [ ] Card links are clickable
- [ ] Images zoom slightly on hover (where applicable)
- [ ] Focus indicators visible when tabbing
- [ ] Modals open/close correctly

#### Typography
- [ ] Headings use correct hierarchy (h1 → h6)
- [ ] Body text is readable (16px minimum on mobile)
- [ ] Line heights prevent text crowding
- [ ] No orphaned text or widows

#### Colors
- [ ] Heritage red (#8B1513) used consistently
- [ ] Muted gold, slate blue variants correct
- [ ] Text contrast meets WCAG AA (4.5:1 minimum)
- [ ] Link colors distinguishable from body text

### Browser Testing Matrix

Test on:
- [ ] **Chrome** (latest)
- [ ] **Firefox** (latest)
- [ ] **Safari** (latest on macOS/iOS)
- [ ] **Edge** (latest)
- [ ] **Mobile Safari** (iOS 15+)
- [ ] **Chrome Mobile** (Android)

### Automated Visual Regression (Optional)

To set up BackstopJS for automated visual regression testing:

```bash
# Install BackstopJS
npm install --save-dev backstopjs

# Initialize configuration
npx backstop init

# Capture reference screenshots
ddev start
npx backstop reference

# Run visual regression tests
npx backstop test
```

**Note**: BackstopJS configuration template available on request.

---

## 2. Accessibility Testing

### Automated Tools

#### Axe DevTools (Browser Extension)
1. Install [Axe DevTools](https://www.deque.com/axe/devtools/) for Chrome/Firefox
2. Open DevTools → Axe tab
3. Click "Scan ALL of my page"
4. Fix all Critical and Serious issues
5. Review Moderate and Minor issues

#### WAVE (Browser Extension)
1. Install [WAVE](https://wave.webaim.org/extension/) extension
2. Navigate to page
3. Click WAVE icon
4. Review errors (red), alerts (yellow), features (green)

#### Lighthouse (Chrome DevTools)
```bash
# Run Lighthouse audit
# Chrome DevTools → Lighthouse → Accessibility
# Target score: 95+
```

### Manual Accessibility Checklist

#### Keyboard Navigation
- [ ] Tab through all interactive elements
- [ ] Focus indicators visible on all focusable elements
- [ ] No keyboard traps (can escape modals, menus)
- [ ] Skip to main content link works
- [ ] Dropdown menus accessible via keyboard

#### Screen Reader Testing
Test with NVDA (Windows) or VoiceOver (macOS):
- [ ] Page title announces correctly
- [ ] Headings announce with proper levels
- [ ] Images have meaningful alt text (or empty for decorative)
- [ ] Links have descriptive text (no "click here")
- [ ] Form labels associated with inputs
- [ ] Error messages announce

#### ARIA & Semantics
- [ ] Landmarks: `<header>`, `<nav>`, `<main>`, `<footer>`
- [ ] Headings follow logical order (no skipped levels)
- [ ] Lists use `<ul>`, `<ol>`, `<li>`
- [ ] Buttons use `<button>` (not `<div>` or `<span>`)
- [ ] Links use `<a>` with `href`
- [ ] Images with decorative icons have `aria-hidden="true"`

#### Color & Contrast
- [ ] Text contrast ≥ 4.5:1 (normal text)
- [ ] Text contrast ≥ 3:1 (large text 18pt+)
- [ ] Focus indicators ≥ 3:1 contrast with background
- [ ] Information not conveyed by color alone
- [ ] Links distinguishable without color

#### Forms
- [ ] All inputs have associated `<label>`
- [ ] Required fields marked with `required` or `aria-required`
- [ ] Error messages linked with `aria-describedby`
- [ ] Fieldsets group related inputs
- [ ] Autocomplete attributes on user data fields

### WCAG 2.1 AA Compliance Checklist

**Perceivable**
- [ ] 1.1.1 Non-text Content (alt text)
- [ ] 1.3.1 Info and Relationships (semantic HTML)
- [ ] 1.4.3 Contrast Minimum (4.5:1)
- [ ] 1.4.4 Resize Text (up to 200%)

**Operable**
- [ ] 2.1.1 Keyboard (all functionality)
- [ ] 2.1.2 No Keyboard Trap
- [ ] 2.4.1 Bypass Blocks (skip links)
- [ ] 2.4.2 Page Titled
- [ ] 2.4.3 Focus Order
- [ ] 2.4.7 Focus Visible

**Understandable**
- [ ] 3.1.1 Language of Page
- [ ] 3.2.1 On Focus (no unexpected changes)
- [ ] 3.3.1 Error Identification
- [ ] 3.3.2 Labels or Instructions

**Robust**
- [ ] 4.1.1 Parsing (valid HTML)
- [ ] 4.1.2 Name, Role, Value (ARIA)

---

## 3. Performance Testing

### Core Web Vitals Targets

- **LCP** (Largest Contentful Paint): < 2.5s
- **FID** (First Input Delay): < 100ms
- **CLS** (Cumulative Layout Shift): < 0.1

### Lighthouse Performance Audit

```bash
# Run Lighthouse in Chrome DevTools
# Target scores:
# - Performance: ≥ 90
# - Accessibility: ≥ 95
# - Best Practices: ≥ 90
# - SEO: ≥ 95
```

#### Pages to Audit
- [ ] Homepage (most critical)
- [ ] Landing page (Politics & Society)
- [ ] Article page
- [ ] Biography page
- [ ] Search results page

### Bundle Analysis

```bash
# Analyze bundle size
npm run analyze

# Check CSS/JS sizes
npm run size
```

**Current Sizes (Target)**
- `main.style.css`: 464 KiB → 350 KiB (target)
- `main.script.js`: 77 KiB → 70 KiB (target)

### Performance Checklist

#### Images
- [ ] All images lazy loaded (except above-fold)
- [ ] Responsive srcset for images > 320px
- [ ] WebP format where supported
- [ ] Aspect ratios defined (prevent CLS)
- [ ] No oversized images (max 1920px width)

#### CSS
- [ ] CSS minified in production
- [ ] PurgeCSS removes unused styles
- [ ] Critical CSS inlined (if applicable)
- [ ] CSS aggregation enabled in Drupal

#### JavaScript
- [ ] JS minified in production
- [ ] JS aggregation enabled in Drupal
- [ ] Scripts loaded with defer/async (where appropriate)
- [ ] No jQuery dependencies for theme JS

#### Drupal
- [ ] Page caching enabled
- [ ] Block caching enabled
- [ ] Views caching enabled (where appropriate)
- [ ] CSS/JS aggregation enabled
- [ ] BigPipe enabled (if applicable)

#### Network
- [ ] Gzip/Brotli compression enabled
- [ ] HTTP/2 enabled
- [ ] CDN configured (if applicable)
- [ ] Browser caching headers set

### Performance Testing Tools

#### WebPageTest
```
https://webpagetest.org
Location: South Africa (closest to target audience)
Connection: Mobile 4G
Metrics: TTFB, LCP, CLS
```

#### Chrome DevTools Performance Panel
1. Open DevTools → Performance
2. Click Record
3. Interact with page
4. Stop recording
5. Analyze:
   - Long tasks (> 50ms)
   - Layout shifts
   - JavaScript execution time

---

## 4. Cross-Browser Testing

### Desktop Browsers

#### Chrome (Latest)
- [ ] Homepage renders correctly
- [ ] Cards display properly
- [ ] Buttons styled correctly
- [ ] Images load with lazy loading
- [ ] No console errors

#### Firefox (Latest)
- [ ] Same as Chrome
- [ ] Test flexbox/grid layouts
- [ ] Verify CSS custom properties

#### Safari (Latest on macOS)
- [ ] Same as Chrome
- [ ] Test WebP fallbacks
- [ ] Verify -webkit- prefixes work

#### Edge (Latest)
- [ ] Same as Chrome (Chromium-based)

### Mobile Browsers

#### Mobile Safari (iOS 15+)
- [ ] Touch interactions work
- [ ] Viewport meta tag correct (no zoom on input)
- [ ] Fixed elements don't overlap content
- [ ] Safe area insets respected

#### Chrome Mobile (Android)
- [ ] Same as Mobile Safari
- [ ] Test on various screen sizes

### Testing Tools

#### BrowserStack (Recommended)
```
https://www.browserstack.com
Test across real devices and browsers
Free trial available
```

#### Responsive Design Mode
```bash
# Chrome DevTools → Toggle device toolbar (Cmd+Shift+M)
# Firefox DevTools → Responsive Design Mode (Cmd+Option+M)
```

### Browser-Specific Issues to Check

#### Safari
- [ ] Flexbox gap property (use fallback if needed)
- [ ] CSS Grid support
- [ ] Date input styling
- [ ] WebP image support (with JPEG fallback)

#### Firefox
- [ ] Scrollbar styling (vendor prefixes)
- [ ] aspect-ratio support (fallback for older versions)

#### Internet Explorer 11 (if required)
- [ ] CSS Grid fallback
- [ ] Flexbox with -ms- prefixes
- [ ] No ES6+ JavaScript without transpilation

---

## 5. Regression Testing Workflow

### Before Making Changes

1. **Capture Baseline**
   ```bash
   # Take screenshots of critical pages
   # Document current behavior
   # Note any existing issues
   ```

2. **Run Tests**
   ```bash
   npm run biome:check
   npm run stylint-fix
   npm run production
   ```

### After Making Changes

1. **Code Quality**
   ```bash
   npm run biome:check  # Should pass
   npm run stylint-fix  # Should pass
   npm run production   # Should compile
   ```

2. **Visual Check**
   - Open all critical pages
   - Compare to baseline screenshots
   - Verify no unintended changes

3. **Functionality Check**
   - Click all buttons
   - Test all links
   - Verify forms work
   - Check mobile views

4. **Performance Check**
   ```bash
   npm run size  # Compare bundle sizes
   # Run Lighthouse audit
   # Verify no regression in scores
   ```

### Commit Checklist

Before committing:
- [ ] All linting tests pass
- [ ] Production build succeeds
- [ ] Visual regression checked
- [ ] No console errors
- [ ] Mobile view tested
- [ ] Accessibility verified (no new issues)

---

## 6. Continuous Integration

### GitHub Actions Workflows

#### Frontend CI (`.github/workflows/frontend-ci.yml`)
Runs on every PR and push to main:
```yaml
- Checkout code
- Install Node.js dependencies
- Run Biome linting
- Run Stylelint
- Build production assets
```

**Status**: ✅ Currently passing

### Local CI Simulation

Run the same checks locally before pushing:
```bash
cd webroot/themes/custom/saho
npm ci
npm run biome:check
npm run stylint-fix
npm run production
```

---

## 7. Testing Checklist for Component Changes

When adding or modifying components, verify:

### Component Files
- [ ] `.component.yml` schema is valid
- [ ] `.twig` template renders correctly
- [ ] `.css` styles apply properly
- [ ] README.md documentation complete

### Component Integration
- [ ] Component works in isolation
- [ ] Component works within cards
- [ ] Component works in grids
- [ ] Props validation works
- [ ] Default values apply correctly

### Backwards Compatibility
- [ ] Legacy classes still work (with `@extend`)
- [ ] Existing templates not broken
- [ ] CSS cascade doesn't conflict
- [ ] JavaScript doesn't throw errors

### Performance
- [ ] No significant CSS size increase
- [ ] No layout shift (CLS)
- [ ] Images lazy load
- [ ] Animations use CSS transitions (not JS)

---

## 8. Known Issues & Warnings

### Expected Warnings

#### Stylelint
- **deprecated keyword "break-word"**: Legacy Bootstrap code in `_bootswatch.scss`
- **deprecated property "clip"**: Legacy accessibility patterns
- **shorthand property overrides**: Legacy modal styles
- **keyframes-name-pattern**: Legacy animation naming

These are pre-existing warnings in legacy code and do not block the build.

### Performance Notes
- Current CSS size: 464 KiB (target: 350 KiB)
  - Reduction pending full template migration to SDC components
  - Legacy `_bootswatch.scss` refactoring will reduce ~110 KiB

---

## 9. Testing Best Practices

1. **Test Early, Test Often**: Run linting before committing
2. **Mobile First**: Always test mobile view first
3. **Real Devices**: Test on real phones/tablets when possible
4. **Accessibility**: Test with keyboard and screen reader
5. **Performance**: Monitor bundle sizes after changes
6. **Documentation**: Update this file when adding new tests

---

## 10. Resources

### Tools
- [Axe DevTools](https://www.deque.com/axe/devtools/) - Accessibility testing
- [WAVE](https://wave.webaim.org/extension/) - Accessibility evaluation
- [Lighthouse](https://developers.google.com/web/tools/lighthouse) - Performance auditing
- [BrowserStack](https://www.browserstack.com) - Cross-browser testing
- [WebPageTest](https://webpagetest.org) - Performance testing

### Documentation
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [Core Web Vitals](https://web.dev/vitals/)
- [Drupal Performance](https://www.drupal.org/docs/managing-performance)
- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.3/)

### SAHO Project
- [GitHub Repository](https://github.com/South-African-History-Online/sahistory-web)
- [Component README](components/README.md) - Component library documentation
- [CLAUDE.md](../../../CLAUDE.md) - AI assistant development guide
