# SAHO Design System Modernization Plan

## Vision

Transform SAHO into a modern, maintainable design system using component-based architecture with inline CSS custom properties, following the successful button system consolidation model.

## Current State Analysis

### Completed ✅
- **Buttons**: Fully modernized (3 components, inline tokens, zero dependencies)
- **Basic Components**: 11 components modernized (cards, grids, metadata, etc.)
- **Module CSS**: 19 files modernized with inline tokens

### Needs Modernization ⚠️

#### 1. **Typography System**
**Current Issues**:
- Font families scattered across multiple SCSS files
- Inconsistent heading sizes
- Mixed use of rem/px/em units
- No clear type scale

**Target State**:
- Unified typography component with inline tokens
- Clear type scale (6-8 levels)
- Consistent font stacks
- Responsive typography using clamp()

**Files to Audit**:
```
/src/scss/abstracts/_typography.scss
/src/scss/base/_typography.scss
```

---

#### 2. **Color System**
**Current Issues**:
- Colors defined in multiple places (_variables.scss, _design-tokens.scss, inline)
- SCSS compilation issues with CSS custom properties
- Inconsistent color usage across components

**Target State**:
- Single source of truth for all colors
- Semantic color tokens (primary, secondary, success, error, etc.)
- Component-specific color overrides using inline tokens
- Automatic dark mode support

**Files to Audit**:
```
/src/scss/abstracts/_variables.scss
/src/scss/abstracts/_design-tokens.scss
/src/scss/base/_saho-colors.scss
```

---

#### 3. **Layout & Grid System**
**Current Issues**:
- Bootstrap 5 grid mixed with custom layouts
- Inconsistent spacing scale
- Some components use Flexbox, others use Grid
- Magic numbers (random px values) everywhere

**Target State**:
- Unified spacing scale (8px baseline grid)
- Modern CSS Grid layouts
- Container queries for responsive components
- Consistent max-width containers

**Files to Audit**:
```
/src/scss/base/_layout.scss
/src/scss/components/_grids.scss
Layout Builder overrides
```

---

#### 4. **Form Components**
**Current Issues**:
- Bootstrap form classes mixed with custom styles
- Inconsistent input styling
- No unified focus states
- Accessibility gaps

**Target State**:
- Self-contained form component system
- Consistent input/textarea/select styling
- Clear validation states
- Full keyboard accessibility

**Files to Audit**:
```
/src/scss/components/_forms.scss
Bootstrap form overrides in _bootswatch.scss
```

---

#### 5. **Navigation Components**
**Current Issues**:
- Header navigation is mostly good but has SCSS dependencies
- Mobile menu uses Bootstrap offcanvas
- Inconsistent hover/active states

**Target State**:
- Header component with inline tokens
- Mobile menu as self-contained component
- Consistent navigation patterns site-wide

**Files to Audit**:
```
/src/scss/_bootswatch.scss (header section)
/templates/layout/header.html.twig
```

---

#### 6. **Card Components**
**Current Status**: ✅ Mostly done (saho-card modernized)

**Remaining Work**:
- Ensure all card variants use saho-card component
- Migrate collection-card to saho-card
- Unify stretched link patterns

---

#### 7. **Modal/Dialog System**
**Current Issues**:
- Bootstrap modals with custom overrides
- Citation modal has some custom CSS
- Sharing modal has custom CSS
- Inconsistent backdrop styling

**Target State**:
- Modern `<dialog>` element
- Self-contained modal component
- Consistent animations and backdrop
- Proper focus trapping

**Files to Audit**:
```
/src/scss/components/_modals.scss
/modules/custom/saho_tools/css/citation-modern.css (modal parts)
/modules/custom/saho_tools/css/sharing-modern.css (modal parts)
```

---

#### 8. **Article/Content Templates**
**Current Issues**:
- Some templates still use legacy classes
- Inconsistent content formatting
- Mixed spacing units

**Target State**:
- Content components (pull quotes, highlights, etc.)
- Consistent content spacing
- Print-optimized styles

