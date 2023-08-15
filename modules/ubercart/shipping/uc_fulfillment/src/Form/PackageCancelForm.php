<?php

namespace Drupal\uc_fulfillment\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_fulfillment\PackageInterface;
use Drupal\uc_fulfillment\Shipment;
use Drupal\uc_order\OrderInterface;

/**
 * Confirms cancellation of a package's shipment.
 */
class PackageCancelForm extends ConfirmFormBase {

  /**
   * The order id.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order_id;

  /**
   * The package.
   *
   * @var \Drupal\uc_fulfillment\PackageInterface
   */
  protected $package;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_fulfillment_package_cancel_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to cancel the shipment of this package?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('It will be available for reshipment.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Cancel shipment');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Nevermind');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('uc_fulfillment.packages', ['uc_order' => $this->order_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL, PackageInterface $uc_package = NULL) {
    $this->order_id = $uc_order->id();
    $this->package = $uc_package;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $shipment = Shipment::load($this->package->getSid());
    $methods = \Drupal::moduleHandler()->invokeAll('uc_fulfillment_method');
    // The notion of "cancel" is specific to the fulfillment method, therefore
    // we delegate all the work to the plugin.
    // @todo: Replace this with calls to the plugin cancel method instead of hooks.
    if (isset($methods[$shipment->getShippingMethod()]['cancel']) &&
        function_exists($methods[$shipment->getShippingMethod()]['cancel'])) {
      $result = call_user_func($methods[$shipment->getShippingMethod()]['cancel'], $shipment->getTrackingNumber(), array($this->package->getTrackingNumber()));
      if ($result) {
        db_update('uc_packages')
          ->fields(array(
            'sid' => NULL,
            'label_image' => NULL,
            'tracking_number' => NULL,
          ))
          ->condition('package_id', $this->package->id())
          ->execute();

        if ($this->package->getLabelImage()) {
          file_usage_delete($this->package->getLabelImage(), 'uc_fulfillment', 'package', $this->package->id());
          file_delete($this->package->getLabelImage());
          $this->package->setLabelImage('');
        }

        unset($shipment->packages[$this->package->id()]);
        if (!count($shipment->getPackages())) {
          $shipment->delete();
        }
      }
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
