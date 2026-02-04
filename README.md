# South African History Online (SAHO) 

[![Frontend CI](https://github.com/South-African-History-Online/sahistory-web/actions/workflows/frontend-ci.yml/badge.svg)](https://github.com/South-African-History-Online/sahistory-web/actions/workflows/frontend-ci.yml)
[![PR Build and Test](https://github.com/South-African-History-Online/sahistory-web/actions/workflows/ci.yml/badge.svg)](https://github.com/South-African-History-Online/sahistory-web/actions/workflows/ci.yml)

The primary Git repository for the South African History Online (SAHO) website - sahistory.org.za

This is a Github project is currently supported and maintained by Mads Nørgaard.

## Table of Contents

- [Stack Overview](#stack-overview)
- [SAHO Design System & Style Guide](#saho-design-system--style-guide)
  - [Color Palette](#color-palette)
  - [Typography](#typography)
  - [Component Guidelines](#component-guidelines)
- [Development](#development)
  - [Getting Started with DDEV](#getting-started)
  - [Backend Developer Commands](#backend-developer-commands)
  - [Frontend Developer Commands](#frontend-developer-commands)
  - [Troubleshooting DDEV Issues](#ddev-commands-to-fix-issues)
- [Composer](#composer)
- [Drupal Configuration](#drupal-configuration-during-development)
- [Code Quality Tools](#code-quality-tools)
- [Custom Modules](#custom-modules)
  - [Module Architecture](#module-architecture)
  - [Available Modules](#available-modules)
- [SAHO Timeline Application](#saho-timeline-application)
- [WebP Image Optimization](#webp-image-optimization)
- [Theme Development](#radix-and-saho-subtheme)
  - [Theme Architecture](#theme-architecture)
  - [Component Development](#component-development)
  - [Styling Guidelines](#styling-guidelines)
  - [Radix CLI with DDEV](#using-the-drupal-radix-cli-with-ddev)
- [Git Strategy & Collaboration](#git-strategy--collaboration)
- [Contributing to SAHO](#contributing-to-saho)
- [CI/CD Pipeline](#cicd-pipeline-)

## Stack Overview
The full stack is:
- **Drupal 11.1.7** (Project started with Drupal 6 and upgraded)
- **PHP 8.3.10**
- **Radix** theme framework with a custom subtheme called **"saho"**
- **Bootstrap 5** for responsive design and UI components
- **Node.js** for frontend asset compilation and build processes

## SAHO Design System & Style Guide

The SAHO Design System ensures consistency across the platform while maintaining our heritage-focused brand identity. This system prioritizes accessibility, readability for long-form academic content, and mobile-first responsive design.

### Color Palette

#### Primary Colors
- **Deep Heritage Red** `#990000` - Primary brand color representing heritage and authority
- **Slate Blue** `#3a4a64` - Used for academic/research sections
- **Muted Gold** `#b88a2e` - Secondary accent for historical significance
- **Faded Brick Red** `#8b2331` - Complementary highlighting for important content

#### Text Colors
- **Primary Text** `#1e293b` - Main body text for optimal readability
- **Secondary Text** `#475569` - Supporting text and descriptions
- **Muted Text** `#94a3b8` - De-emphasized content, timestamps, metadata

#### Background Colors
- **Surface** `#ffffff` - Primary content areas
- **Surface Alt** `#f8fafc` - Secondary surfaces for hierarchy
- **Hover State** `#f1f5f9` - Interactive element hover states

#### Semantic Colors
- **Success** `#22c55e` - Successful operations, confirmations
- **Warning** `#eab308` - Warnings, important notices
- **Danger** `#ef4444` - Errors, destructive actions
- **Info** `#3b82f6` - Informational messages

### Typography

#### Font Stack
```css
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
```

#### Type Scale (Desktop)
- **Display XL**: `clamp(3rem, 5vw + 1rem, 5rem)` - Hero sections
- **Display**: `clamp(2.5rem, 4vw + 1rem, 4rem)` - Page titles
- **H1**: `clamp(2rem, 3vw + 1rem, 3rem)` - 36px (2.25rem)
- **H2**: `clamp(1.5rem, 2vw + 1rem, 2.25rem)` - 28px (1.75rem)
- **H3**: `clamp(1.25rem, 1.5vw + 0.5rem, 1.875rem)` - 24px (1.5rem)
- **H4**: `clamp(1.125rem, 1vw + 0.5rem, 1.5rem)` - 20px (1.25rem)
- **Body Large**: 18px (1.125rem) - Lead paragraphs
- **Body**: 16px (1rem) - Standard content
- **Body Small**: 14px (0.875rem) - Supporting text
- **Caption**: 12px (0.75rem) - Smallest text

#### Font Weights
- **Light**: 300 - Decorative text only
- **Regular**: 400 - Body text
- **Medium**: 500 - UI elements
- **Semibold**: 600 - Subheadings
- **Bold**: 700 - Headings, emphasis
- **Black**: 900 - Display text (sparingly)

### Component Guidelines

#### Spacing System
- **XS**: 4px (0.25rem) - Tight spacing
- **SM**: 8px (0.5rem) - Compact elements
- **MD**: 16px (1rem) - Standard spacing
- **LG**: 24px (1.5rem) - Section spacing
- **XL**: 32px (2rem) - Major sections

#### Border Radius
- **Small**: 6px (0.375rem) - Buttons, inputs
- **Medium**: 8px (0.5rem) - Cards, modals
- **Large**: 12px (0.75rem) - Large containers

#### Shadows
```css
--shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
--shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
--shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
--shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.15);
```

#### Mobile-First Breakpoints
- **Mobile**: 320px - 640px
- **Tablet**: 641px - 1024px
- **Desktop**: 1025px - 1280px
- **Large Desktop**: 1281px+

### Design Principles

1. **Heritage & Authority**: Use deep red sparingly for primary actions and brand moments
2. **Readability First**: Maintain high contrast ratios (minimum 4.5:1) for all text
3. **Academic Tone**: Clean, professional layouts that prioritize content over decoration
4. **Mobile Optimization**: Over 50% of users are mobile - ensure touch-friendly interfaces
5. **Progressive Enhancement**: Start with a solid mobile experience, enhance for larger screens
6. **Consistent Spacing**: Use the spacing scale consistently to create visual rhythm
7. **Accessible Colors**: All color combinations meet WCAG AA standards

### Implementation Notes

- CSS custom properties are defined in `/webroot/themes/custom/saho/src/scss/base/_variables.scss`
- Modern components use CSS Grid and Flexbox for layouts
- Fluid typography scales smoothly between breakpoints using `clamp()`
- Interactive elements have clear hover, focus, and active states
- Forms follow Bootstrap 5 patterns with SAHO color overrides

## Development
You can run this project in a local environment using DDEV (quick setup).

### Getting Started
These instructions will get you a copy of the project up and running on your local machine for development, demo and testing purposes. See deployment for notes on how to deploy the project to staging, QA and production systems.

#### DDEV
DDEV sets up the project and necessary backing services. We use Node.js to build the frontend, which can be done both on the host machine or within the `web` Docker container created by DDEV.

##### Benefits
DDEV is fast, reliable, and we have extensive in-house experience using it during development.

##### Requirements
On your host machine, install the following:
- [Git](https://git-scm.com/)
- [Docker](https://ddev.readthedocs.io/en/stable/users/docker_installation/)
- [DDEV](https://ddev.readthedocs.io/en/stable/)

##### Setup Steps
1. Clone the project repository:
   ```sh
   git clone git@github.com:South-African-History-Online/sahistory-web.git
   ```
2. Enter the newly created project directory:
   ```sh
   cd sahistory-web
   ```
3. Install dependencies with Composer:
   ```sh
   ddev composer install
   ```
4. Run the project with DDEV - the database is available internally via SharePoint (ask a fellow developer):
   ```sh
   ddev import-db
   ```
5. Start the DDEV environment:
   ```sh
   ddev start
   ```
6. List entire DDEV project:
   ```sh
   ddev describe
   ```
7. List all DDEV commands:
   ```sh
   ddev list
   ```

##### Backend Developer Commands
For backend developers working in DDEV, follow these commands to get started and manage the environment:

1. **Start DDEV environment**: This command will start all required services:
   ```sh
   ddev start
   ```
2. **Install Composer dependencies**: Ensure all PHP packages are installed:
   ```sh
   ddev composer install
   ```
3. **Import the database**: Load the latest database snapshot for development:
   ```sh
   ddev import-db
   ```
4. **Run custom update scripts**: To apply any project-specific updates:
   ```sh
   .ddev/commands/host/local_update
   ```
5. **Access the Drupal site locally**: Access the site through the browser using:
   ```sh
   ddev describe
   ```
   This will provide the local URL and credentials.

##### Frontend Developer Commands
For frontend developers working in DDEV, follow these commands to set up and build the front end:

1. **Start DDEV environment**: Start the services required to run the project:
   ```sh
   ddev start
   ```
2. **Install Node.js dependencies**: Install required Node.js packages:
   ```sh
   ddev ssh
   cd webroot/themes/custom/saho
   npm install
   ```
3. **Build the SCSS files**: Compile the styles using the provided build script:
   ```sh
   npm run production
   ```
4. **Watch for SCSS changes**: If making frequent changes to styles, use the watch command:
   ```sh
   npm run watch
   ```

#### DDEV Commands to Fix Issues
If you encounter issues with your project, try running these commands:

1. Update your local master branch or feature branch:
   ```sh
   git pull
   ```
2. Install the latest modules and themes:
   ```sh
   ddev composer install
   ```
3. Run the custom DDEV Drupal local update script:
   ```sh
   .ddev/commands/host/local_update
   ```
4. If the project is still broken, restart DDEV:
   ```sh
   ddev restart
   ```
5. If issues persist, try reimporting the database:
   ```sh
   ddev import-db
   ```
6. If the project is still not working, consult the project's tech lead.

### Composer
New functionality is often added through contributed, premium, or custom modules. These are installed and maintained using Composer, which is a dependency manager for PHP. This documentation on [Composer](https://getcomposer.org/doc/01-basic-usage.md) will help you get up to speed and manage dependencies efficiently.

**Note:** While developing new features or structures, do not update Composer dependencies unless necessary. Avoid running `composer update` unless it's part of the assigned task.

#### Quick Commands List
- To add a dependency to a project:
  ```sh
  composer require drupal/example_contrib_module
  ```
- To install all dependencies from `composer.json` or `composer.lock`:
  ```sh
  composer install
  ```

Changes to dependencies are written to `composer.json` and `composer.lock` in the root of our project, where they can be committed and pushed remotely.

### Drupal Configuration During Development
While developing a new feature or structure in Drupal, it's important to export the configuration of the feature you're working on. You can always check the status of the configuration synchronization using Drush:
   ```sh
   drush config:status
   ```
If you have changes to the configuration, use Drush to export these:
   ```sh
   drush cex -y
   ```
The configuration will be written to `.yml` files in `/config/sync`, from where they can be committed and pushed.

**Note:** When exporting config, check for language switches or overwriting customized configuration (such as standard Drupal emails changed through core updates).

### Code Quality Tools

This project uses several tools to maintain code quality and ensure adherence to Drupal coding standards:

#### PHP Code Sniffer (PHPCS)

PHPCS is used to check PHP code against the Drupal coding standards. To check your code:

```sh
./vendor/bin/phpcs --standard=Drupal webroot/modules/custom
```

#### PHP Code Beautifier and Fixer (PHPCBF)

PHPCBF can automatically fix many coding standard violations detected by PHPCS. To auto-fix your code:

```sh
./vendor/bin/phpcbf --standard=Drupal webroot/modules/custom
```

#### Drupal Check

Drupal Check is used to identify deprecated code usage and other potential issues:

```sh
./vendor/bin/drupal-check webroot/modules/custom
```

Running these tools regularly helps maintain code quality and prevents issues from accumulating over time.

## Custom Modules

The SAHO project includes several custom modules that provide specific functionality tailored to the site's needs. These modules are located in `/webroot/modules/custom/`.

### Module Architecture

Our custom modules follow Drupal's standard module structure and coding standards:

- **Naming Convention**: All custom modules are prefixed with `saho_` to distinguish them from contributed modules
- **Service-Oriented**: Modules utilize Drupal's service container for dependency injection
- **Block Plugins**: Custom blocks are implemented as plugins for reusability
- **Component-Based**: Frontend components use templates and libraries for consistent theming

### Available Modules

#### Core Functionality Modules

##### `saho_tools`
**Purpose**: Provides citation and sharing functionality across the site
- **Features**:
  - Citation generation for academic references
  - Social media sharing buttons
  - Citation formatter field plugin
  - Citation block for node display
- **Key Components**:
  - CitationService: Handles citation formatting logic
  - CitationController: Provides citation endpoints
  - Custom JavaScript for citation interactions

##### `saho_media_migration`
**Purpose**: Handles migration of media assets and file mappings
- **Features**:
  - Batch processing for large media migrations
  - File mapping service for tracking migrations
  - Drush commands for command-line operations
  - Admin UI for migration management
- **Usage**: Essential for content migrations and media updates

##### `saho_statistics`
**Purpose**: Tracks and analyzes site usage statistics
- **Features**:
  - Term tracking for taxonomy usage
  - Custom statistics collection
  - Integration with Drupal's statistics module

#### Utility Modules (`saho_utils`)

The `saho_utils` module serves as a container for smaller, focused sub-modules:

##### `tdih` (This Day in History)
**Purpose**: Displays historical events for specific dates
- **Features**:
  - Interactive date selector
  - Birthday event display
  - AJAX-powered event loading
  - Custom blocks for different display modes
- **Components**:
  - TdihBlock: Standard event display
  - TdihInteractiveBlock: Interactive calendar interface
  - NodeFetcher service: Retrieves events by date

##### `featured_biography`
**Purpose**: Showcases prominent biographical content
- **Features**:
  - Responsive biography cards
  - Image carousel support
  - Custom JavaScript for interactions
  - Configurable block displays

##### `featured_articles`
**Purpose**: Highlights important articles and content
- **Features**:
  - Curated article selection
  - Multiple display formats
  - Integration with Views for dynamic content

##### `entity_overview`
**Purpose**: Provides comprehensive entity summaries
- **Features**:
  - Dynamic entity information display
  - JavaScript-enhanced interactions
  - Customizable overview templates

#### Maintenance Modules

##### `saho_cleanup`
**Purpose**: Data cleanup and maintenance utilities
- **Features**:
  - Custom field type definitions
  - Data migration helpers
  - Legacy data handling

##### `db_fixes`
**Purpose**: Database maintenance and fixes
- **Features**:
  - Update hooks for database changes
  - Data integrity checks
  - Migration fixes

##### `gdoc_field`
**Purpose**: Google Docs field integration
- **Features**:
  - Custom field formatter for Google Docs
  - Embedded document display
  - Responsive iframe handling

### Module Development Guidelines

1. **Creating New Modules**:
   - Use meaningful prefixes: `saho_` for general modules, descriptive names for specific features
   - Include comprehensive README files in each module
   - Follow Drupal coding standards (use PHPCS/PHPCBF)

2. **Service Development**:
   - Define services in `.services.yml` files
   - Use dependency injection for service dependencies
   - Document service methods with PHPDoc

3. **Block Plugin Development**:
   - Extend `BlockBase` for custom blocks
   - Implement proper cache contexts and tags
   - Use configuration forms for block settings

4. **JavaScript Integration**:
   - Define libraries in `.libraries.yml`
   - Use Drupal behaviors for JavaScript initialization
   - Follow ES6+ standards where supported

5. **Template Structure**:
   - Place templates in `templates/` directory
   - Use semantic HTML5 elements
   - Implement proper accessibility attributes

## SAHO Timeline Application

The SAHO Timeline is a high-performance, interactive timeline application built with **Svelte 5** and **Vite**. It provides users with an immersive way to explore South African history through thousands of chronologically organized events.

###  Key Features

- **Interactive Timeline**: Smooth scrolling through centuries of South African history
- **Advanced Filtering**: Filter by historical periods, themes, event types, and search terms  
- **Research Tools**: Academic citation support with pipe-separated references
- **Responsive Design**: Mobile-first approach with desktop enhancements
- **Virtual Scrolling**: Efficient rendering of thousands of historical events
- **Historical Periods**: Pre-Colonial, Colonial, Union, Apartheid, and Democratic eras

### 📁 Location & Access

- **Source Code**: `/saho-timeline-svelte/`
- **Production URL**: `https://sahistory-web.ddev.site/saho-timeline/`
- **Development Server**: `http://localhost:5173` (when running locally)

###  Quick Start

```bash
# Navigate to timeline app
cd saho-timeline-svelte

# Install dependencies  
npm install

# Start development server
npm run dev

# Build for production
npm run build

# Deploy to production
cp -r dist/* ../webroot/saho-timeline/
```

### 📖 Documentation

For comprehensive documentation including:
- Development workflow
- Build and deployment processes  
- Component architecture
- API integration
- Troubleshooting guide

See the detailed README: **`saho-timeline-svelte/README.md`**

###  Integration with SAHO

The timeline app integrates seamlessly with the main SAHO Drupal site:

- **API Connection**: Fetches events from `/api/timeline/events` endpoint
- **Citation System**: Uses SAHO's academic citation format
- **Responsive Design**: Matches SAHO's mobile-first approach
- **Theme Consistency**: Follows SAHO's design language and color scheme

###  For Developers

The timeline app is a **standalone Svelte application** with its own:
- Build process (`npm run build`)
- Development server (`npm run dev`) 
- Dependencies (`package.json`)
- Component structure (`src/lib/`)
- Deployment workflow (to `/webroot/saho-timeline/`)

**Important**: Always deploy built files to `/webroot/saho-timeline/` (not `/webroot/timeline-app/`)

## WebP Image Optimization

The SAHO website includes a comprehensive WebP image optimization system for improved performance and reduced bandwidth usage.

### Quick Start

```bash
# Check conversion status
scripts/webp.sh status

# Run complete optimization
scripts/webp.sh clean && scripts/webp.sh convert
```

### Key Features

- **Automatic WebP conversion** for all uploaded images
- **95%+ conversion rate** with significant bandwidth savings (60-70%)
- **Fake HTML file cleanup** (removes 404 error pages saved as images)
- **Production-ready scripts** with comprehensive error handling
- **Automatic browser detection** (serves WebP to compatible browsers)

### Production Deployment

```bash
# Copy to production
scp -r scripts/ user@production:/path/to/drupal/

# Run on production  
scripts/webp.sh clean && scripts/webp.sh convert
```

### Documentation

All WebP scripts and detailed documentation are located in the `scripts/` directory:

- **`scripts/WEBP_OPTIMIZATION.md`** - Complete setup and usage guide
- **`scripts/README.md`** - Detailed script reference  
- **`scripts/PRODUCTION_COMMANDS.md`** - Production deployment guide
- **`scripts/webp.sh`** - Main script runner with simple commands

### Available Commands

| Command | Purpose |
|---------|---------|
| `scripts/webp.sh status` | Check conversion rate |
| `scripts/webp.sh clean` | Remove fake HTML files |
| `scripts/webp.sh convert` | Convert all images |
| `scripts/webp.sh audit` | Production audit |
| `scripts/webp.sh debug file.jpg` | Debug specific file |

The system automatically detects development vs production environments and handles path detection seamlessly.

### Radix and "saho" Subtheme
The SAHO website uses the Radix theme as the base, with a custom subtheme named "saho." The Radix theme allows for the use of components, which makes it easy to maintain consistency across the website.

For detailed information on Radix, you can visit [Radix Documentation](https://radix.trydrupal.com/radix/working-with-the-components/components-intro).

### Theme Architecture

The SAHO theme follows a modern, component-based architecture built on top of Radix and Bootstrap 5:

#### Directory Structure
```
webroot/themes/custom/saho/
├── components/          # Radix components with Twig templates
├── src/                 # Source files for compilation
│   ├── scss/           # SCSS source files
│   │   ├── base/       # Base styles and utilities
│   │   └── components/ # Component-specific styles
│   └── js/             # JavaScript source files
├── css/                # Additional compiled CSS
├── js/                 # Additional JavaScript files
├── templates/          # Drupal template overrides
└── includes/           # Theme hooks and preprocessing
```

### Component Development

Components in the SAHO theme are self-contained units that include:
- **Twig template** (`.twig`): HTML structure
- **SCSS file** (`.scss`): Component styles
- **Component definition** (`.component.yml`): Metadata and properties
- **README** (`.mdx`): Documentation

#### Available Components
- **block**: Standard content blocks with consistent styling
- **carousel**: Image and content carousels
- **navbar**: Main navigation with responsive behavior
- **page-footer**: Site footer with social links
- **page-navigation**: Breadcrumbs and local navigation
- **local-tasks**: Admin tabs and actions

### Styling Guidelines

#### SCSS Architecture
The theme uses a structured SCSS approach:

1. **Base Styles** (`src/scss/base/`):
   - `_variables.scss`: Theme colors, spacing, typography
   - `_mixins.scss`: Reusable style patterns
   - `_typography.scss`: Font definitions and text styles
   - `_utilities.scss`: Helper classes
   - `_featured-biography.scss`: Biography-specific styles
   - `_tdih-interactive.scss`: This Day in History styles

2. **Component Styles** (`src/scss/components/`):
   - `_unified-cards.scss`: Consistent card layouts
   - `_glass-card.scss`: Modern glass-morphism effects
   - `_hover-effects.scss`: Interactive hover states
   - `_search-results.scss`: Search result styling
   - `_context-sections.scss`: Contextual content areas

3. **Additional Styles** (`css/`):
   - `modern-modals.css`: Modal dialog enhancements
   - `sidebar-accordion.css`: Collapsible sidebar elements
   - `biography-metadata.css`: Biography-specific metadata display
   - `url-truncation.css`: Smart URL display truncation

#### CSS Best Practices
- Use BEM naming convention for classes
- Leverage Bootstrap 5 utilities where appropriate
- Maintain mobile-first responsive design
- Use CSS custom properties for dynamic theming
- Minimize specificity to ease overrides

#### JavaScript Architecture
JavaScript files follow Drupal behaviors pattern:

1. **Core Scripts** (`src/js/`):
   - `main.script.js`: Primary theme JavaScript
   - `search-enhancements.js`: Search UX improvements
   - `mobile-enhancements.js`: Mobile-specific features
   - `advanced-search.js`: Advanced search functionality

2. **Bootstrap Integration**:
   - Custom Bootstrap 5 configuration
   - Toast notifications
   - Tooltip initialization

3. **Interactive Features** (`js/`):
   - `sidebar-accordion.js`: Accordion functionality
   - `sidebar-tabs.js`: Tab navigation
   - `url-truncation.js`: Dynamic URL truncation

### Build Process

The theme uses **Vite** for fast asset compilation. Built assets are output to `dist/` and **must be committed** (no Node.js on production server).

1. **Install Dependencies**:
   ```sh
   cd webroot/themes/custom/saho
   npm install
   ```

2. **Development Build** (with source maps):
   ```sh
   npm run dev
   ```

3. **Production Build** (minified):
   ```sh
   npm run production
   ```

4. **Watch Mode** (auto-compile on changes):
   ```sh
   npm run watch
   ```

5. **Code Quality** (Biome linting):
   ```sh
   npm run biome:check
   npm run biome:fix
   ```

> ** Important**: After running `npm run production`, you must commit the `dist/` folder. The production server does not have Node.js, so built assets must be in the repository.

### Theme Configuration

- **Libraries** (`saho.libraries.yml`): Defines CSS/JS assets
- **Breakpoints** (`saho.breakpoints.yml`): Responsive breakpoints
- **Theme Settings** (`saho.info.yml`): Theme metadata and regions
- **Block Configuration** (`config/optional/`): Default block placement

### Template Overrides

The theme provides extensive template overrides organized by type:
- **Content**: Node displays for articles, biographies, events
- **Blocks**: Custom block templates
- **Forms**: Search forms and exposed filters
- **Views**: Landing pages, archives, search results
- **Navigation**: Menus, breadcrumbs, pagers

#### Working with the "saho" Subtheme
- The "saho" subtheme builds upon Radix's component-based architecture.
- When developing components, use the `components/` directory within the "saho" subtheme for better organization.
- Use Radix's built-in tools for consistent styling and JS integration.
- Theming is done by extending Radix templates within the "saho" theme directory, which keeps the customizations modular and easy to maintain.
- SCSS files are used to create styles, and they are compiled using Node.js. Ensure you run `npm install` in the theme directory to get the required dependencies.

## Using the Drupal Radix CLI with DDEV

The following steps will help you use the `drupal-radix-cli` command within a DDEV environment for managing Radix themes.

### Step-by-Step Instructions

1. **List Available Components**
   To use the Radix CLI we have to enter our Drupal container within DDEV, run:
   ```sh
   ddev ssh
   ```
   You are now in your Drupal and you can use the radix CLI:
   ```sh
   cd webroot/themes/contrib/radix
   ```

2. **List Available Components**
   To list all components available in your Radix theme, run:
   ```sh
   drupal-radix-cli list
   ```

3. **Add a Radix Component**
   To add a Radix component to your current theme, use:
   ```sh
   drupal-radix-cli add
   ```
   Use the `--radix-path` flag to specify a custom Radix components directory if your Radix base theme is installed in a non-standard location:
   ```sh
   drupal-radix-cli add --radix-path ../../radix/components
   ```

4. **Generate a New Component**
   To generate a clean new component folder within your subtheme components directory:
   ```sh
   drupal-radix-cli generate
   ```
   This will generate a new component folder with the following files:
   - `[component-name]/[component-name].twig`
   - `[component-name]/[component-name].component.yml`
   - `[component-name]/[component-name].scss`
   - `[component-name]/_[component-name].js`
   - `[component-name]/README.md`

   Make sure to remove any unwanted files and update your files accordingly.

5. **Help**
   To display usage instructions:
   ```sh
   drupal-radix-cli --help
   ```
   Or simply run:
   ```sh
   drupal-radix-cli
   ```

The `drupal-radix-cli` provides various commands to help manage and work with Radix themes effectively. You can explore more commands and options by referring to the [official Radix documentation](https://radix.trydrupal.com/radix/working-with-the-components/the-drupal-radix-cli).

### Git Strategy & Collaboration
We use Git to facilitate collaboration and versioning of our code. Our Git strategy aims to keep things lean and straightforward, allowing for a dynamic workflow suitable for the size of the project and developer team.

#### Branch Naming Convention and Commit Messages
Branch naming follows Gitflow conventions with Novicell customizations:
- Branch names should include the issue tag (example: `SAHO-50`):
  ```
  SAHO-50--example-commit-message
  ```

#### Trunk-Based Development & Rebase
Trunk-based development is a version control management practice where developers merge small, frequent updates to a core "trunk" or master branch, helping achieve CI/CD and enhancing software delivery and team performance. You can learn more about [trunk-based development here](https://www.atlassian.com/continuous-delivery/continuous-integration/trunk-based-development).

##### Rebase in Developer Workflow
Here's an example workflow for using Git in this project:
1. Pull the latest changes from the master branch:
   ```sh
   git pull --rebase origin master
   ```
2. Create a feature branch:
   ```sh
   git switch -c my-awesome-feature-branch
   ```
3. Make changes and commit often:
   ```sh
   git add .
   ```
   ```sh
   git commit -m "My awesome comment"
   ```
4. Periodically rebase your work onto the master branch to incorporate recent changes:
   ```sh
   git checkout master
   ```
   ```sh
   git pull --rebase origin master
   ```
5. Rebase your feature branch onto the updated master branch:
   ```sh
   git checkout feature/my-awesome-feature-branch
   ```
   ```sh
   git rebase master
   ```
6. Push your branch to the remote repository:
   ```sh
   git push origin feature/my-awesome-feature-branch --force
   ```

**Note:** Always use the `--force` flag to overwrite the remote branch history when necessary. Avoid merging master into your feature branch; instead, rebase to keep the commit history clean.

#### Pull Requests & Reviews
New features should be submitted as Pull Requests (PRs) and reviewed by other developers on the team. A good PR process includes:
- Locally building the project with the new feature.
- Testing the new feature locally.
- Creating a PR including a list of added/removed features.
- Providing a short guide on how to test the feature for the reviewer.
- Communicating if the PR is blocked by another PR, decision, or action.

#### Best Practices for Collaboration
- Use only fast-forward merges to the trunk.
- Use Pull Requests for merges to engage team members (master branch is protected).
- Squash multiple commits into one for a cleaner history.
- Rebase master against your short-lived branches, but do not merge master into them.
- Feature branches should be short-lived (ideally max. two working days).
- Keep commit messages concise and always include the case number.
- Never commit secrets of any kind to the repository.
- Use the `--sort-packages` flag with Composer for better dependency management.

Let's keep the repository clean and delete old feature branches when they are no longer needed.

## Contributing to SAHO

South African History Online welcomes contributions from people with diverse skills and backgrounds. You don't need to be a developer to make a meaningful impact on this project!

### Ways to Contribute

#### Historians, Researchers, and Content Creators
- **Historical Research**: Help research and verify historical information
- **Content Creation**: Write or edit articles about South African history
- **Translation**: Translate content into South African languages
- **Fact-checking**: Review existing content for accuracy
- **Multimedia**: Contribute photographs, videos, or audio recordings related to South African history

#### Designers and UX Specialists
- **User Experience**: Suggest improvements to the website's usability
- **Accessibility**: Help make the site more accessible to all users
- **Visual Design**: Create graphics, icons, or other visual elements
- **User Testing**: Participate in or help organize user testing sessions

#### Educators and Community Members
- **Educational Resources**: Develop teaching materials based on SAHO content
- **Community Outreach**: Help connect SAHO with schools, universities, and community organizations
- **Feedback**: Provide feedback on how SAHO could better serve educational needs
- **Event Organization**: Help organize events related to South African history

#### Technical Contributors
- **Development**: Contribute code to improve the website
- **Documentation**: Improve technical documentation
- **Testing**: Help test new features before they're released
- **DevOps**: Assist with deployment, performance, and security improvements

### Getting Started

1. **Explore the site**: Familiarize yourself with [South African History Online](https://www.sahistory.org.za/)
2. **Check out open issues**: Look for issues labeled "good first issue" or "help wanted"
3. **Share your ideas**: Use our [Contribution Ideas](https://github.com/South-African-History-Online/sahistory-web/issues/new?template=contribution_guide.yml) template to suggest how you'd like to contribute
4. **Join the conversation**: Engage with the community through issue discussions

We value all contributions, big and small, and are committed to creating an inclusive and welcoming environment for contributors of all backgrounds and experience levels.

### CI/CD Pipeline 

[![Frontend CI](https://github.com/South-African-History-Online/sahistory-web/actions/workflows/frontend-ci.yml/badge.svg)](https://github.com/South-African-History-Online/sahistory-web/actions/workflows/frontend-ci.yml)
[![PR Build and Test](https://github.com/South-African-History-Online/sahistory-web/actions/workflows/ci.yml/badge.svg)](https://github.com/South-African-History-Online/sahistory-web/actions/workflows/ci.yml)

This project leverages a robust Continuous Integration and Continuous Deployment (CI/CD) pipeline to ensure high code quality and streamline the development process. Our automated workflows help catch issues early and maintain a consistent codebase.

####  Benefits of Our CI/CD Pipeline

- **Catch Issues Early**: Identify and fix problems before they reach production
- **Consistent Quality**: Enforce coding standards across all contributions
- **Faster Development**: Automate repetitive tasks like testing and building
- **Reliable Deployments**: Ensure only working code gets deployed
- **Better Collaboration**: Provide immediate feedback on pull requests

#### 🔍 What the Pipeline Checks

- **JavaScript Linting** ⚡: Uses Biome to enforce code style and catch potential errors
- **SCSS Compilation** 🎨: Ensures that SCSS files compile correctly
- **Asset Building** 📦: Builds and optimizes frontend assets
- **PHP Coding Standards** 🐘: Verifies PHP code follows Drupal coding standards
- **Drupal Best Practices** 💧: Checks for Drupal-specific issues and recommendations
- **Composer Validation** 🎵: Ensures Composer configuration is valid

#### 🛠 How to Ensure the Pipeline Passes

Before pushing your changes, run these checks locally:

```sh
# For frontend changes
cd webroot/themes/custom/saho
npm run biome:check
npm run production

# For PHP/Drupal changes
composer validate --strict
./vendor/bin/phpcs --standard=Drupal webroot/modules/custom
./vendor/bin/drupal-check webroot/modules/custom
```

The pipeline status is displayed at the top of this README and on each pull request. Green means everything is working correctly! 🟢

##### Useless Incrementor
This is a simple counter that can be incremented to trigger the CI/CD pipeline for testing purposes. When you need to trigger a build without making any meaningful changes to the codebase, simply increment this number and commit the change.

Current value: 4