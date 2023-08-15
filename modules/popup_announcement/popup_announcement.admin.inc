<?php

/**
 * Display list of all blocks with announcements
 *
 * Admin page
 */
function popup_announcement_list() {
  
  // table header
  $header = array(
    array('data' => t('#')),
    array('data' => t('Block name')),
    array('data' => t('Status')), // active this block or not (associated with theme region)
    array('data' => t('Size')),
    array('data' => t('Number visit')),
    array('data' => t('Delay')),
    array('data' => t('Visibility Settings')),
    array('data' => t('Pages')),
    array('data' => t('Edit')),
    array('data' => t('Created')),
    array('data' => t('Delete')),
  );

  // visibility types
  $visibility = array(
    BLOCK_VISIBILITY_NOTLISTED => t('All pages except those listed'),
    BLOCK_VISIBILITY_LISTED => t('Only the listed pages'),
    BLOCK_VISIBILITY_PHP => t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'),
  );
  
  // collect data about blocks with announcements
  $rows = array();
  
  $pa = new Popup_announcement_block();
  $bs = $pa->get_blocks();
  
  $i = 1;
  foreach ($bs as $bid => $b) {
    $block = block_load('popup_announcement', 'popup_announcement_' . $bid);
    $destination = drupal_get_destination();

    $rows[] = array(
      '#' => $i,
      'name' => $b['name'] == FALSE ? t('Pop-up announcement ' . $bid) : $b['name'],
      'status' => $block->status == 1 ? 'enabled' : 'disabled',
      'size' => $b['width'] . 'px / ' . $b['height'] . 'px',
      'number_visit' => $b['number_visit'],
      'delay' => $b['delay'],
      'visibility_settings' => $visibility[$block->visibility],
      'pages' =>  check_plain($block->pages),
      'action' => l('edit', 'admin/structure/block/manage/popup_announcement/popup_announcement_' . $bid . '/configure'),
      'created' => format_date($b['created']),
      'delete' => l('delete', 'admin/config/popup_announcement/' . $bid . '/delete'),
    );
    
    $i++;
  }

  $build['popup_announcement_list'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );

  // add button "add new announcement"
  $build['popup_announcement_create_button'] = drupal_get_form('popup_announcement_create_button');

  return $build;

}

/**
 * Create new addirional block with announcement
 */
function popup_announcement_create_button($form) {

  $form['create_button'] = array(
    '#type' => 'submit',
    '#value' => t('Create new announcement'),
    '#submit' => array('popup_announcement_create_button_submit'),
  );

  return $form;
}

/**
 * Form submission handler for popup_announcement_create_button()
 */
function popup_announcement_create_button_submit() {
  
  $pa = new Popup_announcement_block();
  $b = $pa->create_block();
  
  $bid = $pa->save_block($b);

  drupal_goto('admin/structure/block/manage/popup_announcement/popup_announcement_' . $bid . '/configure');
}

/**
 * Form constructor for the popup announcement deletion confirmation form.
 */
function popup_announcement_delete_confirm($form, &$form_state, $bid) {
  
  $form['bid'] = array('#type' => 'value', '#value' => $bid);
  
  return confirm_form($form,
    t('Are you sure you want to delete this popup announcement?'),
    'admin/config/popup_announcement/list',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Executes popup announcement deletion.
 */
function popup_announcement_delete_confirm_submit($form, &$form_state) {
  
  if ($form_state['values']['confirm']) {
    
    $bid = $form_state['values']['bid'];
    
    $pa = new Popup_announcement_block();
    $b = $pa->get_block($bid);
    $name =  $b['name'] == FALSE ? 'Popup announcement ' . $bid : $b['name'];
    
    $pa->delete_block($bid);
    
    cache_clear_all();
    
    watchdog('content', 'Popup announcement: @name was deleted.', array('@name' => $name));
    drupal_set_message(t('Popup announcement: @name was deleted.', array('@name' => $name)));
  }

  $form_state['redirect'] = 'admin/config/popup_announcement/list';
}















