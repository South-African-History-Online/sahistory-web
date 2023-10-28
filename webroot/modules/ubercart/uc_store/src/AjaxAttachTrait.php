<?php

namespace Drupal\uc_store;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\uc_store\Ajax\CommandWrapper;

/**
 * Contains logic to aid in attaching multiple ajax behaviors to form
 * elements on the checkout and order-edit forms.
 *
 * Both the checkout and the order edit forms are made up of multiple panes,
 * many supplied by contrib modules. Any pane may wish to update its own
 * display or that of another pane based on user input from input elements
 * anywhere on the form. The mechanism here described enables modules
 * to add ajax behaviors to the form in an orderly and efficient manner.
 *
 * Generally, an implementing pane should not add #ajax keys to existing form
 * elements directly. Rather, it should attach ajax behavior by adding
 * to the $form_state['uc_ajax'] array.
 *
 * $form_state['uc_ajax'] is an associative array keyed by the name of the
 * implementing module. Each implementing module should provide an array
 * of ajax callbacks, keyed by the name of the triggering element as it would
 * be specified when invoking form_set_error(). The entry for each element
 * may be either the name of a single ajax callback to be attached to that
 * element, or an array of ajax callbacks, optionally keyed by wrapper.
 * For example:
 *
 * @code
 *   $form_state->set(['uc_ajax', 'mymodule', 'panes][quotes][quote_button'], array(
 *     'quotes-pane' => '::ajaxReplaceCheckoutPane',
 *   ));
 * @endcode
 *
 * This will cause the contents of 'quotes-pane' to be replaced by the return
 * value of ajaxReplaceCheckoutPane(). Note that if more than one module
 * assign a callback to the same wrapper key, the heavier module or pane will
 * take precedence.
 *
 * Implementors need not provide a wrapper key for each callback, in which case
 * the callback must return an array of ajax commands rather than a renderable
 * form element. For example:
 *
 * @code
 *   $form_state->set(['uc_ajax', 'mymodule', 'panes][quotes][quote_button'], array(
 *     '::myAjaxCallback',
 *   ));
 *   ...
 *   public function myAjaxCallback($form, $form_state) {
 *     $response = new AjaxResponse();
 *     $response->addCommand(new InvokeCommand('#my-input-element', 'val', 0));
 *     return $response;
 *   }
 * @endcode
 *
 * However, using a wrapper key where appropriate will reduce redundant
 * replacements of the same element.
 *
 * NOTE: 'ajaxReplaceCheckoutPane()' is a convenience callback which will
 * replace the contents of an entire checkout pane. It is generally preferable
 * to use this when updating data on the checkout form, as this will
 * further reduce the likelihood of redundant replacements. You should use
 * your own callback only when behaviours other than replacement are
 * desired, or when replacing data that lie outside a checkout pane. Note
 * also that you may combine both formulations by mixing numeric and string keys.
 * For example:
 *
 * @code
 *   $form_state->set(['uc_ajax', 'mymodule', 'panes][quotes][quote_button'], array(
 *     '::myAjaxCallback',
 *     'quotes-pane' => '::ajaxReplaceCheckoutPane',
 *   ));
 * @endcode
 */
trait AjaxAttachTrait {

