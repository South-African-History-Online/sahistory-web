<?php

namespace Drupal\saho_ai_tdih\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for processing TDIH events using Claude AI.
 */
class TdihEventProcessor {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The AI provider manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProvider;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Wikidata lookup service.
   *
   * @var \Drupal\saho_ai_tdih\Service\WikidataLookup
   */
  protected $wikidataLookup;

  /**
   * Constructs a TdihEventProcessor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    AiProviderPluginManager $ai_provider,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    WikidataLookup $wikidata_lookup,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->aiProvider = $ai_provider;
    $this->logger = $logger_factory->get('saho_ai_tdih');
    $this->configFactory = $config_factory;
    $this->wikidataLookup = $wikidata_lookup;
  }

  /**
   * Gets dateless events from the database.
   *
   * @param int $limit
   *   Maximum number of events to retrieve.
   * @param int $offset
   *   Offset for pagination.
   * @param string $category
   *   Filter by category: 'births', 'deaths', 'all'.
   *
   * @return array
   *   Array of event data.
   */
  public function getDatelessEvents(int $limit = 50, int $offset = 0, string $category = 'all'): array {
    $query = $this->database->select('node_field_data', 'n');
    $query->leftJoin('node__field_event_date', 'fed', 'n.nid = fed.entity_id');
    $query->leftJoin('saho_ai_tdih_results', 'r', 'n.nid = r.nid');
    $query->fields('n', ['nid', 'title', 'created']);
    $query->condition('n.type', 'event');
    $query->condition('n.status', 1);
    $query->isNull('fed.field_event_date_value');
    // Exclude already processed events.
    $query->isNull('r.nid');

    // Filter by category.
    if ($category === 'births') {
      $query->condition('n.title', '%is born%', 'LIKE');
    }
    elseif ($category === 'deaths') {
      $or = $query->orConditionGroup()
        ->condition('n.title', '%dies%', 'LIKE')
        ->condition('n.title', '%died%', 'LIKE')
        ->condition('n.title', '%passes away%', 'LIKE');
      $query->condition($or);
    }

    $query->orderBy('n.title', 'ASC');
    $query->range($offset, $limit);

    return $query->execute()->fetchAll();
  }

  /**
   * Counts dateless events.
   *
   * @param string $category
   *   Filter by category.
   *
   * @return int
   *   Count of dateless events.
   */
  public function countDatelessEvents(string $category = 'all'): int {
    $query = $this->database->select('node_field_data', 'n');
    $query->leftJoin('node__field_event_date', 'fed', 'n.nid = fed.entity_id');
    $query->condition('n.type', 'event');
    $query->condition('n.status', 1);
    $query->isNull('fed.field_event_date_value');

    if ($category === 'births') {
      $query->condition('n.title', '%is born%', 'LIKE');
    }
    elseif ($category === 'deaths') {
      $or = $query->orConditionGroup()
        ->condition('n.title', '%dies%', 'LIKE')
        ->condition('n.title', '%died%', 'LIKE')
        ->condition('n.title', '%passes away%', 'LIKE');
      $query->condition($or);
    }

    return (int) $query->countQuery()->execute()->fetchField();
  }

  /**
   * Processes a single event - tries Wikidata first, then AI fallback.
   *
   * @param int $nid
   *   The node ID to process.
   *
   * @return array
   *   Processing result.
   */
  public function processEvent(int $nid): array {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node || $node->bundle() !== 'event') {
      return ['error' => 'Invalid node'];
    }

    $title = $node->getTitle();

    // Determine if this is a birth or death event.
    $is_birth = stripos($title, 'is born') !== FALSE || stripos($title, 'born') !== FALSE;
    $is_death = stripos($title, 'dies') !== FALSE || stripos($title, 'died') !== FALSE || stripos($title, 'passes away') !== FALSE;

    // Try Wikidata first for birth/death events.
    if ($is_birth || $is_death) {
      $person_name = $this->wikidataLookup->extractPersonName($title);

      if ($person_name) {
        $wikidata_result = $this->wikidataLookup->lookupPerson($person_name);

        if ($wikidata_result) {
          $date = $is_birth ? $wikidata_result['birth_date'] : $wikidata_result['death_date'];

          if ($date) {
            $result = [
              'nid' => $nid,
              'original_title' => $title,
              'researched_date' => $date,
              'date_verified' => 1,
              'suggested_body' => '',
              'references' => array_filter([
                $wikidata_result['wikipedia_url'],
                'https://www.wikidata.org/wiki/' . $wikidata_result['wikidata_id'],
              ]),
              'manual_review' => 0,
              'review_reason' => '',
              'status' => 'processed',
              'raw_response' => 'Source: Wikidata (' . $wikidata_result['wikidata_id'] . ')',
            ];

            $this->storeResult($result);
            return $result;
          }
        }
      }
    }

