<?php

namespace Drupal\saho_tools\Service;

use Drupal\node\NodeInterface;

/**
 * Interface for Schema.org builders.
 *
 * Each content type should have its own builder implementing this interface
 * to generate appropriate Schema.org JSON-LD structured data.
 */
interface SchemaOrgBuilderInterface {

  /**
   * Build Schema.org structured data for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity to build schema for.
   *
   * @return array
   *   Schema.org structured data as an associative array.
   *   Returns empty array if this builder doesn't handle the node type.
   */
  public function build(NodeInterface $node): array;

  /**
   * Check if this builder supports the given node type.
   *
   * @param string $node_type
   *   The node bundle/type to check.
   *
   * @return bool
   *   TRUE if this builder handles the node type, FALSE otherwise.
   */
  public function supports(string $node_type): bool;

}
