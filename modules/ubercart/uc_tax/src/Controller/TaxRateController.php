<?php

namespace Drupal\uc_tax\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_tax\TaxRateInterface;

/**
 * Route controller for tax rates.
 */
class TaxRateController extends ControllerBase {

  /**
   * Build the tax rate add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the tax rate.
   *
   * @return array
   *   The tax rate edit form.
   */
  public function addForm($plugin_id) {
    // Create a tax rate configuration entity.
    $entity = $this->entityTypeManager()->getStorage('uc_tax_rate')->create(array('plugin' => $plugin_id));

    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Clones a tax rate.
   *
   * @param \Drupal\uc_tax\TaxRateInterface $uc_tax_rate
   *   The tax rate entity.
   */
  public function saveClone(TaxRateInterface $uc_tax_rate) {
    $name = $uc_tax_rate->label();

    // Tweak the name and unset the rate ID.
    $cloned_rate = $uc_tax_rate->createDuplicate();
    $cloned_rate->setLabel($this->t('Copy of @name', ['@name' => $name]));
    // @todo: Have to check for uniqueness of name first - in case we have
    // cloned this rate before ...
    $cloned_rate->setId($uc_tax_rate->id() . "_clone");

    // Save the new rate without clearing the Rules cache.
    $cloned_rate->save();

    // Clone the associated conditions as well.
    // if ($conditions = rules_config_load('uc_tax_' . $uc_tax_rate->id())) {
    //   $conditions->id = NULL;
    //   $conditions->name = '';
    //   $conditions->save('uc_tax_' . $uc_tax_rate->id());
    // }

    // entity_flush_caches();

    // Display a message and redirect back to the methods page.
    drupal_set_message($this->t('Tax rate %name was cloned.', ['%name' => $name]));

    return $this->redirect('entity.uc_tax_rate.collection');
  }

  /**
   * Performs an operation on the tax rate entity.
   *
   * @param \Drupal\uc_tax\TaxRateInterface $uc_tax_rate
   *   The tax rate entity.
   * @param string $op
   *   The operation to perform, usually 'enable' or 'disable'.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the tax rate listing page.
   */
  public function performOperation(TaxRateInterface $uc_tax_rate, $op) {
    $uc_tax_rate->$op()->save();

    if ($op == 'enable') {
      drupal_set_message($this->t('The %label tax rate has been enabled.', ['%label' => $uc_tax_rate->label()]));
    }
    elseif ($op == 'disable') {
      drupal_set_message($this->t('The %label tax rate has been disabled.', ['%label' => $uc_tax_rate->label()]));
    }

    $url = $uc_tax_rate->toUrl('collection');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
  }

}
