<?php

declare(strict_types=1);

namespace Drupal\saho_classroom\Drush\Commands;

use Drupal\saho_classroom\DeckSync;
use Drupal\saho_classroom\TermSeeder;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for reproducibly syncing classroom presentation decks.
 */
final class DeckSyncCommands extends DrushCommands {

  /**
   * Constructs the command set.
   *
   * @param \Drupal\saho_classroom\TermSeeder $termSeeder
   *   The taxonomy term seeder.
   * @param \Drupal\saho_classroom\DeckSync $deckSync
   *   The deck sync service.
   */
  public function __construct(
    private readonly TermSeeder $termSeeder,
    private readonly DeckSync $deckSync,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('saho_classroom.term_seeder'),
      $container->get('saho_classroom.deck_sync'),
    );
  }

  /**
   * Seeds classroom terms, then syncs presentation decks into nodes.
   */
  #[CLI\Command(name: 'saho_classroom:sync-decks', aliases: ['sc:sync'])]
  #[CLI\Usage(name: 'drush sc:sync', description: 'Seed classroom taxonomy, then create/update presentation nodes from every content/**/*.slides.json deck. Idempotent.')]
  public function syncDecks(): void {
    $terms = $this->termSeeder->seed();
    $this->logger()->success(dt('Terms seeded: @c created, @e already present.', [
      '@c' => $terms['created'],
      '@e' => $terms['existing'],
    ]));
    $summary = $this->deckSync->syncAll();
    foreach ($summary['decks'] as $line) {
      $this->io()->writeln('  ' . $line);
    }
    $this->logger()->success(dt('Decks synced: @c created, @u updated, @s skipped.', [
      '@c' => $summary['created'],
      '@u' => $summary['updated'],
      '@s' => $summary['skipped'],
    ]));
  }

}
