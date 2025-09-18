# Deployment Guide

Complete guide for deploying SA History Web to production environments, including staging and production deployment procedures.

## Deployment Overview

### Environments
- **Development**: Local DDEV environment
- **Staging**: Pre-production testing environment  
- **Production**: Live public website

### Deployment Strategy
We use a **configuration-driven deployment** approach:
- Code and configuration stored in Git
- Database content managed separately
- Files (media) synchronized between environments

## Pre-Deployment Checklist

### Code Quality
- [ ] **All tests pass** in DDEV environment
- [ ] **Code review completed** and approved
- [ ] **No PHP errors** in error logs
- [ ] **Performance tested** - page load times acceptable
- [ ] **Mobile/responsive testing** completed
- [ ] **Accessibility testing** performed

### Configuration Management
- [ ] **Configuration exported**: `ddev drush cex -y`
- [ ] **Database updates tested**: `ddev drush updb -y`
- [ ] **New modules enabled/tested**: `ddev drush pml`
- [ ] **Custom module functionality verified**
- [ ] **Template changes tested**

### Content Verification
- [ ] **Featured content displays properly**
- [ ] **Category navigation works**
- [ ] **All links functional**
- [ ] **Images loading correctly**
- [ ] **No broken content**

## Staging Deployment

### 1. Prepare Staging Environment
```bash
# SSH to staging server
ssh user@staging.sahistory.org

# Navigate to project directory
cd /var/www/sahistory-staging

# Create backup before deployment
drush sql:dump --result-file=backups/pre-deploy-$(date +%Y%m%d-%H%M).sql

# Backup files directory
tar -czf backups/files-$(date +%Y%m%d-%H%M).tar.gz web/sites/default/files/
```

### 2. Deploy Code Changes
```bash
# Pull latest changes from main branch
git fetch origin
git checkout main
git pull origin main

# Update PHP dependencies
composer install --no-dev --optimize-autoloader

# Clear Drupal cache
drush cache:rebuild
```

### 3. Apply Database Updates
```bash
# Run database updates
drush updatedb -y

# Import new configuration
drush config:import -y

# Clear cache after configuration import
drush cache:rebuild

# Rebuild node access permissions (if needed)
drush node:access:rebuild
```

### 4. Verify Staging Deployment
- [ ] **Site loads without errors**
- [ ] **Featured page functional**: https://staging.sahistory.org/featured
- [ ] **Admin interface accessible**
- [ ] **New features working**
- [ ] **No PHP errors in logs**

## Production Deployment

### 1. Production Pre-Flight Checks
```bash
# Verify staging is working correctly
curl -I https://staging.sahistory.org/featured
curl -I https://staging.sahistory.org/

# Check for critical errors in staging
drush watchdog:show --severity=Error --count=10

# Verify database connection
drush status
```

### 2. Maintenance Mode
```bash
# Enable maintenance mode
drush state:set system.maintenance_mode 1
drush cache:rebuild

# Display maintenance message to users
echo "Site is temporarily down for maintenance. Please check back shortly."
```

### 3. Create Production Backup
```bash
# Database backup
drush sql:dump --gzip --result-file=backups/production-$(date +%Y%m%d-%H%M).sql

# Files backup
tar -czf backups/files-production-$(date +%Y%m%d-%H%M).tar.gz web/sites/default/files/

# Code backup (optional - already in Git)
tar -czf backups/code-$(date +%Y%m%d-%H%M).tar.gz --exclude='web/sites/default/files' .
```

### 4. Deploy to Production
```bash
# Pull latest code
git fetch origin
git checkout main
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run database updates
drush updatedb -y

# Import configuration
drush config:import -y

# Clear all caches
drush cache:rebuild

# Update search index (if applicable)
drush search-api:index
```

### 5. Post-Deployment Verification
```bash
# Check site status
drush status

# Verify featured articles functionality
curl -I https://sahistory.org/featured

# Check for recent errors
drush watchdog:show --severity=Error --count=5

# Disable maintenance mode
drush state:set system.maintenance_mode 0
drush cache:rebuild
```

### 6. Post-Deployment Testing
- [ ] **Homepage loads correctly**
- [ ] **Featured page functional**
- [ ] **Category navigation working**
- [ ] **Search functionality active**
- [ ] **Admin login working**
- [ ] **No PHP errors in logs**
- [ ] **Mobile site responsive**

## Configuration Management

### Exporting Configuration
```bash
# Export all configuration
ddev drush config:export

# Export specific configuration
ddev drush config:export --diff

# Configuration files stored in: /config/sync/
```

### Configuration Files Structure
```
config/
├── sync/
│   ├── core.entity_form_display.node.article.default.yml
│   ├── core.entity_view_display.node.article.default.yml
│   ├── field.field.node.article.field_staff_picks.yml
│   ├── field.field.node.article.field_home_page_feature.yml
│   ├── saho_featured_articles.settings.yml
│   └── system.site.yml
└── README.md
```

### Importing Configuration
```bash
# Import all configuration
drush config:import

# Import specific configuration items
drush config:import --partial

# Review configuration differences
drush config:status
```

## Database Management

### Database Updates
```bash
# Check for pending updates
drush updatedb:status

# Run all pending updates
drush updatedb -y

# Run specific update hooks
drush updatedb --entity-updates
```

### Data Migration
If migrating content between environments:

```bash
# Export content (development)
ddev drush sql:dump --result-file=/tmp/sahistory-content.sql

# Import content (staging/production)
drush sql:cli < /tmp/sahistory-content.sql
drush updatedb -y
drush cache:rebuild
```

## File Synchronization

