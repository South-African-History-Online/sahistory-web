# CLAUDE.md - AI Assistant Guide for SAHO Development

## Project Overview
South African History Online (SAHO) - sahistory.org.za
- **Drupal Version**: 11.1.7
- **PHP Version**: 8.3.10
- **Local Environment**: DDEV
- **Theme**: Radix with custom "saho" subtheme
- **Frontend**: Bootstrap 5, Svelte 5 (Timeline app)

## Essential Commands

### DDEV Environment
```bash
ddev start                    # Start environment
ddev describe                 # Get local URLs and info
ddev ssh                      # Enter container
ddev restart                  # Restart if issues
ddev import-db                # Import database
.ddev/commands/host/local_update  # Run update scripts
```

### Drupal Configuration
```bash
ddev drush config:status      # Check config sync status
ddev drush cex -y            # Export configuration
ddev drush cr                # Clear cache
ddev drush updb              # Run database updates
```

### Code Quality - MUST RUN BEFORE COMMITTING
```bash
# PHP Code Standards (Drupal 11)
./vendor/bin/phpcs --standard=Drupal webroot/modules/custom
./vendor/bin/phpcbf --standard=Drupal webroot/modules/custom  # Auto-fix

# Check for deprecated code
./vendor/bin/drupal-check webroot/modules/custom

# Frontend (in theme directory)
cd webroot/themes/custom/saho
npm run biome:check          # Lint JavaScript
npm run biome:fix           # Auto-fix JS issues
npm run production          # Build production assets
```

### Theme Development
```bash
cd webroot/themes/custom/saho
npm install                  # Install dependencies
npm run dev                  # Development build with source maps
npm run watch               # Auto-compile on changes
npm run production          # Production build (minified)
```

## Drupal 11 Coding Standards

### Module Development
- **Naming**: Prefix all custom modules with `saho_`
- **Structure**: Follow standard Drupal module structure
- **Services**: Use dependency injection via `.services.yml`
- **Blocks**: Extend `BlockBase`, implement proper cache contexts
- **Controllers**: Return render arrays or Response objects
- **Forms**: Extend `FormBase` or `ConfigFormBase`

### PHP Standards
```php
<?php

namespace Drupal\saho_module_name;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom block.
 *
 * @Block(
 *   id = "saho_custom_block",
 *   admin_label = @Translation("SAHO Custom Block"),
 * )
 */
class CustomBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'saho_custom_block',
      '#data' => $this->getData(),
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['node_list'],
        'max-age' => 3600,
      ],
    ];
  }

}
```

### JavaScript Standards
```javascript
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.sahoFeature = {
    attach: function (context, settings) {
      once('saho-feature', '.saho-element', context).forEach(function (element) {
        // Initialize component
      });
    }
  };

})(Drupal, once);
```

### Twig Templates
```twig
{#
/**
 * @file
 * Theme template for SAHO component.
 *
 * Available variables:
 * - attributes: HTML attributes for the element.
 * - title: The title of the component.
 * - items: Array of items to display.
 */
#}
<div{{ attributes.addClass('saho-component') }}>
  {% if title %}
    <h2>{{ title }}</h2>
  {% endif %}
  {% for item in items %}
    <div class="saho-component__item">
      {{ item }}
    </div>
  {% endfor %}
</div>
```

## Project Structure

### Key Directories
```
/webroot/modules/custom/       # Custom modules
  saho_tools/                 # Citation and sharing
  saho_featured_articles/     # Featured content blocks
  saho_utils/                # Utility sub-modules
    tdih/                    # This Day in History
    featured_biography/      # Biography showcases
/webroot/themes/custom/saho/  # Custom theme
  components/                # Radix components
  src/scss/                 # SCSS source
  templates/                # Template overrides
/saho-timeline-svelte/        # Timeline application
/scripts/                    # WebP optimization scripts
/config/sync/               # Configuration export
```

## Development Workflow

### Before Starting Work
1. Pull latest changes: `git pull --rebase origin main`
2. Update dependencies: `ddev composer install`
3. Run updates: `.ddev/commands/host/local_update`
4. Clear cache: `ddev drush cr`

### While Working
1. Export config after Drupal changes: `ddev drush cex -y`
2. Build theme assets: `npm run dev` (in theme directory)
3. Test your changes thoroughly
4. Run code quality checks (see above)

