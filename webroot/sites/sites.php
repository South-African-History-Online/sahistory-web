<?php

$sites[getenv('DOMAIN')] = getenv('WEBROOT_SITE');

if (file_exists(__DIR__ . '/sites.local.php')) {
    include __DIR__ . '/sites.local.php';
};