---

#### 9. **Icon System**
**Current Issues**:
- Mixed inline SVG and icon fonts
- No consistent icon sizing
- Some icons hardcoded in templates

**Target State**:
- SVG sprite system or component-based icons
- Consistent icon sizing scale
- Icon component with props

---

#### 10. **Utility Classes**
**Current Issues**:
- Some Bootstrap utilities, some custom
- Inconsistent naming
- Over-reliance on utilities vs components

**Target State**:
- Minimal utility classes (only for truly generic needs)
- Prefer components over utilities
- Consistent naming if used

---

## Modernization Strategy

### Approach: Incremental Component Migration

**Don't**: Rewrite everything at once ❌
**Do**: Migrate one component/system at a time ✅

### Priority Matrix

| Component | Impact | Effort | Priority | Timeline |
|-----------|--------|--------|----------|----------|
| **Typography** | High | Medium | 🔴 P0 | Week 2-3 |
| **Color System** | High | Medium | 🔴 P0 | Week 2-3 |
| **Layout/Spacing** | High | High | 🟠 P1 | Week 4-5 |
| **Forms** | High | High | 🟠 P1 | Week 6-7 |
| **Navigation** | Medium | Medium | 🟡 P2 | Week 8 |
| **Modals** | Medium | Medium | 🟡 P2 | Week 9 |
| **Cards** | Low | Low | 🟢 P3 | Week 10 |
| **Icons** | Low | High | 🟢 P3 | Week 11-12 |
| **Content Templates** | Low | Medium | 🟢 P3 | Week 13-14 |
| **Utilities** | Low | Low | 🟢 P3 | Week 15 |

---

## Implementation Pattern (Per Component)

### Step 1: Create Component Structure
```
/components/{category}/{component-name}/
├── {component-name}.component.yml    # Drupal SDC definition
├── {component-name}.twig             # Template
├── {component-name}.css              # Self-contained CSS with inline tokens
└── README.md                         # Usage documentation
```

### Step 2: Define Inline Design Tokens
```css
.component-name {
  /* Inline design tokens - NO external dependencies */
  --component-color-primary: #990000;
  --component-color-hover: #8b0000;
  --component-spacing-sm: 0.5rem;
  --component-spacing-md: 1rem;
  --component-radius: 8px;
  --component-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);

  /* Component styles using tokens */
  color: var(--component-color-primary);
  padding: var(--component-spacing-md);
  border-radius: var(--component-radius);
  box-shadow: var(--component-shadow);
}
```

### Step 3: Add Accessibility Features
```css
/* Keyboard focus */
.component-name:focus-visible {
  outline: 2px solid var(--component-color-primary);
  outline-offset: 2px;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .component-name {
    transition: none;
  }
}

/* High contrast */
@media (prefers-contrast: high) {
  .component-name {
    border-width: 2px;
  }
}
```

### Step 4: Write Documentation
- Component README with props and examples
- Add to main design system docs
- Include accessibility notes

### Step 5: Migrate Templates
- Find all usages of old classes/markup
- Replace with component includes
- Test thoroughly

### Step 6: Deprecate Legacy CSS
- Add deprecation warnings to old CSS files
- Keep for backwards compatibility
- Plan removal in next major version

---

## Quick Wins (Do These First)

### 1. **Create Typography Component** (2-3 days)
```
/components/content/saho-typography/
├── saho-typography.component.yml
├── saho-typography.twig
└── saho-typography.css
```

**Inline tokens**:
```css
.saho-typography {
  --typo-font-family-base: 'Roboto', sans-serif;
  --typo-font-family-heading: 'Merriweather', serif;

  --typo-size-xs: 0.75rem;    /* 12px */
  --typo-size-sm: 0.875rem;   /* 14px */
  --typo-size-base: 1rem;     /* 16px */
  --typo-size-lg: 1.125rem;   /* 18px */
  --typo-size-xl: 1.25rem;    /* 20px */
  --typo-size-2xl: 1.5rem;    /* 24px */
  --typo-size-3xl: 2rem;      /* 32px */
  --typo-size-4xl: 2.5rem;    /* 40px */

  --typo-line-height-tight: 1.2;
  --typo-line-height-normal: 1.5;
  --typo-line-height-loose: 1.8;

  --typo-color-primary: #212529;
  --typo-color-secondary: #6c757d;
}
```

