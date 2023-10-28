<?php

$form['glazed_settings']['block'] = [
  '#title' => t('Block Design'),
  '#type' => 'details',
  '#group' => 'glazed_settings',
  '#weight' => 25,
];

$form['glazed_settings']['block']['block_preset'] = [
  '#type' => 'select',
  '#title' => t('Block Design Preset'),
  '#default_value' => ((theme_get_setting('block_preset') !== null)) ? theme_get_setting('block_preset') : 'none',
  '#options' => [
    '' => t('None (Theme Defaults)'),
    'block_boxed' => t('Block Boxed'),
    'block_outline' => t('Block Outline'),
    'block_well' => t('Block Well Style'),
    'title_inverted' => t('Title Inverted'),
    'title_inverted_shape' => t('Title Inverted Pill'),
    'title_sticker' => t('Title Sticker'),
    'title_sticker_color' => t('Title Sticker Colored'),
    'title_outline' => t('Title Outline'),
    'default_divider' => t('Divider'),
    'hairline_divider' => t('Hairline Divider'),
    'custom' => t('Custom'),
  ],
  '#prefix' => '<div class="block-design-wrapper clearfix"><div class="block-design-settings">',
];

$form['glazed_settings']['block']['block_advanced'] = [
  '#title' => t('Customize Design'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
  '#prefix' => '<br>',
];

$form['glazed_settings']['block']['block_advanced']['block_el'] = [
  '#title' => t('Block Element'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];


$well_options = [
  '' => t('None'),
  'well' => t('Well'),
  'well bg-primary' => t('Well Glazed Primary Color'),
  'well glazed-util-background-accent1' => t('Well Accent 1 Color'),
  'well glazed-util-background-accent2' => t('Well Accent 2 Color'),
  'well glazed-util-background-black' => t('Well Black'),
  'well glazed-util-background-white' => t('Well White'),
  'well glazed-util-background-gray' => t('Well Gray'),
];

$form['glazed_settings']['block']['block_advanced']['block_el']['block_well'] = [
  '#type' => 'select',
  '#title' => t('Block Well Style'),
  '#default_value' =>  theme_get_setting('block_well'),
  '#options' => $well_options,
];

$form['glazed_settings']['block']['block_advanced']['block_el']['block_background'] = [
  '#type' => 'select',
  '#title' => t('Block Background'),
  '#default_value' =>  theme_get_setting('block_background'),
  '#options' => _glazed_color_options($theme),
];

$form['glazed_settings']['block']['block_advanced']['block_el']['block_background_custom'] = [
  '#type' => 'textfield',
  '#title' => t('Custom Value'),
  '#default_value' => ((theme_get_setting('block_background_custom') !== null)) ? theme_get_setting('block_background_custom') : '#eeeeee',
  '#states' => [
    'visible' => [
      'select[name="block_background"]' => ['value' => 'custom'],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['block_el']['block_padding'] = [
  '#type' => 'textfield',
  '#title' => t('Block padding'),
  '#default_value' => ((theme_get_setting('block_padding') !== null)) ? theme_get_setting('block_padding') : '0',
  '#attributes' => ['class' => ['border-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['block']['block_advanced']['block_el']['block_border_radius'] = [
  '#type' => 'textfield',
  '#title' => t('Block Border Radius'),
  '#default_value' => ((theme_get_setting('block_border_radius') !== null)) ? theme_get_setting('block_border_radius') : '0',
  '#attributes' => ['class' => ['border-radius-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['block']['block_advanced']['block_el']['block_border'] = [
  '#type' => 'textfield',
  '#title' => t('Block Border'),
  '#default_value' => ((theme_get_setting('block_border') !== null)) ? theme_get_setting('block_border') : '0',
  '#attributes' => ['class' => ['border-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['block']['block_advanced']['block_el']['block_border_color'] = [
  '#type' => 'select',
  '#title' => t('Block Border Color'),
  '#default_value' =>  theme_get_setting('block_border_color'),
  '#options' => _glazed_color_options($theme),
  '#states' => [
    'visible' => [
      'input[name="block_border"]' => ['!value' => '0'],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['block_el']['block_border_color_custom'] = [
  '#type' => 'textfield',
  '#title' => t('Custom Value'),
  '#default_value' => ((theme_get_setting('block_border_color_custom') !== null)) ? theme_get_setting('block_border_color_custom') : '#eeeeee',
  '#states' => [
    'visible' => [
      'input[name="block_border"]' => ['!value' => '0'],
      'select[name="block_border_color"]' => ['value' => 'custom'],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['title_el'] = [
  '#title' => t('Block Title Element'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_font_size'] = [
  '#type' => 'radios',
  '#title' => t('Title Font Size'),
    '#default_value' => ((theme_get_setting('title_font_size') !== null)) ? theme_get_setting('title_font_size') : 'h3',
    '#options' => [
      'h1' => t('h1'),
      'h2' => t('h2'),
      'h3' => t('h3'),
      'h4' => t('h4'),
      'body' => t('Paragraph'),
    ],
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_align'] = [
  '#type' => 'radios',
  '#title' => t('Title Align'),
    '#default_value' => ((theme_get_setting('title_align') !== null)) ? theme_get_setting('title_align') : 'left',
    '#options' => [
      'left' => t('Left'),
      'center' => t('Center'),
      'right' => t('Right')
    ],
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_well'] = [
  '#type' => 'select',
  '#title' => t('Title Well Style'),
  '#default_value' =>  theme_get_setting('title_well'),
  '#options' => $well_options,
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_background'] = [
  '#type' => 'select',
  '#title' => t('Title Background'),
  '#default_value' =>  theme_get_setting('title_background'),
  '#options' => _glazed_color_options($theme),
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_background_custom'] = [
  '#type' => 'textfield',
  '#title' => t('Custom Value'),
  '#default_value' => ((theme_get_setting('title_background_custom') !== null)) ? theme_get_setting('title_background_custom') : '#eeeeee',
  '#states' => [
    'visible' => [
      'select[name="title_background"]' => ['value' => 'custom'],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_padding'] = [
  '#type' => 'textfield',
  '#title' => t('Title padding'),
  '#default_value' => ((theme_get_setting('title_padding') !== null)) ? theme_get_setting('title_padding') : '0',
  '#attributes' => ['class' => ['border-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_border'] = [
  '#type' => 'textfield',
  '#title' => t('Title Border'),
  '#default_value' => ((theme_get_setting('title_border') !== null)) ? theme_get_setting('title_border') : '0',
  '#attributes' => ['class' => ['border-size-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_border_radius'] = [
  '#type' => 'textfield',
  '#title' => t('Title Border Radius'),
  '#default_value' => ((theme_get_setting('title_border_radius') !== null)) ? theme_get_setting('title_border_radius') : '0',
  '#attributes' => ['class' => ['border-radius-slider']],
  '#size' => 9,
  '#maxlength' => 9,
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_border_color'] = [
  '#type' => 'select',
  '#title' => t('Title Border Color'),
  '#default_value' =>  theme_get_setting('title_border_color'),
  '#options' => _glazed_color_options($theme),
  '#states' => [
    'visible' => [
      'input[name="title_border"]' => ['!value' => '0'],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_border_color_custom'] = [
  '#type' => 'textfield',
  '#title' => t('Custom Value'),
  '#default_value' => ((theme_get_setting('title_border_color_custom') !== null)) ? theme_get_setting('title_border_color_custom') : '#eeeeee',
  '#states' => [
    'visible' => [
      'input[name="title_border"]' => ['!value' => '0'],
      'select[name="title_border_color"]' => ['value' => 'custom'],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_align'] = [
  '#type' => 'radios',
  '#title' => t('Title Align'),
    '#default_value' => ((theme_get_setting('title_align') !== null)) ? theme_get_setting('title_align') : 'left',
    '#options' => [
      'left' => t('Left'),
      'center' => t('Center'),
      'right' => t('Right')
    ],
  '#states' => [
    'visible' => [
      ':input[name="title_sticker"]' => ['checked' => FALSE],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['title_el']['title_sticker'] = [
  '#type' => 'checkbox',
  '#title' => t('Title Sticker Mode'),
  '#default_value' => ((theme_get_setting('title_sticker') !== null)) ? theme_get_setting('title_sticker') : 0,
  '#description' => 'In Sticker mode the title\'s width is flexible. By default the title takes the full width of the block',
  '#prefix' => '<br>',
];

$form['glazed_settings']['block']['block_advanced']['divider_el'] = [
  '#title' => t('Divider Element'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
];

$form['glazed_settings']['block']['block_advanced']['divider_el']['block_divider'] = [
  '#type' => 'checkbox',
  '#title' => t('Add Divider to Block'),
  '#default_value' => ((theme_get_setting('block_divider') !== null)) ? theme_get_setting('block_divider') : 0,
];

$form['glazed_settings']['block']['block_advanced']['divider_el']['block_divider_custom'] = [
  '#type' => 'checkbox',
  '#title' => t('Customize Divider'),
  '#description' => t('Turn on to customize block divider specifically. To customize the divider default styling globally across the website find the Divider settings in the Typography tab.'),
  '#default_value' => ((theme_get_setting('block_divider_custom') !== null)) ? theme_get_setting('block_divider_custom') : 0,
  '#states' => [
    'visible' => [
      ':input[name="block_divider"]' => ['checked' => TRUE],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['divider_el']['block_divider_color'] = [
  '#type' => 'select',
  '#title' => t('Divider Color'),
  '#default_value' =>  theme_get_setting('block_divider_color'),
  '#options' => _glazed_color_options($theme),
  '#states' => [
    'visible' => [
      ':input[name="block_divider"]' => ['checked' => TRUE],
      ':input[name="block_divider_custom"]' => ['checked' => TRUE],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['divider_el']['block_divider_color_custom'] = [
  '#type' => 'textfield',
  '#title' => t('Custom Value'),
  '#default_value' => ((theme_get_setting('block_divider_color_custom') !== null)) ? theme_get_setting('block_divider_color_custom') : '#eeeeee',
  '#states' => [
    'visible' => [
      'select[name="block_divider_color"]' => ['value' => 'custom'],
      ':input[name="block_divider"]' => ['checked' => TRUE],
      ':input[name="block_divider_custom"]' => ['checked' => TRUE],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['divider_el']['block_divider_thickness'] = [
  '#type' => 'textfield',
  '#title' => t('Divider Thickness'),
  '#default_value' => ((theme_get_setting('block_divider_thickness') !== null)) ? theme_get_setting('block_divider_thickness') : '4',
  '#size' => 9,
  '#maxlength' => 9,
  '#states' => [
    'visible' => [
      ':input[name="block_divider"]' => ['checked' => TRUE],
      ':input[name="block_divider_custom"]' => ['checked' => TRUE],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['divider_el']['block_divider_length'] = [
  '#type' => 'textfield',
  '#title' => t('Divider Length'),
  '#default_value' => ((theme_get_setting('block_divider_length') !== null)) ? theme_get_setting('block_divider_length') : '10',
  '#size' => 9,
  '#maxlength' => 9,
  '#states' => [
    'visible' => [
      ':input[name="block_divider"]' => ['checked' => TRUE],
      ':input[name="block_divider_custom"]' => ['checked' => TRUE],
    ],
  ],
];

$form['glazed_settings']['block']['block_advanced']['divider_el']['block_divider_spacing'] = [
  '#type' => 'textfield',
  '#title' => t('Divider Spacing'),
  '#default_value' => ((theme_get_setting('block_divider_spacing') !== null)) ? theme_get_setting('block_divider_spacing') : '20',
  '#size' => 9,
  '#maxlength' => 9,
  '#states' => [
    'visible' => [
      ':input[name="block_divider"]' => ['checked' => TRUE],
      ':input[name="block_divider_custom"]' => ['checked' => TRUE],
    ],
  ],
];

$form['glazed_settings']['block']['regions'] = [
  '#title' => t('Choose Regions'),
  '#type' => 'details',
  '#collapsible' => TRUE,
  '#collapsed' => TRUE,
  '#description' => t('The custom block design only applies to selected regions.'),
  '#suffix' => '</div><div class="block-preview">' . _glazed_block_preview() . '</div></div>',
];

$form['glazed_settings']['block']['regions']['block_design_regions'] = [
  '#type' => 'checkboxes',
  '#multiple' => TRUE,
  '#title' => t('Regions'),
  '#default_value' => ((theme_get_setting('block_design_regions') !== null)) ? theme_get_setting('block_design_regions') : [],
  '#options' => [
    'secondary_header' => t('Secondary Header'),
    'navigation' => t('Navigation'),
    'slider' => t('Hero Region'),
    'header' => t('Page Title'),
    'highlighted' => t('Highlighted'),
    'help' => t('Help'),
    'sidebar_first' => t('Sidebar First'),
    'sidebar_second' => t('Sidebar Second'),
    'content_top' => t('Content Top'),
    'content_bottom' => t('Content Bottom'),
    'content' => t('Main Content'),
    'footer' => t('Footer'),
  ],
];

function _glazed_block_preview() {
  $block_well = theme_get_setting('block_well');
  $title_well = theme_get_setting('title_well');
  $output = <<<EOT
<div class="blocks-preview">
  <div class="region region-sidebar-second">
    <section class="block block-tagclouds clearfix {$block_well}" id="block-tagclouds-2">
      <div class="wrap-block-title"><h2 class="block-title {$title_well}">Tags in Tags</h2></div>
      <hr class="block-hr">
      <span class=
      "tagclouds-term"><a class="tagclouds level1" href="#" title=
      "">Business</a></span> <span class="tagclouds-term"><a class=
      "tagclouds level6" href="#" title="">Inspiration</a></span>
      <span class="tagclouds-term"><a class="tagclouds level3" href=
      "#" title="">Oppurtunities</a></span> <span class=
      "tagclouds-term"><a class="tagclouds level3" href="#" title=
      "">Secrets</a></span> <span class="tagclouds-term"><a class=
      "tagclouds level5" href="#" title="">Travel</a></span>
    </section>
    <section class="block block-views clearfix {$block_well}" id=
    "block-views-cms-blog-cat-blog-categories">
      <div class="wrap-block-title"><h2 class="block-title {$title_well}">Categories</h2></div>
      <hr class="block-hr">
      <div class=
      "view view-cms-blog-cat view-id-cms_blog_cat view-display-id-blog_categories view-dom-id-414e72d6e00d6259852916a789bc63e5 ">
      <div class="view-content">
          <ul class="glazed-processed nav">
            <li class="">
              <a href="#">Business <span class=
              "glazed-util-text-muted">(12)</span></a>
            </li>
            <li class="">
              <a href="#">Lifestyle <span class=
              "glazed-util-text-muted">(3)</span></a>
            </li>
          </ul>
        </div>
      </div>
    </section>
  </div>
</div>
EOT;
  return $output;
}
