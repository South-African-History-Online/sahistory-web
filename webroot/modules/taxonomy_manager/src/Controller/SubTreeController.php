<?php

namespace Drupal\taxonomy_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\taxonomy_manager\Element\TaxonomyManagerTree;

/**
 * Exposes a list of terms to any JS library via JSON.
 *
 * @package Drupal\taxonomy_manager\Controller
 */
class SubTreeController extends ControllerBase {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a SubTreeController object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct(Request $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * JSON callback for subtree.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON object with list of terms.
   */
  public function json() {
    $list = [];
    $parent = $this->request->get('parent');

    $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load($parent);
    if ($term) {
      $taxonomy_vocabulary = $this->entityTypeManager()->getStorage('taxonomy_vocabulary')->load($term->bundle());
      if ($taxonomy_vocabulary) {
        $terms = TaxonomyManagerTree::loadTerms($taxonomy_vocabulary, $parent);
        $list = TaxonomyManagerTree::getNestedListJsonArray($terms);
      }
    }
    return new JsonResponse($list);
  }

  /**
   * Gets term tids key strings including child itself.
   *
   * Used by fancytree.loadKeyPath.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON object with list of terms.
   *
   * @see js/tree.js
   * @see $tree.loadKeyPath
   */
  public function jsonChildParentsString() {
    $data = [];
    $childTid = $this->request->get('tid');
    /** @var \Drupal\taxonomy\TermStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage("taxonomy_term");
    $childTerm = $storage->load($childTid);
    if ($childTerm) {
      $terms = $storage->loadAllParents($childTid);
      $terms[$childTid] = $childTerm;
      $tids = array_keys($terms);
      $tids = array_reverse($tids);
      $data = [
        'path' => implode('/', $tids),
      ];
    }
    return new JsonResponse($data);
  }

}