**Impact**: Consistent typography site-wide, easier to maintain

---

### 2. **Create Spacing Utility Component** (1 day)
```css
.saho-spacing {
  /* 8px baseline grid */
  --space-xs: 0.5rem;   /* 8px */
  --space-sm: 1rem;     /* 16px */
  --space-md: 1.5rem;   /* 24px */
  --space-lg: 2rem;     /* 32px */
  --space-xl: 3rem;     /* 48px */
  --space-2xl: 4rem;    /* 64px */
  --space-3xl: 6rem;    /* 96px */
}
```

**Usage**: Apply to all components to replace magic numbers

---

### 3. **Consolidate Color System** (2-3 days)

Create single color palette component:
```css
.saho-colors {
  /* Brand colors */
  --color-primary: #990000;         /* SAHO Deep Heritage Red */
  --color-primary-light: #b22222;
  --color-primary-dark: #8b0000;

  --color-secondary: #3a4a64;       /* SAHO Slate Blue */
  --color-accent: #b88a2e;          /* SAHO Muted Gold */

  /* Semantic colors */
  --color-success: #22c55e;
  --color-warning: #eab308;
  --color-error: #ef4444;
  --color-info: #3b82f6;

  /* Neutral colors */
  --color-gray-50: #f9fafb;
  --color-gray-100: #f3f4f6;
  --color-gray-200: #e5e7eb;
  --color-gray-300: #d1d5db;
  --color-gray-400: #9ca3af;
  --color-gray-500: #6b7280;
  --color-gray-600: #4b5563;
  --color-gray-700: #374151;
  --color-gray-800: #1f2937;
  --color-gray-900: #111827;

  /* Text colors */
  --color-text-primary: #212529;
  --color-text-secondary: #6c757d;
  --color-text-muted: #adb5bd;

  /* Surface colors */
  --color-surface: #ffffff;
  --color-surface-secondary: #f8f9fa;
  --color-border: #dee2e6;
}
```

**Replace all instances** of hardcoded colors with these tokens

---

### 4. **Create Form Input Component** (3-4 days)
```
/components/forms/saho-input/
├── saho-input.component.yml
├── saho-input.twig
└── saho-input.css
```

**Benefits**:
- Consistent input styling
- Built-in validation states
- Accessibility features (focus, error announcements)

---

## Tooling & Workflow

### Development Tools

**1. Component Generator Script**
```bash
# Create new component with boilerplate
./scripts/generate-component.sh saho-new-component
```

**2. CSS Linting**
```bash
# Enforce modern CSS practices
npm run stylelint
```

**3. Design Token Validation**
```bash
# Ensure all components use inline tokens
./scripts/validate-tokens.sh
```

**4. Accessibility Testing**
```bash
# Automated a11y checks
npm run test:a11y
```

### Build Process

**Keep it simple:**
- Use Laravel Mix (already configured)
- Minimal SCSS compilation (only for legacy)
- No complex build steps for components
- Fast development workflow

### Documentation

**Maintain living documentation:**
- Storybook or similar component showcase
- Generated docs from component YML files
- Accessibility compliance notes per component
- Migration guides for developers

---

## Migration Path

### For Each Component System:

**Week N: Research & Plan**
- Audit current implementation
- Document all usages
- Design new component API
- Get team approval

**Week N+1: Build & Test**
- Create new component with inline tokens
- Write comprehensive tests
- Validate accessibility
- Document thoroughly

**Week N+2: Migrate**
- Update templates to use new component
- Deprecate old CSS
- Monitor for issues
- Fix any edge cases

**Week N+3: Cleanup**
- Remove deprecated CSS (if safe)
- Update documentation
- Train team on new component

