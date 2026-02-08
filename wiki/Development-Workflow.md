# Development Workflow

Complete guide to development processes, coding standards, and collaboration workflows for SA History Web.

## Git Workflow

### Branch Strategy
We use a **feature branch workflow** with the following conventions:

```
main                    # Production-ready code
├── develop            # Integration branch (optional)
├── feature/user-auth  # New features
├── bugfix/login-issue # Bug fixes  
├── hotfix/security    # Critical production fixes
└── content/new-pages  # Content-related updates
```

### Branch Naming Conventions
```bash
# Features
feature/featured-articles-page
feature/search-enhancement
feature/mobile-navigation

# Bug fixes
bugfix/template-rendering-error
bugfix/image-upload-validation
bugfix/cache-clear-issue

# Content updates
content/liberation-struggle-section
content/heritage-month-campaign

# Hotfixes (critical production issues)
hotfix/security-patch-drupal
hotfix/database-connection-fix
```

## Development Process

### 1. Setting Up a New Feature

```bash
# Start from main branch
git checkout main
git pull origin main

# Create and switch to feature branch
git checkout -b feature/your-feature-name

# Make your changes...
# Test thoroughly in DDEV

# Stage and commit changes
git add .
git commit -m "Add featured articles dynamic loading

- Implement category switching functionality
- Add South African history context to descriptions
- Fix Node object rendering in templates
- Add proper Bootstrap card components

# Push feature branch
git push -u origin feature/your-feature-name
```

### 2. Code Review Process

#### Pull Request Requirements
- [ ] **Descriptive title** and detailed description
- [ ] **Testing performed** in DDEV environment  
- [ ] **Screenshots** for UI changes
- [ ] **Database changes** documented (if any)
- [ ] **Performance impact** considered
- [ ] **Accessibility tested** (if frontend changes)

#### Review Checklist
- [ ] **Code Quality**: Follows Drupal coding standards
- [ ] **Security**: No vulnerabilities introduced
- [ ] **Performance**: No significant performance degradation  
- [ ] **Documentation**: Code is well-commented
- [ ] **Testing**: Adequate test coverage
- [ ] **Cultural Sensitivity**: Appropriate for SA history context

### 3. Merge and Deployment

```bash
# After PR approval, merge to main
git checkout main
git merge feature/your-feature-name
git push origin main

# Clean up feature branch
git branch -d feature/your-feature-name
git push origin --delete feature/your-feature-name

# Deploy to production (see Deployment Guide)
```

## Coding Standards

### PHP/Drupal Standards

#### File Organization
```php
<?php

/**
 * @file
 * Brief description of the file's purpose.
 */

namespace Drupal\module_name\Controller;

use Drupal\Core\Controller\ControllerBase;
// Other use statements...

/**
 * Class description.
 */
class ExampleController extends ControllerBase {
  // Implementation...
}
```

#### Method Documentation
```php
/**
 * Builds the featured articles page.
 *
 * Queries the database for nodes marked as featured or staff picks,
 * loads the entities, and renders them using the custom template.
 *
 * @return array
 *   A render array for the featured articles page.
 */
public function page() {
  // Implementation...
}
```

#### Database Queries
```php
// Use Drupal's database abstraction
$query = $this->database->select('node_field_data', 'n');
$query->fields('n', ['nid']);
$query->condition('n.status', 1);
$query->range(0, 50);

// Always use parameterized queries
$results = $query->execute();
```

### Twig Templates

#### Template Structure
```twig
{#
/**
 * @file
 * Template for the featured articles landing page.
 *
 * Available variables:
 * - nodes: Array of featured article nodes.
 * - section_name: The section name for display.
 */
#}

{% set section_name = 'Featured Articles' %}
{% set section_color = 'saho-deep-heritage-red' %}

<div class="saho-landing-page">
  {# Main content #}
{% endif %}
```

#### Safe Output
```twig
{# Auto-escaped by default #}
{{ node_title }}

{# Sanitized output #}
{{ summary|striptags|trim|slice(0, 120) }}

{# Avoid raw output unless absolutely necessary #}
{{ content|raw }}  {# Use with caution #}
```

### CSS/JavaScript Standards

#### SCSS Organization
```scss
// webroot/themes/custom/saho/src/scss/
├── base/
│   ├── _variables.scss     # Color variables, fonts
│   ├── _mixins.scss        # Reusable mixins
│   └── _landing-pages.scss # Landing page components
├── components/
│   ├── _cards.scss         # Card components
│   ├── _navigation.scss    # Navigation elements
│   └── _featured.scss      # Featured content specific
└── main.scss              # Main import file
```

#### JavaScript Patterns
```javascript
// Use Drupal behaviors pattern
(function ($, Drupal) {
  'use strict';

  /**
   * Featured content navigation behavior.
   */
  Drupal.behaviors.featuredContentNavigation = {
    attach: function (context, settings) {
      // Initialize once per context
      $('.view-featured-content', context).once('featured-init').each(function() {
        initializeFeaturedContent();
      });
    }
  };

  /**
   * Private function for initialization.
   */
  function initializeFeaturedContent() {
    // Implementation...
  }

})(jQuery, Drupal);
```

## Testing Standards

### Manual Testing Checklist

#### Before Every Commit
- [ ] **DDEV Environment**: All changes tested locally
- [ ] **Cache Cleared**: `ddev drush cr` after changes
- [ ] **Database Updated**: `ddev drush updb` if schema changes
- [ ] **No PHP Errors**: Check `/admin/reports/dblog`
- [ ] **Template Rendering**: No Twig errors or warnings

