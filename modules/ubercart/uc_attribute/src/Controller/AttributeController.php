<?php

namespace Drupal\uc_attribute\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller routines for product attribute routes.
 */
class AttributeController extends ControllerBase {

  /**
   * Displays a paged list and overview of existing product attributes.
   */
  public function overview() {
    $header = array(
      array('data' => $this->t('Name'), 'field' => 'a.name', 'sort' => 'asc'),
      array('data' => $this->t('Label'), 'field' => 'a.label'),
      $this->t('Required'),
      array('data' => $this->t('List position'), 'field' => 'a.ordering'),
      $this->t('Number of options'),
      $this->t('Display type'),
      array('data' => $this->t('Operations'), 'colspan' => 1),
    );

    $display_types = _uc_attribute_display_types();

    $query = db_select('uc_attributes', 'a')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->fields('a', array('aid', 'name', 'label', 'required', 'ordering', 'display'))
      ->orderByHeader($header)
      ->limit(30);

    $build['attributes'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('No product attributes have been added yet.'),
    );

    $result = $query->execute();
    foreach ($result as $attr) {
      $attr->options = db_query('SELECT COUNT(*) FROM {uc_attribute_options} WHERE aid = :aid', [':aid' => $attr->aid])->fetchField();
      if (empty($attr->label)) {
        $attr->label = $attr->name;
      }
      $build['attributes'][] = array(
        'name' => array('#plain_text' => $attr->name),
        'label' => array('#plain_text' => $attr->label),
        'required' => array(
          '#plain_text' => $attr->required == 1 ? $this->t('Yes') : $this->t('No'),
        ),
        'ordering' => array('#markup' => $attr->ordering),
        'options' => array('#markup' => $attr->options),
        'display' => array('#markup' => $display_types[$attr->display]),
        'operations' => array(
          '#type' => 'operations',
          '#links' => array(
            'edit' => array(
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('uc_attribute.edit', ['aid' => $attr->aid]),
            ),
            'options' => array(
              'title' => $this->t('Options'),
              'url' => Url::fromRoute('uc_attribute.options', ['aid' => $attr->aid]),
            ),
            'delete' => array(
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('uc_attribute.delete', ['aid' => $attr->aid]),
            ),
          ),
        ),
      );
    }

    $build['pager'] = array(
      '#type' => 'pager',
    );

    return $build;
  }

}
