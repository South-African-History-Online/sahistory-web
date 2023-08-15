<?php

namespace Drupal\uc_order\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for order routes.
 */
class OrderAdminController extends ControllerBase {

  /**
   * Displays a form to select a previously entered address.
   */
  public function addressBook(Request $request) {
    $uid = intval($request->request->get('uid'));
    $type = $request->request->get('type');
    $func = $request->request->get('func');

    $form = \Drupal::formBuilder()->getForm('\Drupal\uc_order\Form\AddressBookForm', $uid, $type, $func);
    return new Response(drupal_render($form));
  }

  /**
   * Presents the customer search results and let one of them be chosen.
   */
  public function selectCustomer(Request $request, $email = NULL, $operation = NULL) {
    $build = array();
    $options = NULL;

    // Return the search results and let them pick one!
    switch ($operation) {
      case 'search':
        $first_name = str_replace('*', '%', db_like($request->request->get('first_name')));
        $last_name = str_replace('*', '%', db_like($request->request->get('last_name')));
        $email = str_replace('*', '%', db_like($request->request->get('email')));

        $query = db_select('users_field_data', 'u')->distinct();
        $query->leftJoin('uc_orders', 'o', 'u.uid = o.uid');
        $query->fields('u', array('uid', 'mail'))
          ->fields('o', array('billing_first_name', 'billing_last_name'))
          ->condition('u.uid', 0, '>')
          ->orderBy('o.billing_last_name');

        if ($first_name && $first_name !== '%') {
          $query->condition('o.billing_first_name', $first_name, 'LIKE');
        }
        if ($last_name && $last_name !== '%') {
          $query->condition('o.billing_last_name', $last_name, 'LIKE');
        }
        if ($email && $email !== '%') {
          $query->condition(db_or()
            ->condition('o.primary_email', $email, 'LIKE')
            ->condition('u.mail', $email, 'LIKE')
          );
        }

        $result = $query->execute();

        $options = array();
        foreach ($result as $user) {
          if (empty($user->billing_first_name) && empty($user->billing_last_name)) {
            $name = '';
          }
          else {
            $name = $user->billing_last_name . ', ' . $user->billing_first_name . ' ';
          }
          $options[$user->uid . ':' . $user->mail] = $name . '(' . $user->mail . ')';
        }

        if (count($options) == 0) {
          $build['description'] = array(
            '#prefix' => '<p>',
            '#markup' => $this->t('Search returned no results.'),
            '#suffix' => '</p>',
          );
          $options = NULL;
        }
        else {
          $build['description'] = array(
            '#prefix' => '<p>',
            '#markup' => $this->t('Search returned the following:'),
            '#suffix' => '</p>',
          );
        }
        break;

      case 'new':
        if ($request->request->get('check') == TRUE) {
        // Check to see if the e-mail address for a new user is unique.
        $email = SafeMarkup::checkPlain($request->request->get('email'));
        $build['email'] = array('#markup' => '');
        $result = db_query("SELECT uid, mail FROM {users_field_data} WHERE mail = :mail", [':mail' => $email]);
        if ($user_field_data = $result->fetchObject()) {

          $build['#attached']['drupalSettings'] = array(
            'userId' => $user_field_data->uid,
            'userEmail' => $user_field_data->mail,
          );
          $build['email']['#markup'] .= $this->t('An account already exists for that e-mail.') . '<br /><br />';
          $build['email']['#markup'] .= '<b>' . $this->t('Use this account now?') . '</b><br />'
            . $this->t('User @uid - @mail', ['@uid' => $user_field_data->uid, '@mail' => $user_field_data->mail])
            . ' <input type="button" id="select-existing-customer" value="' . $this->t('Apply') . '" /><br /><br /><hr /><br/>';
        }
        else {
          $name = uc_store_email_to_username($email);

          $fields = array(
            'name' => $name,
            'mail' => $email,
            'pass' => user_password(6),
            'status' => \Drupal::config('uc_cart.settings')->get('new_customer_status_active') ? 1 : 0,
          );

          $account = \Drupal\user\Entity\User::create($fields);
          $account->save();

          if ($request->request->get('sendmail') == 'true') {
            // Manually set the password so it appears in the e-mail.
            $account->password = $fields['pass'];

            // Send the e-mail through the user module.
            \Drupal::service('plugin.manager.mail')->mail('user', 'register_admin_created', $email, uc_store_mail_recipient_langcode($email), array('account' => $account), uc_store_email_from());

            $build['email']['#markup'] .= $this->t('Account details sent to e-mail provided.<br /><br /><strong>Username:</strong> @username<br /><strong>Password:</strong> @password', array('@username' => $fields['name'], '@password' => $fields['pass'])) . '<br /><br />';
          }

          $build['#attached']['drupalSettings'] = array(
            'userId' => $account->id(),
            'userEmail' => $account->getEmail(),
          );
          $build['result'] = array(
            '#markup' => '<strong>' . $this->t('Use this account now?') . '</strong><br />'
              . $this->t('User @uid - @mail', array('@uid' => $account->id(), '@mail' => $account->getEmail())) . ' <input type="button" ' . 'id="select-existing-customer" value="' . $this->t('Apply') . '" /><br /><br /><hr /><br/>',
          );
        }
}
        break;

      default:
        break;
    }

    $build['customer_select_form'] = \Drupal::formBuilder()->getForm('\Drupal\uc_order\Form\SelectCustomerForm', $operation, $options);

    return new Response(drupal_render($build));
  }

}
