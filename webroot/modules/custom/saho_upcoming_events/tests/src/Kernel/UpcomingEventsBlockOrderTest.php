<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_upcoming_events\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * The Upcoming Events block lists the soonest events, not the furthest.
 *
 * With more than limit * 3 future events published, the old DESC query
 * window never reached the nearest ones and the block showed events a
 * year out while next week's went missing.
 *
 * @group saho_upcoming_events
 */
final class UpcomingEventsBlockOrderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'datetime',
    'views',
    'file',
    'image',
    'saho_upcoming_events',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'filter', 'node']);
    // Anonymous needs to see published nodes for the access-checked query.
    $this->installConfig(['user']);
    user_role_grant_permissions('anonymous', ['access content']);

    NodeType::create(['type' => 'upcomingevent', 'name' => 'Upcoming Event'])->save();
    foreach (['field_start_date', 'field_end_date'] as $field) {
      FieldStorageConfig::create([
        'field_name' => $field,
        'entity_type' => 'node',
        'type' => 'datetime',
        'settings' => ['datetime_type' => 'datetime'],
      ])->save();
      FieldConfig::create([
        'field_name' => $field,
        'entity_type' => 'node',
        'bundle' => 'upcomingevent',
      ])->save();
    }
  }

  /**
   * Creates a published event starting the given number of days from now.
   */
  private function event(string $title, int $start_days, ?int $end_days = NULL): void {
    $format = static fn(int $days): string => (new \DateTime("now +{$days} days", new \DateTimeZone('UTC')))
      ->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
    $values = [
      'type' => 'upcomingevent',
      'title' => $title,
      'status' => 1,
      'field_start_date' => $format($start_days),
    ];
    if ($end_days !== NULL) {
      $values['field_end_date'] = $format($end_days);
    }
    Node::create($values)->save();
  }

  /**
   * Builds the block and returns the listed event titles in order.
   */
  private function titles(int $limit): array {
    $block = \Drupal::service('plugin.manager.block')
      ->createInstance('saho_upcoming_events_block', ['number_of_events' => $limit]);
    $build = $block->build();
    return array_map(static fn($node) => $node->label(), $build['#events'] ?? []);
  }

  /**
   * Soonest events come first even when many more are published.
   */
  public function testSoonestEventsFirst(): void {
    $this->event('Long gone', -400, -398);
    $this->event('Ended yesterday', -3, -1);
    // Twelve future events, well past a limit-of-3 window of nine.
    for ($i = 1; $i <= 12; $i++) {
      $this->event("In {$i}0 days", $i * 10);
    }

    $this->assertSame(['In 10 days', 'In 20 days', 'In 30 days'], $this->titles(3));
    $this->assertSame(['In 10 days', 'In 20 days', 'In 30 days', 'In 40 days'], $this->titles(4));
  }

  /**
   * A multi-day event that already started leads, ahead of the soonest.
   */
  public function testHappeningNowLeads(): void {
    $this->event('Festival week', -2, 3);
    $this->event('Tomorrow talk', 1);
    $this->event('Next month', 30);
    $this->event('Ended yesterday', -3, -1);

    $this->assertSame(['Festival week', 'Tomorrow talk', 'Next month'], $this->titles(3));
  }

  /**
   * Nothing upcoming collapses the block.
   */
  public function testEmptyWhenNothingUpcoming(): void {
    $this->event('Long gone', -400, -398);
    $this->assertSame([], $this->titles(3));
  }

}
