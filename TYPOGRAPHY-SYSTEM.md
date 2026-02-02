# SAHO Typography System

**Fluid, Accessible, Responsive Type Scale**

The SAHO typography system uses modern fluid typography with `clamp()` to create a seamless reading experience across all devices while maintaining WCAG 2.1 AA accessibility standards.

---

## Table of Contents

1. [Overview](#overview)
2. [Font Stack](#font-stack)
3. [Type Scale](#type-scale)
4. [Line Height System](#line-height-system)
5. [Font Weight Scale](#font-weight-scale)
6. [Usage Guidelines](#usage-guidelines)
7. [Accessibility](#accessibility)
8. [Code Examples](#code-examples)

---

## Overview

### Philosophy

The SAHO typography system prioritizes:

1. **Readability**: Optimized for long-form academic content
2. **Fluidity**: Smooth scaling between mobile and desktop
3. **Accessibility**: WCAG 2.1 AA compliant with 200% zoom support
4. **Consistency**: Single source of truth for all type sizes
5. **Performance**: System fonts first, web fonts as enhancement

### Key Features

- **10-tier fluid scale** using `clamp()` for responsive sizing
- **4-tier line height system** for different content types
- **6-tier font weight scale** from light to black
- **Semantic heading tokens** (h1-h6) mapped to scale
- **Automatic scaling** without media queries
- **Zero magic numbers** - all sizes derived from scale

---

## Font Stack

### Primary Font Family

```css
font-family: "Inter", -apple-system, BlinkMacSystemFont,
             "Segoe UI", Roboto, "Helvetica Neue", Arial,
             sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
```

**File**: `/webroot/themes/custom/saho/src/scss/base/_variables.scss:369`

### Fallback Strategy

1. **Inter** - Primary web font (clean, modern, readable)
2. **-apple-system** - iOS/macOS native font
3. **BlinkMacSystemFont** - Chrome on macOS
4. **Segoe UI** - Windows native font
5. **Roboto** - Android native font
6. **Helvetica Neue** - Legacy macOS
7. **Arial** - Universal fallback
8. **sans-serif** - Browser default
9. **Emoji fonts** - Proper emoji rendering

### Why Inter?

- **Excellent readability** at all sizes
- **Professional appearance** for academic content
- **Variable font support** (future enhancement)
- **Open source** and widely tested
- **Optimized for screens** with tall x-height

---

## Type Scale

### 10-Tier Fluid Scale

All typography scales smoothly using `clamp(min, preferred, max)`:

```css
/* Extra Small */
--saho-font-size-xs: clamp(0.75rem, 0.7rem + 0.25vw, 0.875rem);
/* Desktop: 14px, Mobile: 12px */

/* Small */
--saho-font-size-sm: clamp(0.875rem, 0.85rem + 0.25vw, 1rem);
/* Desktop: 16px, Mobile: 14px */

/* Base (Body Text) */
--saho-font-size-base: clamp(1rem, 0.95rem + 0.25vw, 1.125rem);
/* Desktop: 18px, Mobile: 16px */

/* Medium */
--saho-font-size-md: clamp(1.125rem, 1.05rem + 0.375vw, 1.25rem);
/* Desktop: 20px, Mobile: 18px */

/* Large */
--saho-font-size-lg: clamp(1.25rem, 1.15rem + 0.5vw, 1.5rem);
/* Desktop: 24px, Mobile: 20px */

/* Extra Large */
--saho-font-size-xl: clamp(1.5rem, 1.35rem + 0.75vw, 1.875rem);
/* Desktop: 30px, Mobile: 24px */

/* 2X Large */
--saho-font-size-2xl: clamp(1.75rem, 1.5rem + 1.25vw, 2.25rem);
/* Desktop: 36px, Mobile: 28px */

/* 3X Large */
--saho-font-size-3xl: clamp(2rem, 1.75rem + 1.5vw, 3rem);
/* Desktop: 48px, Mobile: 32px */

/* 4X Large */
--saho-font-size-4xl: clamp(2.5rem, 2rem + 2vw, 4rem);
/* Desktop: 64px, Mobile: 40px */

/* 5X Large */
--saho-font-size-5xl: clamp(3rem, 2.5rem + 2.5vw, 5rem);
/* Desktop: 80px, Mobile: 48px */
```

**File**: `/webroot/themes/custom/saho/src/scss/base/_variables.scss:116-125`

### Semantic Heading Tokens

Map headings to the fluid scale:

```css
/* Heading sizes */
--saho-font-h1: var(--saho-font-size-3xl);    /* 32-48px */
--saho-font-h2: var(--saho-font-size-2xl);    /* 28-36px */
--saho-font-h3: var(--saho-font-size-xl);     /* 24-30px */
--saho-font-h4: var(--saho-font-size-lg);     /* 20-24px */
--saho-font-h5: var(--saho-font-size-md);     /* 18-20px */
--saho-font-h6: var(--saho-font-size-base);   /* 16-18px */

/* Display sizes for hero sections */
--saho-font-display-xl: var(--saho-font-size-5xl);  /* 48-80px */
--saho-font-display: var(--saho-font-size-4xl);     /* 40-64px */
```

**File**: `/webroot/themes/custom/saho/src/scss/base/_variables.scss:128-137`

### Size Usage Guide

| Token | Mobile | Desktop | Use Case |
|-------|--------|---------|----------|
| `xs` | 12px | 14px | Captions, badges, metadata |
| `sm` | 14px | 16px | Supporting text, small UI |
| `base` | 16px | 18px | **Body text (default)** |
| `md` | 18px | 20px | Lead paragraphs, subtitles |
| `lg` | 20px | 24px | H4 headings, card titles |
| `xl` | 24px | 30px | H3 headings, section titles |
| `2xl` | 28px | 36px | H2 headings, page subtitles |
| `3xl` | 32px | 48px | **H1 headings, page titles** |
| `4xl` | 40px | 64px | Display text, hero titles |
| `5xl` | 48px | 80px | Large hero text |

---

## Line Height System

### 4-Tier Line Height Scale

```css
/* Tight - Headings and display text */
--saho-line-height-tight: 1.2;      /* 120% */

/* Snug - Subheadings */
--saho-line-height-snug: 1.3;       /* 130% */

/* Normal - Body text (default) */
--saho-line-height-normal: 1.5;     /* 150% */

/* Relaxed - Comfortable reading */
--saho-line-height-relaxed: 1.6;    /* 160% */

/* Loose - Wide line spacing */
--saho-line-height-loose: 1.8;      /* 180% */
```

**File**: `/webroot/themes/custom/saho/src/scss/base/_variables.scss:143-147`

### Line Height Usage

| Line Height | Ratio | Use Case |
|-------------|-------|----------|
| `tight` | 1.2 | H1, H2, display text, short lines |
| `snug` | 1.3 | H3-H6, card titles |
| `normal` | 1.5 | **Body text (WCAG minimum)** |
| `relaxed` | 1.6 | Long-form content, articles |
| `loose` | 1.8 | Poetry, quotes, emphasis |

### WCAG 2.1 AA Compliance

- **Minimum line height**: 1.5 for body text
- **Minimum paragraph spacing**: 2× font size
- **200% zoom**: All text remains readable

---

## Font Weight Scale

### 6-Tier Weight System

```css
/* Light - Decorative text only */
--saho-font-weight-light: 300;

/* Regular - Body text (default) */
--saho-font-weight-regular: 400;

/* Medium - UI elements, emphasis */
--saho-font-weight-medium: 500;

/* Semibold - Subheadings, buttons */
--saho-font-weight-semibold: 600;

/* Bold - Headings, strong emphasis */
--saho-font-weight-bold: 700;

/* Black - Display text (sparingly) */
--saho-font-weight-black: 900;
```

**File**: `/webroot/themes/custom/saho/src/scss/base/_variables.scss:153-158`

### Weight Usage Guidelines

| Weight | Value | Use Case | Examples |
|--------|-------|----------|----------|
| `light` | 300 | Decorative only | Large display text, quotes |
| `regular` | 400 | **Body text** | Paragraphs, descriptions |
| `medium` | 500 | UI elements | Buttons, badges, labels |
| `semibold` | 600 | Subheadings | H4-H6, card titles, CTAs |
| `bold` | 700 | **Headings** | H1-H3, strong emphasis |
| `black` | 900 | Display text | Hero titles (sparingly) |

**Accessibility Note**: Avoid light weights (300) for body text - use 400+ for readability.

---

## Usage Guidelines

### Body Text (Articles & Content)

```css
.article-content {
  font-size: var(--saho-font-size-base);           /* 16-18px */
  line-height: var(--saho-line-height-relaxed);    /* 1.6 */
  font-weight: var(--saho-font-weight-regular);    /* 400 */
  color: var(--saho-color-text-primary);
}
```

### Headings

```css
h1 {
  font-size: var(--saho-font-h1);                  /* 32-48px */
  line-height: var(--saho-line-height-tight);      /* 1.2 */
  font-weight: var(--saho-font-weight-bold);       /* 700 */
  margin-bottom: var(--saho-space-3);              /* 24px */
}

h2 {
  font-size: var(--saho-font-h2);                  /* 28-36px */
  line-height: var(--saho-line-height-tight);      /* 1.2 */
  font-weight: var(--saho-font-weight-bold);       /* 700 */
}

h3 {
  font-size: var(--saho-font-h3);                  /* 24-30px */
  line-height: var(--saho-line-height-snug);       /* 1.3 */
  font-weight: var(--saho-font-weight-semibold);   /* 600 */
}
```

### Display Text (Heroes)

```css
.hero-title {
  font-size: var(--saho-font-display);             /* 40-64px */
  line-height: var(--saho-line-height-tight);      /* 1.2 */
  font-weight: var(--saho-font-weight-bold);       /* 700 */
  letter-spacing: -0.02em;                         /* Tighter tracking */
}
```

### UI Text (Buttons, Labels)

```css
.button {
  font-size: var(--saho-font-size-sm);             /* 14-16px */
  font-weight: var(--saho-font-weight-semibold);   /* 600 */
  line-height: 1;                                   /* No extra space */
  letter-spacing: 0.025em;                         /* Slight tracking */
}
```

### Metadata (Dates, Authors)

```css
.metadata {
  font-size: var(--saho-font-size-xs);             /* 12-14px */
  font-weight: var(--saho-font-weight-regular);    /* 400 */
  color: var(--saho-color-text-muted);
}
```

---

## Accessibility

### WCAG 2.1 AA Requirements

#### Minimum Text Size
- **Small text**: 16px minimum (14px acceptable for supporting text)
- **Touch targets**: 44×44px minimum for interactive elements

#### Contrast Ratios
- **Normal text** (<18pt): 4.5:1 minimum
- **Large text** (≥18pt or ≥14pt bold): 3:1 minimum

#### Line Height & Spacing
- **Line height**: 1.5 minimum for body text
- **Paragraph spacing**: 2× font size minimum
- **Letter spacing**: 0.12× font size minimum

#### 200% Zoom Testing
All typography tested at 200% browser zoom:
- ✅ No horizontal scrolling
- ✅ All text readable
- ✅ No content overlap
- ✅ Maintain layout integrity

### Reduced Motion Support

```css
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
}
```

### High Contrast Mode

```css
@media (prefers-contrast: high) {
  body {
    font-weight: var(--saho-font-weight-medium);  /* Increase weight */
  }

  h1, h2, h3, h4, h5, h6 {
    font-weight: var(--saho-font-weight-black);   /* Maximum weight */
  }
}
```

---

## Code Examples

### Example 1: TDIH Block Typography

**From** `/webroot/themes/custom/saho/src/scss/_bootswatch.scss:169-197`:

```css
.tdih-block {
  /* Component-scoped typography tokens */
  --tdih-block-font-title: var(--saho-font-size-xl);       /* 24-30px */
  --tdih-block-font-subtitle: var(--saho-font-size-md);    /* 18-20px */
  --tdih-block-font-date: var(--saho-font-size-sm);        /* 14-16px */
  --tdih-block-font-body: var(--saho-font-size-base);      /* 16-18px */

  font-family: $font-family-base;
}

.tdih-block .block-title {
  font-size: var(--tdih-block-font-title);
  color: var(--saho-color-primary);
  font-weight: var(--saho-font-weight-bold);
  margin-bottom: var(--saho-space-1);
}

.tdih-item h3 {
  font-size: var(--tdih-block-font-subtitle);
  color: var(--saho-color-primary);
  line-height: 1.4;
  font-weight: var(--saho-font-weight-semibold);
}

.tdih-item .date {
  font-size: var(--tdih-block-font-date);
  color: var(--saho-color-gray-600);
  font-style: italic;
}
```

### Example 2: Card Title Typography

**From** `/webroot/themes/custom/saho/components/content/saho-card/saho-card.css:248-265`:

```css
.saho-card {
  /* Typography tokens */
  --card-title-size: var(--saho-font-size-lg);           /* 20-24px */
  --card-title-weight: var(--saho-font-weight-semibold); /* 600 */
  --card-meta-size: var(--saho-font-size-sm);            /* 14-16px */
  --card-body-size: var(--saho-font-size-base);          /* 16-18px */
}

.saho-card-title {
  font-size: var(--card-title-size);
  font-weight: var(--card-title-weight);
  color: var(--saho-color-text-primary);
  margin-bottom: var(--saho-space-1);
  line-height: var(--saho-line-height-tight);  /* 1.25 */
}

.saho-card-description {
  font-size: var(--card-body-size);
  color: var(--saho-color-text-muted);
  line-height: var(--saho-line-height-normal);  /* 1.5 */
}
```

### Example 3: Citation Modal Typography

**From** `/webroot/modules/custom/saho_tools/css/citation-modern.css:61-72`:

```css
#citation-modal .modal-title {
  --cite-title-size: var(--saho-font-size-lg);          /* 20-24px */
  --cite-title-size-mobile: var(--saho-font-size-md);   /* 18-20px */
  --cite-title-weight: var(--saho-font-weight-semibold);

  font-size: var(--cite-title-size);
  font-weight: var(--cite-title-weight);
  color: var(--saho-color-text-primary);
}

@media (max-width: 640px) {
  #citation-modal .modal-title {
    font-size: var(--cite-title-size-mobile);
  }
}
```

### Example 4: Header Navigation Typography

**From** `/webroot/themes/custom/saho/src/scss/_bootswatch.scss:1370-1380`:

```css
.saho-logo-title {
  font-family: $font-family-sans-serif;
  font-size: 1.4rem;
  font-weight: 700;
  color: $saho-deep-heritage-red;
  line-height: 1.2;

  @media (min-width: 992px) {
    font-size: 1.6rem;
  }
}

.saho-logo-tagline {
  font-size: 0.9rem;
  color: $saho-slate-blue;
  font-style: italic;
}
```

### Example 5: Article Content Typography

```css
.article-body {
  /* Base typography */
  font-size: var(--saho-font-size-base);           /* 16-18px fluid */
  line-height: var(--saho-line-height-relaxed);    /* 1.6 for readability */
  font-weight: var(--saho-font-weight-regular);    /* 400 */
  color: var(--saho-color-text-primary);

  /* Paragraph spacing */
  p {
    margin-bottom: var(--saho-space-3);            /* 24px */
  }

  /* Headings within content */
  h2 {
    font-size: var(--saho-font-h2);
    margin-top: var(--saho-space-6);               /* 48px */
    margin-bottom: var(--saho-space-3);            /* 24px */
  }

  /* Links */
  a {
    color: var(--saho-color-primary);
    text-decoration: underline;
    text-underline-offset: 0.125em;                /* Improve readability */
  }

  /* Quotes */
  blockquote {
    font-size: var(--saho-font-size-md);           /* Slightly larger */
    line-height: var(--saho-line-height-loose);    /* 1.8 for emphasis */
    font-style: italic;
  }
}
```

---

## Best Practices

### Do ✅

- **Use semantic heading tokens** (`--saho-font-h1`) instead of scale tokens
- **Apply line-height-normal (1.5)** or higher for body text
- **Test at 200% zoom** to ensure readability
- **Use font-weight-regular (400)** or higher for body text
- **Implement reduced motion** media query for accessibility
- **Use fluid scale** for responsive sizing without media queries

### Don't ❌

- **Don't use magic numbers** - always use tokens
- **Don't use font-weight-light (300)** for body text
- **Don't set line heights below 1.2** for any text
- **Don't use fixed pixel sizes** - use fluid tokens
- **Don't ignore 200% zoom testing**
- **Don't skip high contrast mode support**

---

## Testing Checklist

### Visual Testing

- [ ] Typography scales smoothly from mobile to desktop
- [ ] No text overlaps or collisions at any viewport
- [ ] Headings maintain hierarchy at all sizes
- [ ] Line length stays between 45-75 characters

### Accessibility Testing

- [ ] All text meets 4.5:1 contrast ratio (normal text)
- [ ] Large text meets 3:1 contrast ratio
- [ ] 200% zoom: no horizontal scrolling
- [ ] Screen reader: proper heading hierarchy
- [ ] High contrast mode: text remains readable

### Browser Testing

- [ ] Chrome/Edge (Blink)
- [ ] Firefox (Gecko)
- [ ] Safari (WebKit)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Android

---

## References

- **Design Tokens**: `/webroot/themes/custom/saho/src/scss/base/_variables.scss`
- **Button System**: `/webroot/themes/custom/saho/src/scss/_bootswatch.scss`
- **Component Examples**: `/webroot/themes/custom/saho/components/`
- **WCAG 2.1**: https://www.w3.org/WAI/WCAG21/quickref/
- **Modern CSS**: https://moderncss.dev/

---

**Last Updated**: February 2026
**Status**: Production
**Version**: 1.0.0
