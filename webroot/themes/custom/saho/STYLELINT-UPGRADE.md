# Stylelint Upgrade from v14 to v15.11.0

This document outlines the changes made to upgrade stylelint from v14.1.0 to v15.11.0 in the SAHO Radix subtheme.

## Changes Made

### Package Updates

The following packages were updated in `package.json`:

```json
"stylelint": "^15.11.0",
"stylelint-config-prettier": "^9.0.5",
"stylelint-config-recess-order": "^4.0.0",
"stylelint-config-standard-scss": "^11.0.0",
"stylelint-stylistic": "^0.4.3",
"stylelint-webpack-plugin": "^4.1.1"
```

> Note: Initially, we attempted to upgrade to stylelint v16.20.0, but encountered a peer dependency conflict with stylelint-config-standard-scss v11.0.0, which requires stylelint v15.x.

### Configuration Updates

1. **Removed Deprecated Rules**:
   - `function-url-quotes`
   - `function-whitespace-after`
   - `media-feature-range-operator-space-after`
   - `media-feature-range-operator-space-before`

2. **Added Stylistic Plugin**:
   - Added `stylelint-stylistic` plugin to handle deprecated formatting rules
   - Updated all deprecated formatting rules to use the `stylistic/` prefix

3. **Disabled Problematic Rules**:
   ```json
   "scss/no-global-function-names": null,
   "scss/comment-no-empty": null,
   "selector-id-pattern": null
   ```

## Addressing Deprecation Warnings

Many rules in stylelint v15 have been deprecated and moved to the `stylelint-stylistic` plugin. We've updated all deprecated rules to use this plugin, which should eliminate the deprecation warnings. For example:

```json
// Before
"at-rule-name-case": "lower",

// After
"stylistic/at-rule-name-case": "lower",
```

## Potential Issues

1. **Color Function Notation**: The configuration still uses `"color-function-notation": "legacy"` which is deprecated but still supported in v15. In a future update, this should be changed to `"modern"`.

2. **Global Function Names**: The `scss/no-global-function-names` rule has been disabled to allow continued use of functions like `darken()` and `lighten()`. In the future, these should be updated to use `color.adjust()`.

3. **Empty Comments**: The `scss/comment-no-empty` rule has been disabled to allow empty comments in the codebase.

4. **ID Selector Pattern**: The `selector-id-pattern` rule has been disabled to allow non-kebab-case ID selectors (e.g., "#toolsDropdown").

## Testing

After upgrading, run the following commands to test the new configuration:

```bash
npm install
npm run stylint
```

If you encounter any issues, you can fix them automatically with:

```bash
npm run stylint-fix
```

## Future Improvements

For a future update, consider:

1. Upgrading to stylelint v16 when compatible versions of all dependencies are available
2. Updating color functions to use `color.adjust()` instead of `darken()` and `lighten()`
3. Cleaning up empty comments in SCSS files
4. Converting ID selectors to kebab-case

## References

- [Stylelint v15 Release Notes](https://github.com/stylelint/stylelint/releases)
- [Stylelint Migration Guide](https://stylelint.io/migration-guide/)
- [Stylelint Stylistic Plugin](https://github.com/stylelint-stylistic/stylelint-stylistic)