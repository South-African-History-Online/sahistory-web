<?php

namespace Drupal\saho_ai_tdih\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service for looking up birth/death dates from Wikidata.
 */
class WikidataLookup {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Wikidata SPARQL endpoint.
   */
  const SPARQL_ENDPOINT = 'https://query.wikidata.org/sparql';

  /**
   * Constructs a WikidataLookup service.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('saho_ai_tdih');
  }

  /**
   * Extracts person name from event title.
   *
   * @param string $title
   *   The event title like "Nelson Mandela is born" or "Steve Biko dies".
   *
   * @return string|null
   *   The extracted person name or NULL.
   */
  public function extractPersonName(string $title): ?string {
    // Remove leading parenthetical info like "(Pieter)".
    $title = preg_replace('/^\s*\([^)]+\)\s*/', '', $title);

    // Common patterns for birth/death events.
    $patterns = [
      // "Name is born" or "Name, description, is born"
      '/^(.+?),?\s+(?:is born|born)/i',
      // "Name dies" or "Name, description, dies"
      '/^(.+?),?\s+(?:dies|died|passes away)/i',
    ];

    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $title, $matches)) {
        $name = trim($matches[1]);
        // Remove parenthetical info like "(d.2002)"
        $name = preg_replace('/\s*\([^)]+\)\s*/', ' ', $name);
        // Remove descriptive text after comma (e.g., "SA painter, author").
        if (strpos($name, ',') !== FALSE) {
          $name = substr($name, 0, strpos($name, ','));
        }
        // Clean up extra spaces.
        $name = preg_replace('/\s+/', ' ', trim($name));
        return $name;
      }
    }

    return NULL;
  }

  /**
   * Looks up a person's birth and death dates from Wikidata.
   *
   * @param string $name
   *   The person's name.
   *
   * @return array|null
   *   Array with 'birth_date', 'death_date', 'wikidata_id', 'wikipedia_url'
   *   or NULL if not found.
   */
  public function lookupPerson(string $name): ?array {
    // First, search for the person in Wikidata.
    $entity = $this->searchWikidata($name);

    if (!$entity) {
      $this->logger->info('Person not found in Wikidata: @name', ['@name' => $name]);
      return NULL;
    }

    // Query for birth/death dates using SPARQL.
    return $this->getPersonDates($entity['id'], $entity['label']);
  }

  /**
   * Searches Wikidata for a person by name.
   *
   * @param string $name
   *   The person's name.
   *
   * @return array|null
   *   Array with 'id' and 'label' or NULL.
   */
  protected function searchWikidata(string $name): ?array {
    $url = 'https://www.wikidata.org/w/api.php';

    try {
      $response = $this->httpClient->request('GET', $url, [
        'query' => [
          'action' => 'wbsearchentities',
          'search' => $name,
          'language' => 'en',
          'type' => 'item',
          'limit' => 5,
          'format' => 'json',
        ],
        'headers' => [
          'User-Agent' => 'SAHOBot/1.0 (https://sahistory.org.za)',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (empty($data['search'])) {
        return NULL;
      }

      // Look for a human (Q5) in the results.
      foreach ($data['search'] as $result) {
        if ($this->isHuman($result['id'])) {
          return [
            'id' => $result['id'],
            'label' => $result['label'] ?? $name,
          ];
        }
      }

      return NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Wikidata search error: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Checks if a Wikidata entity is a human (instance of Q5).
   *
   * @param string $entity_id
   *   The Wikidata entity ID.
   *
   * @return bool
   *   TRUE if the entity is a human.
   */
  protected function isHuman(string $entity_id): bool {
    $sparql = <<<SPARQL
ASK {
  wd:{$entity_id} wdt:P31 wd:Q5 .
}
SPARQL;

    try {
      $response = $this->httpClient->request('GET', self::SPARQL_ENDPOINT, [
        'query' => [
          'query' => $sparql,
          'format' => 'json',
        ],
        'headers' => [
          'User-Agent' => 'SAHOBot/1.0 (https://sahistory.org.za)',
          'Accept' => 'application/sparql-results+json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data['boolean'] ?? FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Gets birth and death dates for a Wikidata entity.
   *
   * @param string $entity_id
   *   The Wikidata entity ID.
   * @param string $label
   *   The entity label/name.
   *
   * @return array|null
   *   Array with date information or NULL.
   */
  protected function getPersonDates(string $entity_id, string $label): ?array {
    $sparql = <<<SPARQL
SELECT ?birthDate ?deathDate ?article WHERE {
  OPTIONAL { wd:{$entity_id} wdt:P569 ?birthDate . }
  OPTIONAL { wd:{$entity_id} wdt:P570 ?deathDate . }
  OPTIONAL {
    ?article schema:about wd:{$entity_id} ;
             schema:isPartOf <https://en.wikipedia.org/> .
  }
}
LIMIT 1
SPARQL;

    try {
      $response = $this->httpClient->request('GET', self::SPARQL_ENDPOINT, [
        'query' => [
          'query' => $sparql,
          'format' => 'json',
        ],
        'headers' => [
          'User-Agent' => 'SAHOBot/1.0 (https://sahistory.org.za)',
          'Accept' => 'application/sparql-results+json',
        ],
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (empty($data['results']['bindings'])) {
        return NULL;
      }

      $result = $data['results']['bindings'][0];

      $birth_date = NULL;
      $death_date = NULL;

      if (!empty($result['birthDate']['value'])) {
        $birth_date = $this->parseWikidataDate($result['birthDate']['value']);
      }

      if (!empty($result['deathDate']['value'])) {
        $death_date = $this->parseWikidataDate($result['deathDate']['value']);
      }

      if (!$birth_date && !$death_date) {
        return NULL;
      }

      return [
        'wikidata_id' => $entity_id,
        'name' => $label,
        'birth_date' => $birth_date,
        'death_date' => $death_date,
        'wikipedia_url' => $result['article']['value'] ?? NULL,
        'source' => 'wikidata',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Wikidata SPARQL error: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Parses a Wikidata date string to YYYY-MM-DD format.
   *
   * @param string $date_string
   *   The Wikidata date (e.g., "1918-07-18T00:00:00Z").
   *
   * @return string|null
   *   Date in YYYY-MM-DD format or NULL.
   */
  protected function parseWikidataDate(string $date_string): ?string {
    // Wikidata returns dates like "1918-07-18T00:00:00Z".
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date_string, $matches)) {
      return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
    }
    return NULL;
  }

}
