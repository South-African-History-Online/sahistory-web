<?php

namespace Drupal\uc_store\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command wrapper for commands that have already been rendered.
 */
class CommandWrapper implements CommandInterface {

  /**
   * The command.
   *
   * @var array
   */
  protected $command;

  /**
   * Constructs an CommandWrapper object.
   *
   * @param array $command
   *   The command.
   */
  public function __construct(array $command) {
    $this->command = $command;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return $this->command;
  }

}
