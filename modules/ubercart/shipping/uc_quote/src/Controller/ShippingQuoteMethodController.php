<?php

namespace Drupal\uc_quote\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_quote\ShippingQuoteMethodInterface;

/**
 * Route controller for shipping quote methods.
 */
class ShippingQuoteMethodController extends ControllerBase {

  /**
   * Build the shipping quote method add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the shipping quote.
   *
   * @return array
   *   The shipping quote method edit form.
   */
  public function addForm($plugin_id) {
    // Create a shipping quote configuration entity.
    $entity = $this->entityTypeManager()->getStorage('uc_quote_method')->create(array('plugin' => $plugin_id));

    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Performs an operation on the shipping quote method entity.
   *
   * @param \Drupal\uc_quote\ShippingQuoteMethodInterface $uc_quote_method
   *   The shipping quote method entity.
   * @param string $op
   *   The operation to perform, usually 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the shipping quote method listing page.
   */
  public function performOperation(ShippingQuoteMethodInterface $uc_quote_method, $op) {
    $uc_quote_method->$op()->save();

    if ($op == 'enable') {
      drupal_set_message($this->t('The %label shipping method has been enabled.', ['%label' => $uc_quote_method->label()]));
    }
    elseif ($op == 'disable') {
      drupal_set_message($this->t('The %label shipping method has been disabled.', ['%label' => $uc_quote_method->label()]));
    }

    $url = $uc_quote_method->toUrl('collection');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

}
