## CONTENTS OF THIS FILE

- Introduction
- Installation
- Configuration
- Build Tools
- Maintainers

## INTRODUCTION

SahoShop is a Bootstrap based theme made for Drupal Commerce 2.x.

The SahoShop Drupal Theme is a highly versatile and customizable Drupal theme that is built around
Commerce Kickstart and Layout Builder. It incorporates fully customized Bootstrap 5 and Bootstrap
Icons, providing a wide range of theming best practice examples. With the SahoShop theme, you have
extensive configuration options to adjust layouts, change fonts, handle status messages, manage
icons, add classes, and utilize predefined product teaser designs. The theme uses modern frontend
tools including PostCSS Autoprefixer and SVG inline icons to enhance development efficiency.

- For a full description of the theme, visit the project page:
  https://www.drupal.org/project/saho_shop

- To submit bug reports and feature suggestions, or track changes:
  https://www.drupal.org/project/issues/saho_shop

## INSTALLATION

- Install as you would normally install a contributed Drupal theme. Visit
  https://www.drupal.org/node/1897420 for further information.

## CONFIGURATION

The SahoShop theme offers various configuration options to customize its appearance. To access the
theme settings:

1. Log in to your Drupal administration panel.
2. Go to Appearance in the admin menu.
3. Find the SahoShop theme and click on the Settings link.

### Customization Options

#### Font Settings

You can easily change the font used throughout your site with the SahoShop theme. Follow these steps
to adjust the font:

1. Go to Appearance and click on the Settings link for the SahoShop theme.
2. Look for the Font Settings section.
3. Choose the desired font from the available options.

#### Region/Layout Adjustments

The SahoShop theme provides flexibility in adjusting the layout of your site. To make layout
adjustments:

1. Go to Appearance and click on the Settings link for the SahoShop theme.
2. Locate the Regions section.
3. Use the options provided to modify the layout, including extened configuration for the offcanvas
   navigation region.

- Change the direction of the offcanvas navigation.
- Control the visibility of the logo within the offcanvas navigation.
- Configure body scrolling behavior when the offcanvas menu is open.
- Choose backdrop options for the offcanvas navigation.

#### Message Styling

Customize the messages displayed to users with the SahoShop theme. To style the messages:

1. Go to Appearance and click on the Settings link for the SahoShop theme.
2. Find the Message Styling section.
3. Customize the message styles according to your preference.

### SVG Integration

The theme offers advanced support for scalable vector graphics (SVG), allowing you to utilize SVG
seamlessly within your site.

## BUILD TOOLS

The SahoShop Drupal Theme uses modern build tools to streamline frontend development. Here's what
you need to know:

### Prerequisites

- Node.js (check version in `.nvmrc`)
- npm

### Installation

```bash
npm install
```

### Available Commands

```bash
# Watch SCSS files and rebuild on changes
npm run watch

# Build assets (CSS)
npm run build

# Generate SVG sprite
npm run icons-sprite

# Lint SCSS files
npm run lint:scss

# Format files (SCSS, JSON, MD)
npm run format
```

### Build Process

The build process includes:

1. SCSS Compilation
   - Uses Sass 1.32.13 (compatible with Bootstrap 5.3.3)
   - Includes source maps
   - Loads from node_modules

2. PostCSS Processing
   - Autoprefixer for browser compatibility
   - SVG inline support

3. SVG Sprite Generation
   - Creates a single sprite from SVGs in the `icons` folder
   - Usage: `svg-sprite --svg-namespace-classnames false`

### SVG Integration

To use inline SVGs in SCSS:

```scss
background: svg-load('PATH_TO_IMAGE', fill=#{$COLOR_VARIABLE});
```

Note: The svg-load() function only overrides attributes in the root `<svg>` element. Child elements
inherit the color unless explicitly set.

## MAINTAINERS

Current maintainers:

- Ivan Buišić (majmunbog) - https://www.drupal.org/u/majmunbog
