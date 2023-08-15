<?php

namespace Drupal\uc_role\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\user\Entity\User;

/**
 * Menu callback for viewing expirations.
 */
class RoleExpirationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_role_expiration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create the header for the pager.
    $header = array(
      array('data' => $this->t('Username'), 'field' => 'u.name'),
      array('data' => $this->t('Role'), 'field' => 'e.rid'),
      array('data' => $this->t('Expiration date'), 'field' => 'e.expiration', 'sort' => 'asc'),
      array('data' => $this->t('Operations'), 'colspan' => 2),
    );

    // Grab all the info to build the pager.
    $query = db_select('uc_roles_expirations', 'e')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->fields('e')
      ->limit(50)
      ->orderByHeader($header);

    $query->join('users', 'u', 'e.uid = u.uid');
    $query->fields('u');

    $results = $query->execute();

    // Stick the expirations into the form.
    $rows = [];
    foreach ($results as $result) {
      $account = User::load($result->id());

      // Each row has user name, role , expiration date, and edit/delete operations.
      $row = array(
        'username' => SafeMarkup::checkPlain($account->getUsername()),
        'role' => SafeMarkup::checkPlain(_uc_role_get_name($result->rid)),
        'expiration' => \Drupal::service('date.formatter')->format($result->expiration, 'short'),
      );

      $ops = [];
      $ops['edit'] = array(
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.user.edit_form', ['user' => $result->id()], ['fragment' => 'role-expiration-' . $result->rid, 'query' => ['destination' => 'admin/people/expiration']]),
      );
      $ops['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('uc_role.expiration', ['user' => $result->id(), 'role' => $result->rid]),
      );
      $row['ops'] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $ops,
        ),
      );

      $rows[] = $row;
    }

    $form['roles_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No expirations set to occur'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
