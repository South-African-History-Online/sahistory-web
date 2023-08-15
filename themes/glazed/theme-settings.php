<?php

use Drupal\node\Entity\NodeType;

function glazed_form_system_theme_settings_alter(&$form, &$form_state, $form_id = NULL) {
  /**
   * @ code
   * a bug in D7 and D8 causes the theme to load twice. Only the second time $form
   * will contain the color module data. So we ignore the first
   * @see https://www.drupal.org/node/943212
   */
  if (!isset($form_id)) { // form_id is only present the second time around
    return;
  };

  global $base_path, $theme_chain;

  $build_info = $form_state->getBuildInfo();
  $subject_theme = $build_info['args'][0];
  $glazed_theme_path = drupal_get_path('theme', 'glazed') . '/';
  $theme_path = drupal_get_path('theme', $subject_theme) . '/';
  $themes = \Drupal::service('theme_handler')->listInfo();
  $theme_chain = [$subject_theme];
  foreach ($themes[$subject_theme]->base_themes as $base_theme => $base_theme_name) {
    $theme_chain[] = $base_theme;
  }

  $img = '<img style="width:35px;margin-right:5px;" src="' . $base_path . $glazed_theme_path . 'logo-white.png" />';
  $form['glazed_settings'] = [
    '#type' => 'vertical_tabs', // SETTING TYPE TO DETAILS OR VERTICAL_TABS STOPS RENDERING OF ALL ELEMENTS INSIDE
    '#weight' => -20,
    '#prefix' => '<h2><small>' . $img . ' ' . ucfirst($subject_theme) . ' ' . $themes[$subject_theme]->info['version'] . ' <span class="lead">(Bootstrap ' . $themes['bootstrap']->info['version'] . ')</span>' . '</small></h2>',
  ];
  // $form['color']['#group'] = 'glazed_settings';
  if (!empty($form['update'])) {
    $form['update']['#group'] = 'global';
  }
  if (!empty($form['color'])) {
    $form['color']['#group'] = 'glazed_settings';
    $form['color']['#title'] = t('Colors');
  }

  /**
   * Glazed cache builder
   * Cannot run as submit function because  it will set outdated values by
   * using theme_get_setting to retrieve settings from database before the db is
   * updated. Cannot put cache builder in form scope and use $form_state because
   * it also needs to initialize default settings by reading the .info file.
   * By calling the cache builder here it will run twice: once before the
   * settings are saved and once after the redirect with the updated settings.
   * @todo come up with a less 'icky' solution
   */
  require_once(drupal_get_path('theme', 'glazed') . '/glazed_callbacks.inc');
  glazed_css_cache_build($subject_theme);

  foreach (file_scan_directory(drupal_get_path('theme', 'glazed') . '/features', '/settings.inc/i') as $file) {
    $theme = 'glazed';
    require_once($file->uri);
  }
  $form['#attached']['library'][] = 'glazed/admin.themesettings';

  if ((\Drupal::moduleHandler()->moduleExists('color')) && ($palette = color_get_palette($subject_theme))) {
    $form['#attached']['drupalSettings']['glazedSettings'] = ['palette' => $palette]; // glazedSetting vs glazed namespace otherwise if deletes other .glazed data
  }

  array_unshift($form['#submit'], 'glazed_form_system_theme_settings_submit');
  array_unshift($form['#validate'], 'glazed_form_system_theme_settings_validate');
}

/**
 * Validate callback for theme settings form
 * @see core/modules/system/src/Form/ThemeSettingsForm.php validateForm
 */
function glazed_form_system_theme_settings_validate(&$form, &$form_state) {
  if (\Drupal::moduleHandler()->moduleExists('file')) {
      // Handle file uploads.
      $validators = ['file_validate_is_image' => []];

      // Check for a new uploaded logo.
      $file = file_save_upload('page_title_image', $validators, FALSE, 0);
      if (isset($file)) {
        // File upload was attempted.
        if ($file) {
          // Put the temporary file in form_values so we can save it on submit.
          $form_state->setValue('page_title_image', $file);
        }
        else {
          // File upload failed.
          $form_state->setErrorByName('page_title_image', t('The logo could not be uploaded.'));
        }
      }

      // Check for a new uploaded background image.
      $file = file_save_upload('background_image', $validators, FALSE, 0);
      if (isset($file)) {
        // File upload was attempted.
        if ($file) {
          // Put the temporary file in form_values so we can save it on submit.
          $form_state->setValue('background_image', $file);
        }
        else {
          // File upload failed.
          $form_state->setErrorByName('background_image', t('The background image could not be uploaded.'));
        }
      }

      // If the user provided a path for a logo or background image file, make sure a file
      // exists at that path.
      if ($form_state->getValue('page_title_image_path')) {
        $path = _glazed_validate_path($form_state->getValue('page_title_image_path'));
        if (!$path) {
          $form_state->setErrorByName('page_title_image_path', t('The custom logo path is invalid.'));
        }
      }
      if ($form_state->getValue('background_image_path')) {
        $path = _glazed_validate_path($form_state->getValue('background_image_path'));
        if (!$path) {
          $form_state->setErrorByName('background_image_path', t('The custom background image path is invalid.'));
        }
      }

    // Handle file uploads.
    $validators = ['file_validate_is_image' => []];
    // $validators = [];

    // Check for a new uploaded logo.
    $file = file_save_upload('page_title_image', $validators, FALSE, 0);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue('page_title_image', $file);
      }
      else {
        // File upload failed.
        $form_state->setErrorByName('page_title_image', t('The logo could not be uploaded.'));
      }
    }
  }
}

