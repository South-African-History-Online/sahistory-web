<?php

namespace Drupal\uc_role\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Creates or edits a role feature for a product.
 */
class RoleFeatureForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_role_feature_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, $feature = NULL) {
    $roles_config = $this->config('uc_role.settings');
    $models = uc_product_get_models($node->id());

    // Check if editing or adding to set default values.
    if (!empty($feature)) {
      $product_role = db_query('SELECT * FROM {uc_roles_products} WHERE pfid = :pfid', [':pfid' => $feature['pfid']])->fetchObject();

      $default_model = $product_role->model;
      $default_role = $product_role->rid;
      $default_qty = $product_role->duration;
      $default_granularity = $product_role->granularity;
      $default_shippable = $product_role->shippable;
      $default_by_quantity = $product_role->by_quantity;
      if ($product_role->end_time) {
        $end_time = $product_role->end_time;
        $default_end_type = 'abs';
      }
      else {
        $end_time = _uc_role_get_expiration($default_qty, $default_granularity);
        $default_end_type = 'rel';
      }

      $form['pfid'] = array(
        '#type' => 'value',
        '#value' => $feature['pfid'],
      );
      $form['rpid'] = array(
        '#type' => 'value',
        '#value' => $product_role->rpid,
      );

      $default_end_override = $product_role->end_override;
    }
    else {
      $default_model = 0;
      $default_role = $roles_config->get('default_role');
      $default_qty = ($roles_config->get('default_granularity') == 'never') ? NULL : $roles_config->get('default_length');
      $default_granularity = $roles_config->get('default_granularity');
      $default_shippable = $node->shippable->value;
      $default_by_quantity = $roles_config->get('default_by_quantity');
      $end_time = (int) $roles_config->get('default_end_time');
      $default_end_type = $roles_config->get('default_end_expiration');
      $default_end_override = FALSE;
    }

    $roles = _uc_role_get_choices();
    if (!count($roles)) {
      // No actions can be done. Remove submit buttons.
      unset($form['buttons']);

      $form['no_roles'] = array(
        '#markup' => $this->t('You need to <a href=":url">create new roles</a> before any can be added as product features.', [':url' => Url::fromRoute('user.role_add', [], ['query' => ['destination' => 'admin/store/config/products']])->toString()]),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      );

      return $form;
    }

