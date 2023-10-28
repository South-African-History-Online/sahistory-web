<?php

if (\Drupal::moduleHandler()->moduleExists('cms_portfolio')) {
  $form['glazed_settings']['portfolio'] = [
    '#title' => t('Portfolio'),
    '#type' => 'details',
    '#weight' => 18,
  ];

  $form['glazed_settings']['portfolio']['portfolio_details'] = [
    '#type' => 'radios',
    '#title' => t('Details Title'),
    '#default_value' => (theme_get_setting('portfolio_details')) ? theme_get_setting('portfolio_details') : 'top',
    '#options' => [
      'top' => t('Top'),
      'bottom' => t('Bottom'),
      'right' => t('Right'),
      'left' => t('Left'),
      'hide' => t('Hide'),
    ],
    '#description' => t('Choose position project details.'),
  ];

  $form['glazed_settings']['portfolio']['portfolio_sidebar'] = [
      '#type' => 'radios',
      '#title' => t('Portfolio Sidebar Width'),
      '#default_value' => (theme_get_setting('portfolio_sidebar')) ? theme_get_setting('portfolio_sidebar') : '4',
      '#options' => [
        '2' => t('1/6'),
        '3' => t('1/4'),
        '4' => t('1/3'),
        '5' => t('5/12'),
        '6' => t('1/2'),
      ],
      '#description' => t('Choose position project details.'),
  ];

  $form['glazed_settings']['portfolio']['portfolio_images'] = [
    '#type' => 'radios',
    '#title' => t('Image Display'),
    '#default_value' => (theme_get_setting('portfolio_images')) ? theme_get_setting('portfolio_images') : 'sidecaption',
    '#options' => [
      'sidecaption' => t('Side Captions'),
      'stack' => t('Stack'),
      'grid' => t('Lightbox Gallery'),
      'hide' => t('Hide'),
    ],
    '#description' => t('Choose mode for display of portfolio images.'),
  ];
}