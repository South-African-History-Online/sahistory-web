<?php

namespace Drupal\uc_catalog;

/**
 * Data structure to mimic Drupal's menu system.
 */
class TreeNode {

  protected $tid = 0;
  protected $name = 'Catalog';
  protected $children = [];
  protected $depth = -1;
  protected $sequence = 0;

  /**
   * Constructor.
   */
  public function __construct($term = NULL) {
    if ($term) {
      $this->tid = $term->tid;
      $this->name = $term->name;
      $this->depth = $term->depth;
      $this->sequence = $term->sequence;
    }
  }

  /**
   * Determines if new child is an immediate descendant or not.
   *
   * This function is completely dependent on the structure of the array
   * returned by taxonomy_get_tree(). Each element in the array knows its
   * depth in the tree and the array is a preorder iteration of the logical
   * tree structure. Therefore, if the parameter is more than one level
   * deeper than $this, it should be passed to the last child of $this.
   */
  public function add_child(&$child) {
    if ($child->getDepth() - $this->getDepth() == 1) {
      $this->children[] = $child;
    }
    else {
      $last_child =&$this->children[count($this->children)-1];
      $last_child->add_child($child);
    }
  }

  /**
   * Gets the tid of the term.
   *
   * @return string
   *   The tid of the term.
   */
  public function getTid() {
    return $this->tid;
  }

  /**
   * Sets the tid of the term.
   *
   * @param string $tid
   *   The node's tid.
   *
   * @return $this
   */
  public function setTid(string $tid) {
    $this->tid = $tid;
    return $this;
  }

  /**
   * Gets the name of the term.
   *
   * @return string
   *   The name of the term.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Sets the name of the term.
   *
   * @param string $name
   *   The node's name.
   *
   * @return $this
   */
  public function setName(string $name) {
    $this->name = $name;
    return $this;
  }

  /**
   * Gets the children of the term.
   *
   * @return array
   *   The children of the term.
   */
  public function getChildren() {
    return $this->children;
  }

  /**
   * Sets the children of the term.
   *
   * @param array $children
   *   The node's children.
   *
   * @return $this
   */
  public function setChildren(array $children) {
    $this->children = $children;
    return $this;
  }

  /**
   * Gets the depth of the term.
   *
   * @return string
   *   The name of the term.
   */
  public function getDepth() {
    return $this->depth;
  }

  /**
   * Sets the depth of the term.
   *
   * @param string $depth
   *   The node's name.
   *
   * @return $this
   */
  public function setDepth(string $depth) {
    $this->depth = $depth;
    return $this;
  }

  /**
   * Gets the name of the term.
   *
   * @return string
   *   The name of the term.
   */
  public function getSequence() {
    return $this->sequence;
  }

  /**
   * Sets the name of the term.
   *
   * @param string $name
   *   The node's name.
   *
   * @return $this
   */
  public function setSequence(string $sequence) {
    $this->sequence = $sequence;
    return $this;
  }

}
