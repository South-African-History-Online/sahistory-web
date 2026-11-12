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
   * Status follows the South African calendar day, not the UTC one.
   */
  public function testStatusFollowsSouthAfricanDay(): void {
    $sast = new \DateTimeZone('Africa/Johannesburg');
    $day = static fn(string $d): \DateTime => new \DateTime($d, $sast);

    // 22:30 UTC on the 18th is 00:30 on the 19th in Johannesburg.
    $late = $this->event('2026-11-18T22:30:00');
    $on_the_18th = saho_upcoming_events_status($late, $day('2026-11-18'));
    $this->assertSame('upcoming', $on_the_18th['status']);
    $this->assertSame('Tomorrow', $on_the_18th['label']);
    $this->assertSame(1, $on_the_18th['days_until']);
    $this->assertSame('in-progress', saho_upcoming_events_status($late, $day('2026-11-19'))['status']);
    $this->assertSame('past', saho_upcoming_events_status($late, $day('2026-11-20'))['status']);

    // Multi-day: in progress from start day to end day inclusive.
    $festival = $this->event('2027-06-16T07:00:00', '2027-06-18T15:00:00');
    $this->assertSame('In 6 days', saho_upcoming_events_status($festival, $day('2027-06-10'))['label']);
    $this->assertSame('Upcoming', saho_upcoming_events_status($festival, $day('2027-06-01'))['label']);
    $now = saho_upcoming_events_status($festival, $day('2027-06-18'));
    $this->assertSame('in-progress', $now['status']);
    $this->assertTrue($now['pulse']);
    $this->assertSame('Past event', saho_upcoming_events_status($festival, $day('2027-06-19'))['label']);

    // No start date: past, no when.
    $none = Node::create(['type' => 'upcomingevent', 'title' => 'No date']);
    $this->assertNull(saho_upcoming_events_status($none)['start']);
    $this->assertSame('', saho_upcoming_events_card_meta($none)['when']);
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
