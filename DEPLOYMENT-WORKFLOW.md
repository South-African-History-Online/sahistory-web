# SAHO Deployment Workflow

**Updated:** 2026-02-13
**Workflow:** `.github/workflows/deploy.yml`

## Overview

The SAHO project uses automated GitHub Actions deployments with two environments:
- **Staging** - Auto-deploys on every PR merge to main
- **Production** - Deploys only on version tag creation

## Deployment Triggers

### 1. Staging (Automatic)

**Trigger:** Push to `main` branch (typically from PR merge)

```bash
# Happens automatically when PR is merged
# No manual action needed
```

**What happens:**
1. GitHub Actions detects push to main
2. Connects to staging server via SSH
3. Runs `git reset --hard origin/main` (clean sync)
4. Executes `scripts/deploy-all.sh staging`
5. Deploys both default and shop sites

**Timeline:** ~2-5 minutes after PR merge

### 2. Production (Version Tags Only)

**Trigger:** Creating and pushing a version tag

```bash
# Create a new version tag
git tag v1.6.2
git push origin v1.6.2

# Or create annotated tag with message
git tag -a v1.6.2 -m "Release v1.6.2: Schema.org improvements and content cleanup"
git push origin v1.6.2
```

**What happens:**
1. GitHub Actions detects tag push
2. Connects to production server via SSH
3. Runs `git checkout v1.6.2` (checks out exact tag)
4. Executes `scripts/deploy-all.sh production`
5. Deploys both default and shop sites

**Timeline:** ~3-7 minutes after tag push

### 3. Manual Deployment (Optional)

**Trigger:** Via GitHub Actions UI

**Steps:**
1. Go to: https://github.com/South-African-History-Online/sahistory-web/actions
2. Select "Deploy" workflow
3. Click "Run workflow"
4. Choose environment: staging or production
5. Click "Run workflow"

**Use cases:**
- Emergency hotfix deployment
- Rollback to previous state
- Testing deployment process

## Deployment Scripts

All deployment jobs execute `scripts/deploy-all.sh <environment>`, which:

1. **deploy-default.sh** - Deploys main site
   - `composer install --no-dev`
   - `drush config:import -y`
   - `drush cache:rebuild`
   - `drush updatedb -y`

2. **deploy-shop.sh** - Deploys shop multisite
   - Same steps as default but for shop site

## Version Numbering

Follow semantic versioning: `vMAJOR.MINOR.PATCH`

- **PATCH** (v1.6.2 → v1.6.3): Bug fixes, minor tweaks
- **MINOR** (v1.6.0 → v1.7.0): New features, non-breaking changes
- **MAJOR** (v1.0.0 → v2.0.0): Breaking changes, major rewrites

**Examples:**
- `v1.6.2` - Hotfix for Schema.org bug
- `v1.7.0` - New content type, improved SEO
- `v2.0.0` - Drupal 12 upgrade, redesign

## Deployment Workflow Examples

### Example 1: Normal Feature Deployment

```bash
# 1. Create feature branch
git checkout -b SAHO-300--feature-name

# 2. Develop and commit changes
git add .
git commit -m "SAHO-300: Implement feature"

# 3. Push and create PR
git push origin SAHO-300--feature-name

# 4. After PR review and merge to main:
#    → Staging auto-deploys (GitHub Actions)

# 5. Test on staging
#    https://staging.sahistory.org.za

# 6. If staging tests pass, create version tag
git checkout main
git pull origin main
git tag v1.7.0
git push origin v1.7.0

# 7. Production deploys automatically (GitHub Actions)
```

### Example 2: Hotfix Deployment

```bash
# 1. Create hotfix branch from main
git checkout main
git pull origin main
git checkout -b hotfix/critical-bug

# 2. Fix the bug
git add .
git commit -m "Hotfix: Fix critical XSS vulnerability"

# 3. Push and create PR
git push origin hotfix/critical-bug

# 4. After emergency PR review and merge:
#    → Staging auto-deploys

# 5. Quickly verify on staging

# 6. Create patch version tag
git checkout main
git pull origin main
git tag v1.6.3
git push origin v1.6.3

# 7. Production deploys (verified in ~5 minutes)
```

### Example 3: Content Type Deletion (Like This PR)

```bash
# 1. PR #287 merged to main
#    → Staging auto-deploys

# 2. SSH to staging and run deletion script
ssh staging.sahistory.org.za
cd /var/www/sahistory-staging
drush php:script scripts/delete-deprecated-content-types.php
# Confirm backup: yes
# Confirm deletion: DELETE

# 3. Verify staging
#    - Check node counts
#    - Test content types are removed
#    - Verify no broken links

# 4. If all looks good, create version tag
git tag v1.6.2
git push origin v1.6.2

# 5. Production deploys automatically

# 6. SSH to production and run deletion script
ssh production.sahistory.org.za
cd /var/www/sahistory
drush php:script scripts/delete-deprecated-content-types.php
# Confirm backup: yes
# Confirm deletion: DELETE
```

