<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Resolves the site's current editorial feature.
 *
 * One engine for every surface that leads with "the current feature" (the
 * front-page editorial block, the /featured register hero): the newest
 * published node flagged with the "Home Page Feature" field
 * (field_home_page_feature) - the editors' explicit front-page flag, not the
 * per-section feature flags - falling back to the newest featured biography
 * so the lead never renders blank. Callers own their cache metadata (tags
 * node_list + node:{id}).
 */
class CurrentFeatureService {

  /**
   * Editorial bundles eligible to lead the front page.
   *
   * The field_home_page_feature flag is shared with the TDIH event pool, so
   * the hero query must exclude events (and images) or the newest flagged
   * event would hijack the lead. Only these bundles carry a real standfirst.
   */
  private const EDITORIAL_BUNDLES = ['article', 'archive', 'biography'];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Loads the curated feature node, or the newest featured biography.
   */
  public function node(): ?NodeInterface {
    $storage = $this->entityTypeManager->getStorage('node');
    $nids = $storage->getQuery()
      ->condition('field_home_page_feature', 1)
      ->condition('type', self::EDITORIAL_BUNDLES, 'IN')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();
    if ($nids !== []) {
      $node = $storage->load(reset($nids));
      if ($node instanceof NodeInterface) {
        return $node;
      }
    }
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