    // Fallback to AI if Wikidata didn't find the date.
    return $this->processEventWithAi($nid, $title, $node->get('body')->value ?? '');
  }

  /**
   * Processes an event using Claude AI (fallback method).
   *
   * @param int $nid
   *   The node ID.
   * @param string $title
   *   The event title.
   * @param string $body
   *   The event body.
   *
   * @return array
   *   Processing result.
   */
  protected function processEventWithAi(int $nid, string $title, string $body): array {
    // Build the prompt for Claude.
    $prompt = $this->buildPrompt($title, $body);

    try {
      // Get the configured provider.
      $config = $this->configFactory->get('saho_ai_tdih.settings');
      $provider_id = $config->get('ai_provider') ?? 'anthropic';
      $model = $config->get('ai_model') ?? 'claude-sonnet-4-20250514';

      // Create the AI request.
      $provider = $this->aiProvider->createInstance($provider_id);

      // Set configuration for the request.
      $provider->setConfiguration([
        'max_tokens' => 1500,
        'temperature' => 0.3,
      ]);

      $messages = new ChatInput([
        new ChatMessage('user', $prompt),
      ]);

      // Tags parameter should be an array of strings for logging/tracking.
      $response = $provider->chat($messages, $model, ['saho_tdih']);

      $content = $response->getNormalized()->getText();

      // Parse the response.
      $result = $this->parseResponse($content, $nid, $title);

      // AI results should always require manual review.
      $result['manual_review'] = 1;
      $result['review_reason'] = 'AI-generated date - requires verification. ' . ($result['review_reason'] ?? '');

      // Store the result.
      $this->storeResult($result);

      return $result;

    }
    catch (\Exception $e) {
      $this->logger->error('AI processing error for node @nid: @error', [
        '@nid' => $nid,
        '@error' => $e->getMessage(),
      ]);
      return [
        'error' => $e->getMessage(),
        'nid' => $nid,
      ];
    }
  }

  /**
   * Builds the prompt for Claude.
   */
  protected function buildPrompt(string $title, string $body): string {
    $prompt = <<<PROMPT
You are a historical researcher for South African History Online (SAHO). Research the following historical event and provide accurate date information.

Event Title: {$title}

Current Body Text (if any): {$body}

Please provide the following in a structured format:

1. DATE: The exact date in YYYY-MM-DD format. If only year and month are known, use YYYY-MM-01. If only year is known, use YYYY-01-01.

2. VERIFIED: Yes, No, or Partial
   - Yes = Date confirmed from multiple reliable sources
   - Partial = Date is approximate or from single source
   - No = Date could not be verified

3. BODY: A concise but informative description (2-4 sentences) suitable for a historical database. Focus on South African context and significance.

4. REFERENCES: List 1-3 reliable sources (Wikipedia, Britannica, academic sources, news archives)

5. MANUAL_REVIEW: Yes or No
   - Yes if there are conflicting dates, disputed facts, or uncertainty

6. REVIEW_REASON: If manual review required, explain why

Format your response exactly like this example:
DATE: 1990-02-11
VERIFIED: Yes
BODY: Nelson Mandela was released from Victor Verster Prison on 11 February 1990 after 27 years of imprisonment. His release marked a pivotal moment in South Africa's transition from apartheid to democracy.
REFERENCES:
- https://en.wikipedia.org/wiki/Nelson_Mandela
- https://www.britannica.com/biography/Nelson-Mandela
MANUAL_REVIEW: No
REVIEW_REASON:

If you cannot determine a date, respond with:
DATE: UNKNOWN
VERIFIED: No
BODY: [Your best description]
REFERENCES: [Any sources found]
MANUAL_REVIEW: Yes
REVIEW_REASON: [Explanation of why date could not be determined]
PROMPT;

    return $prompt;
  }

  /**
   * Parses the AI response into structured data.
   */
  protected function parseResponse(string $content, int $nid, string $title): array {
    $result = [
      'nid' => $nid,
      'original_title' => $title,
      'researched_date' => NULL,
      'date_verified' => 0,
      'suggested_body' => '',
      'references' => [],
      'manual_review' => 0,
      'review_reason' => '',
      'status' => 'processed',
      'raw_response' => $content,
    ];

    // Parse DATE.
    if (preg_match('/DATE:\s*(\d{4}-\d{2}-\d{2}|UNKNOWN)/i', $content, $matches)) {
      $date = trim($matches[1]);
      if ($date !== 'UNKNOWN') {
        $result['researched_date'] = $date;
      }
    }

    // Parse VERIFIED.
    if (preg_match('/VERIFIED:\s*(Yes|No|Partial)/i', $content, $matches)) {
      $verified = strtolower(trim($matches[1]));
      $result['date_verified'] = match($verified) {
        'yes' => 1,
        'partial' => 2,
        default => 0,
      };
    }

    // Parse BODY.
    if (preg_match('/BODY:\s*(.+?)(?=REFERENCES:|MANUAL_REVIEW:|$)/is', $content, $matches)) {
      $result['suggested_body'] = trim($matches[1]);
    }

    // Parse REFERENCES.
    if (preg_match('/REFERENCES:\s*(.+?)(?=MANUAL_REVIEW:|$)/is', $content, $matches)) {
      $refs = trim($matches[1]);
      $lines = explode("\n", $refs);
      $references = [];
      foreach ($lines as $line) {
        $line = trim($line, "- \t\n\r");
        if (!empty($line) && (str_starts_with($line, 'http') || str_contains($line, '://'))) {
          $references[] = $line;
        }
      }
      $result['references'] = $references;
    }

    // Parse MANUAL_REVIEW.
    if (preg_match('/MANUAL_REVIEW:\s*(Yes|No)/i', $content, $matches)) {
      $result['manual_review'] = strtolower(trim($matches[1])) === 'yes' ? 1 : 0;
    }

    // Parse REVIEW_REASON.
    if (preg_match('/REVIEW_REASON:\s*(.+?)$/is', $content, $matches)) {
      $result['review_reason'] = trim($matches[1]);
    }

    // If no date found, flag for manual review.
    if (empty($result['researched_date'])) {
      $result['manual_review'] = 1;
      if (empty($result['review_reason'])) {
        $result['review_reason'] = 'Date could not be determined';
      }
    }

    return $result;
  }

  /**
   * Stores the processing result in the database.
   */
  protected function storeResult(array $result): void {
    $this->database->insert('saho_ai_tdih_results')
      ->fields([
        'nid' => $result['nid'],
        'original_title' => $result['original_title'],
        'researched_date' => $result['researched_date'],
        'date_verified' => $result['date_verified'],
        'suggested_body' => $result['suggested_body'],
        'references' => json_encode($result['references']),
        'manual_review' => $result['manual_review'],
        'review_reason' => $result['review_reason'],
        'status' => $result['status'],
        'processed' => \Drupal::time()->getRequestTime(),
        'raw_response' => $result['raw_response'],
      ])
      ->execute();
  }

  /**
   * Applies an AI result to the actual node.
   */
  public function applyResult(int $result_id): bool {
    // Get the result.
    $result = $this->database->select('saho_ai_tdih_results', 'r')
      ->fields('r')
      ->condition('id', $result_id)
      ->execute()
      ->fetchAssoc();

    if (!$result || empty($result['researched_date'])) {
      return FALSE;
    }

    // Load the node.
    $node = $this->entityTypeManager->getStorage('node')->load($result['nid']);
    if (!$node) {
      return FALSE;
    }

    try {
      // Update the date field.
      $node->set('field_event_date', $result['researched_date']);

      // Optionally update the body if it was empty.
      if ($node->get('body')->isEmpty() && !empty($result['suggested_body'])) {
        $node->set('body', [
          'value' => $result['suggested_body'],
          'format' => 'full_html',
        ]);
      }

      $node->save();

      // Update result status.
      $this->database->update('saho_ai_tdih_results')
        ->fields([
          'status' => 'applied',
          'applied' => \Drupal::time()->getRequestTime(),
        ])
        ->condition('id', $result_id)
        ->execute();

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error applying result @id: @error', [
        '@id' => $result_id,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets processing statistics.
   */
  public function getStatistics(): array {
    $stats = [];

    // Total dateless.
    $stats['total_dateless'] = $this->countDatelessEvents('all');
    $stats['births_dateless'] = $this->countDatelessEvents('births');
    $stats['deaths_dateless'] = $this->countDatelessEvents('deaths');

    // Processed counts.
    $stats['processed'] = (int) $this->database->select('saho_ai_tdih_results', 'r')
      ->condition('status', 'processed')
      ->countQuery()
      ->execute()
      ->fetchField();

    $stats['applied'] = (int) $this->database->select('saho_ai_tdih_results', 'r')
      ->condition('status', 'applied')
      ->countQuery()
      ->execute()
      ->fetchField();

    $stats['manual_review'] = (int) $this->database->select('saho_ai_tdih_results', 'r')
      ->condition('manual_review', 1)
      ->condition('status', 'processed')
      ->countQuery()
      ->execute()
      ->fetchField();

    return $stats;
  }

}
