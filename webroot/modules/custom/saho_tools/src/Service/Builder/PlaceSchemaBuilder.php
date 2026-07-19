<?php

namespace Drupal\saho_tools\Service\Builder;

use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;

/**
 * Builds Schema.org structured data for Place nodes.
 *
 * Schema.org has no dedicated Google Search Console rich-result template
 * for generic Place / TouristAttraction / Landform; the LocalBusiness
 * family is the only Place-adjacent rich result, and it requires data
 * we do not have (phone, hours, street address). So this builder
 * optimises for Knowledge Graph reconciliation, image-search context,
 * Maps/AI surfaces, and breadcrumb signals on linked Article pages -
 * not for clearing a GSC "Places" bucket (there isn't one).
 *
 * Key behaviours:
 * - field_place_type is an entity_reference taxonomy term: read via
 *   ->entity->getName(), never ->value (which is always NULL).
 * - Live geo lives in field_geolocation; field_geofield has zero
 *   populated rows on this site.
 * - field_country (boolean) marks the place itself as a Country.
 * - field_parent is a thematic feature tag, not a parent place -
 *   it is deliberately NOT mapped to containedInPlace.
 * - LocalBusiness subtypes (Museum, Hotel, Restaurant) are kept off
 *   the top-level @type because Google requires phone/hours/address
 *   for those - they survive as additionalType instead.
 */
class PlaceSchemaBuilder extends SchemaBuilderBase {

  /**
   * Maps SAHO field_place_type term names to Schema.org @type values.
   *
   * Two-element arrays use Schema.org's array @type syntax to combine
   * a structural type with TouristAttraction, which has no required
   * properties and signals visitor relevance to Maps and AI surfaces.
   *
   * Lowercased term names are used as keys; terms not listed fall
   * through to the conditional default (see ::resolvePlaceType()).
   */
  protected const TYPE_MAP = [
    // Historical structures and monuments.
    'monument' => ['LandmarksOrHistoricalBuildings', 'TouristAttraction'],
    'memorial' => ['LandmarksOrHistoricalBuildings', 'TouristAttraction'],
    'statue' => ['LandmarksOrHistoricalBuildings', 'TouristAttraction'],
    'heritage site' => ['LandmarksOrHistoricalBuildings', 'TouristAttraction'],
    'world heritage site' => ['LandmarksOrHistoricalBuildings', 'TouristAttraction'],
    'archaeological site' => ['LandmarksOrHistoricalBuildings', 'TouristAttraction'],
    'fort' => ['LandmarksOrHistoricalBuildings', 'TouristAttraction'],
    'building' => ['LandmarksOrHistoricalBuildings', 'TouristAttraction'],
    'house' => ['LandmarksOrHistoricalBuildings', 'TouristAttraction'],
    'windmill' => ['LandmarksOrHistoricalBuildings', 'TouristAttraction'],
    'shipwreck' => ['Place', 'TouristAttraction'],

    // Cultural and civic buildings - Place + additionalType keeps us
    // out of the LocalBusiness required-field territory.
    'museum' => ['Place', 'TouristAttraction'],
    'theatre' => 'Place',
    'library' => 'Place',
    'college' => 'Place',
    'school' => 'Place',
    'city hall' => 'CityHall',
    'post office' => 'PostOffice',
    'stadium' => ['StadiumOrArena', 'TouristAttraction'],
    'bridge' => 'Bridge',
    'tunnel' => 'CivicStructure',
    'prison' => 'Place',

    // Places of worship.
    'church' => 'PlaceOfWorship',
    'mosque' => 'PlaceOfWorship',
    'temple' => 'PlaceOfWorship',
    'hindu temple' => 'PlaceOfWorship',
    'synagogue' => 'PlaceOfWorship',
    'kramat' => ['PlaceOfWorship', 'TouristAttraction'],
    'missionary station' => ['PlaceOfWorship', 'TouristAttraction'],

    // Hospitality - Place + additionalType, never LocalBusiness.
    'hotel' => 'Place',
    'hostel' => 'Place',
    'restaurant' => 'Place',
    'wine estate' => ['Place', 'TouristAttraction'],
    'retirement centre' => 'Place',
    'orphanage' => 'Place',

    // Outdoor / nature.
    'park' => ['Park', 'TouristAttraction'],
    'garden' => ['Park', 'TouristAttraction'],
    'botanical garden' => ['Park', 'TouristAttraction'],
    'reserve' => ['Park', 'TouristAttraction'],
    'hiking trail' => ['Place', 'TouristAttraction'],
    'holiday destination' => ['Place', 'TouristAttraction'],
    'outdoor activities' => ['Place', 'TouristAttraction'],
    'beach' => ['Place', 'TouristAttraction'],
    'zoo' => ['Place', 'TouristAttraction'],

    // Landforms and bodies of water.
    'mountain' => ['Mountain', 'TouristAttraction'],
    'peak' => ['Mountain', 'TouristAttraction'],
    'cliff' => 'Landform',
    'gorge' => 'Landform',
    'ravine' => 'Landform',
    'valley' => 'Landform',
    'ridge' => 'Landform',
    'cave' => ['Landform', 'TouristAttraction'],
    'desert' => 'Landform',
    'island' => 'Landform',
    'pan' => 'Landform',
    'river' => 'BodyOfWater',
    'waterfall' => ['BodyOfWater', 'TouristAttraction'],
    'spring' => 'BodyOfWater',
    'lagoon' => 'BodyOfWater',

    // Administrative / settlement units.
    'country' => 'Country',
    'province' => 'AdministrativeArea',
    'region' => 'AdministrativeArea',
    'district' => 'AdministrativeArea',
    'municipality' => 'AdministrativeArea',
    'homeland' => 'AdministrativeArea',
    'trust land' => 'AdministrativeArea',
    'town' => 'City',
    'village' => 'City',
    'suburb' => 'AdministrativeArea',
    'township' => 'AdministrativeArea',
    'settlement' => 'AdministrativeArea',

    // Transport infrastructure.
    'airport' => 'Airport',
    'railway station' => 'TrainStation',
    'railway siding' => 'CivicStructure',
    'port' => 'CivicStructure',
    'road' => 'CivicStructure',
    'street' => 'CivicStructure',

    // Misc with clear Schema.org matches.
    'cemetery' => 'Cemetery',
    'farm' => 'Place',
    'mine' => 'Place',
  ];

