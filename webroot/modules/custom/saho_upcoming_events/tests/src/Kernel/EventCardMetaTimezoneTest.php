<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_upcoming_events\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Event cards show South African time, whatever the stored UTC value is.
 *
 * The block and listing cards used to print the raw UTC storage string, so
 * an 18:00 SAST event read 16:00 on the home page and 18:00 on its page.
 *
 * @group saho_upcoming_events
 */
final class EventCardMetaTimezoneTest extends KernelTestBase {

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
    'taxonomy',
    'saho_refs',
    'saho_tools',
    'saho_upcoming_events',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'filter', 'node']);
    $this->setSetting('saho_canonical_url', 'https://schema.test');
    NodeType::create(['type' => 'upcomingevent', 'name' => 'Upcoming Event'])->save();
    foreach (['field_start_date', 'field_end_date'] as $field) {
      FieldStorageConfig::create([
        'field_name' => $field,
        'entity_type' => 'node',
        'type' => 'datetime',
        'settings' => ['datetime_type' => 'datetime'],
      ])->save();
      FieldConfig::create(['field_name' => $field, 'entity_type' => 'node', 'bundle' => 'upcomingevent'])->save();
    }
  }

  /**
   * Creates an event from UTC storage strings.
   */
  private function event(string $start_utc, ?string $end_utc = NULL): Node {
    $values = ['type' => 'upcomingevent', 'title' => 'Talk', 'field_start_date' => $start_utc];
    if ($end_utc !== NULL) {
      $values['field_end_date'] = $end_utc;
    }
    $node = Node::create($values);
    $node->save();
    return $node;
  }

  /**
   * A 16:00 UTC start is an 18:00 South African start.
   */
  public function testWhenIsSouthAfricanTime(): void {
    $this->assertSame('18 November 2026, 18:00', saho_upcoming_events_card_meta($this->event('2026-11-18T16:00:00'))['when']);
    $this->assertSame('18 November 2026, 18:00 - 20:00', saho_upcoming_events_card_meta($this->event('2026-11-18T16:00:00', '2026-11-18T18:00:00'))['when']);
    $this->assertSame('16 June 2027, 09:00 - 18 June 2027, 17:00', saho_upcoming_events_card_meta($this->event('2027-06-16T07:00:00', '2027-06-18T15:00:00'))['when']);
  }

  /**
   * A late UTC evening already belongs to the next South African day.
   */
  public function testMidnightCrossing(): void {
    $this->assertSame('19 November 2026, 00:30', saho_upcoming_events_card_meta($this->event('2026-11-18T22:30:00'))['when']);
  }

  /**
   * Status follows the South African calendar day.
   */
  public function testStatus(): void {
    $utc = new \DateTimeZone('UTC');
    $fmt = static fn(string $rel): string => (new \DateTime($rel, $utc))->format('Y-m-d\TH:i:s');
    $this->assertSame('past', saho_upcoming_events_card_meta($this->event($fmt('-10 days')))['status']);
    $now = saho_upcoming_events_card_meta($this->event($fmt('-1 day'), $fmt('+1 day')));
    $this->assertSame('in-progress', $now['status']);
    $this->assertTrue($now['pulse']);
    $soon = saho_upcoming_events_card_meta($this->event($fmt('+30 days')));
    $this->assertSame('upcoming', $soon['status']);
    $this->assertSame('Upcoming', $soon['label']);
    $this->assertSame('', saho_upcoming_events_card_meta(Node::create(['type' => 'upcomingevent', 'title' => 'No date']))['when']);
  }

  /**
   * Schema.org dates carry the South African offset.
   */
  public function testSchemaDatesCarryOffset(): void {
    $schema = \Drupal::service('saho_tools.schema_builder.upcoming_event')
      ->build($this->event('2026-11-18T16:00:00', '2026-11-18T18:00:00'));
    $this->assertSame('2026-11-18T18:00:00+02:00', $schema['startDate']);
    $this->assertSame('2026-11-18T20:00:00+02:00', $schema['endDate']);
  }

}
