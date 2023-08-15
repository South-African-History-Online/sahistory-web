<?php

/**
 * @file
 * Token hooks for the uc_file module.
 */

use Drupal\Core\Link;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function uc_file_token_info() {
  $type = array(
    'name' => t('File downloads'),
    'description' => t('Tokens for purchased file downloads.'),
    'needs-data' => 'uc_file',
  );

  $tokens['downloads'] = array(
    'name' => t('Downloads'),
    'description' => t('The list of file download links (if any) associated with an order'),
  );

  return array(
    'types' => array('uc_file' => $type),
    'tokens' => array('uc_file' => $tokens),
  );
}

/**
 * Implements hook_tokens().
 */
function uc_file_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  if ($type == 'uc_file' && !empty($data['uc_file'])) {
    $files = $data['uc_file'];

    if (isset($tokens['downloads'])) {
      $replacements[$tokens['downloads']] = theme('uc_file_downloads_token', array('file_downloads' => $files));
    }
  }

  return $replacements;
}

/**
 * Themes the file download links token.
 *
 * @ingroup themeable
 */
function theme_uc_file_downloads_token(array $variables) {
  $file_downloads = $variables['file_downloads'];

  $output = '';

  foreach ($file_downloads as $file_download) {
    // Let's only notify of them of the files, not the directories.
    if (is_dir($file_download->filename)) {
      continue;
    }

    $output .= Link::createFromRoute($file_download->filename, 'uc_file.download_file', ['file' => $file_download->fid], ['absolute' => TRUE])->toString() . "\n";
  }

  return $output;
}
