<?php

namespace Drupal\uc_fulfillment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_fulfillment\FulfillmentMethodInterface;

/**
 * Route controller for fulfillment methods.
 */
class FulfillmentMethodController extends ControllerBase {

  /**
   * Build the fulfillment method add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the fulfillment method.
   *
   * @return array
   *   The fulfillment method instance edit form.
   */
  public function addForm($plugin_id) {
    // Create a fulfillment method configuration entity.
    $entity = $this->entityTypeManager()->getStorage('uc_fulfillment_method')->create(array('plugin' => $plugin_id));

    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Performs an operation on the fulfillment method entity.
   *
   * @param \Drupal\uc_fulfillment\FulfillmentMethodInterface $uc_fulfillment_method
   *   The fulfillment method entity.
   * @param string $op
   *   The operation to perform, usually 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the fulfillment method listing page.
   */
  public function performOperation(FulfillmentMethodInterface $uc_fulfillment_method, $op) {
    $uc_fulfillment_method->$op()->save();

    if ($op == 'enable') {
      drupal_set_message($this->t('The %label fulfillment method has been enabled.', ['%label' => $uc_fulfillment_method->label()]));
    }
    elseif ($op == 'disable') {
      drupal_set_message($this->t('The %label fulfillment method has been disabled.', ['%label' => $uc_fulfillment_method->label()]));
    }

    $url = $uc_fulfillment_method->toUrl('collection');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

}
