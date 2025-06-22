# Drupal Core Patches

This directory contains patches for Drupal core issues.

## layout-builder-inlineblock-logger-interface-drupal11-fixed.patch

### Issue
This patch fixes a type error in the InlineBlock class in the Layout Builder module after updating to Drupal 11:

```
TypeError: Drupal\layout_builder\Plugin\Block\InlineBlock::__construct(): Argument #7 ($logger) must be of type ?Drupal\layout_builder\Plugin\Block\LoggerInterface, Drupal\Core\Logger\LoggerChannel given
```

### Problem
The InlineBlock class in Drupal 11 was updated to accept a LoggerInterface parameter, but the class is missing the proper import statement for the PSR LoggerInterface. This causes a type mismatch when a Drupal\Core\Logger\LoggerChannel object is passed to the constructor.

### Solution
This patch is based on the official patch from [#3049332: Log error + visual warning for missing or broken block](https://www.drupal.org/project/drupal/issues/3049332), adapted to work with Drupal 11. It includes:

1. Adding the missing `use Psr\Log\LoggerInterface;` statement
2. Adding the logger property and its docblock
3. Updating the constructor to accept the logger parameter
4. Adding error handling for missing blocks
5. Updating the return type documentation
6. Changing the blockAccess method to allow admin access if the entity is missing
7. Adding error logging for missing blocks

### Why This Patch Works Better
The previous patch (`layout-builder-inlineblock-logger-interface-drupal11.patch`) had several issues that prevented it from applying correctly:

1. **Incorrect File Path**: The previous patch included "webroot/" in the file path, but Composer applies patches relative to the package root, not the project root.
2. **Typo in Deprecation Message**: Fixed a typo from "amd will be required" to "and will be required".
3. **Duplicate Return Type Documentation**: Consolidated the return type documentation to avoid confusion.
4. **Proper Formatting**: Ensured consistent indentation and complete code blocks.

### How to Apply
The patch is automatically applied by Composer when running:
```
composer update
```

## layout-builder-inlineblock-logger-interface.patch

This is a simpler version of the patch that only adds the missing import statement. The comprehensive version above is recommended instead.

## layout-builder-inlineblock-logger-interface-drupal11.patch

This was an earlier attempt at creating a comprehensive patch, but it had issues with the file path and formatting that prevented it from applying correctly. The fixed version above should be used instead.