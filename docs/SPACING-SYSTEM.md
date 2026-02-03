# SAHO Spacing System

**8px Baseline Grid for Consistent Rhythm**

The SAHO spacing system uses an 8px baseline grid to create consistent vertical and horizontal rhythm across all components, ensuring visual harmony and predictable layouts.

---

## Table of Contents

1. [Overview](#overview)
2. [8px Baseline Grid](#8px-baseline-grid)
3. [Spacing Scale](#spacing-scale)
4. [Layout Patterns](#layout-patterns)
5. [Component Spacing](#component-spacing)
6. [Grid System](#grid-system)
7. [Responsive Spacing](#responsive-spacing)
8. [Code Examples](#code-examples)
9. [Best Practices](#best-practices)

---

## Overview

### Why 8px?

The 8px baseline grid provides:

1. **Consistency**: All spacing is predictable and harmonious
2. **Scalability**: Easy to double/halve for different contexts
3. **Alignment**: Components naturally align to a grid
4. **Simplicity**: Fewer decisions, faster development
5. **Accessibility**: Proper spacing improves readability

### Philosophy

- **No magic numbers**: Every spacing value comes from the scale
- **Rhythm over randomness**: Consistent spacing creates visual flow
- **Component isolation**: Components define their own spacing using global tokens
- **Responsive by default**: Spacing adapts to viewport size

---

## 8px Baseline Grid

### Core Principle

**All spacing values are multiples of 8px (0.5rem)**

```
8px  → 0.5rem → var(--saho-space-1)
16px → 1rem   → var(--saho-space-2)
24px → 1.5rem → var(--saho-space-3)
32px → 2rem   → var(--saho-space-4) ← STANDARD for grids
48px → 3rem   → var(--saho-space-6)
64px → 4rem   → var(--saho-space-8)
```

### Why rem?

- **Respects user preferences**: Scales with browser font size
- **Accessibility**: Users can increase default font size
- **Consistency**: 1rem = 16px on most browsers
- **Calculation**: Easy mental math (multiply by 16)

---

## Spacing Scale

### 9-Level Scale

```css
/* Zero - No spacing */
--saho-space-0: 0;

/* Level 1 - Minimal spacing (8px) */
--saho-space-1: 0.5rem;    /* 8px */

/* Level 2 - Standard spacing (16px) */
--saho-space-2: 1rem;      /* 16px */

/* Level 3 - Comfortable spacing (24px) */
--saho-space-3: 1.5rem;    /* 24px */

/* Level 4 - Section separation (32px) - STANDARD for grids */
--saho-space-4: 2rem;      /* 32px */

/* Level 6 - Large spacing (48px) */
--saho-space-6: 3rem;      /* 48px */

/* Level 8 - Extra large spacing (64px) */
--saho-space-8: 4rem;      /* 64px */

/* Level 12 - Hero spacing (96px) */
--saho-space-12: 6rem;     /* 96px */

/* Level 16 - Maximum spacing (128px) */
--saho-space-16: 8rem;     /* 128px */
```

**File**: `/webroot/themes/custom/saho/src/scss/base/_variables.scss:93-101`

### Usage Guide

| Token | Size | Use Case | Examples |
|-------|------|----------|----------|
| `space-0` | 0px | Reset spacing | Removing margins |
| `space-1` | 8px | **Tight spacing** | Icon gaps, badge padding |
| `space-2` | 16px | **Standard spacing** | Button padding, card padding |
| `space-3` | 24px | **Comfortable spacing** | Paragraph margins, form fields |
| `space-4` | 32px | **STANDARD gap** | Card grids, section margins |
| `space-6` | 48px | **Large sections** | Hero padding, major breaks |
| `space-8` | 64px | **Extra large** | Page sections, containers |
| `space-12` | 96px | **Hero spacing** | Banner sections |
| `space-16` | 128px | **Maximum** | Landing page sections |

### Deprecated Aliases

**⚠️ Do NOT use - migrate to numbered scale:**

```css
/* OLD - DEPRECATED */
--saho-space-xs: var(--saho-space-1);    /* Use --saho-space-1 */
--saho-space-sm: var(--saho-space-2);    /* Use --saho-space-2 */
--saho-space-md: var(--saho-space-3);    /* Use --saho-space-3 */
--saho-space-lg: var(--saho-space-4);    /* Use --saho-space-4 */
--saho-space-xl: var(--saho-space-6);    /* Use --saho-space-6 */
--saho-space-2xl: var(--saho-space-8);   /* Use --saho-space-8 */
```

---

## Layout Patterns

### Card Padding

**Internal spacing within cards:**

```css
/* Standard card */
--card-spacing: var(--saho-space-3);         /* 24px */

/* Compact card */
--card-spacing-compact: var(--saho-space-2); /* 16px */

/* Spacious card */
--card-spacing-spacious: var(--saho-space-4);/* 32px */
```

**Example** from `/webroot/themes/custom/saho/components/content/saho-card/saho-card.css:46-48`:

```css
.saho-card {
  --card-spacing: var(--saho-card-padding-compact);   /* 24px */
  --card-spacing-mobile: var(--saho-space-2);         /* 16px */

  padding: var(--card-spacing);
}

@media (max-width: 768px) {
  .saho-card {
    padding: var(--card-spacing-mobile);
  }
}
```

### Section Spacing

**Spacing between major sections:**

```css
/* Standard section margin */
.section {
  margin-bottom: var(--saho-space-6);  /* 48px */
}

/* Large section margin */
.section--large {
  margin-bottom: var(--saho-space-8);  /* 64px */
}

/* Hero section padding */
.hero {
  padding: var(--saho-space-12) var(--saho-space-4);  /* 96px 32px */
}
```

### Stack Spacing (Vertical)

**Consistent vertical spacing between elements:**

```css
.stack > * + * {
  margin-top: var(--saho-space-3);  /* 24px between items */
}

.stack--tight > * + * {
  margin-top: var(--saho-space-1);  /* 8px tight */
}

.stack--relaxed > * + * {
  margin-top: var(--saho-space-6);  /* 48px relaxed */
}
```

### Inline Spacing (Horizontal)

**Spacing between inline elements:**

```css
.inline {
  display: flex;
  gap: var(--saho-space-2);  /* 16px standard gap */
}

.inline--tight {
  gap: var(--saho-space-1);  /* 8px tight */
}

.inline--relaxed {
  gap: var(--saho-space-4);  /* 32px relaxed */
}
```

---

## Component Spacing

### Button Spacing

**From** `/webroot/themes/custom/saho/src/scss/base/_variables.scss:220-222`:

```css
/* Button padding */
--saho-button-padding-sm: 0.4rem 0.8rem;      /* 6.4px 12.8px (adjusted) */
--saho-button-padding-md: 0.625rem 1.875rem;  /* 10px 30px */
--saho-button-padding-lg: 0.75rem 2rem;       /* 12px 32px */
```

**Note**: Button padding intentionally breaks the 8px grid slightly for optical balance.

### Badge/Label Spacing

```css
.badge {
  padding: var(--saho-space-1) var(--saho-space-2);  /* 8px 16px */
  margin-right: var(--saho-space-1);                  /* 8px gap */
}
```

**Example** from `/webroot/themes/custom/saho/components/content/saho-card/saho-card.css:224-235`:

```css
.saho-card-badge {
  position: absolute;
  top: var(--saho-space-2);     /* 16px from top */
  right: var(--saho-space-2);   /* 16px from right */
  padding: var(--saho-space-1) var(--saho-space-2);  /* 8px 16px */
  border-radius: var(--saho-radius-sm);
  font-size: var(--saho-font-size-xs);
}
```

### Form Spacing

```css
.form-field {
  margin-bottom: var(--saho-space-3);  /* 24px between fields */
}

.form-field__input {
  padding: var(--saho-space-2);        /* 16px internal padding */
}

.form-field__label {
  margin-bottom: var(--saho-space-1);  /* 8px label gap */
}
```

### Modal/Dialog Spacing

**From** `/webroot/modules/custom/saho_tools/css/citation-modern.css:24-29, 50-54`:

```css
#citation-modal .modal-dialog {
  --cite-modal-margin: var(--saho-space-3);            /* 24px */
  --cite-modal-margin-mobile: var(--saho-space-1);     /* 8px */

  margin: var(--cite-modal-margin) auto;
}

#citation-modal .modal-header {
  --cite-padding: var(--saho-space-3);  /* 24px */
  padding: var(--cite-padding);
}

@media (max-width: 640px) {
  #citation-modal .modal-header {
    padding: var(--saho-space-2);  /* 16px on mobile */
  }
}
```

---

## Grid System

### Card Grid - STANDARD 32px Gap

**The universal standard for card grids across SAHO:**

```css
--saho-grid-gap-default: var(--saho-space-4);  /* 32px STANDARD */
```

**File**: `/webroot/themes/custom/saho/src/scss/base/_variables.scss:247`

**Example** from `/webroot/themes/custom/saho/components/layout/saho-card-grid/saho-card-grid.css:12-37`:

```css
.saho-card-grid {
  /* Grid-scoped tokens */
  --grid-gap: var(--saho-space-4);               /* 32px - STANDARD */
  --grid-gap-compact: var(--saho-space-2);       /* 16px */
  --grid-gap-spacious: var(--saho-space-6);      /* 48px */

  display: grid;
  gap: var(--grid-gap);  /* 32px STANDARD for all card grids */
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
}
```

### Grid Gap Variants

```css
/* Compact grid - tighter spacing */
.saho-card-grid--gap-compact {
  gap: var(--saho-space-2);  /* 16px */
}

/* Normal grid - STANDARD */
.saho-card-grid--gap-normal {
  gap: var(--saho-space-4);  /* 32px */
}

/* Relaxed grid - more breathing room */
.saho-card-grid--gap-relaxed {
  gap: var(--saho-space-6);  /* 48px */
}
```

### Bootstrap Override

**From** `/_bootswatch.scss:2213-2222`:

```css
.saho-landing-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(var(--saho-grid-card-min-width), 1fr));
  gap: var(--saho-space-4);  /* 32px - STANDARD for card grids */

  @media (max-width: 768px) {
    gap: var(--saho-space-2);  /* 16px on mobile */
  }
}
```

---

## Responsive Spacing

### Mobile-First Approach

**Start with mobile spacing, enhance for desktop:**

```css
/* Mobile (default) */
.component {
  padding: var(--saho-space-2);  /* 16px */
  margin-bottom: var(--saho-space-3);  /* 24px */
}

/* Tablet */
@media (min-width: 768px) {
  .component {
    padding: var(--saho-space-3);  /* 24px */
    margin-bottom: var(--saho-space-4);  /* 32px */
  }
}

/* Desktop */
@media (min-width: 1200px) {
  .component {
    padding: var(--saho-space-4);  /* 32px */
    margin-bottom: var(--saho-space-6);  /* 48px */
  }
}
```

### Responsive Grid Gaps

**From** `/webroot/themes/custom/saho/components/layout/saho-card-grid/saho-card-grid.css:142-164`:

```css
/* Tablet */
@media (max-width: 992px) {
  .saho-card-grid {
    gap: var(--saho-space-3);  /* 24px on tablet */
  }
}

/* Mobile */
@media (max-width: 768px) {
  .saho-card-grid {
    gap: var(--saho-space-2);  /* 16px on mobile */
  }
}
```

### Block Section Responsive Spacing

**From** `/webroot/themes/custom/saho/src/scss/base/_variables.scss:256-271`:

```css
/* Block padding (8px baseline grid) */
--saho-block-padding-mobile: 1rem;    /* 16px (space-2) */
--saho-block-padding-tablet: 1.5rem;  /* 24px (space-3) */
--saho-block-padding-desktop: 2rem;   /* 32px (space-4) */

/* Current active padding (defaults to desktop) */
--saho-block-padding: var(--saho-block-padding-desktop);

/* Override with media queries */
@media (max-width: 768px) {
  :root {
    --saho-block-padding: var(--saho-block-padding-mobile);
  }
}

@media (min-width: 769px) and (max-width: 1024px) {
  :root {
    --saho-block-padding: var(--saho-block-padding-tablet);
  }
}
```

---

## Code Examples

### Example 1: TDIH Block Spacing

**From** `/_bootswatch.scss:168-213`:

```css
.tdih-block {
  /* Component-scoped spacing tokens */
  --tdih-block-spacing: var(--saho-space-4);      /* 32px section margin */
  --tdih-block-padding: var(--saho-space-3);      /* 24px internal padding */
  --tdih-block-item-spacing: var(--saho-space-3); /* 24px between items */

  margin: var(--tdih-block-spacing) 0;
  padding: var(--tdih-block-padding);
  box-shadow: var(--saho-shadow-md);
  border-radius: var(--saho-radius-md);
}

.tdih-block .block-title {
  margin-bottom: var(--saho-space-1);  /* 8px tight */
}

.tdih-item {
  margin-bottom: var(--tdih-block-item-spacing);  /* 24px between */
}

.tdih-button-wrapper {
  margin-top: var(--saho-space-2);  /* 16px top margin */
}
```

### Example 2: Card Component Spacing

**From** `/webroot/themes/custom/saho/components/content/saho-card/saho-card.css`:

```css
.saho-card {
  /* Spacing tokens */
  --card-spacing: var(--saho-card-padding-compact);   /* 24px */
  --card-spacing-mobile: var(--saho-space-2);         /* 16px */
  --card-gap: var(--saho-card-gap);                   /* 24px internal */

  padding: var(--card-spacing);
}

.saho-card-title {
  margin-bottom: var(--saho-space-1);  /* 8px tight */
}

.saho-card-subtitle {
  margin-bottom: var(--saho-space-2);  /* 16px standard */
}

.saho-card-description {
  margin-bottom: var(--saho-space-2);  /* 16px before footer */
}

.saho-card-footer {
  margin-top: auto;
  padding-top: var(--saho-space-2);    /* 16px separation */
}

@media (max-width: 768px) {
  .saho-card {
    padding: var(--card-spacing-mobile);  /* 16px on mobile */
  }
}
```

### Example 3: Form Field Spacing

```css
.form-field {
  /* Vertical spacing between fields */
  margin-bottom: var(--saho-space-3);  /* 24px */
}

.form-field__label {
  /* Label to input gap */
  margin-bottom: var(--saho-space-1);  /* 8px tight */
  font-weight: var(--saho-font-weight-semibold);
}

.form-field__input {
  /* Internal padding */
  padding: var(--saho-space-2);        /* 16px all sides */
  border-radius: var(--saho-radius-sm);
}

.form-field__description {
  /* Description below input */
  margin-top: var(--saho-space-1);     /* 8px tight */
  font-size: var(--saho-font-size-sm);
  color: var(--saho-color-text-muted);
}

.form-actions {
  /* Button group spacing */
  margin-top: var(--saho-space-4);     /* 32px above buttons */
  display: flex;
  gap: var(--saho-space-2);            /* 16px between buttons */
}
```

### Example 4: Header Navigation Spacing

**From** `/_bootswatch.scss:1320-1400`:

```css
.saho-header-content {
  min-height: 70px;
  padding: var(--saho-space-2) 0;      /* 16px vertical */
}

.saho-desktop-nav ul.menu {
  display: flex;
  gap: 2rem;                            /* 32px - custom for nav */
}

.saho-desktop-nav li a {
  padding: 10px 5px;                    /* Custom for optical balance */
}

.saho-actions {
  display: flex;
  gap: var(--saho-space-2);             /* 16px between tools */
}

@media (max-width: 767px) {
  .saho-header-content {
    padding: var(--saho-space-1) 0;     /* 8px on mobile */
  }
}
```

### Example 5: Stack Layout Pattern

```css
/* General stack component */
.stack {
  display: flex;
  flex-direction: column;
}

/* Apply spacing between children using adjacent sibling selector */
.stack > * + * {
  margin-top: var(--saho-space-3);      /* 24px default */
}

/* Variants */
.stack--tight > * + * {
  margin-top: var(--saho-space-1);      /* 8px tight */
}

.stack--comfortable > * + * {
  margin-top: var(--saho-space-4);      /* 32px comfortable */
}

.stack--relaxed > * + * {
  margin-top: var(--saho-space-6);      /* 48px relaxed */
}

/* Responsive adjustment */
@media (max-width: 768px) {
  .stack > * + * {
    margin-top: var(--saho-space-2);    /* 16px on mobile */
  }
}
```

---

## Best Practices

### Magic Number Elimination

**❌ Bad - Magic numbers:**

```css
.component {
  padding: 17px;        /* Random number */
  margin-bottom: 23px;  /* No relationship to grid */
  gap: 19px;            /* Unpredictable */
}
```

**✅ Good - Using scale:**

```css
.component {
  padding: var(--saho-space-2);         /* 16px - predictable */
  margin-bottom: var(--saho-space-3);   /* 24px - harmonious */
  gap: var(--saho-space-2);             /* 16px - consistent */
}
```

### Consistent Patterns

**Use the same spacing for similar elements:**

```css
/* All cards use the same padding */
.card {
  padding: var(--saho-space-3);  /* 24px */
}

/* All grids use the same gap */
.grid {
  gap: var(--saho-space-4);      /* 32px STANDARD */
}

/* All form fields use the same margin */
.form-field {
  margin-bottom: var(--saho-space-3);  /* 24px */
}
```

### Component Isolation

**Define spacing within components using tokens:**

```css
.my-component {
  /* Component-scoped tokens inheriting from global */
  --component-padding: var(--saho-space-3);
  --component-gap: var(--saho-space-2);

  padding: var(--component-padding);
  display: flex;
  gap: var(--component-gap);
}
```

### Do ✅

- **Use the spacing scale** for all margins, padding, gaps
- **Start with mobile spacing**, enhance for desktop
- **Use space-4 (32px)** as STANDARD for card grids
- **Test at multiple viewport sizes** to ensure spacing works
- **Document spacing decisions** in component comments

### Don't ❌

- **Don't use magic numbers** - always use tokens
- **Don't break the 8px grid** (except for optical balance)
- **Don't use deprecated aliases** (xs, sm, md, lg)
- **Don't use pixel values** - use rem for scalability
- **Don't forget mobile spacing** - test on small screens

---

## Testing Checklist

### Visual Testing

- [ ] All spacing aligns to 8px grid
- [ ] No magic numbers in CSS
- [ ] Consistent spacing between similar elements
- [ ] Responsive spacing works at all breakpoints
- [ ] No awkward gaps or overlaps

### Accessibility Testing

- [ ] Touch targets are at least 44×44px
- [ ] Spacing aids readability (not too tight)
- [ ] Focus indicators have proper spacing
- [ ] Form fields have adequate spacing
- [ ] 200% zoom: spacing remains functional

### Browser Testing

- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari
- [ ] Mobile Safari (iOS)
- [ ] Chrome Android

---

## References

- **Design Tokens**: `/webroot/themes/custom/saho/src/scss/base/_variables.scss`
- **Card Component**: `/webroot/themes/custom/saho/components/content/saho-card/`
- **Grid Component**: `/webroot/themes/custom/saho/components/layout/saho-card-grid/`
- **Bootstrap Overrides**: `/webroot/themes/custom/saho/src/scss/_bootswatch.scss`

---

**Last Updated**: February 2026
**Status**: Production
**Version**: 1.0.0
