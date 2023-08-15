<?php

/**
 * @file
 * Lists available colors and color schemes for the Bartik theme.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'base' => t('Primary Color'),
    'link' => t('Link color'),
    'accent1' => t('Accent color 1'),
    'accent2' => t('Accent color 2'),
    'text' => t('Text color'),
    'headings' => t('Headings color'),
    'well' => t('Well color'),
    'welltext' => t('Well text'),
    'footer' => t('Footer background'),
    'footertext' => t('Footer text'),
    'secheader' => t('Secondary Header bg'),
    'secheadertext' => t('Secondary Header text'),
    'header' => t('Top Header background'),
    'headertext' => t('Top Header text'),
    'headerside' => t('Side Header/Mobile Menu background'),
    'headersidetext' => t('Side Header/Mobile Menu text'),
    'pagetitle' => t('Page Title background'),
    'pagetitletext' => t('Page Title text'),
    'graylight' => t('Gray Light'),
    'graylighter' => t('Gray Lighter'),
    'silver' => t('Silver'),
    'body' => t('Body Background'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => t('Glazed Default'),
      'colors' => [
        'base' => '#0180ef',
        'link' => '#0180ef',
        'accent1' => '#05e993',
        'accent2' => '#0181ee',
        'text' => '#555555',
        'headings' => '#333333',
        'footer' => '#21252a',
        'footertext' => '#747a85',
        'secheader' => '#0181ef',
        'secheadertext' => '#fffffe',
        'well' => '#eeeeee',
        'welltext' => '#444444',
        'header' => '#ffffff',
        'headertext' => '#696969',
        'headerside' => '#20252a',
        'headersidetext' => '#737a85',
        'pagetitle' => '#0181f0',
        'pagetitletext' => '#fffeff',
        'graylight' => '#777777',
        'graylighter' => '#ededed',
        'silver' => '#f1f1f1',
        'body' => '#efefef',
      ],
    ],
  ],

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => [
    'css/glazed.css',
  ],

  // Files to copy.
  'copy' => [
    'logo.svg',
    'glazed-favicon.png',
  ],
];
