<?php

namespace Drupal\uc_catalog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\TermInterface;

/**
 * Controller routines for catalog routes.
 */
class CatalogController extends ControllerBase {

  /**
   * Returns forum page for a given forum.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The forum to render the page for.
   *
   * @return array
   *   A render array.
   */
  public function catalogPage(TermInterface $taxonomy_term) {
    // Get forum details.
    $taxonomy_term->forums = $this->forumManager->getChildren($this->config('forum.settings')->get('vocabulary'), $taxonomy_term->id());
    $taxonomy_term->parents = $this->forumManager->getParents($taxonomy_term->id());

    if (empty($taxonomy_term->forum_container->value)) {
      $build = $this->forumManager->getTopics($taxonomy_term->id(), $this->currentUser());
      $topics = $build['topics'];
      $header = $build['header'];
    }
    else {
      $topics = '';
      $header = array();
    }
    return $this->build($taxonomy_term->forums, $taxonomy_term, $topics, $taxonomy_term->parents, $header);
  }

}
