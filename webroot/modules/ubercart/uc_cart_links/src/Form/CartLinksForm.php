<?php

namespace Drupal\uc_cart_links\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Preprocesses a cart link, confirming with the user for destructive actions.
 */
class CartLinksForm extends ConfirmFormBase {

  /**
   * The cart link actions.
   */
  protected $actions;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('The current contents of your shopping cart will be lost. Are you sure you want to continue?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_links_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_cart_links.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $actions = NULL) {
    $cart_links_config = $this->config('uc_cart_links.settings');

    $this->actions = $actions;

    // Fail if the link is restricted.
    $data = $cart_links_config->get('restrictions');
    if (!empty($data)) {
      $restrictions = explode("\n", $cart_links_config->get('restrictions'));
      $restrictions = array_map('trim', $restrictions);

      if (!empty($restrictions) && !in_array($this->actions, $restrictions)) {
        unset($_GET['destination']);
        $path = $cart_links_config->get('invalid_page');
        if (empty($path)) {
          return $this->redirect('<front>', [], ['absolute' => TRUE]);
        }
        return new RedirectResponse(Url::fromUri('internal:/' . $path, ['absolute' => TRUE])->toString());
      }
    }

    // Confirm with the user if the form contains a destructive action.
    $cart = \Drupal::service('uc_cart.manager')->get();
    $items = $cart->getContents();
    if ($cart_links_config->get('empty') && !empty($items)) {
      $actions = explode('-', urldecode($this->actions));
      foreach ($actions as $action) {
        $action = Unicode::substr($action, 0, 1);
        if ($action == 'e' || $action == 'E') {
          $form = parent::buildForm($form, $form_state);
          $form['actions']['cancel']['#href'] = $cart_links_config->get('invalid_page');
          return $form;
        }
      }
    }

    // No destructive actions, so process the link immediately.
    return $this->submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cart_links_config = $this->config('uc_cart_links.settings');

    $actions = explode('-', urldecode($this->actions));
    $messages = array();
    $id = $this->t('(not specified)');

    $cart = \Drupal::service('uc_cart.manager')->get();
    foreach ($actions as $action) {
      switch (Unicode::substr($action, 0, 1)) {
        // Set the ID of the Cart Link.
        case 'i':
        case 'I':
          $id = Unicode::substr($action, 1, 32);
          break;

        // Add a product to the cart.
        case 'p':
        case 'P':
          // Set the default product variables.
          $p = array('nid' => 0, 'qty' => 1, 'data' => array());
          $msg = TRUE;

          // Parse the product action to adjust the product variables.
          $parts = explode('_', $action);
          foreach ($parts as $part) {
            switch (Unicode::substr($part, 0, 1)) {
              // Set the product node ID: p23
              case 'p':
              case 'P':
                $p['nid'] = intval(Unicode::substr($part, 1));
                break;
              // Set the quantity to add to cart: _q2
              case 'q':
              case 'Q':
                $p['qty'] = intval(Unicode::substr($part, 1));
                break;
              // Set an attribute/option for the product: _a3o6
              case 'a':
              case 'A':
                $attribute = intval(Unicode::substr($part, 1, stripos($part, 'o') - 1));
                $option = (string) Unicode::substr($part, stripos($part, 'o') + 1);
                if (!isset($p['attributes'][$attribute])) {
                  $p['attributes'][$attribute] = $option;
                }
                else {
                  // Multiple options for this attribute implies checkbox
                  // attribute, which we must store as an array
                  if (is_array($p['attributes'][$attribute])) {
                    // Already an array, just append this new option
                    $p['attributes'][$attribute][$option] = $option;
                  }
                  else {
                    // Set but not an array, means we already have at least one
                    // option, so put that into an array with this new option
                    $p['attributes'][$attribute] = array(
                      $p['attributes'][$attribute] => $p['attributes'][$attribute],
                      $option => $option
                    );
                  }
                }
                break;
              // Suppress the add to cart message: _s
              case 's':
              case 'S':
                $msg = FALSE;
                break;
            }
          }

          // Add the item to the cart, suppressing the default redirect.
          if ($p['nid'] > 0 && $p['qty'] > 0) {
            // If it's a product kit, we need black magic to make everything work
            // right. In other words, we have to simulate FAPI's form values.
            $node = node_load($p['nid']);
            if ($node->status) {
              if (isset($node->products) && is_array($node->products)) {
                foreach ($node->products as $nid => $product) {
                  $p['data']['products'][$nid] = array(
                    'nid' => $nid,
                    'qty' => $product->qty,
                  );
                }
              }
              $cart->addItem($p['nid'], $p['qty'], $p['data'] + \Drupal::moduleHandler()->invokeAll('uc_add_to_cart_data', array($p)), $msg);
            }
            else {
              $this->logger('uc_cart_link')->error('Cart Link on %url tried to add an unpublished product to the cart.', array('%url' => $this->getRequest()->server->get('HTTP_REFERER')));
            }
          }
          break;

        // Empty the shopping cart.
        case 'e':
        case 'E':
          if ($cart_links_config->get('empty')) {
            $cart->emptyCart();
          }
          break;

        // Display a pre-configured message.
        case 'm':
        case 'M':
          // Load the messages if they haven't been loaded yet.
          if (empty($messages)) {
            $data = explode("\n", $cart_links_config->get('messages'));
            foreach ($data as $message) {
              // Skip blank lines.
              if (preg_match('/^\s*$/', $message)) {
                 continue;
              }
              list($mkey, $mdata) = explode('|', $message, 2);
              $messages[trim($mkey)] = trim($mdata);
            }
          }

          // Parse the message key and display it if it exists.
          $mkey = intval(Unicode::substr($action, 1));
          if (!empty($messages[$mkey])) {
            drupal_set_message($messages[$mkey]);
          }
          break;
      }
    }

    if ($cart_links_config->get('track')) {
      db_merge('uc_cart_link_clicks')
        ->key(array('cart_link_id' => (string) $id))
        ->fields(array(
          'clicks' => 1,
          'last_click' => REQUEST_TIME,
        ))
        ->expression('clicks', 'clicks + :i', array(':i' => 1))
        ->execute();
    }

    \Drupal::service('session')->set('uc_cart_last_url', $this->getRequest()->server->get('HTTP_REFERER'));

    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      $path = $options['path'];
    }
    else {
      $path = 'cart';
      $options = array();
    }
    $options += array('absolute' => TRUE);

    // Form redirect is for confirmed links.
    $form_state->setRedirectUrl(Url::fromUri('base:/' . $path, $options));
  }

}