#### Frontend Testing
- [ ] **Responsive Design**: Test mobile, tablet, desktop
- [ ] **Browser Compatibility**: Chrome, Firefox, Safari, Edge
- [ ] **Accessibility**: Basic keyboard navigation
- [ ] **Performance**: Page loads under 3 seconds

#### Content Testing
- [ ] **Featured Content**: Test marking/unmarking as featured
- [ ] **Category Navigation**: All category switches work
- [ ] **Image Display**: Images load properly with fallbacks
- [ ] **Links**: All internal and external links work

### Database Testing
```bash
# Test featured content queries
ddev drush sql-query "SELECT COUNT(*) FROM node__field_staff_picks WHERE field_staff_picks_value = 1"

# Test content display
ddev drush sql-query "SELECT n.title, n.changed FROM node_field_data n 
JOIN node__field_home_page_feature f ON n.nid = f.entity_id 
WHERE f.field_home_page_feature_value = 1 AND n.status = 1 LIMIT 10"
```

## Code Quality Tools

### PHP Code Sniffer
```bash
# Check coding standards
ddev exec vendor/bin/phpcs --standard=Drupal webroot/modules/custom
ddev exec vendor/bin/phpcs --standard=Drupal webroot/themes/custom

# Auto-fix coding standards (where possible)
ddev exec vendor/bin/phpcbf --standard=Drupal webroot/modules/custom
```

### Drupal Check (Static Analysis)
```bash
# Analyze custom modules for Drupal best practices
ddev exec vendor/bin/drupal-check webroot/modules/custom

# Check specific module
ddev exec vendor/bin/drupal-check webroot/modules/custom/saho_featured_articles
```

## Debugging

### Drupal Debugging
```php
// Use Drupal's logging system
\Drupal::logger('saho_featured_articles')->notice('Featured page loaded with @count items', ['@count' => count($nodes)]);

// Dump variables in development
dump($variable);  // Use with devel module

// Use watchdog for important events
watchdog('saho', 'Featured content updated: @title', ['@title' => $node->getTitle()], WATCHDOG_INFO);
```

### DDEV Debugging
```bash
# View all logs
ddev logs

# View specific service logs
ddev logs web
ddev logs db

# SSH into containers for debugging
ddev ssh

# Database debugging
ddev mysql
mysql> SHOW FULL PROCESSLIST;
```

### Template Debugging
```yaml
# Enable Twig debugging (development only)
parameters:
  twig.config:
    debug: true
    auto_reload: true
    cache: false
```

## Performance Guidelines

### Database Performance
```php
// Use entity queries efficiently
$query = \Drupal::entityQuery('node');
$query->condition('type', 'article');
$query->condition('status', 1);
$query->range(0, 10);
$nids = $query->execute();

// Load entities in bulk
$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
```

### Frontend Performance
```scss
// Use efficient CSS selectors
.saho-featured-content .card { }  // Good
div div div .card { }             // Avoid

// Minimize CSS specificity
.featured-card { }                // Good
.page .container .row .col .card { } // Avoid
```

### Caching Strategy
```php
// Use proper cache tags
$build['#cache'] = [
  'tags' => ['node_list:article', 'config:views.view.featured_content'],
  'contexts' => ['url.path'],
  'max-age' => 300, // 5 minutes
];
```

## Cultural and Content Guidelines

### South African History Context
- **Sensitivity**: Always consider cultural implications of changes
- **Inclusivity**: Ensure diverse perspectives are represented
- **Accuracy**: Historical content must be factually accurate
- **Language**: Use inclusive, respectful language

### Content Categories
When working on content-related features, consider these SA history themes:
- **Liberation Struggle**: Anti-apartheid movements, freedom fighters
- **Cultural Heritage**: Languages, traditions, arts, music
- **Social Justice**: Land reform, economic transformation, human rights
- **Historical Events**: Key moments in SA history
- **Notable Figures**: Leaders, activists, artists, intellectuals

## Deployment Workflow

### Pre-Deployment Checklist
- [ ] **Feature Complete**: All requirements implemented
- [ ] **Testing Passed**: Manual and automated tests pass
- [ ] **Code Review**: PR approved by team members
- [ ] **Database Updates**: Migration scripts prepared (if needed)
- [ ] **Documentation Updated**: Wiki and inline docs current
- [ ] **Performance Tested**: No significant performance degradation

### Production Deployment
```bash
# Export current configuration
ddev drush cex -y

# Commit configuration changes
git add config/
git commit -m "Export configuration for deployment"

# Follow deployment guide procedures
# See: wiki/Deployment.md
```

## Troubleshooting Common Issues

### Module Development Issues
```bash
# Module not recognized
ddev drush pm:list | grep module_name
ddev drush en module_name -y

# Template not updating
ddev drush cr
# Or specifically clear Twig cache
ddev drush cache:rebuild
```

### Database Issues
```bash
# Connection problems
ddev restart
ddev describe

# Schema updates
ddev drush updb -y
```

### Performance Issues
```bash
# Clear all caches
ddev drush cr

# Check slow queries
ddev mysql
mysql> SHOW FULL PROCESSLIST;
mysql> SELECT * FROM information_schema.processlist WHERE TIME > 10;
```

---

**Next Steps**:
- Review [Technical Setup](Technical-Setup.md) for development environment
- Check [Architecture](Architecture.md) for system design
- Follow [Deployment Guide](Deployment.md) for production releases