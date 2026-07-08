<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage\Drush\Commands;

use Drupal\saho_frontpage\HomeLayoutRebuilder;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for the Open Record front page.
 */
final class SahoFrontpageCommands extends DrushCommands {

  public function __construct(
    protected readonly HomeLayoutRebuilder $rebuilder,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('saho_frontpage.home_layout'),
    );
  }

  /**
   * Re-applies the Open Record home layout to node 144647.
   *
   * Safe to run any time: it is a no-op when the layout is already in place,
   * and it discards pending Layout Builder drafts that could republish an
   * older layout.
   */
  #[CLI\Command(name: 'saho:frontpage-rebuild', aliases: ['sfpr'])]
  public function rebuild(): void {
    $this->io()->success($this->rebuilder->rebuild());
  }

}