---

## Risk Management

### Potential Issues

**1. Breaking Changes**
- **Risk**: New components break existing layouts
- **Mitigation**: Run visual regression tests, staged rollout

**2. Performance Impact**
- **Risk**: More CSS loaded per page
- **Mitigation**: Lazy load components, monitor bundle size

**3. Developer Adoption**
- **Risk**: Team continues using old patterns
- **Mitigation**: Clear docs, code reviews, training sessions

**4. Browser Support**
- **Risk**: Modern CSS features not supported
- **Mitigation**: Graceful degradation, polyfills where needed

---

## Success Metrics

### Quantitative Goals

- **CSS Size**: Reduce total CSS by 40-50%
- **Component Coverage**: 80%+ of UI uses design system components
- **Accessibility**: 100% WCAG 2.1 AA compliance
- **Build Time**: Reduce by 30%+
- **Page Load**: No regression (maintain <2s LCP)

### Qualitative Goals

- **Developer Experience**: Easier to build new features
- **Consistency**: UI looks uniform across the site
- **Maintainability**: Changes are faster and safer
- **Scalability**: Easy to add new components

---

## Example: Typography Modernization

### Before (Current State)
```scss
// _variables.scss
$font-family-base: 'Roboto', sans-serif;
$headings-font-family: 'Merriweather', serif;

// _typography.scss
h1 { font-size: 2.5rem; }
h2 { font-size: 2rem; }
// ... scattered across multiple files
```

### After (Modernized)
```css
/* /components/content/saho-typography/saho-typography.css */
.saho-typography {
  /* Inline design tokens - Self-contained typography system */
  --typo-font-family-base: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --typo-font-family-heading: 'Merriweather', Georgia, serif;
  --typo-font-family-mono: 'Courier New', monospace;

  /* Type scale - fluid typography using clamp() */
  --typo-size-xs: 0.75rem;
  --typo-size-sm: 0.875rem;
  --typo-size-base: 1rem;
  --typo-size-lg: clamp(1.125rem, 1rem + 0.5vw, 1.25rem);
  --typo-size-xl: clamp(1.25rem, 1rem + 1vw, 1.5rem);
  --typo-size-2xl: clamp(1.5rem, 1.25rem + 1.5vw, 2rem);
  --typo-size-3xl: clamp(2rem, 1.5rem + 2vw, 2.5rem);
  --typo-size-4xl: clamp(2.5rem, 2rem + 2.5vw, 3rem);

  /* Line heights */
  --typo-line-height-tight: 1.2;
  --typo-line-height-normal: 1.5;
  --typo-line-height-loose: 1.8;

  /* Font weights */
  --typo-weight-normal: 400;
  --typo-weight-medium: 500;
  --typo-weight-semibold: 600;
  --typo-weight-bold: 700;

  /* Colors */
  --typo-color-primary: #212529;
  --typo-color-secondary: #6c757d;
  --typo-color-muted: #adb5bd;
  --typo-color-inverse: #ffffff;

  /* Spacing */
  --typo-spacing-heading: 0.5em;
  --typo-spacing-paragraph: 1em;

  font-family: var(--typo-font-family-base);
  font-size: var(--typo-size-base);
  line-height: var(--typo-line-height-normal);
  color: var(--typo-color-primary);
}

/* Headings */
.saho-typography h1,
.saho-typography .h1 {
  font-family: var(--typo-font-family-heading);
  font-size: var(--typo-size-4xl);
  font-weight: var(--typo-weight-bold);
  line-height: var(--typo-line-height-tight);
  margin-bottom: var(--typo-spacing-heading);
}

.saho-typography h2,
.saho-typography .h2 {
  font-family: var(--typo-font-family-heading);
  font-size: var(--typo-size-3xl);
  font-weight: var(--typo-weight-bold);
  line-height: var(--typo-line-height-tight);
  margin-bottom: var(--typo-spacing-heading);
}

/* Responsive typography */
@media (prefers-reduced-motion: reduce) {
  .saho-typography * {
    transition: none !important;
  }
}

@media print {
  .saho-typography {
    --typo-size-base: 12pt;
    --typo-color-primary: #000;
  }
}
```

