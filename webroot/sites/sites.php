<?php

$sites[getenv('DOMAIN')] = getenv('WEBROOT_SITE');
$sites['saho.novicell.dev'] = 'default';

if (file_exists(__DIR__ . '/sites.local.php')) {
    include __DIR__ . '/sites.local.php';
};
