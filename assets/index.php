<?php

/**
 * @file
 * The PHP page that serves all page requests on a Drupal installation.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 *
 * SAHO override: Drupal 11.4 core ships a Symfony Runtime front controller
 * (`require_once 'autoload_runtime.php'; return static function () {...}`).
 * That variant requires an `autoload_runtime.php` resolvable next to index.php,
 * but this project uses a split docroot (`web-root: webroot/`) where core only
 * scaffolds `autoload.php`, so the bare require fails at runtime and every web
 * request fatals. We pin the classic, server-independent front controller here
 * and map it in via composer.json `extra.drupal-scaffold.file-mapping`.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';

$kernel = new DrupalKernel('prod', $autoloader);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
