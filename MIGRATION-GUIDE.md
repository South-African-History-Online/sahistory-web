# SAHO Design System Migration Guide

**Developer Guide for Modernizing Components**

Step-by-step guide for migrating legacy SCSS components to the modern inline CSS custom property pattern established during the 2026 design system modernization.

---

## Table of Contents

1. [Overview](#overview)
2. [Migration Process](#migration-process)
3. [Token Mapping Reference](#token-mapping-reference)
4. [Common Patterns & Solutions](#common-patterns--solutions)
5. [Testing Checklist](#testing-checklist)
6. [Code Review Criteria](#code-review-criteria)
7. [Troubleshooting](#troubleshooting)

---

## Overview

### What We're Migrating

**From**: Legacy SCSS with external variables and magic numbers
**To**: Modern CSS with inline custom properties and unified tokens

### Why Migrate?

✅ **Runtime Theming**: Change tokens without recompilation
✅ **Component Isolation**: Zero external dependencies
✅ **Maintainability**: Clear token source and usage
✅ **DevTools Friendly**: Easy to inspect and debug
✅ **Dark Mode Ready**: Switch themes via media queries
✅ **Performance**: No SCSS compilation overhead

### Migration Scope

**High Priority** (Migrate First):
- Public-facing components (cards, grids, buttons)
- Frequently reused elements (modals, forms)
- Template overrides (_bootswatch.scss sections)

**Medium Priority**:
- Admin interface components
- Layout Builder blocks
- Utility components

**Low Priority** (Can Remain):
- Bootstrap core overrides (if not customized)
- Rarely used components
- Legacy deprecated components

---

## Migration Process

### Step 1: Audit Current Component

**Identify dependencies:**

```bash
# Search for SCSS variables in component
grep -n '\$' component.scss

# Search for magic numbers (non-token px/rem values)
grep -n '[0-9]\+px' component.scss
grep -n '[0-9]\+rem' component.scss
```

**Document findings:**

```markdown
## Component: saho-my-component

### External Dependencies
- $primary-color (used 3 times)
- $spacing-md (used 5 times)
- $font-size-base (used 2 times)

### Magic Numbers
- padding: 17px (line 23)
- margin-bottom: 23px (line 45)
- font-size: 1.3rem (line 67)

### Responsive Overrides
- Mobile: 768px breakpoint
- Tablet: 1024px breakpoint
```

---

### Step 2: Create Component Token Map

**Map SCSS variables to CSS custom properties:**

```css
/* OLD → NEW Mapping */

/* Colors */
$primary-color       → var(--saho-color-primary)
$text-color          → var(--saho-color-text-primary)
$border-color        → var(--saho-color-border)

/* Spacing */
$spacing-sm          → var(--saho-space-2)
$spacing-md          → var(--saho-space-3)
$spacing-lg          → var(--saho-space-4)
padding: 17px        → var(--saho-space-2)  /* Round to 16px */
margin: 23px         → var(--saho-space-3)  /* Round to 24px */

/* Typography */
$font-size-base      → var(--saho-font-size-base)
font-size: 1.3rem    → var(--saho-font-size-md)  /* Use scale */
$font-weight-bold    → var(--saho-font-weight-bold)
```

---

### Step 3: Define Inline Tokens

**Create component-scoped tokens:**

```css
.saho-my-component {
  /* ===== COLOR TOKENS ===== */
  --my-component-bg: var(--saho-color-white);
  --my-component-text: var(--saho-color-text-primary);
  --my-component-border: var(--saho-color-border);
  --my-component-primary: var(--saho-color-primary);

  /* ===== SPACING TOKENS (8px baseline grid) ===== */
  --my-component-padding: var(--saho-space-3);      /* 24px */
  --my-component-margin: var(--saho-space-4);       /* 32px */
  --my-component-gap: var(--saho-space-2);          /* 16px */

  /* ===== TYPOGRAPHY TOKENS ===== */
  --my-component-font-size: var(--saho-font-size-base);
  --my-component-font-weight: var(--saho-font-weight-regular);
  --my-component-line-height: var(--saho-line-height-normal);

  /* ===== LAYOUT TOKENS ===== */
  --my-component-radius: var(--saho-radius-md);
  --my-component-shadow: var(--saho-shadow-md);
  --my-component-transition: var(--saho-transition-base);

  /* ===== RESPONSIVE TOKENS ===== */
  --my-component-padding-mobile: var(--saho-space-2);  /* 16px */
}
```

---

### Step 4: Update Component Styles

**Replace old values with component tokens:**

```css
/* OLD (SCSS with magic numbers) */
.saho-my-component {
  background: $primary-color;
  padding: 17px;
  margin-bottom: 23px;
  font-size: 1.3rem;
  border-radius: 8px;
}

/* NEW (CSS custom properties) */
.saho-my-component {
  /* Tokens defined above */

  background: var(--my-component-bg);
  padding: var(--my-component-padding);
  margin-bottom: var(--my-component-margin);
  font-size: var(--my-component-font-size);
  border-radius: var(--my-component-radius);
  box-shadow: var(--my-component-shadow);
  transition: var(--my-component-transition);
}
```

---

### Step 5: Add Responsive Overrides

**Override tokens at breakpoints:**

```css
/* Mobile override */
@media (max-width: 768px) {
  .saho-my-component {
    --my-component-padding: var(--my-component-padding-mobile);
    --my-component-font-size: var(--saho-font-size-sm);
  }
}

/* All child elements automatically update! */
```

---

### Step 6: Test & Validate

**Browser Testing:**

1. **Visual inspection** at all breakpoints
2. **DevTools inspection** to verify token values
3. **Runtime override test** (change tokens in console)
4. **Accessibility check** (WCAG contrast, keyboard nav)

**Automated Testing:**

```bash
# CSS validation
npx stylelint "components/**/*.css"

# Accessibility audit
npm run test:a11y

# Visual regression (if available)
npm run test:visual
```

---

## Token Mapping Reference

### Complete SCSS to CSS Custom Property Map

#### Colors

| SCSS Variable | CSS Custom Property | Hex Value |
|---------------|---------------------|-----------|
| `$deep-heritage-red` | `var(--saho-color-primary)` | #990000 |
| `$saho-deep-heritage-red` | `var(--saho-color-primary)` | #990000 |
| `$primary` | `var(--saho-color-primary)` | #990000 |
| `$slate-blue` | `var(--saho-color-secondary)` | #3a4a64 |
| `$saho-slate-blue` | `var(--saho-color-secondary)` | #3a4a64 |
| `$secondary` | `var(--saho-color-secondary)` | #3a4a64 |
| `$muted-gold` | `var(--saho-color-accent)` | #b88a2e |
| `$saho-muted-gold` | `var(--saho-color-accent)` | #b88a2e |
| `$accent` | `var(--saho-color-accent)` | #b88a2e |
| `$faded-brick-red` | `var(--saho-color-highlight)` | #8b2331 |
| `$forest-green` | `var(--saho-color-forest-green)` | #2d5016 |
| `$saho-forest-green` | `var(--saho-color-forest-green)` | #2d5016 |
| `$text-primary` | `var(--saho-color-text-primary)` | #1e293b |
| `$text-secondary` | `var(--saho-color-text-secondary)` | #475569 |
| `$text-muted` | `var(--saho-color-text-muted)` | #94a3b8 |
| `$surface` | `var(--saho-color-surface)` | #ffffff |
| `$surface-alt` | `var(--saho-color-surface-alt)` | #f7f7f7 |
| `$border` | `var(--saho-color-border)` | #d9d9d9 |
| `$white` | `var(--saho-color-white)` | #ffffff |
| `$black` | `var(--saho-color-black)` | #000000 |

#### Spacing (8px Baseline Grid)

| SCSS Variable | CSS Custom Property | Value |
|---------------|---------------------|-------|
| `$spacing-xs` | `var(--saho-space-1)` | 0.5rem (8px) |
| `$spacing-sm` | `var(--saho-space-2)` | 1rem (16px) |
| `$spacing-md` | `var(--saho-space-3)` | 1.5rem (24px) |
| `$spacing-lg` | `var(--saho-space-4)` | 2rem (32px) |
| `$spacing-xl` | `var(--saho-space-6)` | 3rem (48px) |
| `$spacing-2xl` | `var(--saho-space-8)` | 4rem (64px) |
| `8px` | `var(--saho-space-1)` | 0.5rem |
| `16px` | `var(--saho-space-2)` | 1rem |
| `24px` | `var(--saho-space-3)` | 1.5rem |
| `32px` | `var(--saho-space-4)` | 2rem |

#### Typography

| SCSS Variable | CSS Custom Property | Value |
|---------------|---------------------|-------|
| `$font-size-base` | `var(--saho-font-size-base)` | clamp(1rem, 0.95rem + 0.25vw, 1.125rem) |
| `$h1-font-size` | `var(--saho-font-h1)` | clamp(2rem, 1.75rem + 1.5vw, 3rem) |
| `$h2-font-size` | `var(--saho-font-h2)` | clamp(1.75rem, 1.5rem + 1.25vw, 2.25rem) |
| `$h3-font-size` | `var(--saho-font-h3)` | clamp(1.5rem, 1.35rem + 0.75vw, 1.875rem) |
| `$h4-font-size` | `var(--saho-font-h4)` | clamp(1.25rem, 1.15rem + 0.5vw, 1.5rem) |
| `$font-weight-regular` | `var(--saho-font-weight-regular)` | 400 |
| `$font-weight-medium` | `var(--saho-font-weight-medium)` | 500 |
| `$font-weight-semibold` | `var(--saho-font-weight-semibold)` | 600 |
| `$font-weight-bold` | `var(--saho-font-weight-bold)` | 700 |
| `$line-height-normal` | `var(--saho-line-height-normal)` | 1.5 |
| `$line-height-tight` | `var(--saho-line-height-tight)` | 1.2 |

#### Layout & Effects

| SCSS Variable | CSS Custom Property | Value |
|---------------|---------------------|-------|
| `$border-radius` | `var(--saho-radius-md)` | 0.5rem (8px) |
| `$border-radius-sm` | `var(--saho-radius-sm)` | 0.25rem (4px) |
| `$border-radius-lg` | `var(--saho-radius-lg)` | 0.75rem (12px) |
| `$box-shadow` | `var(--saho-shadow-md)` | 0 0.1875rem 0.625rem rgba(0, 0, 0, 0.05) |
| `$box-shadow-sm` | `var(--saho-shadow-sm)` | 0 0.125rem 0.375rem rgba(0, 0, 0, 0.05) |
| `$box-shadow-lg` | `var(--saho-shadow-lg)` | 0 0.25rem 0.75rem rgba(0, 0, 0, 0.08) |
| `$transition-base` | `var(--saho-transition-base)` | 200ms ease |

---

## Common Patterns & Solutions

### Pattern 1: Magic Number Rounding

**Problem**: `padding: 17px` doesn't align to 8px grid

**Solution**: Round to nearest 8px multiple

```css
/* OLD */
padding: 17px;

/* NEW - Round to 16px (8px × 2) */
padding: var(--saho-space-2);  /* 16px */

/* OR if 17px was intentional "between" value */
/* Consider if component needs custom token */
--component-padding: 1.0625rem;  /* 17px */
```

### Pattern 2: SCSS Color Functions

**Problem**: `darken($primary, 10%)`

**Solution**: Define explicit dark variant

```css
/* OLD */
background: $primary-color;
&:hover {
  background: darken($primary-color, 10%);
}

/* NEW */
--component-bg: var(--saho-color-primary);
--component-bg-hover: var(--saho-color-primary-dark);

background: var(--component-bg);
&:hover {
  background: var(--component-bg-hover);
}
```

### Pattern 3: Responsive Typography

**Problem**: Fixed font sizes at breakpoints

**Solution**: Use fluid typography tokens

```css
/* OLD */
h2 {
  font-size: 2rem;

  @media (max-width: 768px) {
    font-size: 1.5rem;
  }
}

/* NEW - Fluid typography */
h2 {
  font-size: var(--saho-font-h2);  /* Scales 28-36px automatically */
}
```

### Pattern 4: Grid Gaps

**Problem**: Inconsistent grid gaps

**Solution**: Use STANDARD 32px gap

```css
/* OLD */
.grid {
  display: grid;
  gap: 30px;  /* Magic number */

  @media (max-width: 768px) {
    gap: 15px;  /* Magic number */
  }
}

/* NEW - STANDARD gap */
.grid {
  --grid-gap: var(--saho-space-4);         /* 32px STANDARD */
  --grid-gap-mobile: var(--saho-space-2);  /* 16px */

  display: grid;
  gap: var(--grid-gap);
}

@media (max-width: 768px) {
  .grid {
    gap: var(--grid-gap-mobile);
  }
}
```

### Pattern 5: Button Padding

**Problem**: Button padding doesn't match system

**Solution**: Use button tokens

```css
/* OLD */
.button {
  padding: 10px 20px;  /* Magic numbers */
}

/* NEW */
.button {
  padding: var(--saho-button-padding-md);  /* 0.625rem 1.875rem */
}

/* OR create component token */
.button {
  --button-padding-y: 0.625rem;
  --button-padding-x: 1.875rem;

  padding: var(--button-padding-y) var(--button-padding-x);
}
```

### Pattern 6: Card Component

**Real-world example:**

**Before:**

```scss
.card {
  background: $white;
  padding: 20px;
  border: 1px solid $border;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
  }

  .card-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 12px;
  }
}
```

**After:**

```css
.saho-card {
  /* Component-scoped tokens */
  --card-bg: var(--saho-color-white);
  --card-padding: var(--saho-space-3);                /* 24px - rounded from 20px */
  --card-border: var(--saho-color-border-light);
  --card-radius: var(--saho-radius-md);               /* 8px */
  --card-shadow: var(--saho-shadow-md);
  --card-shadow-hover: var(--saho-shadow-xl);
  --card-transition: var(--saho-transition-base);

  --card-title-size: var(--saho-font-size-lg);        /* 20-24px fluid */
  --card-title-weight: var(--saho-font-weight-semibold);
  --card-title-margin: var(--saho-space-1);           /* 8px - rounded from 12px */

  /* Styles */
  background: var(--card-bg);
  padding: var(--card-padding);
  border: 1px solid var(--card-border);
  border-radius: var(--card-radius);
  box-shadow: var(--card-shadow);
  transition: var(--card-transition);
}

.saho-card:hover {
  transform: translateY(-8px);  /* 8px baseline */
  box-shadow: var(--card-shadow-hover);
}

.saho-card__title {
  font-size: var(--card-title-size);
  font-weight: var(--card-title-weight);
  margin-bottom: var(--card-title-margin);
}
```

---

## Testing Checklist

### Visual Testing

- [ ] Component displays correctly at all breakpoints
- [ ] No visual regressions compared to original
- [ ] Spacing aligns to 8px grid
- [ ] Typography scales smoothly (fluid types)
- [ ] Colors match brand guidelines
- [ ] Shadows and effects render properly

### Functional Testing

- [ ] Interactive elements work (hover, focus, active)
- [ ] Responsive behavior works as expected
- [ ] Animations/transitions are smooth
- [ ] Component works with different content lengths
- [ ] Component works in different contexts (light/dark bg)

### Browser Testing

- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Android

### Accessibility Testing

- [ ] WCAG 2.1 AA contrast ratios met
- [ ] Keyboard navigation works
- [ ] Screen reader announces correctly
- [ ] Focus indicators visible (2:1 contrast minimum)
- [ ] 200% zoom: no horizontal scrolling
- [ ] High contrast mode supported
- [ ] Reduced motion preference respected

### DevTools Testing

- [ ] Computed tab shows correct token values
- [ ] Can trace token inheritance
- [ ] Runtime override works (change tokens in console)
- [ ] No SCSS compilation errors
- [ ] CSS validates (no syntax errors)

### Code Quality

- [ ] All tokens defined inline at component level
- [ ] No magic numbers in styles
- [ ] No external SCSS variable dependencies
- [ ] Component tokens prefixed appropriately
- [ ] Token sections commented
- [ ] Semantic token names used
- [ ] Code formatted consistently

---

## Code Review Criteria

### Token Definition

✅ **Pass**: All design tokens defined inline at component level
❌ **Fail**: External SCSS variables used

```css
/* ✅ PASS */
.component {
  --component-color: var(--saho-color-primary);
  color: var(--component-color);
}

/* ❌ FAIL */
.component {
  color: $scss-variable;
}
```

### Token Naming

✅ **Pass**: Component-scoped, semantic names
❌ **Fail**: Generic or global names

```css
/* ✅ PASS */
--card-title-size: var(--saho-font-size-lg);
--card-padding: var(--saho-space-3);

/* ❌ FAIL */
--size-1: var(--saho-font-size-lg);
--spacing: var(--saho-space-3);
```

### Magic Numbers

✅ **Pass**: All values from token scale
❌ **Fail**: Arbitrary px/rem values

```css
/* ✅ PASS */
padding: var(--saho-space-3);  /* 24px */

/* ❌ FAIL */
padding: 17px;  /* Magic number */
```

### Responsive Behavior

✅ **Pass**: Token overrides at breakpoints
❌ **Fail**: Magic numbers in media queries

```css
/* ✅ PASS */
@media (max-width: 768px) {
  .component {
    --component-padding: var(--saho-space-2);
  }
}

/* ❌ FAIL */
@media (max-width: 768px) {
  .component {
    padding: 15px;
  }
}
```

### Documentation

✅ **Pass**: Token sections commented, usage documented
❌ **Fail**: No comments, unclear token purpose

```css
/* ✅ PASS */
.component {
  /* ===== SPACING TOKENS (8px baseline grid) ===== */
  --component-padding: var(--saho-space-3);  /* 24px - comfortable spacing */
}

/* ❌ FAIL */
.component {
  --component-padding: var(--saho-space-3);
}
```

---

## Troubleshooting

### Issue 1: Token Not Updating

**Symptom**: Changed token value but style doesn't update

**Causes:**
1. Token defined but not used in style
2. More specific selector overriding token
3. Inline style overriding CSS

**Solutions:**

```css
/* Check token is actually used */
.component {
  --component-color: var(--saho-color-primary);  /* Defined */
  color: var(--component-color);                  /* Used ✅ */
}

/* Check specificity */
.component {
  --component-color: var(--saho-color-primary);
}

/* More specific selector wins */
body .wrapper .component {
  color: #000 !important;  /* Overrides token */
}

/* Increase specificity or remove !important */
```

### Issue 2: Inheritance Not Working

**Symptom**: Child element not inheriting parent token

**Cause**: Child redefines token

**Solution:**

```css
/* ❌ PROBLEM */
.parent {
  --component-padding: var(--saho-space-3);
}

.child {
  --component-padding: var(--saho-space-1);  /* Redefines! */
  padding: var(--component-padding);          /* Uses child's value */
}

/* ✅ SOLUTION */
.parent {
  --component-padding: var(--saho-space-3);
}

.child {
  padding: var(--component-padding);  /* Inherits parent's value */
}

/* OR create separate token */
.parent {
  --component-padding: var(--saho-space-3);
}

.child {
  --component-padding-child: var(--saho-space-1);
  padding: var(--component-padding-child);
}
```

### Issue 3: Responsive Override Not Working

**Symptom**: Mobile spacing same as desktop

**Cause**: Token override after style application

**Solution:**

```css
/* ❌ PROBLEM - Override after usage */
.component {
  padding: var(--component-padding);  /* Uses undefined value */
}

@media (max-width: 768px) {
  .component {
    --component-padding: var(--saho-space-2);  /* Defined too late */
  }
}

/* ✅ SOLUTION - Define default, override in media query */
.component {
  --component-padding: var(--saho-space-3);  /* Default */
  padding: var(--component-padding);
}

@media (max-width: 768px) {
  .component {
    --component-padding: var(--saho-space-2);  /* Override */
  }
}
```

### Issue 4: DevTools Shows Wrong Value

**Symptom**: DevTools computed value doesn't match code

**Cause**: Token inherited from unexpected source

**Solution:**

```css
/* Trace inheritance in DevTools */
/* 1. Inspect element */
/* 2. Check Computed tab */
/* 3. Click arrow next to property */
/* 4. See full inheritance chain */

/* If unexpected source found, add specificity */
.specific-context .component {
  --component-color: var(--saho-color-primary);
}
```

### Issue 5: SCSS Compilation Error

**Symptom**: Build fails with SCSS error

**Cause**: Mixed SCSS and CSS custom properties

**Solution:**

```scss
/* ❌ PROBLEM - SCSS can't interpolate CSS custom properties */
$spacing: var(--saho-space-3);
.component {
  padding: $spacing + 10px;  /* ERROR */
}

/* ✅ SOLUTION - Use CSS calc() */
.component {
  --component-padding-base: var(--saho-space-3);
  padding: calc(var(--component-padding-base) + 10px);
}

/* OR define separate token */
.component {
  --component-padding-increased: var(--saho-space-4);  /* Use next step */
  padding: var(--component-padding-increased);
}
```

---

## Support & Resources

- **Design Tokens**: `DESIGN-TOKENS.md`
- **Typography System**: `TYPOGRAPHY-SYSTEM.md`
- **Spacing System**: `SPACING-SYSTEM.md`
- **Color System**: `COLOR-SYSTEM.md`
- **Component Patterns**: `COMPONENT-PATTERNS.md`
- **CLAUDE.md**: Development workflow and commands

---

**Last Updated**: February 2026
**Status**: Production
**Version**: 1.0.0
