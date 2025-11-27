<?php

/**
 * @file
 * Multi-site configuration for SAHO project.
 *
 * This file maps domain names to site directories.
 */

// Shop multisite configuration
$sites['shop.sahistory.org.za'] = 'shop.sahistory.org.za';
$sites['shop.ddev.site'] = 'shop.sahistory.org.za';
$sites['shop.ddev.site'] = 'shop-staging.sahistory.org.za';

// Include local overrides if available
if (file_exists(__DIR__ . '/sites.local.php')) {
    include __DIR__ . '/sites.local.php';
};
