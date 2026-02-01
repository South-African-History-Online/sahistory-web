# Shop Config Spillover - Cleanup Plan

## Problem Summary

Commerce modules and configuration from the shop multisite (`shop.sahistory.org.za`) have spilled into the default site's configuration, affecting all environments.

## Impact

- **77 config files** with commerce/shop references in `config/sync` (default site config)
- **8 Commerce modules** incorrectly enabled on default site
- **No Config Split** setup for multisite management
- **All environments affected** (local, staging, production)

## Root Cause

Commerce modules were enabled on the default site and their configuration was exported to `config/sync`. This shared config is now deployed to all environments, causing the shop functionality to bleed into the main site.

## Solution Strategy

### Option 1: Complete Cleanup (Recommended)
**Uninstall Commerce from default site, set up proper multisite config management**

**Pros**:
- Clean separation of concerns
- Prevents future spillover
- Reduces default site complexity
- Proper multisite architecture

**Cons**:
- Requires careful execution
- Need to ensure shop site stays functional

### Option 2: Config Split (Alternative)
**Keep Commerce installed but use Config Split to manage differences**

**Pros**:
- Less disruptive
- Can be done incrementally

**Cons**:
- Commerce still enabled on default (unnecessary overhead)
- More complex config management
- Doesn't address root cause

## Recommended Approach: Option 1

## Execution Plan

### Phase 1: Audit & Backup (CURRENT)

**Status**: In Progress

1. [x] Identify all commerce config files (77 files found)
2. [x] List all commerce modules (8 modules)
3. [ ] Export current config as backup
4. [ ] Document shop site requirements
5. [ ] Verify shop site uses default config or separate config

### Phase 2: Uninstall Commerce from Default Site

1. Switch to default site context
2. Uninstall Commerce modules in correct order:
   ```bash
   ddev drush pm:uninstall commerce_recurring -y
   ddev drush pm:uninstall commerce_product -y
   ddev drush pm:uninstall commerce_order -y
   ddev drush pm:uninstall commerce_payment -y
   ddev drush pm:uninstall commerce_price -y
   ddev drush pm:uninstall commerce_store -y
   ddev drush pm:uninstall commerce_number_pattern -y
   ddev drush pm:uninstall commerce -y
   ```
3. Export config: `ddev drush cex -y`

### Phase 3: Remove Commerce Config Files

Delete all 77 commerce-related config files from `config/sync`:
```bash
# Use the list in /tmp/commerce_config_files.txt
while read file; do
  git rm "$file"
done < /tmp/commerce_config_files.txt
```

### Phase 4: Set Up Shop Site Config

Choose one of:

**A. Separate Config Directory** (Recommended for true multisite)
```bash
mkdir -p webroot/sites/shop.sahistory.org.za/config/sync
# Configure shop settings.php to use its own config directory
```

**B. Config Split** (If shop and default share most config)
```bash
ddev drush en config_split -y
# Create split for shop-specific modules
```

### Phase 5: Re-enable Commerce on Shop Site

1. Switch to shop site context: `ddev drush -l shop.ddev.site`
2. Enable Commerce modules on shop only
3. Configure commerce for shop
4. Export shop config to its own directory

### Phase 6: Testing

1. Verify default site has no commerce references
2. Verify shop site functions correctly
3. Test config import/export on both sites
4. Deploy to staging and test

### Phase 7: Deploy to All Environments

1. Deploy config cleanup to staging
2. Run update hooks and config import
3. Verify both sites work correctly
4. Deploy to production
5. Monitor for issues

## Commerce Config Files to Remove (77 total)

Full list saved in `/tmp/commerce_config_files.txt`

Key categories:
- Commerce core config (11 files)
- Entity form/view displays (30+ files)
- Views (10 files)
- Fields (15+ files)
- Workflows and actions (10 files)

## Modules to Uninstall from Default Site

1. commerce_recurring
2. commerce_product
3. commerce_order
4. commerce_payment
5. commerce_price
6. commerce_store
7. commerce_number_pattern
8. commerce (core)

## Risk Mitigation

1. **Backup first**: Export current config before any changes
2. **Test on local**: Complete process locally before deploying
3. **Staging verification**: Test on staging environment
4. **Rollback plan**: Keep backups of config before cleanup
5. **Shop site verification**: Ensure shop continues to function

## Timeline

- **Phase 1 (Audit)**: 30 minutes - IN PROGRESS
- **Phase 2-3 (Cleanup)**: 1 hour
- **Phase 4 (Config Setup)**: 1 hour
- **Phase 5 (Re-enable)**: 30 minutes
- **Phase 6 (Testing)**: 1 hour
- **Total**: ~4 hours

## Success Criteria

- [ ] Default site has 0 commerce references in config
- [ ] Default site has 0 commerce modules enabled
- [ ] Shop site fully functional with commerce
- [ ] Config exports clean on default site
- [ ] No errors on config import
- [ ] All environments consistent

## Current Status

**Task 7**: ✓ Audit complete - 77 files identified
**Task 8**: Pending - Uninstall modules
**Task 9**: Pending - Remove config files
**Task 10**: Pending - Set up proper multisite config

---

**Created**: 2026-02-01
**Branch**: SAHO-fix-shop-config-spillover
**Priority**: HIGH - Affects all environments
