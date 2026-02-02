# Bootswatch Refactor Plan - Component Extraction

## Problem

`_bootswatch.scss` (2,424 lines) contains component-specific styles that should live in their respective component/module files. This violates our component isolation pattern and makes maintenance difficult.

## Files to Create/Update

### 1. **TDIH Block Styles** (Lines 166-293, 128 lines)
**Current Location**: `_bootswatch.scss`
**Target File**: `/webroot/modules/custom/saho_utils/tdih/css/tdih-block.css` (new file)
**Contains**:
- `.tdih-block` container
- `.tdih-list`, `.tdih-item`
- `.tdih-separator`
- `.tdih-button-wrapper`
- Component-scoped tokens already in place

**Action**: Extract to dedicated file, ensure module loads it

---

### 2. **Featured Topics / Block Section** (Lines 405-493, 89 lines)
**Current Location**: `_bootswatch.scss`
**Target File**: `/webroot/themes/custom/saho/src/scss/base/_block-section.scss` (append)
**Contains**:
- `.two-blocks-container`
- `.block-section` (general block styles)
- `.block-section h2`, `.block-section h3`
- `.block-image`, `.block-label`, `.block-button`

**Action**: Merge into existing `_block-section.scss`, ensure no duplicates

---

### 3. **Entity Overview Block** (Lines 495-547, 53 lines)
**Current Location**: `_bootswatch.scss`
**Target File**: `/webroot/modules/custom/saho_utils/entity_overview/css/entity-overview.css` (append)
**Contains**:
- `.entity-overview-block`
- `.entity-overview-list`, `.entity-overview-item`
- `.entity-overview-image`

**Action**: Append to existing entity-overview.css (already has 121 lines)

---

### 4. **Views Grid Styles** (Lines 549-616, 68 lines)
**Current Location**: `_bootswatch.scss`
**Target File**: `/webroot/themes/custom/saho/src/scss/layout/_views-grid.scss` (append)
**Contains**:
- `.views-view--grid`
- `.views-row`, `.views-col`
- Grid cell styling with images, headings, links

**Action**: Merge into existing `_views-grid.scss` (already has styles)

---

### 5. **Archive View Styles** (Lines 618-721, 104 lines DUPLICATED)
**Current Location**: `_bootswatch.scss`
**Target File**: `/webroot/themes/custom/saho/src/scss/views/_archive-view.scss` (new file)
**Contains**:
- `.archive-view` (DUPLICATED at lines 620-679 AND 681-721)
- `.archive-exposed` (filter forms)
- `.archive-rows`, `.archive-row`
- `.archive-header`, `.archive-pager`

**Action**: Create new file, deduplicate, move all archive view styles here

---

### 6. **Layout Builder Modal** (Lines 723-750, 28 lines)
**Current Location**: `_bootswatch.scss`
**Target File**: Keep in `_bootswatch.scss` OR move to admin.css
**Contains**:
- `#layout-builder-modal` styles
- `.inline-block-create-button`, `.inline-block-list`

**Action**: DECISION NEEDED - admin-specific, could move to admin.css

---

### 7. **Form Styling** (Lines 751-824, 74 lines)
**Current Location**: `_bootswatch.scss`
**Target File**: `/webroot/themes/custom/saho/src/scss/components/_forms.scss` (new file)
**Contains**:
- `.form-item`, `.form-wrapper`
- `.form-item__label`, `.js-form-item label`
- `.form-text`, `.form-textarea`, `.form-select`
- `.form-submit`, `.webform-submit`
- Input focus states, descriptions

**Action**: Create new forms component file

---

### 8. **Article Layout Styles** (Lines 826-1263, 438 lines - MASSIVE)
**Current Location**: `_bootswatch.scss`
**Target File**: `/webroot/themes/custom/saho/src/scss/layout/_article-layout.scss` (append)
**Contains**:
- `h1.title.page-title` (page title styling)
- `.saho-article-meta` (DUPLICATED at lines 841-891, 867-891, 995-1018)
- `.saho-article-wrapper`, `.saho-article-grid`
- `.saho-main-content`, `.saho-article-sidebar`
- `.saho-feature-banner`, `.saho-image-block`
- `.saho-references` (reference lists)
- `.saho-further-reading` (DUPLICATED at lines 1060-1084, 1114-1145)
- `.saho-thematic-context`
- `.saho-view-block`, `.saho-accordion`
- `.saho-taxonomy-group`, `.saho-tags`
- `.saho-comment` (comments)

**Action**: Merge into existing `_article-layout.scss`, DEDUPLICATE heavily

---

### 9. **Header & Navigation** (Lines 1317-2087, 771 lines - HUGE)
**Current Location**: `_bootswatch.scss`
**Target File**: `/webroot/themes/custom/saho/src/scss/components/_header.scss` (new file)
**Contains**:
- `.saho-header`, `.saho-accent-bar`, `.saho-header-content`
- `.saho-logo`, `.saho-logo-img`, `.saho-logo-title`, `.saho-logo-tagline`
- `.saho-desktop-nav` (complete desktop navigation with dropdowns)
- `.saho-search`, `.saho-search-input`, `.saho-search-btn`
- `.saho-contribute-btn` (contribute button)
- `#toolsDropdown` (tools dropdown)
- `.saho-mobile-toggle`, `.saho-hamburger`
- `.saho-mobile-menu` (complete offcanvas mobile menu)
- `.saho-mobile-search`, `.saho-mobile-nav`
- Responsive breakpoints
- Print styles for header

