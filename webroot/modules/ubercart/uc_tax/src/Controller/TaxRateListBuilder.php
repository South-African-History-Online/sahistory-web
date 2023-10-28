<?php

namespace Drupal\uc_tax\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of uc_tax_rate configuration entities.
 */
class TaxRateListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['rate'] = $this->t('Rate');
    $header['shippable'] = $this->t('Taxed products');
    $header['product_types'] = $this->t('Taxed product types');
    $header['line_item_types'] = $this->t('Taxed line items');
    $header['weight'] = $this->t('Weight');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    $row['rate'] = ((float) $entity->getRate() * 100) . '%' ;
    $row['shippable'] = $entity->isForShippable() ? $this->t('Shippable products') : $this->t('Any product');
    $row['product_types'] = implode(', ', $entity->getProductTypes());
    $row['line_item_types'] = implode(', ', $entity->getLineItemTypes());
    $row['weight'] = $entity->getWeight();
//    $row['weight'] = array(
//      '#type' => 'weight',
//      '#default_value' => $entity->getWeight(),
//      '#attributes' => array('class' => array('uc-tax-method-weight')),
//    );

    //$row['weight']['#attributes'] = array('class' => array('uc-quote-method-weight'));

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = parent::buildOperations($entity);
    $build['#links']['clone'] = array(
      'title' => $this->t('Clone'),
      'url' => Url::fromRoute('entity.uc_tax_rate.clone', ['uc_tax_rate' => $entity->id()]),
      'weight' => 10, // 'edit' is 0, 'delete' is 100
    );

    uasort($build['#links'], 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>This is a list of the tax rates currently"
        . " defined on your Drupal site.</p><p>You may use the 'Add a tax rate'"
        . " button to add a new rate, or use the widget in the 'Operations'"
        . " column to edit, delete, or clone existing tax rates.</p>"),
    );
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No tax rates have been configured yet.');
    $build['table']['#tabledrag'] = array(array(
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'uc-tax-method-weight',
    ));
    return $build;
  }

}
