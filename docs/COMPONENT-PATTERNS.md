# SAHO Component Patterns

**Inline CSS Custom Properties for Modern, Maintainable Components**

This guide documents the component patterns established during the 2026 design system modernization, with a focus on inline CSS custom properties and zero-dependency architecture.

---

## Table of Contents

1. [Card Standard](#card-standard)
2. [Module CSS Pattern](#module-css-pattern)
3. [Common Token Mappings](#common-token-mappings)
4. [SDC Component Inventory](#sdc-component-inventory)
5. [Overview](#overview)
6. [Inline Custom Property Pattern](#inline-custom-property-pattern)
7. [Component Isolation](#component-isolation)
8. [Token Inheritance](#token-inheritance)
9. [Before/After Examples](#beforeafter-examples)
10. [Best Practices](#best-practices)
11. [Anti-Patterns](#anti-patterns)

---

---

## Card Standard

Every card block in every custom module **must** match this specification. Deviating from these values creates visual inconsistency across the site.

### Canonical Card Spec

| Property | Token | Value | Rule |
|---|---|---|---|
| `border-radius` | `--saho-radius-md` | 8px | NEVER use 12px (`--saho-radius-lg`) for cards |
| `box-shadow` (default) | `--saho-shadow-sm` | `0 1px 3px …` | Use on card default state |
| `box-shadow` (hover) | `--saho-shadow-xl` | `0 20px 60px …` | ALWAYS xl on hover — never lg or raw rgba |
| `hover transform` | — | `translateY(-4px)` | NEVER -8px, -6px, -5px, -3px, -2px |
| `image height` (desktop) | `--saho-block-image-height-desktop` | 200px | Modules may define `--module-image-height` |
| `image height` (mobile) | `--saho-block-image-height-mobile` | 160px | `@media (max-width: 576px)` |
| `image zoom` | — | `scale(1.05)` | On `.card:hover img` |
| `transition` | `--saho-transition-slow` | `0.3s ease` | All card hover transitions |
| Pill button `border-radius` | `--saho-button-radius-pill` | 9999px | Pill/CTA buttons |
| Circular element `border-radius` | `--saho-radius-full` | 9999px | Avatars, carousel buttons, badges |
| Modal/panel `border-radius` | `--saho-radius-lg` | 12px | NOT for cards |

### Card Token Block (Required Boilerplate)

Every module CSS file that renders cards must start with this block:

```css
.my-block-wrapper {
  /* ===== CARD STANDARD TOKENS ===== */
  --card-radius:              var(--saho-radius-md);             /* 8px — STANDARD */
  --card-shadow:              var(--saho-shadow-sm);
  --card-shadow-hover:        var(--saho-shadow-xl);             /* ALWAYS xl on hover */
  --card-transition:          all var(--saho-transition-slow, 0.3s ease);
  --card-hover-lift:          translateY(-4px);                  /* STANDARD — never change */
  --card-image-height:        200px;                             /* desktop */
  --card-image-height-mobile: 160px;                             /* ≤ 576px */
  --card-image-zoom:          scale(1.05);
}
```

### Required Hover Pattern

```css
.card {
  border-radius: var(--card-radius);
  box-shadow: var(--card-shadow);
  transition: var(--card-transition);
}

.card:hover {
  transform: var(--card-hover-lift);       /* translateY(-4px) */
  box-shadow: var(--card-shadow-hover);    /* --saho-shadow-xl */
}

.card:hover img {
  transform: var(--card-image-zoom);       /* scale(1.05) */
}
```

---

## Module CSS Pattern

### Boilerplate Structure

Every custom module CSS file should follow this structure:

```css
/**
 * Module Name Block
 * Brief description.
 *
 * Uses SAHO Design Token System.
 * See docs/DESIGN-TOKENS.md for token reference.
 */

/* ===== COMPONENT TOKENS ===== */
/* All design values inherit from the global --saho-* system.          */
/* Module-specific overrides use the --module-* prefix.                */

.module-wrapper {
  /* --- Colors --- */
  --module-color-primary:    var(--saho-color-primary);
  --module-color-surface:    var(--saho-color-white);
  --module-color-surface-alt: var(--saho-color-surface-alt);
  --module-color-text:       var(--saho-color-text-primary);
  --module-color-text-muted: var(--saho-color-text-muted);
  --module-color-border:     var(--saho-color-border);

  /* --- Card Standard (must match every other module) --- */
  --card-radius:              var(--saho-radius-md);
  --card-shadow:              var(--saho-shadow-sm);
  --card-shadow-hover:        var(--saho-shadow-xl);
  --card-transition:          all var(--saho-transition-slow, 0.3s ease);
  --card-hover-lift:          translateY(-4px);
  --card-image-height:        200px;
  --card-image-height-mobile: 160px;
  --card-image-zoom:          scale(1.05);

  /* --- Module-specific values --- */
  --module-image-height:     var(--saho-block-image-height-desktop, 200px);
}
```

### Rules

1. **No raw hex values** — every color must be a `var(--saho-*)` token
2. **No raw rgba shadows** — use `var(--saho-shadow-sm/md/lg/xl)`
3. **No raw pixel values for spacing** — use `var(--saho-space-*)` tokens
4. **Hover transform is always `translateY(-4px)`** — the card standard
5. **`darken()` is forbidden** — SCSS functions cannot process CSS custom properties; use `var(--saho-color-primary-dark)` instead
6. **`border-radius: 50%`** — replace with `var(--saho-radius-full)` (semantically correct for circular elements)

---

## Common Token Mappings

Reference table for migrating raw values to tokens:

### Colors

| Raw Value | Token | Notes |
|---|---|---|
| `#990000`, `#900` | `var(--saho-color-primary)` | SAHO heritage red |
| `#8b0000`, `#7a0000` | `var(--saho-color-primary-dark)` | Hover/dark state |
| `#b30000`, `#b22222` | `var(--saho-color-primary-dark)` | Closest token |
| `#B22222` (fire-brick) | `var(--saho-color-primary-light)` | If lighter intent |
| `rgba(153,0,0,0.1)` | `var(--saho-color-primary-alpha-10)` | |
| `rgba(153,0,0,0.25)` | `var(--saho-color-primary-alpha-25)` | Focus rings |
| `#2c3e50`, `#1e293b`, `#1a202c` | `var(--saho-color-text-primary)` | Dark text |
| `#4a5568`, `#374151`, `#475569` | `var(--saho-color-text-secondary)` | |
| `#6b7280`, `#6c757d`, `#718096` | `var(--saho-color-text-muted)` | Muted/caption text |
| `#ffffff`, `white`, `#fff` | `var(--saho-color-white)` | Pure white |
| `#f8f9fa`, `#f9fafb`, `#f3f4f6` | `var(--saho-color-surface-alt)` | Off-white backgrounds |
| `#e9ecef`, `#e5e7eb`, `#dee2e6` | `var(--saho-color-border)` | Borders |
| `#8b4513` (brown-earth) | `var(--saho-color-primary)` | **Brown is not a SAHO brand color** |

### Shadows

| Raw Value | Token | Context |
|---|---|---|
| `0 1px 3px rgba(0,0,0,0.1)` | `var(--saho-shadow-sm)` | Default card |
| `0 2px 8px rgba(0,0,0,0.08)` | `var(--saho-shadow-md)` | Elevated elements |
| `0 8px 24px rgba(0,0,0,0.12)` | `var(--saho-shadow-lg)` | Dropdowns, tooltips |
| `0 20px 60px rgba(0,0,0,0.15)` | `var(--saho-shadow-xl)` | **Card hover — STANDARD** |
| `rgba(0,0,0,0.05)` | `var(--saho-color-black-alpha-05)` | Subtle overlays |
| `rgba(0,0,0,0.10)` | `var(--saho-color-black-alpha-10)` | |
| `rgba(0,0,0,0.15)` | `var(--saho-color-black-alpha-15)` | |

### Spacing

| Raw Value | Token | px Value |
|---|---|---|
| `0.5rem` | `var(--saho-space-1)` | 8px |
| `1rem` | `var(--saho-space-2)` | 16px |
| `1.5rem` | `var(--saho-space-3)` | 24px |
| `2rem` | `var(--saho-space-4)` | 32px |
| `3rem` | `var(--saho-space-6)` | 48px |

### Border Radius

| Raw Value | Token | Use Case |
|---|---|---|
| `8px` | `var(--saho-radius-md)` | **Cards — STANDARD** |
| `12px` | `var(--saho-radius-lg)` | Modals, panels only |
| `25px`, `9999px`, `50%` (pill) | `var(--saho-button-radius-pill)` | Pill buttons |
| `50%` (circle) | `var(--saho-radius-full)` | Avatar, carousel button |

---

## SDC Component Inventory

Status of all Single Directory Components (SDC) in the theme:

### Content Components

| Component | Path | Token Status | Notes |
|---|---|---|---|
| `saho-card` | `components/content/saho-card/` | ✅ Fully tokenised | 3 layout variants: default, horizontal, feature |
| `saho-button` | `components/utilities/saho-button/` | ✅ Fully tokenised | Uses `--saho-*` tokens |
| `saho-hero-banner` | `components/layout/saho-hero-banner/` | ✅ | SDC component |
| `saho-featured-grid` | `components/layout/saho-featured-grid/` | ✅ | SDC component |
| `saho-card-grid` | `components/layout/saho-card-grid/` | ✅ | Grid layout wrapper |
| `page-footer` | `components/page-footer/` | ✅ | Footer SDC |
| `region` | `components/region/` | ✅ | Region wrapper |

### Module Block CSS (Post-Audit Status)

| Module CSS File | Token Status | Card Standard |
|---|---|---|
| `saho_statistics/css/top-read-content.css` | ✅ Excellent | ✅ |
| `saho_statistics/css/history-through-pictures.css` | ✅ Full rewrite done | ✅ |
| `saho_featured_articles/css/featured-articles.css` | ✅ Tokenised | ✅ `--saho-radius-md` (was 12px) |
| `saho_utils/featured_biography/css/featured-biography.css` | ✅ Tokenised | ✅ hover `-4px` |
| `saho_utils/entity_overview/css/entity-overview.css` | ✅ Tokenised | ✅ hover `-4px`, no brown |
| `saho_upcoming_events/css/upcoming-events.css` | ✅ Tokenised | ✅ image 240px |
| `saho_suggested_reading/css/suggested-reading.css` | ✅ Tokenised | ✅ hover `-4px` |
| `saho_utils/tdih/css/tdih-block.css` | ✅ Tokenised | ✅ |
| `saho_utils/tdih/css/tdih-page.css` | ✅ Tokenised | ✅ no brown border |
| `saho_utils/educational_resources/css/educational-resources.css` | ✅ Tokenised | ✅ hover `-4px` |
| `saho_utils/history_classroom/css/history-classroom.css` | ✅ Tokenised | ✅ hover `-4px` |

---

## Overview

### The Problem

**Traditional SCSS approach:**
- Components depend on external variables
- Recompilation needed for changes
- Hard to trace token usage
- Components not truly isolated
- Dark mode requires SCSS recompilation

### The Solution

**Inline CSS custom properties:**
- Component self-contained with all tokens
- Runtime configuration without recompilation
- Clear token usage within component
- True component isolation
- Dark mode via media queries

### Core Philosophy

1. **Self-Contained**: All design tokens defined inline
2. **Inherit Global Tokens**: Reference unified system via `var(--saho-*)`
3. **Component-Scoped**: Prefix with component name (`--card-*`, `--cite-*`)
4. **Zero Dependencies**: No external SCSS variables required
5. **Inspectable**: Easy to debug in browser DevTools

---

## Inline Custom Property Pattern

### Basic Pattern

```css
.component-name {
  /* 1. Define component-scoped tokens */
  --component-color: var(--saho-color-primary);
  --component-spacing: var(--saho-space-3);
  --component-radius: var(--saho-radius-md);

  /* 2. Apply tokens to styles */
  color: var(--component-color);
  padding: var(--component-spacing);
  border-radius: var(--component-radius);
}
```

### Complete Component Example

**From** `/webroot/themes/custom/saho/components/content/saho-card/saho-card.css:16-76`:

```css
.saho-card {
  /* ===== COMPONENT-SCOPED DESIGN TOKENS ===== */
  /* Inherit from global system, scope to component */

  /* Colors */
  --card-color-primary: var(--saho-color-primary);
  --card-color-secondary: var(--saho-color-secondary);
  --card-bg-white: var(--saho-color-white);
  --card-text-primary: var(--saho-color-text-primary);
  --card-text-muted: var(--saho-color-text-muted);
  --card-border-light: var(--saho-color-border-light);

  /* Spacing (8px baseline grid) */
  --card-border-radius: var(--saho-card-radius);      /* 8px */
  --card-spacing: var(--saho-card-padding-compact);   /* 24px */
  --card-spacing-mobile: var(--saho-space-2);         /* 16px */
  --card-gap: var(--saho-card-gap);                   /* 24px */

  /* Typography */
  --card-title-size: var(--saho-font-size-lg);        /* 20px */
  --card-title-weight: var(--saho-font-weight-semibold);
  --card-body-size: var(--saho-font-size-base);       /* 16px */

  /* Transitions */
  --card-transition-base: var(--saho-transition-base);

  /* ===== COMPONENT STYLES ===== */
  /* Use scoped tokens, NOT global tokens directly */
  background: var(--card-bg-white);
  border: 1px solid var(--card-border-light);
  border-radius: var(--card-border-radius);
  padding: var(--card-spacing);
  box-shadow: var(--saho-shadow-md);
  transition: var(--card-transition-base);
}
```

### Benefits of This Pattern

 **Self-Documenting**: All tokens visible at component level
 **Easy Overrides**: Change tokens without touching styles
 **Runtime Theming**: No recompilation needed
 **DevTools Friendly**: Inspect computed values easily
 **Maintainable**: Clear token source and usage
 **Testable**: Easy to verify token values

---

## Component Isolation

### True Component Independence

Each component should be completely self-contained:

```css
/*  BAD - External dependency */
.component {
  color: $external-variable;      /* SCSS dependency */
  padding: 1.5rem;                /* Magic number */
}

/*  GOOD - Self-contained */
.component {
  --component-color: var(--saho-color-primary);
  --component-padding: var(--saho-space-3);

  color: var(--component-color);
  padding: var(--component-padding);
}
```

### Namespace Scoping

**Always prefix component tokens with component name:**

```css
/* Button component */
.saho-button {
  --button-bg: var(--saho-color-primary);       /*  button- prefix */
  --button-padding: var(--saho-space-2);
  --button-radius: var(--saho-radius-pill);
}

/* Card component */
.saho-card {
  --card-bg: var(--saho-color-white);           /*  card- prefix */
  --card-padding: var(--saho-space-3);
  --card-radius: var(--saho-radius-md);
}

/* Modal component */
#citation-modal {
  --cite-bg: var(--saho-color-white);           /*  cite- prefix */
  --cite-padding: var(--saho-space-3);
  --cite-radius: var(--saho-radius-lg);
}
```

### Why Namespace?

- **Avoids collisions** between components
- **Clear ownership** of tokens
- **Easy search** in DevTools
- **Predictable inheritance**

---

## Token Inheritance

### Three-Tier Token System

```
Global Tokens → Component Tokens → Element Styles
  (:root)     →  (.component)   →  (.component__element)
```

### Example: Citation Modal

**From** `/webroot/modules/custom/saho_tools/css/citation-modern.css:20-119`:

```css
/* TIER 1: Global tokens (defined in _variables.scss) */
:root {
  --saho-color-white: #ffffff;
  --saho-space-3: 1.5rem;        /* 24px */
  --saho-radius-lg: 0.75rem;     /* 12px */
  --saho-shadow-xl: 0 0.625rem 1.875rem rgba(0, 0, 0, 0.1);
}

/* TIER 2: Component-scoped tokens (inherit from global) */
#citation-modal .modal-content {
  --cite-bg: var(--saho-color-white);           /* Inherits global */
  --cite-radius: var(--saho-radius-lg);         /* Inherits global */
  --cite-shadow: var(--saho-shadow-xl);         /* Inherits global */
  --cite-padding: var(--saho-space-3);          /* Inherits global */

  /* TIER 3: Element styles (use component tokens) */
  background: var(--cite-bg);
  border-radius: var(--cite-radius);
  box-shadow: var(--cite-shadow);
  padding: var(--cite-padding);
}

/* Child elements inherit component tokens */
#citation-modal .modal-header {
  padding: var(--cite-padding);                  /* Inherits from parent */
}

#citation-modal .modal-body {
  background: var(--cite-bg);                    /* Inherits from parent */
}
```

### Inheritance Benefits

 **Cascade-Aware**: Leverage CSS cascade naturally
 **Easy Overrides**: Change parent token, children update
 **Consistent Spacing**: All elements reference same tokens
 **Responsive**: Override tokens at media queries

### Responsive Token Overrides

```css
.component {
  --component-padding: var(--saho-space-3);      /* 24px default */
}

@media (max-width: 768px) {
  .component {
    --component-padding: var(--saho-space-2);    /* 16px mobile */
  }
}

/* All child elements automatically update! */
```

---

## Before/After Examples

### Example 1: Button Component

#### Before (Legacy SCSS)

```scss
// _variables.scss
$primary-color: #990000;
$button-padding: 0.75rem 1.5rem;

// button.scss
.btn-primary {
  background: $primary-color;          // External dependency
  padding: $button-padding;            // External dependency
  border-radius: 25px;                 // Magic number

  &:hover {
    background: darken($primary-color, 10%);  // Requires SCSS function
  }
}
```

**Problems:**
-  Depends on external variables
-  Requires SCSS compilation for changes
-  Magic numbers
-  Can't override at runtime

#### After (Modern CSS)

**From** `/_bootswatch.scss:56-87`:

```css
.btn-primary {
  /* Self-contained tokens inheriting from unified system */
  background: var(--saho-color-primary) !important;
  color: var(--saho-color-white) !important;
  border-color: var(--saho-color-primary) !important;
  border-radius: 25px !important;      /* Pill shape */
  font-weight: 600 !important;
  padding: 0.75rem 1.5rem !important;
  transition: all 0.2s ease !important;

  &:hover {
    background: var(--saho-color-primary-dark) !important;
    box-shadow: 0 3px 10px var(--saho-color-black-alpha-10) !important;
  }

  &:focus-visible {
    outline: 2px solid var(--saho-color-primary) !important;
    box-shadow: 0 0 0 4px var(--saho-color-primary-alpha-25) !important;
  }
}
```

**Benefits:**
-  Uses unified tokens
-  Runtime configurable
-  Clear token source
-  Can override with specificity

---

### Example 2: Card Grid Component

#### Before (Legacy SCSS)

```scss
// _variables.scss
$grid-gap: 2rem;
$card-min-width: 300px;

// grid.scss
.card-grid {
  display: grid;
  gap: $grid-gap;                      // External dependency
  grid-template-columns: repeat(auto-fill, minmax($card-min-width, 1fr));

  @media (max-width: 768px) {
    gap: 1rem;                         // Magic number
  }
}
```

**Problems:**
-  External SCSS variables
-  Magic numbers in media queries
-  Hard to trace gap source

#### After (Modern CSS)

**From** `/webroot/themes/custom/saho/components/layout/saho-card-grid/saho-card-grid.css:12-37`:

```css
.saho-card-grid {
  /* Grid-scoped tokens - inherit from unified system */
  --grid-gap: var(--saho-space-4);               /* 32px - STANDARD */
  --grid-gap-mobile: var(--saho-space-2);        /* 16px */
  --grid-card-min-width: var(--saho-grid-card-min-width);  /* 300px */

  /* Apply styles using scoped tokens */
  display: grid;
  gap: var(--grid-gap);
  grid-template-columns: repeat(auto-fill, minmax(var(--grid-card-min-width), 1fr));
}

/* Responsive - override tokens */
@media (max-width: 768px) {
  .saho-card-grid {
    gap: var(--grid-gap-mobile);  /* Uses token, not magic number */
  }
}
```

**Benefits:**
-  Self-documenting gap values
-  Semantic token names
-  No magic numbers
-  Easy to update globally

---

### Example 3: TDIH Block

#### Before (Mixed Approach)

```scss
// Scattered across multiple files
.tdih-block {
  margin: 32px 0;                      // Magic number
  padding: 24px;                       // Magic number
}

.tdih-block .title {
  font-size: 1.5rem;                   // Magic number
  color: $primary-color;               // External variable
}
```

#### After (Unified Tokens)

**From** `/_bootswatch.scss:169-197`:

```css
.tdih-block {
  /* Component-scoped tokens */
  --tdih-block-spacing: var(--saho-space-4);           /* 32px */
  --tdih-block-padding: var(--saho-space-3);           /* 24px */
  --tdih-block-item-spacing: var(--saho-space-3);      /* 24px */
  --tdih-block-font-title: var(--saho-font-size-xl);   /* 24-30px */
  --tdih-block-font-subtitle: var(--saho-font-size-md); /* 18-20px */
  --tdih-block-font-date: var(--saho-font-size-sm);    /* 14-16px */

  margin: var(--tdih-block-spacing) 0;
  padding: var(--tdih-block-padding);
  box-shadow: var(--saho-shadow-md);
  border-radius: var(--saho-radius-md);
}

.tdih-block .block-title {
  font-size: var(--tdih-block-font-title);
  color: var(--saho-color-primary);
  font-weight: var(--saho-font-weight-semibold);
}
```

**Benefits:**
-  All tokens defined in one place
-  Zero magic numbers
-  Fluid typography
-  Easy to customize

---

## Best Practices

### 1. Always Define Tokens First

```css
/*  GOOD - Tokens then styles */
.component {
  /* Define all tokens */
  --component-color: var(--saho-color-primary);
  --component-spacing: var(--saho-space-3);

  /* Apply tokens */
  color: var(--component-color);
  padding: var(--component-spacing);
}

/*  BAD - Mixed definition and usage */
.component {
  color: var(--component-color);         /* Defined where? */
  --component-color: var(--saho-color-primary);
  padding: var(--component-spacing);
  --component-spacing: var(--saho-space-3);
}
```

### 2. Comment Token Sections

```css
.component {
  /* ===== COLOR TOKENS ===== */
  --component-color-bg: var(--saho-color-white);
  --component-color-text: var(--saho-color-text-primary);

  /* ===== SPACING TOKENS ===== */
  --component-padding: var(--saho-space-3);
  --component-gap: var(--saho-space-2);

  /* ===== TYPOGRAPHY TOKENS ===== */
  --component-font-size: var(--saho-font-size-base);
  --component-font-weight: var(--saho-font-weight-regular);

  /* ===== COMPONENT STYLES ===== */
  background: var(--component-color-bg);
  color: var(--component-color-text);
  padding: var(--component-padding);
}
```

### 3. Use Semantic Names

```css
/*  GOOD - Semantic names */
--card-title-size: var(--saho-font-size-lg);
--card-body-size: var(--saho-font-size-base);
--card-meta-size: var(--saho-font-size-sm);

/*  BAD - Generic names */
--card-font-1: var(--saho-font-size-lg);
--card-font-2: var(--saho-font-size-base);
--card-font-3: var(--saho-font-size-sm);
```

### 4. Document Token Usage

```css
.component {
  --component-padding: var(--saho-space-3);    /* 24px - comfortable spacing */
  --component-radius: var(--saho-radius-md);   /* 8px - STANDARD for cards */
  --component-gap: var(--saho-space-4);        /* 32px - STANDARD for grids */
}
```

### 5. Group Related Tokens

```css
.modal {
  /* Modal container tokens */
  --modal-max-width: 720px;
  --modal-margin: var(--saho-space-3);

  /* Modal content tokens */
  --modal-bg: var(--saho-color-white);
  --modal-radius: var(--saho-radius-lg);
  --modal-shadow: var(--saho-shadow-xl);

  /* Modal padding tokens */
  --modal-padding: var(--saho-space-3);
  --modal-padding-mobile: var(--saho-space-2);
}
```

---

## Anti-Patterns

###  Don't Reference Global Tokens Directly in Styles

```css
/*  BAD - Direct global token usage */
.component {
  color: var(--saho-color-primary);      /* Skip component layer */
  padding: var(--saho-space-3);          /* Hard to override */
}

/*  GOOD - Component-scoped tokens */
.component {
  --component-color: var(--saho-color-primary);
  --component-padding: var(--saho-space-3);

  color: var(--component-color);         /* Easy to override */
  padding: var(--component-padding);
}
```

###  Don't Use Magic Numbers

```css
/*  BAD - Magic numbers */
.component {
  padding: 17px;          /* Random value */
  margin: 23px;           /* No relationship to grid */
  font-size: 1.3rem;      /* Arbitrary size */
}

/*  GOOD - Token-based */
.component {
  padding: var(--saho-space-2);         /* 16px - grid aligned */
  margin: var(--saho-space-3);          /* 24px - harmonious */
  font-size: var(--saho-font-size-md);  /* 18-20px - fluid */
}
```

###  Don't Mix SCSS Variables and CSS Custom Properties

```css
/*  BAD - Mixed approach */
.component {
  --component-padding: var(--saho-space-3);
  color: $scss-variable;         /* SCSS dependency */
  padding: var(--component-padding);
}

/*  GOOD - CSS custom properties only */
.component {
  --component-color: var(--saho-color-primary);
  --component-padding: var(--saho-space-3);

  color: var(--component-color);
  padding: var(--component-padding);
}
```

###  Don't Nest Token Definitions Too Deeply

```css
/*  BAD - Hard to trace */
.component {
  --level-1: var(--saho-space-3);
}

.component__child {
  --level-2: var(--level-1);
}

.component__grandchild {
  --level-3: var(--level-2);
  padding: var(--level-3);       /* Where does this come from? */
}

/*  GOOD - Clear inheritance */
.component {
  --component-padding: var(--saho-space-3);
}

.component__child {
  padding: var(--component-padding);     /* Clear source */
}
```

---

## Code Review Checklist

When reviewing component code, check for:

- [ ]  All design tokens defined inline
- [ ]  Component-scoped token names (prefixed)
- [ ]  Inherits from unified `--saho-*` tokens
- [ ]  No external SCSS variable dependencies
- [ ]  No magic numbers in styles
- [ ]  Token sections commented
- [ ]  Semantic token names
- [ ]  Responsive token overrides
- [ ]  DevTools-inspectable values
- [ ]  Documentation includes token usage

---

## Testing Component Tokens

### Browser DevTools

1. **Inspect element**
2. **Check Computed tab** for final values
3. **Trace token inheritance** back to source
4. **Override tokens** to test theming
5. **Verify responsive overrides**

### Runtime Theming Test

```javascript
// Test token override in browser console
document.querySelector('.saho-card').style.setProperty('--card-spacing', '32px');
```

### Dark Mode Test

```css
/* Test dark mode override */
@media (prefers-color-scheme: dark) {
  .component {
    --component-bg: var(--saho-dark-bg-primary);
    --component-text: var(--saho-dark-text-primary);
  }
}
```

---

## Migration Strategy

### Phase 1: Identify Components

- List all components in codebase
- Note external dependencies
- Document current token usage

### Phase 2: Create Component Tokens

- Define inline tokens for each component
- Inherit from unified `--saho-*` system
- Remove SCSS variable dependencies

### Phase 3: Update Styles

- Replace magic numbers with tokens
- Replace global tokens with component tokens
- Test in DevTools

### Phase 4: Verify & Document

- Test responsive behavior
- Document token usage
- Update component README

---

## References

- **Design Tokens**: `DESIGN-TOKENS.md`
- **Card Component**: `/webroot/themes/custom/saho/components/content/saho-card/`
- **Grid Component**: `/webroot/themes/custom/saho/components/layout/saho-card-grid/`
- **Citation Modal**: `/webroot/modules/custom/saho_tools/css/citation-modern.css`
- **Button System**: `/webroot/themes/custom/saho/src/scss/_bootswatch.scss`

---

**Last Updated**: March 2026 (Phase A–D design system unification)
**Status**: Production
**Version**: 2.0.0