  /**
   * Maps African country names to ISO 3166-1 alpha-2 codes.
   *
   * Google and the Knowledge Graph accept the full name, but ISO codes
   * are unambiguous and prevent "Republic of South Africa" vs
   * "South Africa" matching issues.
   */
  protected const COUNTRY_ISO = [
    'south africa' => 'ZA',
    'algeria' => 'DZ',
    'angola' => 'AO',
    'benin' => 'BJ',
    'botswana' => 'BW',
    'burkina faso' => 'BF',
    'burundi' => 'BI',
    'cameroon' => 'CM',
    'cape verde' => 'CV',
    'central african republic' => 'CF',
    'chad' => 'TD',
    'comoros' => 'KM',
    'democratic republic of the congo' => 'CD',
    'dr congo' => 'CD',
    'republic of the congo' => 'CG',
    'congo' => 'CG',
    "cote d'ivoire" => 'CI',
    'ivory coast' => 'CI',
    'djibouti' => 'DJ',
    'egypt' => 'EG',
    'equatorial guinea' => 'GQ',
    'eritrea' => 'ER',
    'eswatini' => 'SZ',
    'swaziland' => 'SZ',
    'ethiopia' => 'ET',
    'gabon' => 'GA',
    'gambia' => 'GM',
    'ghana' => 'GH',
    'guinea' => 'GN',
    'guinea-bissau' => 'GW',
    'kenya' => 'KE',
    'lesotho' => 'LS',
    'liberia' => 'LR',
    'libya' => 'LY',
    'madagascar' => 'MG',
    'malawi' => 'MW',
    'mali' => 'ML',
    'mauritania' => 'MR',
    'mauritius' => 'MU',
    'morocco' => 'MA',
    'mozambique' => 'MZ',
    'namibia' => 'NA',
    'niger' => 'NE',
    'nigeria' => 'NG',
    'rwanda' => 'RW',
    'sao tome and principe' => 'ST',
    'senegal' => 'SN',
    'seychelles' => 'SC',
    'sierra leone' => 'SL',
    'somalia' => 'SO',
    'south sudan' => 'SS',
    'sudan' => 'SD',
    'tanzania' => 'TZ',
    'togo' => 'TG',
    'tunisia' => 'TN',
    'uganda' => 'UG',
    'zambia' => 'ZM',
    'zimbabwe' => 'ZW',
  ];

