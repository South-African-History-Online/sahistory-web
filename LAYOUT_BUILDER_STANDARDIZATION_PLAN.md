# Layout Builder Block Standardization Plan

## Problem Statement

SAHO's custom inline blocks have inconsistent styling approaches:
- Conflicting CSS rules (`.block-section` defined differently in 2 files)
- Inconsistent container widths (320px, 700px, 1600px, 100%)
- Mixed grid systems (CSS Grid, Flexbox, minimal Bootstrap)
- Bootstrap Styles plugin configured but underutilized
- Spacing inconsistencies (1rem to 3rem gaps)

## Solution: Unified Layout System

### Phase 1: Establish Container Hierarchy

Define 4 standard container widths for different content types:

```scss
// Container width tokens
$container-narrow: 700px;    // Single column content, hero banners
$container-standard: 1200px; // Default content blocks
$container-wide: 1600px;     // Multi-column grids
$container-full: 100%;       // Full-width sections
```

### Phase 2: Standardize Grid System

**Decision: Use Bootstrap Grid + CSS Grid Hybrid**

#### For Layout Builder Sections
Use Bootstrap's grid system:
```html
<div class="container">
  <div class="row">
    <div class="col-md-6">Block 1</div>
    <div class="col-md-6">Block 2</div>
  </div>
</div>
```

#### For Card Grids (Inside Blocks)
Use consistent CSS Grid:
```scss
.saho-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
}
```

### Phase 3: Fix CSS Conflicts

**Remove conflicting `.block-section` rules:**

1. Delete from `components/_blocks.scss`:
```scss
// DELETE THIS:
.block-section {
  max-width: 320px; // Too restrictive!
}
```

2. Update `base/_block-section.scss`:
```scss
.block-section {
  max-width: var(--container-standard, 1200px);
  margin-left: auto;
  margin-right: auto;
  width: 100%;
}

// Modifiers for different widths
.block-section--narrow {
  max-width: var(--container-narrow, 700px);
}

.block-section--wide {
  max-width: var(--container-wide, 1600px);
}

.block-section--full {
  max-width: 100%;
}
```

### Phase 4: Standardize Spacing

**Spacing Scale:**
```scss
$spacing-scale: (
  'xs': 0.5rem,  // 8px
  'sm': 1rem,    // 16px
  'md': 1.5rem,  // 24px
  'lg': 2rem,    // 32px
  'xl': 3rem,    // 48px
  'xxl': 4rem,   // 64px
);

// Apply to gaps
.saho-card-grid {
  gap: var(--spacing-lg, 2rem); // Consistent 32px
}
```

### Phase 5: Leverage Bootstrap Styles Plugin

Enable Layout Builder users to control spacing/width via UI instead of hardcoded CSS:

#### Update Block Templates

Add Bootstrap Styles classes to block wrappers:

**Before:**
```twig
<div class="featured-article-block">
  {{ content }}
</div>
```

**After:**
```twig
<div{{ attributes.addClass('featured-article-block') }}>
  {{ content }}
</div>
```

This allows Layout Builder to inject:
- `bs-p-3` (padding)
- `bs-m-4` (margin)
- `bs-bg-primary` (background colors)
- Custom width classes

### Phase 6: Create Layout Builder Presets

Define reusable layout configurations in Layout Builder:

#### Preset 1: Full-Width Hero
```yaml
layout: layout_builder_base_full_width
container: false
classes: ['block-section--full', 'hero-section']
```

#### Preset 2: Standard Content
```yaml
layout: layout_builder_base
container: true
classes: ['block-section']
max_width: 1200px
```

#### Preset 3: Wide Card Grid
```yaml
layout: layout_builder_base
container: true
classes: ['block-section--wide']
max_width: 1600px
```

### Phase 7: Update Custom Blocks

Update each custom block to use unified system:

#### Hero Banner
```scss
.hero-banner {
  width: 100%;
  max-width: 100%; // Full-width

  .hero-banner__content {
    max-width: var(--container-narrow); // 700px
    margin: 0 auto;
  }
}
```

#### Featured Article/Biography
```scss
.featured-article-block,
.featured-biography-block {
  // Remove max-width: 320px
  width: 100%;

  .saho-card-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--spacing-lg);
  }
}
```

#### TDIH Block
```scss
.tdih-block {
  // Remove max-width: 320px
  width: 100%;

  .tdih-card {
    max-width: 400px; // Individual card max-width
    margin: 0 auto;
  }
}
```

## Implementation Plan

### Step 1: Fix CSS Conflicts (Priority: Critical)
- [ ] Remove conflicting `.block-section` rule from `components/_blocks.scss`
- [ ] Update `base/_block-section.scss` with new container system
- [ ] Add CSS custom properties for container widths