## Environment Variables (GitHub Secrets)

Required secrets in GitHub repository settings:

- `DEPLOY_SSH_KEY` - Private SSH key for server access
- `DEPLOY_HOST` - Server hostname
- `DEPLOY_USER` - SSH username
- `DEPLOY_PATH_STAGING` - Staging directory path
- `DEPLOY_PATH_PROD` - Production directory path

## Monitoring Deployments

### GitHub Actions UI

1. Go to: https://github.com/South-African-History-Online/sahistory-web/actions
2. View deployment status (in progress, success, failure)
3. Click on workflow run to see detailed logs

### Notifications

- **Success:** Green checkmark, "Deployment completed successfully" notice
- **Failure:** Red X, "Deployment failed" error message

### SSH to Server

```bash
# Check deployment logs on server
ssh staging.sahistory.org.za
cd /var/www/sahistory-staging
tail -f deploy.log

# Check Drupal logs
drush watchdog:show --severity=error --count=20
```

## Rollback Procedure

If deployment fails or introduces bugs:

### Option 1: Revert Git Commit

```bash
# 1. Revert the problematic commit
git revert <commit-sha>
git push origin main

# 2. Staging auto-deploys the revert
# 3. Create new version tag if production needs rollback
git tag v1.6.4
git push origin v1.6.4
```

### Option 2: Manual Rollback via Tag

```bash
# 1. Find previous working version
git tag --sort=-v:refname | head -5

# 2. SSH to server
ssh production.sahistory.org.za
cd /var/www/sahistory

# 3. Checkout previous tag
git fetch origin --tags
git checkout v1.6.1

# 4. Run deployment script
scripts/deploy-all.sh production
```

### Option 3: Database Rollback

```bash
# If content deletion needs to be undone
drush sql:drop -y
drush sqlc < backup-before-deletion-TIMESTAMP.sql
drush cr
```

## Pre-Deployment Checklist

Before creating a version tag for production:

- [ ] PR merged to main
- [ ] Staging auto-deployed successfully
- [ ] Tested on staging environment
- [ ] No errors in staging logs
- [ ] Configuration imported successfully
- [ ] Database updates ran without errors
- [ ] All CI checks passed (PHPCS, PHPUnit, Biome)
- [ ] Breaking changes documented
- [ ] Version number follows semantic versioning

## Post-Deployment Verification

After production deployment:

1. **Smoke Test:**
   ```bash
   # Check homepage loads
   curl -I https://sahistory.org.za

   # Check for PHP errors
   drush watchdog:show --severity=error --count=10
   ```

2. **Content Verification:**
   - Visit homepage
   - Check key content types
   - Test search functionality
   - Verify navigation menus

3. **Performance Check:**
   - Page load times acceptable
   - No new slow queries
   - Cache hit rate normal

4. **Schema.org Validation:**
   - View page source, check for JSON-LD
   - Use Google Rich Results Test
   - Verify structured data

## Troubleshooting

### Deployment Hangs

```bash
# SSH to server and check process
ssh production.sahistory.org.za
ps aux | grep deploy

# Kill stuck process if needed
kill <process-id>

# Re-run deployment manually
cd /var/www/sahistory
scripts/deploy-all.sh production
```

### Config Import Fails

```bash
# Check config status
drush config:status

# View specific error
drush config:import -y --verbose

# Manual intervention may be needed
drush config:edit <config-name>
```

### Database Update Errors

```bash
# Check pending updates
drush updatedb:status

# Run specific update
drush updatedb --entity-updates

# Rollback if needed
drush sql:drop -y
drush sqlc < backup.sql
```

## Security Notes

- **Never commit secrets** to repository
- **SSH keys** are stored in GitHub Secrets
- **Database backups** created before destructive operations
- **Deletion scripts** require double confirmation
- **Version tags** are immutable (don't force push tags)

## Support

**GitHub Issues:** https://github.com/South-African-History-Online/sahistory-web/issues
**Workflow File:** `.github/workflows/deploy.yml`
**Deployment Scripts:** `scripts/deploy-*.sh`

---

**Last Updated:** 2026-02-13
**Current Version:** v1.6.1 (as of this documentation)
**Next Planned Release:** v1.6.2 (Schema.org improvements + content cleanup)