  /**
   * {@inheritdoc}
   */
  public function supports(string $node_type): bool {
    return $node_type === 'place';
  }

  /**
   * {@inheritdoc}
   */
  public function build(NodeInterface $node): array {
    if (!$this->supports($node->getType())) {
      return [];
    }

    [$type, $additional_type] = $this->resolvePlaceType($node);

    $schema = [
      '@context' => 'https://schema.org',
      '@type' => $type,
      'name' => $node->getTitle(),
      'dateModified' => date('c', $node->getChangedTime()),
    ] + $this->identityProperties($node);

    if ($additional_type !== NULL) {
      $schema['additionalType'] = 'https://schema.org/' . $additional_type;
    }

    // Description from body, plain text capped at 500 chars. We
    // strip tags AND decode entities (&nbsp;, &amp;, &#39;, ...)
    // so the rendered description in JSON-LD is clean prose.
    if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
      $body = (string) $node->get('body')->value;
      $description = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
      // Collapse runs of whitespace introduced by removed markup.
      $description = preg_replace('/\s+/u', ' ', $description) ?? $description;
      if (strlen($description) > 500) {
        $description = substr($description, 0, 497) . '...';
      }
      if ($description !== '') {
        $schema['description'] = $description;
      }
    }

    // Geo - prefer field_geolocation (the live field; field_geofield
    // has zero populated rows on this site). Validate ranges so we
    // never emit nonsense coordinates to Schema.org consumers.
    $geo = $this->extractGeo($node);
    if ($geo !== NULL) {
      $schema['geo'] = $geo;
    }

    // Address: just the country (we do not store street/locality).
    $address = $this->buildAddress($node);
    if ($address !== NULL) {
      $schema['address'] = $address;
    }

    // Image with width, height, and caption when available.
    $image = $this->buildImage($node);
    if ($image !== NULL) {
      $schema['image'] = $image;
    }

    // Keywords from place category taxonomy (Historical Site,
    // World Heritage Site, etc).
    $keywords = $this->extractKeywords($node);
    if ($keywords !== '') {
      $schema['keywords'] = $keywords;
    }

    $schema['isAccessibleForFree'] = TRUE;
    $schema['inLanguage'] = 'en-ZA';

