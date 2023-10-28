<?php

/**
 * @file
 * Version agnostic parts of feeds_tamper.module.
 */

/**
 * Return a machine name safe version of a string.
 *
 * @param string $string
 *   String to get machine nameized.
 *
 * @return string
 *   A lowercase string with all values not in [a-zA-Z0-9] replaced with an
 *   underscore and shortened to 128 characters.
 */
function feeds_tamper_make_machine($string) {
  return drupal_substr(preg_replace('/[^a-z0-9-]/u', '_', drupal_strtolower($string)), 0, 127);
}

/**
 * @} End of "feeds_tamper_api".
 */

/**
 * Menu access callback.
 *
 * @param string|FeedsImporter $importer
 *   The importer or importer id being tampered with.
 * @param string|stdClass $instance
 *   (optional) If set, the importer attached to $instance will be tried first.
 *   Defaults to NULL.
 *
 * @return bool
 *   TRUE if the user has acces, FALSE if not.
 */
function feeds_tamper_access($importer, $instance = NULL) {
  if (isset($instance)) {
    if (is_object($instance)) {
      $importer = $instance->importer;
    }
    else {
      $importer = feeds_tamper_load_instance($instance)->importer;
    }
  }
  elseif (is_object($importer)) {
    $importer = $importer->id;
  }

  // Verify actual input if above failed.
  if ($importer) {
    // Check for permissions, otherwise return FALSE.
    if (user_access('administer feeds_tamper') || user_access('tamper ' . $importer)) {
      return TRUE;
    }
  }
  return FALSE;
}
