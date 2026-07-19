<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_tools\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Biography schema: date fallback chain, topics and stable identity.
 *
 * @group saho_tools
 */
final class BiographySchemaBuilderTest extends KernelTestBase {

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
    'taxonomy',
    'saho_refs',
    'saho_tools',
  ];

  /**
   * The builder under test.
   *
   * @var \Drupal\saho_tools\Service\Builder\BiographySchemaBuilder
   */
  protected $builder;

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

    NodeType::create(['type' => 'biography', 'name' => 'Biography'])->save();
    Vocabulary::create(['vid' => 'people_category', 'name' => 'People category'])->save();

    $fields = [
      ['field_drupal_birth_date', 'datetime', ['datetime_type' => 'date'], []],
      ['field_drupal_death_date', 'datetime', ['datetime_type' => 'date'], []],
      ['field_dob', 'string', [], []],
      ['field_dod', 'string', [], []],
      ['field_people_category', 'entity_reference', ['target_type' => 'taxonomy_term'], []],
    ];
    foreach ($fields as [$name, $type, $storage_settings, $field_settings]) {
      FieldStorageConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'type' => $type,
        'cardinality' => $type === 'entity_reference' ? -1 : 1,
        'settings' => $storage_settings,
      ])->save();
      FieldConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'bundle' => 'biography',
        'settings' => $field_settings,
      ])->save();
    }

    $this->builder = $this->container->get('saho_tools.schema_builder.biography');
  }

  /**
   * Creates a biography node with the given field values.
   */
  protected function bio(array $values): Node {
    $node = Node::create(['type' => 'biography', 'title' => 'Test Person'] + $values);
    $node->save();
    return $node;
  }

  /**
   * The structured datetime field wins over the legacy string field.
   */
  public function testStructuredDateWins(): void {
    $schema = $this->builder->build($this->bio([
      'field_drupal_birth_date' => [['value' => '1925-09-19']],
      'field_dob' => [['value' => '1-January-1900']],
    ]));
    $this->assertSame('1925-09-19', $schema['mainEntity']['birthDate']);
  }

  /**
   * Legacy field_dob/field_dod shapes normalise when no structured date.
   */
  public function testLegacyDateFallback(): void {
    $schema = $this->builder->build($this->bio([
      'field_dob' => [['value' => '19-September-1925']],
      'field_dod' => [['value' => '1977']],
    ]));
    $this->assertSame('1925-09-19', $schema['mainEntity']['birthDate']);
    $this->assertSame('1977', $schema['mainEntity']['deathDate']);
  }

  /**
   * Prose dates are omitted, never emitted verbatim.
   */
  public function testProseDatesOmitted(): void {
    $schema = $this->builder->build($this->bio([
      'field_dob' => [['value' => 'circa 1918']],
    ]));
    $this->assertArrayNotHasKey('birthDate', $schema['mainEntity']);
  }

  /**
   * People categories feed knowsAbout, deduplicated.
   */
  public function testPeopleCategoryTopics(): void {
    $term_a = Term::create(['vid' => 'people_category', 'name' => 'Activist']);
    $term_a->save();
    $term_b = Term::create(['vid' => 'people_category', 'name' => 'Author']);
    $term_b->save();
    $schema = $this->builder->build($this->bio([
      'field_people_category' => [
        ['target_id' => $term_a->id()],
        ['target_id' => $term_b->id()],
        ['target_id' => $term_a->id()],
      ],
    ]));
    $this->assertSame(['Activist', 'Author'], $schema['mainEntity']['knowsAbout']);
  }

  /**
   * ProfilePage @id sits on the /ref/ URL; the Person gets its own anchor.
   */
  public function testStableIdentity(): void {
    $node = $this->bio([]);
    $schema = $this->builder->build($node);

    $ref = sprintf('https://schema.test/ref/B-%07d', $node->id());
    $this->assertSame('ProfilePage', $schema['@type']);
    $this->assertSame($ref, $schema['@id']);
    $this->assertSame($ref . '#person', $schema['mainEntity']['@id']);
    $this->assertSame('SAHO', $schema['identifier']['propertyID']);
    $this->assertStringNotContainsString('ddev.site', json_encode($schema));
  }

}
