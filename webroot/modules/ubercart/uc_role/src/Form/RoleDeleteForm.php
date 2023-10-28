<?php

namespace Drupal\uc_role\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Form builder for role expirations.
 */
class RoleDeleteForm extends ConfirmFormBase {

  /**
   * The attribute to be deleted.
   */
  protected $attribute;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $username = array(
      '#theme' => 'username',
      '#account' => $account,
    );
    return $this->t('Delete expiration of %role_name role for the user @user?', array(
      '@user' => drupal_render($username),
      '%role_name' => $role_name,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $username = array(
      '#theme' => 'username',
      '#account' => $account,
    );
    return $this->t('Deleting the expiration will give @user privileges set by the %role_name role indefinitely unless manually removed.', array(
      '@user' => drupal_render($username),
      '%role_name' => $role_name,
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('No');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('uc_role.expiration');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_role_deletion_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL, $role = NULL) {
    $expiration = db_query('SELECT expiration FROM {uc_roles_expirations} WHERE uid = :uid AND rid = :rid', [':uid' => $user->id(), ':rid' => $role])->fetchField();
    if ($expiration) {

      $role_name = _uc_role_get_name($role);

      $form['user'] = array('#type' => 'value', '#value' => $user->getUsername());
      $form['uid'] = array('#type' => 'value', '#value' => $user->id());
      $form['role'] = array('#type' => 'value', '#value' => $role_name);
      $form['rid'] = array('#type' => 'value', '#value' => $role);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_role_delete(User::load($form_state->getValue('uid')), $form_state->getValue('rid'));

    $form_state->setRedirect('uc_role.expiration');
  }
}
