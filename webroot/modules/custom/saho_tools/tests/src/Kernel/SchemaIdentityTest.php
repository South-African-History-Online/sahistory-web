<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_tools\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Sitewide entities: one organization, @id-linked, on the canonical host.
 *
 * @group saho_tools
 */
final class SchemaIdentityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'saho_tools',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->setSetting('saho_canonical_url', 'https://schema.test');
  }

  /**
   * The organization entity carries the shared @id on the canonical host.
   */
  public function testOrganizationIdentity(): void {
    $schema = $this->container->get('saho_tools.schema_builder.organization')->build();

    $this->assertSame('https://schema.test/#organization', $schema['@id']);
    $this->assertSame('https://schema.test', $schema['url']);
    $this->assertStringStartsWith('https://schema.test/', $schema['logo']['url']);
    $this->assertStringNotContainsString('ddev.site', json_encode($schema));
  }

  /**
   * The website entity links its publisher to the organization @id.
   */
  public function testWebsiteIdentity(): void {
    $schema = $this->container->get('saho_tools.schema_builder.website')->build();

    $this->assertSame('https://schema.test/#website', $schema['@id']);
    $this->assertSame('https://schema.test/', $schema['url']);
    $this->assertSame('https://schema.test/#organization', $schema['publisher']['@id']);
    $this->assertSame(
      'https://schema.test/search?search_api_fulltext={search_term_string}',
      $schema['potentialAction']['target']['urlTemplate']
    );
  }

  /**
   * Without the settings override, the default is the production apex host.
   */
  public function testDefaultCanonicalHost(): void {
    $this->setSetting('saho_canonical_url', NULL);
    $schema = $this->container->get('saho_tools.schema_builder.website')->build();
    $this->assertSame('https://sahistory.org.za/', $schema['url']);
  }

}