/**
 * Submit callback for theme settings form
 * @see core/modules/system/src/Form/ThemeSettingsForm.php submitForm
 */
function glazed_form_system_theme_settings_submit(&$form, &$form_state) {
    // If the user uploaded a new image, save it to a permanent location
    $value = $form_state->getValue('page_title_image');
    if (!empty($value)) {
      $filename = \Drupal::service('file_system')->copy($value->getFileUri());
      $form_state->setValue('page_title_image', '');
      $form_state->setValue('page_title_image_path', $filename);
    }

    $value = $form_state->getValue('background_image');
    if (!empty($value)) {
      $filename = \Drupal::service('file_system')->copy($value->getFileUri());
      $form_state->setValue('background_image', '');
      $form_state->setValue('background_image_path', $filename);
    }

    // If the user entered a path relative to the system files directory for
    // a logo or favicon, store a public:// URI so the theme system can handle it.
    if (!empty($form_state->getValue('page_title_image_path'))) {
      $path = _glazed_validate_path($form_state->getValue('page_title_image_path'));
      $form_state->setValue('page_title_image_path', $path);
    }
    if (!empty($form_state->getValue('background_image_path'))) {
      $path = _glazed_validate_path($form_state->getValue('background_image_path'));
      $form_state->setValue('background_image_path', $path);
    }
}

/**
 * Retrieves the Color module information for a particular theme.
 */
function _glazed_get_color_names($theme = NULL) {
  static $theme_info = [];
  if (!isset($theme)) {
    $theme = \Drupal::config('system.theme');
  }

  if (isset($theme_info[$theme])) {
    return $theme_info[$theme];
  }

  $path = drupal_get_path('theme', $theme);
  $file = DRUPAL_ROOT . '/' . $path . '/color/color.inc';
  if ($path && file_exists($file)) {
    include $file;
    $theme_info[$theme] = $info['fields'];
    return $info['fields'];
  } else {
    return [];
  }
}

/**
 * Color options for theme settings
 */
function _glazed_color_options($theme) {
  $colors = [
    '' => t('None (Theme Default)'),
    'white' => t('White'),
    'custom' => t('Custom Color'),
  ];
  $theme_colors = _glazed_get_color_names($theme);
  $colors = array_merge($colors, $theme_colors);
  return $colors;
}

function _glazed_node_types_options() {
  $types = [];
  foreach (NodeType::loadMultiple() as $key => $value) {
    $types[$key] = $value->get('name');
  }
  return $types;
}

function _glazed_type_preview() {
  $output = <<<EOT
<div class="type-preview">
  <div class="type-container type-title-container">
    <h1>Beautiful Typography</h1>
  </div>

  <div class="type-container">
    <h2>Typewriter delectus cred. Thundercats, sed scenester before they sold out et aesthetic</h2>
    <hr>
    <p class="lead">Lead Text Direct trade gluten-free blog, fanny pack cray labore skateboard before they sold out adipisicing non magna id Helvetica freegan. Disrupt aliqua Brooklyn church-key lo-fi dreamcatcher.</p>


    <h3>Truffaut disrupt sartorial deserunt</h3>

    <p>Cosby sweater plaid shabby chic kitsch pour-over ex. Try-hard fanny pack mumblecore cornhole cray scenester. Assumenda narwhal occupy, Blue Bottle nihil culpa fingerstache. Meggings kogi vinyl meh, food truck banh mi Etsy magna 90's duis typewriter banjo organic leggings Vice.</p>

    <ul>
      <li>Roof party put a bird on it incididunt sed umami craft beer cred.</li>
      <li>Carles literally normcore, Williamsburg Echo Park fingerstache photo booth twee keffiyeh chambray whatever.</li>
      <li>Scenester High Life Banksy, proident master cleanse tousled squid sriracha ad chillwave post-ironic retro.</li>
    </ul>

    <h4>Fingerstache nesciunt lomo nostrud hoodie</h4>

    <blockquote>
      <p>Cosby sweater plaid shabby chic kitsch pour-over ex. Try-hard fanny pack mumblecore cornhole cray scenester. Assumenda narwhal occupy, Blue Bottle nihil culpa fingerstache. Meggings kogi vinyl meh, food truck banh mi Etsy magna 90's duis typewriter banjo organic leggings Vice.</p>
      <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
    </blockquote>
  </div>
</div>
EOT;
  return $output;
}



/**
 * Helper function for the system_theme_settings form.
 * @see core/modules/system/src/Form/ThemeSettingsForm.php validatePath
 *
 * Attempts to validate normal system paths, paths relative to the public files
 * directory, or stream wrapper URIs. If the given path is any of the above,
 * returns a valid path or URI that the theme system can display.
 *
 * @param string $path
 *   A path relative to the Drupal root or to the public files directory, or
 *   a stream wrapper URI.
 * @return mixed
 *   A valid path that can be displayed through the theme system, or FALSE if
 *   the path could not be validated.
 */
function _glazed_validate_path($path) {
  // Absolute local file paths are invalid.
  if (\Drupal::service('file_system')->realpath($path) == $path) {
    return FALSE;
  }
  // A path relative to the Drupal root or a fully qualified URI is valid.
  if (is_file($path)) {
    return $path;
  }
  // Prepend 'public://' for relative file paths within public filesystem.
  if (\Drupal::service('file_system')->uriScheme($path) === FALSE) {
    $path = 'public://' . $path;
  }
  if (is_file($path)) {
    return $path;
  }
  return FALSE;
}
