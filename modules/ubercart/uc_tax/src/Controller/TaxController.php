<?php

namespace Drupal\uc_tax\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\uc_tax\Entity\TaxRate;

/**
 * Controller routines for tax routes.
 */
class TaxController extends ControllerBase {

  /**
   * Clones a tax rate.
   */
  public function saveClone($uc_tax_rate) {
    // Load the source rate entity.
    $rate = TaxRate::load($uc_tax_rate);
    $name = $rate->label();

    // Tweak the name and unset the rate ID.
    $cloned_rate = $rate->createDuplicate();
    $cloned_rate->setLabel($this->t('Copy of @name', ['@name' => $name]));
    $cloned_rate->setId($rate->id() . "_clone");

    // Save the new rate without clearing the Rules cache.
    $cloned_rate->save();

    // Clone the associated conditions as well.
    // if ($conditions = rules_config_load('uc_tax_' . $rate->id())) {
    //   $conditions->id = NULL;
    //   $conditions->name = '';
    //   $conditions->save('uc_tax_' . $rate->id());
    // }

    // entity_flush_caches();

    // Display a message and redirect back to the methods page.
    drupal_set_message($this->t('Tax rate %name was cloned.', ['%name' => $name]));

    return $this->redirect('entity.uc_tax_rate.collection');
  }

}