**Action**: Create new header component file - this is PRIORITY since it's huge

---

### 10. **Collection Cards** (Lines 2089-2246, 158 lines)
**Current Location**: `_bootswatch.scss`
**Target File**: `/webroot/themes/custom/saho/components/content/saho-card/saho-card.css` (append)
**Contains**:
- `.collection-card.saho-card` (standard collection cards)
- `.card-image-wrapper`, `.card-placeholder-image`
- `.card-label` (badge)
- `.card-content`, `.card-title`, `.card-meta`
- `.collection-count`, `.card-description`, `.card-actions`
- `.saho-landing-grid` (grid layout with STANDARD 32px gap)
- Responsive adjustments

**Action**: Merge into existing saho-card.css (already has 315 lines)

---

### 11. **Content Type Color Coding** (Lines 2248-2424, 177 lines)
**Current Location**: `_bootswatch.scss`
**Target File**: `/webroot/themes/custom/saho/src/scss/themes/_content-type-colors.scss` (new file)
**Contains**:
- `.saho-card--archive` (gold)
- `.saho-card--place` (blue)
- `.saho-card--article` (red)
- `.saho-card--biography` (green)
- `.saho-card--event` (charcoal)
- `.content-type-archive`, `.content-type-place`, etc. (alternative class pattern)
- `.collection-card .card-label` content type variants

**Action**: Create new content type theming file

---

## What Stays in _bootswatch.scss

**Keep these Bootstrap overrides** (Lines 1-165):
- Mixins (`.btn-shadow`)
- Navbar base styles
- Bootstrap button overrides (`.btn-primary`, `.btn-secondary`, etc.)
- Reduced motion support for buttons
- Custom inline block button (`.block-inline-blockbutton`)
- Typography utilities (`.text-secondary`)
- Form basics (`legend`, `label`)
- Navigation utilities (`.breadcrumb`, `.pagination`)
- Indicators (`.badge`)
- History pictures block carousel (`.history-pictures-block`)
- Spacer block (`.spacer-block`)
- Field labels (`.field__label`, `.field__item`)
- Contextual links (`.contextual-links-wrapper`)
- Highlight section (`.saho-highlight`)

**Estimated lines remaining**: ~400 lines (83% reduction from 2,424)

---

## Duplication Issues Found

### CRITICAL: Fix These Duplications

1. **`.saho-article-meta`** - Defined 3 times:
   - Lines 841-865 (25 lines)
   - Lines 867-891 (25 lines) - EXACT DUPLICATE
   - Lines 995-1018 (24 lines) - EXACT DUPLICATE

2. **`.saho-further-reading`** - Defined 2 times:
   - Lines 1060-1084 (25 lines)
   - Lines 1114-1145 (32 lines) - Nearly identical

3. **`.archive-view`** - Defined 2 times:
   - Lines 620-679 (60 lines)
   - Lines 681-721 (41 lines) - Partial overlap

---

## Implementation Order (Priority)

### Phase 1: Extract Largest Components (Week 1)
1. **Header & Navigation** → `_header.scss` (771 lines) - PRIORITY
2. **Article Layout** → Merge into `_article-layout.scss` (438 lines, deduplicate)
3. **Collection Cards** → Merge into `saho-card.css` (158 lines)

### Phase 2: Extract Medium Components (Week 1-2)
4. **Content Type Colors** → `_content-type-colors.scss` (177 lines)
5. **Archive Views** → `_archive-view.scss` (104 lines, deduplicate)
6. **Views Grid** → Merge into `_views-grid.scss` (68 lines)

### Phase 3: Extract Small Components (Week 2)
7. **Forms** → `_forms.scss` (74 lines)
8. **Featured Topics** → Merge into `_block-section.scss` (89 lines)
9. **TDIH Block** → `tdih-block.css` (128 lines)
10. **Entity Overview** → Merge into `entity-overview.css` (53 lines)
11. **Layout Builder Modal** → Decision needed (28 lines)

---

## Testing Checklist (Per Component)

After extracting each component:
- [ ] Compile SCSS successfully
- [ ] Clear Drupal cache
- [ ] Test component visually
- [ ] Verify no broken styles
- [ ] Check responsive behavior
- [ ] Test component isolation

---

## Expected Results

### Before
- `_bootswatch.scss`: 2,424 lines (all-in-one)
- Component isolation: Poor
- Maintainability: Difficult

### After
- `_bootswatch.scss`: ~400 lines (Bootstrap overrides only)
- 11 new/updated component files
- Component isolation: Excellent
- Maintainability: Easy
- **Total reduction: 83% (2,024 lines moved)**

---

## Commands for Each Extraction

```bash
# After moving styles to new file:
cd webroot/themes/custom/saho
npm run production  # Rebuild CSS
ddev drush cr       # Clear cache

# Test the affected component
# Visual check on site
```

---

## Next Steps

1. Create new branch: `git checkout -b SAHO-refactor--bootswatch-component-extraction`
2. Start with Phase 1 (largest components)
3. Extract one component at a time
4. Test after each extraction
5. Commit frequently with clear messages

**Ready to start?** Begin with header extraction (771 lines → biggest impact).
