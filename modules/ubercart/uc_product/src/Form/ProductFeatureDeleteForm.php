<?php

namespace Drupal\uc_product\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Defines the attribute option delete form.
 */
class ProductFeatureDeleteForm extends ConfirmFormBase {

  /**
   * The node that is the feature is to be deleted from.
   */
  protected $node;

  /**
   * The feature type to be deleted.
   */
  protected $featureId;

  /**
   * The feature to be deleted.
   */
  protected $feature;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this %feature?', ['%feature' => uc_product_feature_data($this->featureId, 'title')]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->feature['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('uc_product.features', ['node' => $this->node->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_product_feature_confirm_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL, $fid = NULL, $pfid = NULL) {
    $this->node = $node;
    $this->featureId = $fid;
    $this->feature = uc_product_feature_load($pfid);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_product_feature_delete($this->feature['pfid']);
    drupal_set_message($this->t('The product feature has been deleted.'));
    $form_state->setRedirect('uc_product.features', ['node' => $this->node->id()]);
  }

}
