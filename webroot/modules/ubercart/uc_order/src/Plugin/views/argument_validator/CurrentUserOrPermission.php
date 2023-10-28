<?php

namespace Drupal\uc_order\Plugin\views\argument_validator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Validate whether an argument is the current user or has a permission.
 *
 * @ViewsArgumentValidator(
 *   id = "user_or_permission",
 *   module = "uc_order",
 *   title = @Translation("Current user or user has permission")
 * )
 */
class CurrentUserOrPermission extends ArgumentValidatorPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['perm'] = array('default' => 'view all orders');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $options = [];
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    foreach ($permissions as $name => $permission) {
      $options[$permission['provider']][$name] = $permission['title'];
    }

    $form['perm'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Permission'),
      '#default_value' => $this->options['perm'],
      '#description' => $this->t('Users with the selected permission flag will be able to bypass validation.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    // Check that the user actually exists.
    if (!User::load($argument)) {
      return FALSE;
    }

    // Check if the argument matches the current user ID.
    if (\Drupal::currentUser()->id() == $argument) {
      return TRUE;
    }

    // Check if the current user has the bypass permission.
    if (\Drupal::currentUser()->hasPermission($this->options['perm'])) {
      return TRUE;
    }

    return FALSE;
  }

}
