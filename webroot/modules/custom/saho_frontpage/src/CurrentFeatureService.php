<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Views;

/**
 * Resolves the site's current editorial feature.
 *
 * One engine for every surface that leads with "the current feature" (the
 * front-page editorial block, the /featured register hero): the first row
 * of the curated "Front page content" view - driven by the
 * field_home_page_feature* flags, bundles sorted DESC then changed DESC -
 * falling back to the newest featured biography so the lead never renders
 * blank. Callers own their cache metadata (tags node_list + node:{id}).
 */
class CurrentFeatureService {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Loads the curated feature node, or the newest featured biography.
   */
  public function node(): ?NodeInterface {
    $view = Views::getView('front_page_content');
    if ($view !== NULL) {
      $view->setDisplay('default');
      $view->setItemsPerPage(1);
      $view->execute();
      $entity = $view->result[0]->_entity ?? NULL;
      if ($entity instanceof NodeInterface) {
        return $entity;
      }
    }
    $storage = $this->entityTypeManager->getStorage('node');
    $nids = $storage->getQuery()
      ->condition('type', 'biography')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();
    $node = $nids !== [] ? $storage->load(reset($nids)) : NULL;
    return $node instanceof NodeInterface ? $node : NULL;
  }

  /**
   * Builds a plain-text standfirst from the node summary or body.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The feature node.
   * @param int $length
   *   Maximum length, truncated on a word boundary with an ellipsis.
   */
  public function standfirst(NodeInterface $node, int $length = 280): ?string {
    if (!$node->hasField('body') || $node->get('body')->isEmpty()) {
      return NULL;
    }
    $item = $node->get('body')->first();
    $summary = trim((string) $item->get('summary')->getValue());
    $value = trim((string) $item->get('value')->getValue());
    $text = $summary !== '' ? $summary : $value;
    $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5));
    if ($text === '') {
      return NULL;
    }
    return Unicode::truncate($text, $length, TRUE, TRUE);
  }

}