    $form['nid'] = array(
      '#type' => 'value',
      '#value' => $node->id(),
    );
    $form['model'] = array(
      '#type' => 'select',
      '#title' => $this->t('SKU'),
      '#default_value' => $default_model,
      '#description' => $this->t('This is the SKU of the product that will grant the role.'),
      '#options' => $models,
    );
    $form['role'] = array(
      '#type' => 'select',
      '#title' => $this->t('Role'),
      '#default_value' => $default_role,
      '#description' => $this->t('This is the role the customer will receive after purchasing the product.'),
      '#options' => $roles,
    );
    $form['shippable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Shippable product'),
      '#default_value' => $default_shippable,
      '#description' => $this->t('Check if this product SKU that uses role assignment is associated with a shippable product.'),
    );

    $form['end_override'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Override the <a href=":url">default role expiration</a>.', [':url' => Url::fromRoute('uc_product.settings')->toString()]),
      '#default_value' => $default_end_override,
    );

    $form['role_lifetime'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Role expiration'),
      '#states' => array(
        'visible' => array('input[name="end_override"]' => array('checked' => TRUE)),
      ),
    );
    $form['role_lifetime']['expiration'] = array(
      '#type' => 'select',
      '#title' => $this->t('Expiration type'),
      '#options' => array(
        'rel' => $this->t('Relative to purchase date'),
        'abs' => $this->t('Fixed date'),
      ),
      '#default_value' => $default_end_type,
    );
    $form['role_lifetime']['expire_relative_duration'] = array(
      '#type' => 'textfield',
      '#default_value' => $default_qty,
      '#size' => 4,
      '#maxlength' => 4,
      '#prefix' => '<div class="expiration">',
      '#suffix' => '</div>',
      '#states' => array(
        'visible' => array('select[name="expiration"]' => array('value' => 'rel')),
        'invisible' => array('select[name="expire_relative_granularity"]' => array('value' => 'never')),
      ),
    );
    $form['role_lifetime']['expire_relative_granularity'] = array(
      '#type' => 'select',
      '#options' => array(
        'never' => $this->t('never'),
        'day' => $this->t('day(s)'),
        'week' => $this->t('week(s)'),
        'month' => $this->t('month(s)'),
        'year' => $this->t('year(s)')
      ),
      '#default_value' => $default_granularity,
      '#description' => $this->t('From the time the role was purchased.'),
      '#prefix' => '<div class="expiration">',
      '#suffix' => '</div>',
      '#states' => array(
        'visible' => array('select[name="expiration"]' => array('value' => 'rel')),
      ),
    );
    $form['role_lifetime']['absolute'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array('select[name="expiration"]' => array('value' => 'abs')),
      ),
    );

    $date = empty($end_time) ? DrupalDateTime::createFromTimestamp(REQUEST_TIME) : DrupalDateTime::createFromTimestamp($end_time);
    $form['role_lifetime']['absolute']['expire_absolute'] = array(
      '#type' => 'datetime',
      '#description' => $this->t('Expire the role at the beginning of this day.'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#default_value' => $date,
    );
    $form['role_lifetime']['by_quantity'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Multiply by quantity'),
      '#default_value' => $default_by_quantity,
      '#description' => $this->t('Check if the role duration should be multiplied by the quantity purchased.'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save feature'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Invalid quantity?
    if ($form_state->getValue('expiration') === 'abs') {
      drupal_set_message(var_export($form_state->getValue('expire_absolute'), TRUE));
      if ($form_state->getValue('expire_absolute')->getTimestamp() <= REQUEST_TIME) {
        $form_state->setErrorByName('expire_absolute', $this->t('The specified date @date has already occurred. Please choose another.', ['@date' => \Drupal::service('date.formatter')->format($form_state->getValue('expire_absolute')->getTimestamp())]));
      }
    }
    else {
      if ($form_state->getValue('expire_relative_granularity') != 'never' && intval($form_state->getValue('expire_relative_duration')) < 1) {
        $form_state->setErrorByName('expire_relative_duration', $this->t('The amount of time must be a positive integer.'));
      }
    }

    // No roles?
    if ($form_state->isValueEmpty('role')) {
      $form_state->setErrorByName('role', $this->t('You must have a role to assign. You may need to <a href=":role_url">create a new role</a> or perhaps <a href=":feature_url">set role assignment defaults</a>.', [':role_url' => Url::fromRoute('user.role_add')->toString(), ':feature_url' => Url::fromRoute('uc_product.settings')->toString()]));
    }

    // This role already set on this SKU?
    if (!$form_state->hasValue('pfid') && ($product_roles = db_query('SELECT * FROM {uc_roles_products} WHERE nid = :nid AND model = :model AND rid = :rid', [':nid' => $form_state->getValue('nid'), ':model' => $form_state->getValue('model'), ':rid' => $form_state->getValue('role')])->fetchObject())) {
      $form_state->setErrorByName('role', $this->t('The combination of SKU and role already exists for this product.'));
      $form_state->setErrorByName('model');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $product_role = array(
      'pfid'        => $form_state->getValue('pfid'),
      'rpid'        => $form_state->getValue('rpid'),
      'nid'         => $form_state->getValue('nid'),
      'model'       => $form_state->getValue('model'),
      'rid'         => $form_state->getValue('role'),
      'duration'    => $form_state->getValue('expire_relative_granularity') != 'never' ? $form_state->getValue('expire_relative_duration') : NULL,
      'granularity' => $form_state->getValue('expire_relative_granularity'),
      'by_quantity' => $form_state->getValue('by_quantity'),
      'shippable'   => $form_state->getValue('shippable'),

      // We should be setting NULL, but drupal_write_record() ...
      'end_override' => $form_state->getValue('end_override'),
      'end_time'     => $form_state->getValue('expiration') === 'abs' ? $form_state->getValue('expire_absolute')->getTimestamp() : NULL,
    );

    $description = empty($product_role['model']) ? $this->t('<strong>SKU:</strong> Any<br />') : $this->t('<strong>SKU:</strong> @sku<br />', ['@sku' => $product_role['model']]);
    $description .=  $this->t('<strong>Role:</strong> @role_name<br />', ['@role_name' => _uc_role_get_name($product_role['rid'])]);

    if ($product_role['end_override']) {
      if ($product_role['end_time']) {
        $description .= $this->t('<strong>Expiration:</strong> @date<br />', ['@date' => \Drupal::service('date.formatter')->format($product_role['end_time'])]);
      }
      else {
        switch ($product_role['granularity']) {
          case 'never':
            $description .= $this->t('<strong>Expiration:</strong> never<br />');
            break;
          case 'day':
            $description .= $this->t('<strong>Expiration:</strong> @qty day(s)<br />', ['@qty' => $product_role['duration']]);
            break;
          case 'week':
            $description .= $this->t('<strong>Expiration:</strong> @qty week(s)<br />', ['@qty' => $product_role['duration']]);
            break;
          case 'month':
            $description .= $this->t('<strong>Expiration:</strong> @qty month(s)<br />', ['@qty' => $product_role['duration']]);
            break;
          case 'year':
            $description .= $this->t('<strong>Expiration:</strong> @qty year(s)<br />', ['@qty' => $product_role['duration']]);
            break;
          default:
            break;
        }
      }
    }
    else {
      $description .= $this->t('<strong>Expiration:</strong> @link (not overridden)<br />', ['@link' => Link::createFromRoute($this->t('Global expiration'), 'uc_product.settings')->toString()]);
    }
    $description .= $product_role['shippable'] ? $this->t('<strong>Shippable:</strong> Yes<br />') : $this->t('<strong>Shippable:</strong> No<br />');
    $description .= $product_role['by_quantity'] ? $this->t('<strong>Multiply by quantity:</strong> Yes') : $this->t('<strong>Multiply by quantity:</strong> No');

    $data = array(
      'pfid' => $product_role['pfid'],
      'nid' => $product_role['nid'],
      'fid' => 'role',
      'description' => $description,
    );

    uc_product_feature_save($data);

    $product_role['pfid'] = $data['pfid'];

    // Insert or update uc_file_product table.
    foreach (['duration', 'granularity', 'end_time'] as $property) {
      $product_role[$property] = $product_role[$property] === NULL ? 0 : $product_role[$property];
    }

    if (!isset($product_role['rpid'])) {
      unset($product_role['rpid']);
      $product_role['rpid'] = db_insert('uc_roles_products')
        ->fields($product_role)
        ->execute();
    }
    else {
      db_update('uc_roles_products')
        ->fields($product_role)
        ->condition('rpid', $product_role['rpid'])
        ->execute();
    }

    $form_state->setRedirect('uc_product.features', ['node' => $data['nid']]);
  }

}
