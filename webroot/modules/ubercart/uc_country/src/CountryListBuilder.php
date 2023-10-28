<?php

namespace Drupal\uc_country;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a listing of countries.
 */
class CountryListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * All countries on one page so sorting works properly.
   * @TODO make sorting work properly in a paged view.
   */
  protected $limit = 250;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Name');
    $header['code'] = $this->t('Code');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = $entity->label();
    $row['code'] = $entity->id();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
//  protected function getEntityIds() {
    // EntityListBuilder parent class ignores the config entity's sort() method
    // and instead re-sorts by Id. We override that here to sort by status
    // first so that enabled countries show at the top of the list, then by
    // label second.
//    $query = $this->getStorage()->getQuery();
//    $keys = $this->entityType->getKeys();
//    return $query
//      ->sort($keys['status'])
//      ->pager($this->limit)
//      ->execute();
//  }

  /**
   * Adds some descriptive text to our entity list.
   *
   * @return array
   *   Renderable array.
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t("<p>This is a list of the countries currently"
        . " defined for use on your Drupal site. This country data adheres to"
        . " the @iso standard for country and zone naming used by payment"
        . " providers and package couriers.</p>"
        . "<p>To make a country available for use at checkout or in a user's"
        . " address book, 'Enable' the country using the widget in the"
        . " 'Operations' for that country. You may also 'Disable' a country to"
        . " prevent customers from selecting that country as a billing or"
        . " shipping address.</p>"
        . "<p>You may also use the 'Edit' widget in the 'Operations' column to"
        . " edit a country's name or address format.</p>",
        ['@iso' => Link::fromTextAndUrl('ISO 3166', Url::fromUri('http://en.wikipedia.org/wiki/ISO_3166'))->toString()]
      ),
    );
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No countries have been configured yet.');
    return $build;
  }
}
