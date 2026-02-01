<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_tools\Service\SchemaOrgBuilderInterface;

/**
 * Builds Schema.org Organization structured data for SAHO site-wide.
 *
 * Provides comprehensive organization information for search engines
 * and AI systems to understand SAHO's identity and mission.
 */
class OrganizationSchemaBuilder implements SchemaOrgBuilderInterface {

  /**
   * Constructs an OrganizationSchemaBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function supports(string $node_type): bool {
    // This builder is for site-wide organization schema, not node-specific.
    return $node_type === 'organization';
  }

  /**
   * {@inheritdoc}
   */
  public function build(?NodeInterface $node = NULL): array {
    $request = \Drupal::request();
    $base_url = $request->getSchemeAndHttpHost();

    $schema = [
      '@context' => 'https://schema.org',
      '@type' => ['Organization', 'EducationalOrganization'],
      'name' => 'South African History Online',
      'alternateName' => 'SAHO',
      'legalName' => 'South African History Online',
      'url' => $base_url,
      'description' => 'South African History Online (SAHO) is a non-profit organization dedicated to making South African history accessible to all. We provide free, educational resources about South African history, including articles, biographies, archives, and educational materials.',
      'foundingDate' => '2000',
      'slogan' => 'Towards a people\'s history',
    ];

    // Add logo.
    $schema['logo'] = [
      '@type' => 'ImageObject',
      'url' => $base_url . '/themes/custom/saho/logo.png',
      'contentUrl' => $base_url . '/themes/custom/saho/logo.png',
    ];

    // Add social media profiles.
    $schema['sameAs'] = [
      'https://www.facebook.com/sahistoryonline',
      'https://twitter.com/sahistoryonline',
      'https://www.youtube.com/user/sahistoryonline',
    ];

    // Add contact information.
    $schema['contactPoint'] = [
      '@type' => 'ContactPoint',
      'contactType' => 'General Inquiries',
      'email' => 'info@sahistory.org.za',
      'url' => $base_url . '/contact',
    ];

    // Add address (if available).
    $schema['address'] = [
      '@type' => 'PostalAddress',
      'addressCountry' => 'ZA',
      'addressLocality' => 'Cape Town',
      'addressRegion' => 'Western Cape',
    ];

    // Add educational properties.
    $schema['educationalCredentialAwarded'] = 'Free educational resources on South African history';
    $schema['isAccessibleForFree'] = TRUE;

    // Add publishing principles.
    $schema['publishingPrinciples'] = [
      '@type' => 'CreativeWork',
      'name' => 'SAHO Editorial Guidelines',
      'description' => 'All content is peer-reviewed and fact-checked by historians and educators.',
    ];

    // Add license.
    $schema['license'] = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';

    // Add knowledge about.
    $schema['knowsAbout'] = [
      'South African History',
      'African History',
      'Apartheid',
      'Anti-Apartheid Movement',
      'South African Heritage',
      'African Liberation',
      'Human Rights',
      'Social Justice',
    ];

    return $schema;
  }

}