### Before Committing
1. **CRITICAL**: Run all code quality checks
   ```bash
   ./vendor/bin/phpcs --standard=Drupal webroot/modules/custom
   ./vendor/bin/drupal-check webroot/modules/custom
   cd webroot/themes/custom/saho && npm run biome:check
   ```
2. Fix any issues found
3. Export configuration if needed: `ddev drush cex -y`
4. Build production assets: `npm run production`
5. **Commit the `dist/` folder** (no Node.js on prod server)
6. Test one more time

### Git Workflow
```bash
# Create feature branch
git switch -c SAHO-XX--feature-description

# Regular commits
git add .
git commit -m "SAHO-XX: Clear description of change"

# Keep branch updated
git checkout main
git pull --rebase origin main
git checkout SAHO-XX--feature-description
git rebase main

# Push changes
git push origin SAHO-XX--feature-description --force
```

### Version Tagging
The project uses semantic versioning with git tags (v1.0.0, v1.1.0, v2.0.0):

```bash
# Create and push a new version tag
git tag v1.0.1 && git push origin v1.0.1

# View current version
git describe --tags

# List all tags
git tag --sort=-v:refname
```

Version tags are automatically detected by:
- `version.php` - displays current version
- `scripts/deploy-default.sh` - uses tags for deployment tracking

**Versioning Guidelines:**
- **Patch** (v1.0.1): Bug fixes, minor tweaks
- **Minor** (v1.1.0): New features, non-breaking changes
- **Major** (v2.0.0): Breaking changes, major rewrites

## Common Tasks

### Adding a New Module
```bash
# Create module structure
mkdir -p webroot/modules/custom/saho_new_feature
cd webroot/modules/custom/saho_new_feature

# Create info file (saho_new_feature.info.yml)
# Create module file if needed
# Add services, blocks, controllers as needed

# Enable module
ddev drush en saho_new_feature -y
```

### Creating a Component
```bash
ddev ssh
cd webroot/themes/contrib/radix
drupal-radix-cli generate
# Follow prompts to create component
```

### Working with Views
1. Create/modify view in UI
2. Export configuration: `ddev drush cex -y`
3. Create template override if needed in `webroot/themes/custom/saho/templates/views/`
4. Clear cache: `ddev drush cr`

### Debugging
```bash
# Check logs
ddev logs -f              # DDEV container logs
ddev drush ws            # Drupal watchdog logs

# Performance
ddev drush sqlq "SHOW PROCESSLIST"  # Check database queries
ddev xhprof              # Profile PHP performance

# Clear everything
ddev drush cr            # Clear cache
ddev drush cim -y       # Import config
ddev drush updb -y      # Run updates
```

## Security Best Practices
- NEVER commit secrets, API keys, or passwords
- Sanitize all user input
- Use Drupal's database API for queries
- Implement proper access controls
- Keep dependencies updated
- Follow OWASP guidelines

## Performance Guidelines
- Implement proper cache tags and contexts
- Use lazy loading for images
- Minimize JavaScript execution
- Optimize database queries
- Use WebP images (scripts available)
- Enable aggregation for CSS/JS

## Testing Requirements
- Test on mobile devices
- Verify accessibility (WCAG 2.1 AA)
- Cross-browser testing (Chrome, Firefox, Safari, Edge)
- Test with JavaScript disabled
- Verify SEO meta tags

## Important Notes
- This is a production site - be careful with database operations
- Always backup before major changes
- Follow trunk-based development (short-lived branches)
- Code reviews required for all PRs
- Keep commit messages clear with SAHO-XX tags
- Timeline app has separate build process in `/saho-timeline-svelte/`

## Support & Resources
- GitHub Issues: https://github.com/South-African-History-Online/sahistory-web/issues
- Drupal Docs: https://www.drupal.org/docs/11
- Radix Docs: https://radix.trydrupal.com/
- DDEV Docs: https://ddev.readthedocs.io/

## Quick Fixes

### "Command not found" errors
```bash
ddev composer install
cd webroot/themes/custom/saho && npm install
```

### Cache issues
```bash
ddev drush cr
ddev restart
```

### Configuration sync issues
```bash
ddev drush cex -y  # Export current
# OR
ddev drush cim -y  # Import from files
```

### Theme not updating
```bash
cd webroot/themes/custom/saho
npm run production
ddev drush cr
```

Remember: When in doubt, check the README.md for more detailed information!