### Media Files Sync
```bash
# Sync files from production to staging
rsync -avz --delete production:/var/www/sahistory/web/sites/default/files/ \
                    staging:/var/www/sahistory-staging/web/sites/default/files/

# Set proper permissions
chown -R www-data:www-data web/sites/default/files/
chmod -R 755 web/sites/default/files/
```

## Performance Optimization

### Production Cache Settings
```bash
# Enable CSS/JS aggregation
drush config:set system.performance css.preprocess 1
drush config:set system.performance js.preprocess 1

# Enable page cache for anonymous users
drush config:set system.performance cache.page.max_age 3600

# Set cache lifetime
drush config:set system.performance cache.page.max_age 21600  # 6 hours
```

### Asset Optimization
```bash
# Clear and rebuild CSS/JS aggregates
drush advagg:clear-all-files  # If Advanced Aggregation module used

# Image style regeneration
drush image:flush --all
```

## Monitoring and Logging

### Error Monitoring
```bash
# Check recent errors
drush watchdog:show --severity=Error --count=20

# Monitor PHP errors
tail -f /var/log/php/error.log

# Check Drupal database logs
drush watchdog:show --count=50
```

### Performance Monitoring
```bash
# Check database query performance
mysql -e "SHOW FULL PROCESSLIST;"

# Monitor disk space
df -h

# Check memory usage
free -m
```

### Log Rotation
```bash
# Configure logrotate for Drupal logs
# /etc/logrotate.d/drupal
/var/log/drupal/*.log {
  daily
  missingok
  rotate 7
  compress
  notifempty
  create 644 www-data www-data
}
```

## Rollback Procedures

### Emergency Rollback
If deployment fails or causes critical issues:

```bash
# 1. Enable maintenance mode
drush state:set system.maintenance_mode 1

# 2. Restore database backup
mysql sahistory_prod < backups/production-YYYYMMDD-HHMM.sql

# 3. Restore code (if needed)
git checkout [previous-commit-hash]

# 4. Clear cache
drush cache:rebuild

# 5. Disable maintenance mode
drush state:set system.maintenance_mode 0

# 6. Verify site functionality
curl -I https://sahistory.org/
```

### Partial Rollback
For configuration-only rollbacks:

```bash
# Revert specific configuration
drush config:import --partial --source=/path/to/previous/config

# Or revert individual config items
drush config:set system.site name "South African History Online"
```

## Security Considerations

### Pre-Deployment Security
- [ ] **Dependencies updated**: No known security vulnerabilities
- [ ] **File permissions correct**: 644 for files, 755 for directories
- [ ] **Database access restricted**: Production credentials secured
- [ ] **HTTPS enforced**: All traffic over SSL/TLS
- [ ] **Admin access limited**: Strong passwords, 2FA enabled

### Post-Deployment Security
```bash
# Check file permissions
find web/sites/default/files -type f -not -perm 644 -ls
find web/sites/default/files -type d -not -perm 755 -ls

# Verify security headers
curl -I https://sahistory.org/ | grep -E "(X-Frame-Options|X-Content-Type-Options|Strict-Transport-Security)"
```

## Troubleshooting Deployment Issues

### Common Deployment Problems

#### Configuration Import Fails
```bash
# Check configuration status
drush config:status

# Import with error details
drush config:import --diff

# Skip problematic configuration
drush config:import --partial
```

#### Database Update Failures
```bash
# Check update status
drush updatedb:status

# Run updates with verbose output
drush updatedb -v

# Skip failing updates (emergency only)
drush updatedb --post-updates
```

#### Permission Issues
```bash
# Fix file permissions
sudo chown -R www-data:www-data web/sites/default/files/
sudo chmod -R 755 web/sites/default/files/

# Fix cache directory permissions
sudo chown -R www-data:www-data /tmp/drupal_cache/
```

#### Module Enabling Issues
```bash
# Check module dependencies
drush pm:list --status=disabled

# Enable modules with dependencies
drush en module_name -y

# Clear cache after module changes
drush cr
```

## Automated Deployment

### CI/CD Pipeline (Future Enhancement)
```yaml
# Example GitHub Actions workflow
name: Deploy to Production
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Deploy to server
        run: |
          ssh user@production "cd /var/www/sahistory && git pull origin main"
          ssh user@production "cd /var/www/sahistory && composer install --no-dev"
          ssh user@production "cd /var/www/sahistory && drush updatedb -y"
          ssh user@production "cd /var/www/sahistory && drush config:import -y"
          ssh user@production "cd /var/www/sahistory && drush cr"
```

## Post-Deployment Checklist

### Immediate Verification (within 5 minutes)
- [ ] **Site loads without errors**
- [ ] **Homepage displays correctly** 
- [ ] **Featured page functional**: `/featured`
- [ ] **Admin login working**
- [ ] **No critical errors in logs**

### Extended Verification (within 1 hour)
- [ ] **Search functionality working**
- [ ] **All content categories accessible**
- [ ] **Mobile site responsive**
- [ ] **Images loading properly**
- [ ] **Forms submitting correctly**
- [ ] **Email notifications working** (if applicable)

### Follow-up Tasks (within 24 hours)
- [ ] **Monitor error logs**
- [ ] **Check site performance metrics**
- [ ] **Verify backup systems working**
- [ ] **Test full user workflows**
- [ ] **Update team on deployment status**

---

**Emergency Contacts**:
- **Technical Lead**: [Contact Information]
- **System Administrator**: [Contact Information]
- **Hosting Provider Support**: [Contact Information]

**Related Documentation**:
- [Development Workflow](Development-Workflow.md) - Development processes
- [Architecture](Architecture.md) - System architecture details
- [Performance](Performance.md) - Performance optimization guide