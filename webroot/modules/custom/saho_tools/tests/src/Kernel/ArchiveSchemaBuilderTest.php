<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_tools\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Archive schema: Catalyst provenance mapping and stable identity.
 *
 * @group saho_tools
 */
final class ArchiveSchemaBuilderTest extends KernelTestBase {

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
    'link',
    'datetime',
    'options',
    'saho_refs',
    'saho_tools',
  ];

  /**
   * The builder under test.
   *
   * @var \Drupal\saho_tools\Service\Builder\ArchiveSchemaBuilder
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'filter', 'node']);
    $this->setSetting('saho_canonical_url', 'https://schema.test');

    NodeType::create(['type' => 'archive', 'name' => 'Archive'])->save();

    $fields = [
      ['field_original_creator', 'string', -1, []],
      ['field_original_creator_role', 'list_string', -1,
        [
          'allowed_values' => [
            'photographer' => 'Photographer',
            'institution' => 'Institution',
            'producer' => 'Producer',
          ],
        ],
      ],
      ['field_original_source_url', 'link', -1, []],
      ['field_original_created_date', 'datetime', 1, ['datetime_type' => 'date']],
      ['field_provenance_note', 'text_long', 1, []],
      ['field_archive_publication_date', 'datetime', 1, ['datetime_type' => 'date']],
      ['field_publication_date_archive', 'string', 1, []],
      ['field_author', 'string', -1, []],
      ['field_copyright', 'string', 1, []],
    ];
    foreach ($fields as [$name, $type, $cardinality, $settings]) {
      FieldStorageConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'type' => $type,
        'cardinality' => $cardinality,
        'settings' => $settings,
      ])->save();
      FieldConfig::create([
        'field_name' => $name,
        'entity_type' => 'node',
        'bundle' => 'archive',
      ])->save();
    }

    $this->builder = $this->container->get('saho_tools.schema_builder.archive');
  }

  /**
   * Creates an archive node with the given field values.
   */
  protected function archiveNode(array $values): Node {
    $node = Node::create(['type' => 'archive', 'title' => 'Test record'] + $values);
    $node->save();
    return $node;
  }

  /**
   * Creators pair with roles by delta; institutions become organizations.
   */
  public function testCreatorRolePairing(): void {
    $node = $this->archiveNode([
      'field_original_creator' => [['value' => 'Everett, Michael'], ['value' => 'LAPL']],
      'field_original_creator_role' => [['value' => 'photographer'], ['value' => 'institution']],
    ]);
    $schema = $this->builder->build($node);

    $this->assertSame([
      [
        '@type' => 'Role',
        'roleName' => 'photographer',
        'creator' => ['@type' => 'Person', 'name' => 'Everett, Michael'],
      ],
      [
        '@type' => 'Role',
        'roleName' => 'institution',
        'creator' => ['@type' => 'Organization', 'name' => 'LAPL'],
      ],
    ], $schema['creator']);
  }

  /**
   * Creators without roles are bare Persons; author is the next fallback.
   */
  public function testCreatorFallbackChain(): void {
    $bare = $this->builder->build($this->archiveNode([
      'field_original_creator' => [['value' => 'Horn, Pat']],
    ]));
    $this->assertSame(['@type' => 'Person', 'name' => 'Horn, Pat'], $bare['creator']);

    $author = $this->builder->build($this->archiveNode([
      'field_author' => [['value' => 'Dirk Kohnert']],
    ]));
    $this->assertSame(['@type' => 'Person', 'name' => 'Dirk Kohnert'], $author['creator']);

    $none = $this->builder->build($this->archiveNode([]));
    $this->assertSame('https://schema.test/#organization', $none['creator']['@id']);
    $this->assertSame('South African History Online', $none['creator']['name']);
  }

  /**
   * Provider pages become sameAs; persistent IDs become typed identifiers.
   */
  public function testSourceUrlSplit(): void {
    $node = $this->archiveNode([
      'field_original_source_url' => [
        ['uri' => 'https://archive.org/details/item1', 'title' => 'Original record'],
        ['uri' => 'https://doi.org/10.2307/2637233', 'title' => ''],
      ],
    ]);
    $schema = $this->builder->build($node);

    $this->assertSame('https://archive.org/details/item1', $schema['sameAs']);
    $identifiers = $schema['identifier'];
    $this->assertCount(2, $identifiers);
    $this->assertSame('SAHO', $identifiers[0]['propertyID']);
    $this->assertMatchesRegularExpression('/^R-\d{7}$/', $identifiers[0]['value']);
    $this->assertSame('doi', $identifiers[1]['propertyID']);
    $this->assertSame('https://doi.org/10.2307/2637233', $identifiers[1]['value']);
  }

  /**
   * Publication date: structured slot wins, free text normalises, prose out.
   */
  public function testDatePublishedChain(): void {
    $structured = $this->builder->build($this->archiveNode([
      'field_archive_publication_date' => [['value' => '1976-06-16']],
      'field_publication_date_archive' => [['value' => 'circa 1918']],
    ]));
    $this->assertSame('1976-06-16', $structured['datePublished']);

    $partial = $this->builder->build($this->archiveNode([
      'field_publication_date_archive' => [['value' => '1993-12-00']],
    ]));
    $this->assertSame('1993-12', $partial['datePublished']);

    $prose = $this->builder->build($this->archiveNode([
      'field_publication_date_archive' => [['value' => 'circa 1918']],
    ]));
    $this->assertArrayNotHasKey('datePublished', $prose);
  }

  /**
   * The provenance note becomes creditText with markup and markers stripped.
   */
  public function testCreditText(): void {
    $node = $this->archiveNode([
      'field_original_created_date' => [['value' => '1976-06-16']],
      'field_provenance_note' => [
        [
          'value' => '<p>Original material from DPLA.</p> [saho_import:dpla-2026] ',
          'format' => 'plain_text',
        ],
      ],
      'field_copyright' => [['value' => 'Rights reserved by LAPL']],
    ]);
    $schema = $this->builder->build($node);

    $this->assertSame('Original material from DPLA.', $schema['creditText']);
    $this->assertSame('Rights reserved by LAPL', $schema['copyrightNotice']);
    $this->assertSame('1976-06-16', $schema['dateCreated']);
  }

  /**
   * Identity: @id on the /ref/ URL, url on the canonical alias, org stubs.
   */
  public function testStableIdentity(): void {
    $node = $this->archiveNode([]);
    $schema = $this->builder->build($node);

    $expected_ref = sprintf('https://schema.test/ref/R-%07d', $node->id());
    $this->assertSame($expected_ref, $schema['@id']);
    $this->assertStringStartsWith('https://schema.test/', $schema['url']);
    $this->assertNotSame($schema['@id'], $schema['url']);
    $this->assertSame('https://schema.test/#organization', $schema['publisher']['@id']);
    $this->assertSame('ArchiveOrganization', $schema['holdingArchive']['@type']);
    $this->assertSame('https://schema.test/#organization', $schema['holdingArchive']['@id']);
    $this->assertStringNotContainsString('ddev.site', json_encode($schema));
  }

}