### Step 2: Standardize Spacing (Priority: High)
- [ ] Define spacing scale in SCSS variables
- [ ] Update all card grids to use `gap: var(--spacing-lg)`
- [ ] Remove hardcoded spacing values

### Step 3: Update Block Templates (Priority: High)
- [ ] Add `attributes` to all block template wrappers
- [ ] Remove hardcoded width classes
- [ ] Enable Bootstrap Styles injection

### Step 4: Update Block CSS (Priority: Medium)
- [ ] Hero Banner: Use container tokens
- [ ] Featured Article: Remove 320px limit
- [ ] Featured Biography: Standardize grid
- [ ] TDIH: Remove 320px limit
- [ ] Entity Overview: Use spacing scale

### Step 5: Configure Layout Builder (Priority: Medium)
- [ ] Create layout presets (hero, standard, wide)
- [ ] Configure default spacing values
- [ ] Document for content editors

### Step 6: Test and Validate (Priority: High)
- [ ] Test on existing pages
- [ ] Verify responsive behavior
- [ ] Check all block combinations
- [ ] QA on mobile, tablet, desktop

## File Changes Required

### SCSS Files to Update
```
webroot/themes/custom/saho/src/scss/
├── base/_block-section.scss (UPDATE)
├── components/_blocks.scss (REMOVE conflicting rule)
├── components/_unified-cards.scss (UPDATE spacing)
├── components/custom-blocks/
│   ├── _hero-banner.scss (UPDATE container)
│   ├── _featured-article.scss (UPDATE width)
│   ├── _featured-biography.scss (UPDATE grid)
│   ├── _tdih.scss (UPDATE width)
│   └── _entity-overview.scss (UPDATE spacing)
```

### Template Files to Update
```
webroot/themes/custom/saho/templates/
├── block/
│   ├── block--inline-block--hero-banner.html.twig
│   ├── block--inline-block--featured-article.html.twig
│   ├── block--inline-block--featured-biography.html.twig
│   ├── block--inline-block--tdih.html.twig
│   └── block--inline-block--basic.html.twig
```

### Module CSS Files to Update
```
webroot/modules/custom/saho_utils/
├── hero_banner/css/hero-banner-modern.css
├── featured_biography/css/featured-biography.css
├── entity_overview/css/entity-overview.css
└── tdih/css/ (if exists)
```

## Benefits

### For Developers
- ✓ Consistent, predictable layout behavior
- ✓ Easier maintenance (single source of truth)
- ✓ No more conflicting CSS rules
- ✓ Reusable spacing/width tokens

### For Content Editors
- ✓ Layout Builder UI controls spacing/width
- ✓ Predictable block behavior
- ✓ No layout surprises
- ✓ Consistent spacing across pages

### For Design
- ✓ Unified visual hierarchy
- ✓ Consistent spacing rhythm
- ✓ Professional, polished appearance
- ✓ Responsive by default

## Rollout Strategy

### Week 1: Foundation
- Fix CSS conflicts
- Define container tokens
- Standardize spacing scale

### Week 2: Block Updates
- Update hero banner
- Update featured blocks
- Update card grids

### Week 3: Templates
- Add Bootstrap Styles support
- Update all block templates
- Test combinations

### Week 4: QA & Deploy
- Full regression testing
- Fix edge cases
- Deploy to staging
- Content editor training
- Deploy to production

## Success Metrics

- [ ] Zero conflicting CSS rules
- [ ] All blocks use container tokens
- [ ] Consistent spacing (2rem gap standard)
- [ ] Bootstrap Styles fully functional
- [ ] 100% of Layout Builder pages validated
- [ ] Content editors trained and comfortable

## Maintenance

### Going Forward
1. **New blocks MUST use**:
   - Container tokens (`--container-*`)
   - Spacing scale (`--spacing-*`)
   - Bootstrap Styles `attributes` in templates
   - Standardized grid (`minmax(300px, 1fr)`)

2. **Code review checklist**:
   - [ ] Uses container tokens?
   - [ ] Uses spacing scale?
   - [ ] Supports Bootstrap Styles?
   - [ ] Responsive grid?
   - [ ] No hardcoded widths?

3. **Documentation**:
   - Update component documentation
   - Update content editor guide
   - Add examples to style guide

## Related Issues

- Legacy unified card system deprecation
- SDC component migration
- Bootstrap 5 full adoption
- Theme modernization

## References

- Bootstrap Styles module: `/config/sync/bootstrap_styles.settings.yml`
- Layout Builder config: `/config/sync/core.entity_view_display.node.*.yml`
- Theme SCSS: `/webroot/themes/custom/saho/src/scss/`
- Custom blocks: `/webroot/modules/custom/saho_utils/*/`