**Usage in template**:
```twig
<article class="saho-typography">
  <h1>{{ title }}</h1>
  <p>{{ body }}</p>
</article>
```

**Benefits**:
- ✅ Fluid typography scales smoothly
- ✅ Self-contained with all tokens inline
- ✅ Print-optimized
- ✅ Respects user motion preferences
- ✅ Easy to customize per instance

---

## Recommended Order

### Months 1-2: Foundation
1. Typography system
2. Color system
3. Spacing scale
4. Create component generator script

### Months 3-4: Core Components
1. Form components (input, textarea, select, checkbox, radio)
2. Navigation components (header, footer, breadcrumbs)
3. Layout components (container, section, grid)

### Months 5-6: Content Components
1. Modal/dialog system
2. Alert/notification system
3. Content components (pull quotes, highlights, tables)
4. Icon system

### Month 7+: Polish & Optimization
1. Migrate remaining templates
2. Remove all deprecated CSS
3. Performance optimization
4. Documentation completion
5. Team training

---

## Team Workflow

### For Developers

**Adding a new component**:
1. Run component generator
2. Define inline tokens
3. Build component with accessibility
4. Write tests
5. Document in README
6. Submit PR with visual screenshots

**Updating existing component**:
1. Read component README
2. Modify inline tokens if needed
3. Test changes
4. Update documentation
5. Submit PR

### Code Review Checklist

- [ ] Component uses inline CSS custom properties
- [ ] No external SCSS dependencies
- [ ] Accessibility features included (focus states, reduced motion, etc.)
- [ ] Documentation updated
- [ ] Visual regression tests pass
- [ ] Browser compatibility verified
- [ ] Mobile tested

---

## Long-Term Maintenance

### Quarterly Reviews
- Audit new components added
- Check for pattern drift
- Update documentation
- Review accessibility compliance

### Annual Overhaul
- Evaluate new CSS features
- Update browser support targets
- Refresh design tokens
- Major version update

---

## Getting Started Today

### Immediate Actions (This Week)

1. **Create design system directory structure**:
```bash
mkdir -p /components/{content,forms,layout,navigation,utilities,media}
```

2. **Set up component generator**:
Create `/scripts/generate-component.sh`

3. **Start typography modernization**:
Create `/components/content/saho-typography/`

4. **Document the plan**:
Share this plan with the team, get feedback

5. **Create first new component**:
Pick something small (e.g., badge, tag) to test the workflow

---

## Resources & References

### Inspiration
- **GOV.UK Design System**: https://design-system.service.gov.uk/
- **Shopify Polaris**: https://polaris.shopify.com/
- **Material Design 3**: https://m3.material.io/
- **Radix UI**: https://www.radix-ui.com/ (accessibility patterns)

### Modern CSS Techniques
- **CSS Custom Properties**: MDN Web Docs
- **Container Queries**: CSS-Tricks guide
- **Modern CSS Reset**: Andy Bell's reset
- **Fluid Typography**: Utopia calculator

### Tools
- **Design Tokens**: Style Dictionary
- **Component Library**: Storybook
- **CSS Linting**: Stylelint
- **Accessibility**: axe DevTools

---

## Conclusion

The button system consolidation proved this approach works. Now we scale it to the entire design system:

**Core Philosophy**:
- Component-based
- Self-contained with inline tokens
- Accessibility first
- Modern CSS features
- No external dependencies

**Timeline**: 6-7 months for complete modernization
**Effort**: ~1-2 developers working incrementally
**Impact**: 40-50% CSS reduction, 100% design system coverage, better maintainability

**Next Steps**: Start with typography and color systems (highest impact, medium effort), then build out from there.

---

**Status**: Ready to begin
**Owner**: Development Team
**Timeline**: 6-7 months
**Last Updated**: February 2026
