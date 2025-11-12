# SAHO Shop Theme

[![Shop Status](https://img.shields.io/badge/status-production%20ready-success)](https://shop.sahistory.org.za)
[![Drupal](https://img.shields.io/badge/Drupal-11.2.5-blue)](https://www.drupal.org/)
[![Commerce](https://img.shields.io/badge/Commerce-2.x-orange)](https://drupalcommerce.org/)

Modern eCommerce theme for South African History Online's shop - **shop.sahistory.org.za**

This theme provides a professional, accessible, and performant storefront for SAHO's publications and historical materials, built on Drupal Commerce with the SAHO design system.

---

## Table of Contents

- [Overview](#overview)
- [Stack](#stack)
- [Design System](#design-system)
- [Getting Started](#getting-started)
- [Theme Architecture](#theme-architecture)
- [Development Workflow](#development-workflow)
- [Product Management](#product-management)
- [Image Management](#image-management)
- [Customization](#customization)
- [Deployment](#deployment)
- [Contributing](#contributing)

---

## Overview

The SAHO Shop theme (`saho_shop`) is a custom child theme built on top of the **SShop** commerce theme, designed specifically for selling SAHO publications, photographs, and accepting donations. The shop was migrated from a previous Drupal 8 installation with all content preserved.

### Key Features

- ✅ **Modern eCommerce Design** - Professional product cards, responsive layouts
- ✅ **SAHO Design System** - Consistent branding with main SAHO site
- ✅ **Mobile-First** - Optimized for mobile shopping experience
- ✅ **Accessible** - WCAG 2.1 AA compliant
- ✅ **Performance-Optimized** - Minimal CSS/JS footprint (21KB total)
- ✅ **Sticky Navigation** - Smart header that hides on scroll down, shows on scroll up
- ✅ **Product Categories** - Publications, photographs, and donations
- ✅ **Cart & Checkout** - Full Drupal Commerce integration

### Live URLs

- **Production**: https://shop.sahistory.org.za
- **Development**: https://shop.ddev.site
- **Admin**: https://shop.ddev.site/admin

---

## Stack

The SAHO Shop runs on:

- **Drupal 11.2.5** - Core CMS and content management
- **Drupal Commerce 2.x** - eCommerce framework for products, cart, checkout
- **PHP 8.3.10** - Server-side language
- **SShop Theme** - Base commerce theme (bootstrap → sshop → saho_shop)
- **Bootstrap 5** - CSS framework (inherited from SShop)
- **Laravel Mix** - Asset compilation (SCSS → CSS, JS minification)
- **Node.js 18+** - Frontend build tooling

### Theme Hierarchy

```
bootstrap (base)
  ↓
sshop (commerce theme)
  ↓
saho_shop (custom child theme) ← YOU ARE HERE
```

---

## Design System

The SAHO Shop theme follows the SAHO Design System established for the main site, ensuring brand consistency across all SAHO properties.

### Color Palette

#### Primary Colors
- **Heritage Red** `#990000` - Primary CTAs, brand accents
- **Slate Blue** `#3a4a64` - Navigation, secondary elements
- **Muted Gold** `#b88a2e` - Highlights, badges, "on sale" indicators
- **Brick Red** `#8b2331` - Hover states, active links

#### Text Colors
- **Primary Text** `#1e293b` - Body text, headings
- **Secondary Text** `#475569` - Descriptions, meta information
- **Muted Text** `#94a3b8` - Timestamps, SKUs, helper text

#### Backgrounds
- **Surface** `#ffffff` - Cards, product backgrounds
- **Surface Alt** `#f8fafc` - Section alternates, subtle backgrounds
- **Hover State** `#f1f5f9` - Button hovers, interactive states

### Typography

All typography follows the SAHO Design System:

```scss
$font-family-base: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;

// Fluid type scale using clamp()
$font-size-h1: clamp(2rem, 3vw + 1rem, 3rem);
$font-size-h2: clamp(1.5rem, 2vw + 1rem, 2.25rem);
$font-size-h3: clamp(1.25rem, 1.5vw + 0.5rem, 1.875rem);
$font-size-body: 1rem;
$font-size-body-small: 0.875rem;
```

### Spacing System

Consistent spacing throughout:

```scss
$spacing-xs: 0.25rem;   // 4px
$spacing-sm: 0.5rem;    // 8px
$spacing-md: 1rem;      // 16px
$spacing-lg: 1.5rem;    // 24px
$spacing-xl: 2rem;      // 32px
$spacing-2xl: 3rem;     // 48px
$spacing-3xl: 4rem;     // 64px
```

### Mobile-First Breakpoints

```scss
$breakpoint-mobile: 320px;
$breakpoint-tablet: 641px;
$breakpoint-desktop: 1025px;
$breakpoint-large: 1281px;
```

---

## Getting Started

### Prerequisites

- DDEV installed and running
- Node.js 18+ and npm
- Basic knowledge of Drupal and Drupal Commerce
- Access to SAHO database (contact team lead)

### Initial Setup

```bash
# 1. Start DDEV (from project root)
ddev start

# 2. Navigate to shop theme
cd webroot/themes/custom/saho_shop

# 3. Install Node dependencies
npm install

# 4. Build assets (production)
npm run production

# 5. Clear Drupal cache
ddev drush --uri=https://shop.ddev.site cr

# 6. Visit the shop
open https://shop.ddev.site
```

---

## Theme Architecture

### Directory Structure

```
webroot/themes/custom/saho_shop/
├── config/                      # Theme configuration
│   └── install/                # Default blocks and settings
├── src/                        # Source files (compiled)
│   ├── scss/                   # SCSS source files
│   │   ├── base/              # Variables, mixins, base styles
│   │   │   ├── _variables.scss
│   │   │   └── _mixins.scss
│   │   ├── components/        # Component styles
│   │   │   ├── _navigation.scss
│   │   │   ├── _hero.scss
│   │   │   ├── _product-card.scss
│   │   │   ├── _cart.scss
│   │   │   ├── _category-cards.scss
│   │   │   ├── _benefits.scss
│   │   │   └── _browse-categories.scss
│   │   ├── global.scss        # Main global styles
│   │   └── commerce.scss      # Commerce-specific styles
│   └── js/                    # JavaScript source
│       ├── global.js          # Global theme JS
│       └── commerce.js        # Commerce functionality
├── templates/                  # Twig template overrides
│   ├── page/                  # Page layouts
│   │   ├── page.html.twig
│   │   └── page--front.html.twig
│   ├── navigation/            # Menu templates
│   │   └── menu--main.html.twig
│   ├── block/                 # Block templates
│   ├── commerce/              # Commerce templates
│   │   ├── commerce-product--teaser.html.twig
│   │   └── commerce-product--full.html.twig
│   └── views/                 # Views templates
├── css/                       # Compiled CSS (DO NOT EDIT)
│   ├── commerce.css
│   └── global.css
├── js/                        # Compiled JS (DO NOT EDIT)
│   ├── commerce.js
│   └── global.js
├── images/                    # Theme images
├── saho_shop.info.yml        # Theme metadata
├── saho_shop.libraries.yml   # CSS/JS library definitions
├── saho_shop.theme           # Theme preprocessing functions
├── package.json              # Node dependencies
├── webpack.mix.js            # Laravel Mix build config
└── README.md                 # This file
```

### Key Files Explained

#### `saho_shop.info.yml`
Theme metadata and base theme declaration:
```yaml
name: SAHO Shop
type: theme
base theme: sshop              # Important: child of sshop, not radix!
core_version_requirement: ^11
libraries:
  - saho_shop/global
  - saho_shop/commerce-styling
```

#### `saho_shop.theme`
Theme preprocessing and hooks. Add custom logic here:
```php
<?php

/**
 * Preprocess variables for product teasers.
 */
function saho_shop_preprocess_commerce_product__teaser(&$variables) {
  // Custom preprocessing
}
```

#### `webpack.mix.js`
Laravel Mix configuration for asset compilation:
```javascript
mix.sass('src/scss/global.scss', 'css/')
   .sass('src/scss/commerce.scss', 'css/')
   .js('src/js/global.js', 'js/')
   .js('src/js/commerce.js', 'js/')
   .options({ processCssUrls: false });

if (mix.inProduction()) {
  mix.version();
}
```

---

## Development Workflow

### Daily Development

```bash
# Start DDEV (if not running)
ddev start

# Navigate to theme
cd webroot/themes/custom/saho_shop

# Watch for changes (auto-compile)
npm run watch
```

This will watch for changes to SCSS and JS files and auto-compile.

### Making Style Changes

1. Edit SCSS files in `src/scss/`
2. Changes auto-compile (if watching)
3. Refresh browser to see changes
4. Clear Drupal cache if needed: `ddev drush cr`

### Making Template Changes

1. Edit Twig templates in `templates/`
2. Clear Drupal cache: `ddev drush cr`
3. Refresh browser to see changes

**Tip**: Enable Twig debugging in `development.services.yml`:
```yaml
twig.config:
  debug: true
  auto_reload: true
```

### Build Commands

```bash
# Development build (with source maps)
npm run dev

# Production build (minified, optimized)
npm run production

# Watch mode (auto-compile on save)
npm run watch

# Check for JS issues (Biome linting)
npm run biome:check

# Auto-fix JS issues
npm run biome:fix
```

### Before Committing

**Always run these commands before committing theme changes:**

```bash
# 1. Build production assets
npm run production

# 2. Check JavaScript
npm run biome:check

# 3. Export Drupal config (if you changed blocks/settings)
ddev drush --uri=https://shop.ddev.site cex -y

# 4. Clear cache
ddev drush --uri=https://shop.ddev.site cr

# 5. Test in browser
open https://shop.ddev.site
```

---

## Product Management

### Product Types

The shop has one primary product type:

- **Publication** - Books, journals, academic publications

### Product Fields

Each publication product has:

- **Title** - Product name (required)
- **Images** (`field_images`) - Product cover images
- **Body** - Full product description
- **Author** (`field_author`) - Book author(s)
- **Editor** (`field_editor`) - Book editor(s)
- **Publisher** (`field_publisher`) - Publishing house
- **Publication Date** (`field_publication_date`) - Year published
- **Subtitle** (`field_subtitle`) - Book subtitle
- **Variations** - Price/SKU variations (Drupal Commerce)
- **Featured** (`field_featured`) - Mark as featured/popular

### Adding Products

```bash
# Via UI
https://shop.ddev.site/admin/commerce/products/add/publication

# Via Drush
ddev drush --uri=https://shop.ddev.site entity:create commerce_product \
  --type=publication \
  --title="New Book Title"
```

### Product Display Modes

- **Teaser** - Used in product grids, lists, category pages
- **Full** - Used on individual product pages

Configure at: `/admin/commerce/config/product-types/publication/edit/display`

---

## Image Management

The shop includes a comprehensive product image management system.

### Image Import System

Located in `/scripts/`, the image import system handles bulk importing of product cover images.

#### Quick Start

```bash
# 1. Copy cover images to covers/ directory
cp /path/to/covers/*.jpg covers/

# 2. Generate mapping CSV
ddev drush -l shop.ddev.site php:script scripts/generate_image_mapping.php

# 3. Match images to products
ddev drush -l shop.ddev.site php:script scripts/match_and_rename_images.php

# 4. Import images
ddev drush -l shop.ddev.site php:script scripts/import_product_images.php

# 5. Clear cache
ddev drush cr
```

#### Available Scripts

| Script | Purpose |
|--------|---------|
| `generate_image_mapping.php` | Creates CSV mapping products to filenames |
| `match_and_rename_images.php` | Matches existing images to products |
| `import_product_images.php` | Imports images and attaches to products |
| `check_image_readiness.sh` | Validates import setup |

#### Image Requirements

- **Format**: JPG, JPEG, or PNG
- **Size**: 800px width minimum
- **Aspect Ratio**: Portrait (book covers)
- **Max File Size**: 2MB per image (recommended)
- **Location**: Copy to `covers/` directory

#### Documentation

See detailed guides:
- `PRODUCT-IMAGE-IMPORT-GUIDE.md` - Step-by-step import guide
- `PRODUCT-IMAGE-IMPORT-COMPLETE.md` - Import results and status
- `IMAGE-IMPORT-STATUS.md` - Current import status

---

## Customization

### Adding New Components

1. **Create SCSS file** in `src/scss/components/`:
   ```scss
   // src/scss/components/_my-component.scss
   .my-component {
     @include card-base;
     padding: $spacing-lg;
   }
   ```

2. **Import in main SCSS**:
   ```scss
   // src/scss/commerce.scss
   @import 'components/my-component';
   ```

3. **Create Twig template** in `templates/`:
   ```twig
   {# templates/my-component.html.twig #}
   <div class="my-component">
     {{ content }}
   </div>
   ```

4. **Build assets**:
   ```bash
   npm run production
   ```

### Customizing Colors

Edit `src/scss/base/_variables.scss`:

```scss
// Override primary color
$color-heritage-red: #990000;

// Add custom color
$color-custom: #123456;
```

Then rebuild:
```bash
npm run production
```

### Customizing Product Cards

Edit `templates/commerce/commerce-product--teaser.html.twig`:

```twig
<article class="product-card">
  <div class="product-card__image">
    {{ content.field_images }}
  </div>
  <div class="product-card__content">
    <h3>{{ product.label }}</h3>
    {{ content.field_author }}
    {{ content.variations }}
  </div>
</article>
```

Style in `src/scss/components/_product-card.scss`.

---

## Deployment

### Pre-Deployment Checklist

- [ ] All assets built with `npm run production`
- [ ] JavaScript linted with `npm run biome:check`
- [ ] Configuration exported with `ddev drush cex -y`
- [ ] Cache cleared with `ddev drush cr`
- [ ] Tested on mobile devices
- [ ] All product images displaying correctly
- [ ] Cart and checkout tested
- [ ] Payment gateway tested (if applicable)

### Deployment Commands

```bash
# 1. Build production assets
cd webroot/themes/custom/saho_shop
npm run production

# 2. Export configuration
ddev drush --uri=https://shop.ddev.site cex -y

# 3. Commit changes
git add .
git commit -m "SAHO-XX: Shop deployment"

# 4. Push to repository
git push origin feature-branch

# 5. Create Pull Request
# Follow project's PR guidelines
```

### On Production Server

```bash
# 1. Pull latest changes
git pull origin main

# 2. Install/update dependencies
composer install --no-dev --optimize-autoloader

# 3. Import configuration
drush @prod cim -y

# 4. Update database
drush @prod updb -y

# 5. Rebuild cache
drush @prod cr

# 6. Clear Varnish/CDN cache if applicable
```

---

## Troubleshooting

### Common Issues

#### Assets Not Updating

```bash
# Clear Drupal cache
ddev drush cr

# Rebuild assets
npm run production

# Hard refresh browser (Cmd+Shift+R or Ctrl+Shift+R)
```

#### Products Showing as Blank Cards

```bash
# Check teaser view mode configuration
ddev drush --uri=https://shop.ddev.site config:get \
  core.entity_view_display.commerce_product.publication.teaser

# Reconfigure if needed
# Visit: /admin/commerce/config/product-types/publication/edit/display/teaser
```

#### Images Not Displaying

```bash
# Check file permissions
ddev exec chmod -R 775 webroot/sites/shop.sahistory.org.za/files

# Check if images are attached
ddev drush sqlq "SELECT COUNT(*) FROM commerce_product__field_images"

# Reimport images
ddev drush -l shop.ddev.site php:script scripts/import_product_images.php
```

#### Navigation Not Appearing

```bash
# Check if main menu has items
# Visit: /admin/structure/menu/manage/main

# Ensure navigation block is placed
# Visit: /admin/structure/block

# Clear cache
ddev drush cr
```

### Getting Help

1. Check this README
2. Review [SHOP-IMPLEMENTATION-COMPLETE.md](../../../SHOP-IMPLEMENTATION-COMPLETE.md)
3. Check issue tracker: https://github.com/South-African-History-Online/sahistory-web/issues
4. Contact project tech lead

---

## Performance

### Build Stats

Current minified asset sizes:

- **CSS (Commerce)**: 21.5 KB
- **CSS (Global)**: 7.31 KB
- **JS (Commerce)**: 2.07 KB
- **JS (Global)**: 2.08 KB
- **Total**: ~33 KB (minified)

### Optimizations Implemented

- ✅ Mobile-first responsive design
- ✅ Minimal JavaScript dependencies
- ✅ CSS Grid and Flexbox (no bloated frameworks)
- ✅ Production minification with Laravel Mix
- ✅ Lazy loading ready for images
- ✅ Efficient SCSS architecture
- ✅ No jQuery dependencies in custom code

---

## Browser Support

- Chrome (last 2 versions)
- Firefox (last 2 versions)
- Safari (last 2 versions)
- Edge (last 2 versions)
- Mobile Safari (iOS 12+)
- Chrome Mobile (Android 8+)

---

## Accessibility

The SAHO Shop theme is built to WCAG 2.1 AA standards:

- ✅ Semantic HTML5 elements
- ✅ ARIA labels on interactive elements
- ✅ Keyboard navigation support
- ✅ Color contrast ratios (minimum 4.5:1)
- ✅ Focus indicators on all interactive elements
- ✅ Screen reader friendly structure
- ✅ Skip to main content link
- ✅ Form labels properly associated

---

## Contributing

### Making Theme Changes

1. Create a feature branch:
   ```bash
   git switch -c SAHO-XX--my-shop-feature
   ```

2. Make your changes in `src/` directory

3. Build and test:
   ```bash
   npm run production
   ddev drush cr
   ```

4. Commit with clear messages:
   ```bash
   git add .
   git commit -m "SAHO-XX: Add new product card hover effect"
   ```

5. Push and create PR:
   ```bash
   git push origin SAHO-XX--my-shop-feature
   ```

### Code Quality

Always run before committing:

```bash
# JavaScript linting
npm run biome:check

# Production build
npm run production

# Export config if changed
ddev drush cex -y
```

---

## Resources

### Documentation

- [Main SAHO README](../../../README.md)
- [CLAUDE.md](../../../CLAUDE.md) - AI development guide
- [Shop Implementation Plan](../../../SHOP-IMPLEMENTATION-COMPLETE.md)
- [Product Image Import Guide](../../../PRODUCT-IMAGE-IMPORT-GUIDE.md)

### Drupal Commerce

- [Drupal Commerce Documentation](https://docs.drupalcommerce.org/)
- [Product Types](https://docs.drupalcommerce.org/commerce2/developer-guide/products/product-architecture)
- [Checkout Flow](https://docs.drupalcommerce.org/commerce2/developer-guide/checkout)

### Frontend

- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.3/)
- [Laravel Mix Docs](https://laravel-mix.com/docs/)
- [Sass Docs](https://sass-lang.com/documentation)

### SAHO Design System

- Main site: https://sahistory.org.za
- Design system: See main README

---

## Migration Notes

This shop was migrated from a previous Drupal 8 installation. All products, orders, and customer data were preserved during the migration. The theme was completely rebuilt with modern best practices and the SAHO design system.

---

## License

Copyright © South African History Online
All rights reserved.

---

## Credits

**Theme Development**: Claude Code + SAHO Development Team
**Design System**: SAHO Design Team
**Implementation**: November 2025

**Built with ❤️ for South African History**

---

**Questions?** Contact the SAHO development team or open an issue on GitHub.
