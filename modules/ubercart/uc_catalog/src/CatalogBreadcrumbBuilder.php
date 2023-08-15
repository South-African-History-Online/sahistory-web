<?php

namespace Drupal\uc_catalog;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Link;

/**
 * Provides a custom breadcrumb builder for catalog node and listing pages.
 */
class CatalogBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\Taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * Constructs a new CatalogBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    return $route_name == 'entity.node.canonical' && $route_match->getParameter('node') && !empty($route_match->getParameter('node')->taxonomy_catalog->target_id)
        || (substr($route_name, 0, 16) == 'view.uc_catalog.' && $route_match->getParameter('arg_0'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);

    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Catalog'), 'view.uc_catalog.page_1'));

    if ($route_match->getRouteName() == 'entity.node.canonical') {
      // Extract term ID for node view.
      $tid = $route_match->getParameter('node')->taxonomy_catalog->target_id;
      $skip_last = FALSE;
    }
    else {
      // Get term ID argument for catalog view, and skip the last term.
      $tid = $route_match->getParameter('arg_0');
      $skip_last = TRUE;
    }

    if ($parents = $this->termStorage->loadAllParents($tid)) {
      if ($skip_last) {
        array_shift($parents);
      }
      $parents = array_reverse($parents);
      foreach ($parents as $parent) {
        $breadcrumb->addCacheableDependency($parent);
        $breadcrumb->addLink(Link::createFromRoute($parent->label(), 'view.uc_catalog.page_1', ['arg_0' => $parent->id()]));
      }
    }

    return $breadcrumb;
  }

}
