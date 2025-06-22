# Layout Builder Fix

## Overview

This module fixes a type error that occurs in the Layout Builder module after updating to Drupal 11. The error is:

```
TypeError: Drupal\layout_builder\Plugin\Block\InlineBlock::__construct(): Argument #7 ($logger) must be of type ?Drupal\layout_builder\Plugin\Block\LoggerInterface, Drupal\Core\Logger\LoggerChannel given
```

## Problem

The issue occurs because the InlineBlock class in the Layout Builder module is using `LoggerInterface` without importing it from the PSR namespace. This causes a type mismatch when a `Drupal\Core\Logger\LoggerChannel` object is passed to the constructor.

## Solution

This module provides a `LoggerInterface` in the `Drupal\layout_builder\Plugin\Block` namespace that extends the PSR `LoggerInterface`. This satisfies the type requirement without modifying core files.

## Installation

1. Place this module in your Drupal installation's `modules/custom` directory.
2. Enable the module using Drush:
   ```
   drush en layout_builder_fix
   ```
   Or through the Drupal admin interface at `/admin/modules`.

## How it Works

The module:

1. Creates a `LoggerInterface` in the `Drupal\layout_builder\Plugin\Block` namespace that extends `Psr\Log\LoggerInterface`.
2. Uses a service provider to ensure the module is loaded before the Layout Builder module.
3. This allows the type requirement to be satisfied without modifying core files.

## Compatibility

This module is designed for Drupal 11 and should be used after updating from Drupal 10.x to Drupal 11.

## Uninstallation

Once an official fix is available in Drupal core, you can uninstall this module:

1. Disable the module using Drush:
   ```
   drush pmu layout_builder_fix
   ```
   Or through the Drupal admin interface at `/admin/modules`.
2. Remove the module files from your Drupal installation.