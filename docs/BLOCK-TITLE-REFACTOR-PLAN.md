# Block Title Refactoring Plan

## Problem
Custom inline blocks have confusing title configuration:
- Block admin label (from `@Block` annotation)
- Display title checkbox (Drupal default)
- Custom "Block Title" field (our custom implementation)

This creates UX confusion for content editors.

## Solution
**Use Drupal's built-in block title system** instead of custom title fields.

## How Drupal Block Titles Work

1. **Admin Label** (`admin_label` in `@Block` annotation)
   - Shows in block library/selection UI
   - NOT displayed on the site
   - Should be descriptive: "SAHO Upcoming Events", "SAHO Featured Articles"

2. **Block Label** (overridable when placing block)
   - Default comes from `label()` method
   - User can override when placing block in Layout Builder
   - This is what gets displayed on the site

3. **Display Title** (checkbox)
   - Core Drupal feature
   - Shows/hides the block label on the frontend

## Files Affected

### Upcoming Events Block
- `/webroot/modules/custom/saho_upcoming_events/src/Plugin/Block/UpcomingEventsBlock.php`
- `/webroot/modules/custom/saho_upcoming_events/templates/saho-upcoming-events-block.html.twig`

### Featured Articles Block
- `/webroot/modules/custom/saho_featured_articles/src/Plugin/Block/FeaturedArticlesBlock.php`
- `/webroot/modules/custom/saho_featured_articles/templates/saho-featured-articles-block.html.twig`

### Other Custom Blocks
- Any other custom blocks following the same pattern

## Implementation Steps

### Step 1: Update Block Plugin Classes

**Remove from `defaultConfiguration()`:**
```php
// REMOVE THIS:
'block_title' => 'Upcoming Events',
```

**Remove from `blockForm()`:**
```php
// REMOVE THIS ENTIRE SECTION:
$form['block_title'] = [
  '#type' => 'textfield',
  '#title' => $this->t('Block Title'),
  '#default_value' => $config['block_title'],
  '#description' => $this->t('The title to display for this block.'),
];
```

**Remove from `blockSubmit()`:**
```php
// REMOVE THIS:
$this->configuration['block_title'] = $form_state->getValue('block_title');
```

**Update `label()` method** (if it doesn't exist, add it):
```php
/**
 * {@inheritdoc}
 */
public function label() {
  // Return a default label - can be overridden in Layout Builder
  return $this->t('Upcoming Events');
}
```

### Step 2: Update Block Templates

**Before:**
```twig
{% if config.block_title %}
  <div class="upcoming-events-header">
    <h2>{{ config.block_title }}</h2>
  </div>
{% endif %}
```

**After:**
```twig
{# Title is handled by block.html.twig wrapper when "Display title" is checked #}
{# Remove custom title rendering entirely #}
```

**OR if you want to keep custom styling:**
```twig
{% if label %}
  <div class="upcoming-events-header block-section-header">
    <h2 class="block-section-title">{{ label }}</h2>
  </div>
{% endif %}
```

### Step 3: Update Block Wrapper Template

Ensure `/webroot/themes/custom/saho/templates/block/block.html.twig` properly renders the title:

```twig
{% if label %}
  <h2{{ title_attributes.addClass('block-title') }}>
    {{ label }}
  </h2>
{% endif %}
```

### Step 4: Database Update (Optional)

For existing block configurations, you may want to:
1. Keep old config for reference
2. Manually update existing blocks in Layout Builder to set proper titles
3. OR write an update hook to migrate `block_title` to block label

## Benefits

✅ **Simpler UX** - Only 2 title-related settings instead of 3
✅ **Standard Drupal** - Uses core functionality instead of custom code
✅ **Less Code** - Remove ~30 lines of code per block
✅ **Consistent** - Same title behavior as all other Drupal blocks
✅ **Maintainable** - Follows Drupal best practices

## Migration Path for Existing Content

When implementing:

1. **Don't break existing blocks** - Check for `config.block_title` in templates for backward compatibility
2. **Update existing blocks** - Manually update via Layout Builder or write update hook
3. **Document change** - Add to release notes

Example backward-compatible template:
```twig
{% if label %}
  {# New standard way #}
  <h2 class="block-section-title">{{ label }}</h2>
{% elseif config.block_title %}
  {# Legacy support - remove after migration #}
  <h2 class="block-section-title">{{ config.block_title }}</h2>
{% endif %}
```

## Testing Checklist

After refactoring each block:
- [ ] Block appears in Layout Builder
- [ ] Can override title when placing block
- [ ] "Display title" checkbox works
- [ ] Custom styling still applies
- [ ] Existing blocks still show titles
- [ ] New blocks work correctly

## Timeline Estimate

- **Per block**: ~30 minutes (code + testing)
- **All custom blocks**: ~2-3 hours
- **Documentation**: 30 minutes
- **Total**: Half day of work

## Priority

**Medium** - This is a UX issue but not breaking functionality. Can be done:
- As part of next sprint
- When touching block code anyway
- As a dedicated refactoring task

## Notes

- This is a **breaking change** for existing block configurations
- Consider doing all custom blocks at once for consistency
- Test thoroughly in development before deploying
- May need to manually reconfigure existing blocks after deployment
