# SAHO Component Patterns

**Inline CSS Custom Properties for Modern, Maintainable Components**

This guide documents the component patterns established during the 2026 design system modernization, with a focus on inline CSS custom properties and zero-dependency architecture.

---

## Table of Contents

1. [Overview](#overview)
2. [Inline Custom Property Pattern](#inline-custom-property-pattern)
3. [Component Isolation](#component-isolation)
4. [Token Inheritance](#token-inheritance)
5. [Before/After Examples](#beforeafter-examples)
6. [Best Practices](#best-practices)
7. [Anti-Patterns](#anti-patterns)

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

**Last Updated**: February 2026
**Status**: Production
**Version**: 1.0.0