  /**
   * Form process callback to allow multiple Ajax callbacks on form elements.
   */
  public function ajaxProcessForm(array $form, FormStateInterface $form_state) {
    // When processing the top level form, add any variable-defined pane wrappers.
    if (isset($form['#form_id'])) {
      switch ($form['#form_id']) {
        case 'uc_cart_checkout_form':
          $config = \Drupal::config('uc_cart.settings')->get('ajax.checkout') ?: [];
          foreach ($config as $key => $panes) {
            foreach ($panes as $pane) {
              $form_state->set(['uc_ajax', 'uc_ajax', $key, $pane], '::ajaxReplaceCheckoutPane');
            }
          }
          break;
      }
    }

    if (!$form_state->has('uc_ajax')) {
      return $form;
    }

    // We have to operate on the children rather than on the element itself, as
    // #process functions are called *after* form_handle_input_elements(),
    // which is where the triggering element is determined. If we haven't added
    // an '#ajax' key by that time, Drupal won't be able to determine which
    // callback to invoke.
    foreach (Element::children($form) as $child) {
      $element =& $form[$child];

      // Add this process function recursively to the children.
      if (empty($element['#process']) && !empty($element['#type'])) {
        // We want to be sure the default process functions for the element type are called.
        $info = element_info($element['#type']);
        if (!empty($info['#process'])) {
          $element['#process'] = $info['#process'];
        }
      }
      $element['#process'][] = array($this, 'ajaxProcessForm');

      // Multiplex any Ajax calls for this element.
      $parents = $form['#array_parents'];
      array_push($parents, $child);
      $key = implode('][', $parents);

      $callbacks = array();
      foreach ($form_state->get('uc_ajax') as $fields) {
        if (!empty($fields[$key])) {
          if (is_array($fields[$key])) {
            $callbacks = array_merge($callbacks, $fields[$key]);
          }
          else {
            $callbacks[] = $fields[$key];
          }
        }
      }

      if (!empty($callbacks)) {
        if (empty($element['#ajax'])) {
          $element['#ajax'] = array();
        }
        elseif (!empty($element['#ajax']['callback'])) {
          if (!empty($element['#ajax']['wrapper'])) {
            $callbacks[$element['#ajax']['wrapper']] = $element['#ajax']['callback'];
          }
          else {
            array_unshift($callbacks, $element['#ajax']['callback']);
          }
        }

        $element['#ajax'] = array_merge($element['#ajax'], array(
          'callback' => array($this, 'ajaxMultiplex'),
          'list' => $callbacks,
        ));
      }
    }

    return $form;
  }

  /**
   * Ajax callback multiplexer.
   *
   * Processes a set of Ajax commands attached to the triggering element.
   */
  public function ajaxMultiplex(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $element = $form_state->getTriggeringElement();
    foreach ($element['#ajax']['list'] as $wrapper => $callback) {
      $callback = $form_state->prepareCallback($callback);
      if (!empty($callback) && is_callable($callback) && $result = call_user_func_array($callback, [$form, $form_state, $wrapper])) {
        if ($result instanceof AjaxResponse) {
          // Merge AjaxResponse commands into our single list.
          foreach ($result->getCommands() as $command) {
            $response->addCommand(new CommandWrapper($command));
          }
        }
        elseif (is_string($wrapper)) {
          // Otherwise, assume the callback returned a string or render-array, and insert it into the wrapper.
          $html = is_string($result) ? $result : drupal_render($result);
          $response->addCommand(new ReplaceCommand('#' . $wrapper, trim($html)));
          $status_messages = array('#type' => 'status_messages');
          $response->addCommand(new PrependCommand('#' . $wrapper, drupal_render($status_messages)));
        }
      }
    }

    return $response;
  }

  /**
   * Ajax callback to replace a whole checkout pane.
   *
   * @param $form
   *   The checkout form.
   * @param $form_state
   *   The current form state.
   * @param $wrapper
   *   Special third parameter passed for uc_ajax callbacks containing the ajax
   *   wrapper for this callback.  Here used to determine which pane to replace.
   *
   * @return
   *   The form element representing the pane, suitable for ajax rendering. If
   *   the pane does not exist, or if the wrapper does not refer to a checkout
   *   pane, returns nothing.
   */
  public function ajaxReplaceCheckoutPane(array $form, FormStateInterface $form_state, $wrapper = NULL) {
    $element = $form_state->getTriggeringElement();
    if (empty($wrapper) && !empty($element['#ajax']['wrapper'])) {
      // If $wrapper is absent, then we were not invoked by ajaxMultiplex(),
      // so try to use the wrapper of the triggering element's #ajax array.
      $wrapper = $element['#ajax']['wrapper'];
    }
    if (!empty($wrapper)) {
      list($pane, $verify) = explode('-', $wrapper);
      if ($verify === 'pane' && !empty($form['panes'][$pane])) {
        return $form['panes'][$pane];
      }
    }
  }

}