    return $schema;
  }

  /**
   * Resolves the Schema.org @type (and optional additionalType) for a place.
   *
   * Resolution order:
   * 1. field_country (boolean) - if TRUE the place IS a country.
   * 2. field_place_type taxonomy term mapped via self::TYPE_MAP.
   * 3. Fallback: Place, upgraded to [Place, TouristAttraction] when
   *    field_place_category marks it as a Historical or World Heritage
   *    Site.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The place node.
   *
   * @return array
   *   Two-element list: [@type value (string|array), additionalType
   *   slug or NULL]. additionalType is set for cultural-building types
   *   (e.g. museum) so we keep semantic precision without taking on
   *   LocalBusiness required fields.
   */
  protected function resolvePlaceType(NodeInterface $node): array {
    if ($node->hasField('field_country') && (bool) $node->get('field_country')->value) {
      return ['Country', NULL];
    }

    // field_place_type is multi-value (cardinality 5). Walk every
    // referenced term: the first one with a mapping defines the
    // primary @type and additionalType, but we then scan the rest
    // for TouristAttraction eligibility so a place tagged
    // [Island, World Heritage Site] still gets TouristAttraction.
    $primary = NULL;
    $additional = NULL;
    $needs_tourist_attraction = $this->categoryImpliesTouristAttraction($node);

    foreach ($this->getPlaceTypeNames($node) as $term_name) {
      $key = strtolower($term_name);
      if (!isset(self::TYPE_MAP[$key])) {
        continue;
      }
      $mapped = self::TYPE_MAP[$key];
      if ($primary === NULL) {
        $primary = $mapped;
        $additional = $this->additionalTypeFor($key);
      }
      if (is_array($mapped) && in_array('TouristAttraction', $mapped, TRUE)) {
        $needs_tourist_attraction = TRUE;
      }
    }

    if ($primary === NULL) {
      $primary = $needs_tourist_attraction ? ['Place', 'TouristAttraction'] : 'Place';
      return [$primary, NULL];
    }

    // Promote scalar primary to an array when TouristAttraction is
    // implied by another tagged term or by place category.
    if ($needs_tourist_attraction) {
      if (is_string($primary)) {
        $primary = [$primary, 'TouristAttraction'];
      }
      elseif (!in_array('TouristAttraction', $primary, TRUE)) {
        $primary[] = 'TouristAttraction';
      }
    }

    return [$primary, $additional];
  }

  /**
   * Returns TRUE when the place category marks visitor relevance.
   */
  protected function categoryImpliesTouristAttraction(NodeInterface $node): bool {
    foreach ($this->getCategoryNames($node) as $cat) {
      $lower = strtolower($cat);
      if ($lower === 'historical site' || $lower === 'world heritage site') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Returns all place_type term names referenced by a node.
   *
   * @return string[]
   *   Term names in field order (may be empty).
   */
  protected function getPlaceTypeNames(NodeInterface $node): array {
    if (!$node->hasField('field_place_type') || $node->get('field_place_type')->isEmpty()) {
      return [];
    }
    $names = [];
    foreach ($node->get('field_place_type') as $item) {
      // @phpstan-ignore-next-line
      $term = $item->entity;
      if ($term) {
        $names[] = $term->getName();
      }
    }
    return $names;
  }

  /**
   * Returns an additionalType slug for cultural-building Place terms.
   *
   * These terms have a meaningful Schema.org subtype (Museum, Hotel,
   * Restaurant, etc.) that we deliberately don't use as @type because
   * they're LocalBusiness subtypes requiring phone/hours/address.
   * additionalType preserves the semantic without the warnings.
   */
  protected function additionalTypeFor(string $term_key): ?string {
    $cultural = [
      'museum' => 'Museum',
      'hotel' => 'Hotel',
      'hostel' => 'Hostel',
      'restaurant' => 'Restaurant',
      'theatre' => 'PerformingArtsTheater',
      'library' => 'Library',
      'college' => 'CollegeOrUniversity',
      'school' => 'School',
      'wine estate' => 'Winery',
      'farm' => 'Farm',
      'zoo' => 'Zoo',
      'prison' => 'GovernmentBuilding',
      'mine' => 'Place',
    ];
    return $cultural[$term_key] ?? NULL;
  }

  /**
   * Reads a single entity_reference taxonomy field as the term name.
   */
  protected function getReferencedTermName(NodeInterface $node, string $field): ?string {
    if (!$node->hasField($field) || $node->get($field)->isEmpty()) {
      return NULL;
    }
    /** @var \Drupal\taxonomy\TermInterface|null $term */
    $term = $node->get($field)->entity;
    return $term ? $term->getName() : NULL;
  }

  /**
   * Returns all category term names for a place.
   *
   * @return string[]
   *   Term names (may be empty).
   */
  protected function getCategoryNames(NodeInterface $node): array {
    if (!$node->hasField('field_place_category') || $node->get('field_place_category')->isEmpty()) {
      return [];
    }
    $names = [];
    foreach ($node->get('field_place_category') as $item) {
      // @phpstan-ignore-next-line
      $term = $item->entity;
      if ($term) {
        $names[] = $term->getName();
      }
    }
    return $names;
  }

  /**
   * Extracts GeoCoordinates from field_geolocation (with sanity bounds).
   *
   * Returns NULL when the coordinates are missing or out of valid
   * range. Some legacy nodes have swapped lat/lng - we let those
   * through if they're individually in range and trust editors to
   * correct them, but we drop true zero-zero entries.
   */
  protected function extractGeo(NodeInterface $node): ?array {
    if (!$node->hasField('field_geolocation') || $node->get('field_geolocation')->isEmpty()) {
      return NULL;
    }
    $item = $node->get('field_geolocation')->first();
    if (!$item) {
      return NULL;
    }
    $lat = $item->get('lat')->getValue();
    $lng = $item->get('lng')->getValue();
    if ($lat === NULL || $lng === NULL) {
      return NULL;
    }
    $lat = (float) $lat;
    $lng = (float) $lng;
    if ($lat === 0.0 && $lng === 0.0) {
      return NULL;
    }
    if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
      return NULL;
    }
    return [
      '@type' => 'GeoCoordinates',
      'latitude' => $lat,
      'longitude' => $lng,
    ];
  }

  /**
   * Builds a PostalAddress with an ISO 3166-1 alpha-2 country code.
   *
   * Falls back to the term name when the country isn't in our ISO map.
   * Returns NULL when no country is set.
   */
  protected function buildAddress(NodeInterface $node): ?array {
    $country_name = $this->getReferencedTermName($node, 'field_african_country');
    if ($country_name === NULL || $country_name === '') {
      return NULL;
    }
    $iso = self::COUNTRY_ISO[strtolower($country_name)] ?? $country_name;
    return [
      '@type' => 'PostalAddress',
      'addressCountry' => $iso,
    ];
  }

  /**
   * Builds an ImageObject with dimensions and caption.
   */
  protected function buildImage(NodeInterface $node): ?array {
    if (!$node->hasField('field_place_image') || $node->get('field_place_image')->isEmpty()) {
      return NULL;
    }
    $field_item = $node->get('field_place_image')->first();
    // @phpstan-ignore-next-line
    $file = $field_item ? $field_item->entity : NULL;
    if (!$file instanceof File) {
      return NULL;
    }
    $image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
    $image = [
      '@type' => 'ImageObject',
      'url' => $image_url,
      'contentUrl' => $image_url,
    ];
    $width = $field_item->get('width')->getValue();
    $height = $field_item->get('height')->getValue();
    if ($width) {
      $image['width'] = (int) $width;
    }
    if ($height) {
      $image['height'] = (int) $height;
    }
    // Prefer the explicit image caption field, then the alt text.
    if ($node->hasField('field_node_image_caption') && !$node->get('field_node_image_caption')->isEmpty()) {
      $caption = trim(strip_tags((string) $node->get('field_node_image_caption')->value));
      if ($caption !== '') {
        $image['caption'] = $caption;
      }
    }
    if (empty($image['caption'])) {
      $alt = $field_item->get('alt')->getValue();
      if ($alt) {
        $image['caption'] = $alt;
      }
    }
    return $image;
  }

  /**
   * Builds a keywords string from place category + all place type terms.
   */
  protected function extractKeywords(NodeInterface $node): string {
    $keywords = $this->getCategoryNames($node);
    foreach ($this->getPlaceTypeNames($node) as $name) {
      if (!in_array($name, $keywords, TRUE)) {
        $keywords[] = $name;
      }
    }
    return implode(', ', $keywords);
  }

}
