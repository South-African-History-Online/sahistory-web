# Multisite Configuration Management

## Overview

SAHO uses Drupal's multisite architecture with two separate sites:
- **Default Site**: sahistory.org.za (main historical archive)
- **Shop Site**: shop.sahistory.org.za (Commerce-enabled shop for memberships and products)

Each site has its own configuration directory to prevent config spillover.

## Site Structure

```
webroot/sites/
├── default/                    # Main SAHO site
│   ├── settings.php
│   ├── settings.ddev.php
│   └── files/
└── shop.sahistory.org.za/     # Commerce shop site
    ├── settings.php
    ├── settings.ddev.php
    └── files/
```

## Configuration Directories

```
config/
├── sync/       # Default site config (sahistory.org.za)
├── shop/       # Shop site config (shop.sahistory.org.za)
└── schema/     # JSON Schema definitions
```

## Configuration Settings

### Default Site
**Location**: `webroot/sites/default/settings.php`

Uses Drupal's default config directory: `config/sync/`

```php
// Uses default: ../config/sync
# $settings['config_sync_directory'] = '/directory/outside/webroot';
```

### Shop Site
**Location**: `webroot/sites/shop.sahistory.org.za/settings.php`

```php
$settings['config_sync_directory'] = '../config/shop';
```

## Module Differences

### Default Site (`config/sync`)
- **Core modules**: All SAHO custom modules, content management, search, media
- **NO Commerce modules**: commerce_*, profile, content_lock
- **NO Wall of Champions**: shop-specific subscriber display

### Shop Site (`config/shop`)
- **All default site modules** PLUS:
- Commerce Order
- Commerce Product
- Commerce Payment
- Commerce Recurring
- Commerce Price
- Commerce Store
- Commerce Number Pattern
- Profile (for customer profiles)
- Content Lock (for order editing)
- Wall of Champions (displays Champion subscribers)

## Working with Multisite Config

### Exporting Configuration

**CRITICAL: Always specify which site you're working on**

#### Export Default Site Config
```bash
# Connect to default site
ddev drush @default cex -y

# Or from root
cd /home/mno/ddev-projects/sahistory-web
ddev drush -l default cex -y
```

#### Export Shop Site Config
```bash
# Connect to shop site
ddev drush -l shop.ddev.site cex -y

# Verify config goes to config/shop
ls config/shop/*.yml | wc -l  # Should show 578+ files
```

### Importing Configuration

#### Import Default Site Config
```bash
ddev drush -l default cim -y
ddev drush -l default cr
```

#### Import Shop Site Config
```bash
ddev drush -l shop.ddev.site cim -y
ddev drush -l shop.ddev.site cr
```

### Checking Config Status

```bash
# Check default site
ddev drush -l default config:status

# Check shop site
ddev drush -l shop.ddev.site config:status
```

## Development Workflow

### When Making Changes to Default Site
1. Make changes in Drupal UI or code
2. Export config: `ddev drush -l default cex -y`
3. Verify changes are in `config/sync/`
4. Commit to git
5. Deploy normally

### When Making Changes to Shop Site
1. Make changes in shop site Drupal UI or code
2. Export config: `ddev drush -l shop.ddev.site cex -y`
3. Verify changes are in `config/shop/`
4. Commit to git
5. Deploy to shop site only

### Enabling a Module

**Default site only:**
```bash
ddev drush -l default en module_name -y
ddev drush -l default cex -y
```

**Shop site only:**
```bash
ddev drush -l shop.ddev.site en module_name -y
ddev drush -l shop.ddev.site cex -y
```

**Both sites:**
Enable on each site separately and export each config directory.

## Common Mistakes to Avoid

### DO NOT: Export shop config without -l flag
```bash
# WRONG - might export to default site's config/sync
cd webroot
drush cex -y
```

### DO: Always specify the site
```bash
# CORRECT
ddev drush -l shop.ddev.site cex -y
ddev drush -l default cex -y
```

### DO NOT: Copy config files between sites manually
Each site has different module dependencies. Copying config can cause spillover.

### DO: Use proper Drush site aliases
```bash
# Define in drush/sites/self.site.yml
default:
  root: /var/www/html/webroot
  uri: 'https://sahistory-web.ddev.site'
shop:
  root: /var/www/html/webroot
  uri: 'https://shop.ddev.site'
```

## Troubleshooting

### Config Spillover (Shop config in default site)
**Symptoms**: Commerce modules showing in default site config, config import failures

**Solution**:
1. Identify shop-specific modules (Commerce, Wall of Champions, Profile, Content Lock)
2. Remove from `config/sync/core.extension.yml`
3. Remove shop-specific config files from `config/sync/`
4. Import clean config: `ddev drush -l default cim -y`
5. Verify shop config intact: `ls config/shop/*.yml | wc -l`

### Config Out of Sync
**Symptoms**: "Configuration differs from sync directory"

**Solution**:
```bash
# Check which site
ddev drush -l default config:status
ddev drush -l shop.ddev.site config:status

# Import the correct config
ddev drush -l default cim -y
# OR
ddev drush -l shop.ddev.site cim -y
```

### Missing Config Directory
**Symptoms**: "Config sync directory does not exist"

**Solution**:
```bash
# Create shop config directory
mkdir -p config/shop

# Export shop config
ddev drush -l shop.ddev.site cex -y
```

## Deployment

### Default Site Deployment
```bash
# On production server
drush -l default cim -y
drush -l default updb -y
drush -l default cr
```

### Shop Site Deployment
```bash
# On shop production server
drush -l shop.sahistory.org.za cim -y
drush -l shop.sahistory.org.za updb -y
drush -l shop.sahistory.org.za cr
```

## Testing

### Verify Multisite Separation
```bash
# Check default site has NO commerce
ddev drush -l default pml | grep commerce
# Should return nothing

# Check shop site HAS commerce
ddev drush -l shop.ddev.site pml | grep commerce
# Should list all Commerce modules

# Verify config directories are separate
diff config/sync/core.extension.yml config/shop/core.extension.yml
# Should show Commerce modules only in shop
```

### Access Sites Locally
- Default site: https://sahistory-web.ddev.site
- Shop site: https://shop.ddev.site

## Git Strategy

Both config directories are committed to the same repository:
```
git add config/sync/
git add config/shop/
git commit -m "Update default and shop site configs"
```

Deployment scripts should import the correct config for each site.

## Summary

The multisite config is properly separated. Key rules:
1. Always use `-l` flag with drush commands
2. Never manually copy config between sites
3. Keep Commerce modules on shop site only
4. Verify config directory after export
5. Test both sites after config changes
