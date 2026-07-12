<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_relations\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the title-match candidate scan (image titles vs the dictionary).
 *
 * @group saho_relations
 */
final class CandidateGeneratorTitleMatchTest extends KernelTestBase {

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
    'search_api',
    'saho_relations',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);

    foreach (['biography', 'image'] as $bundle) {
      NodeType::create(['type' => $bundle, 'name' => $bundle])->save();
    }

    FieldStorageConfig::create([
      'field_name' => 'field_source',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_source',
      'entity_type' => 'node',
      'bundle' => 'image',
    ])->save();
  }

  /**
   * Creates a published node.
   */
  protected function makeNode(string $bundle, string $title, array $values = []): Node {
    $node = Node::create($values + ['type' => $bundle, 'title' => $title, 'status' => 1]);
    $node->save();
    return $node;
  }

  /**
   * Builds the dictionary and runs the title scan over image nodes.
   */
  protected function scan(array $options = []): array {
    $dictionary = $this->container->get('saho_relations.dictionary_builder')
      ->build(['bundles' => ['biography']]);
    return $this->container->get('saho_relations.candidate_generator')
      ->titleMatchCandidates($dictionary, 'field_people_related_tab', $options);
  }

  /**
   * An image titled exactly like a biography emits an exact edge.
   */
  public function testExactTitleMatch(): void {
    $bio = $this->makeNode('biography', 'Hendrik Verwoerd');
    $this->makeNode('image', 'Hendrik Verwoerd');

    $edges = $this->scan();
    $this->assertCount(1, $edges);
    $this->assertSame((int) $bio->id(), $edges[0]['target_id']);
    $this->assertSame('exact', $edges[0]['match_kind']);
    $this->assertSame('title', $edges[0]['matched_in']);
    $this->assertSame('title_match', $edges[0]['signal']);
  }

  /**
   * A longer caption containing the biography title emits a contains edge.
   */
  public function testContainmentMatch(): void {
    $bio = $this->makeNode('biography', 'Hendrik Verwoerd');
    $this->makeNode('image', 'Hendrik Verwoerd addressing a crowd, 1958');

    $edges = $this->scan();
    $this->assertCount(1, $edges);
    $this->assertSame((int) $bio->id(), $edges[0]['target_id']);
    $this->assertSame('contains', $edges[0]['match_kind']);
  }

  /**
   * Punctuation and case differences still match through normalisation.
   *
   * (Initials-only names like "F.W. de Klerk" are a separate, deliberate
   * gap: their single-letter tokens are barred from anchoring, exactly as
   * in the body scan.)
   */
  public function testNormalisedMatch(): void {
    $this->makeNode('biography', 'Albertina Sisulu');
    $this->makeNode('image', 'ALBERTINA SISULU - portrait, c.1955');

    $edges = $this->scan();
    $this->assertCount(1, $edges);
    $this->assertSame('contains', $edges[0]['match_kind']);
  }

  /**
   * Single-token biography titles never match (min_tokens guard).
   */
  public function testSingleTokenTitleSkipped(): void {
    $this->makeNode('biography', 'Verwoerd');
    $this->makeNode('image', 'Verwoerd at a rally');

    $this->assertSame([], $this->scan());
  }

  /**
   * A field_source-only mention is emitted and flagged as such.
   */
  public function testSourceFieldMatchFlagged(): void {
    $bio = $this->makeNode('biography', 'Sol Plaatje');
    $this->makeNode('image', 'Delegation portrait', [
      'field_source' => 'From the Sol Plaatje collection, Kimberley',
    ]);

    $edges = $this->scan();
    $this->assertCount(1, $edges);
    $this->assertSame((int) $bio->id(), $edges[0]['target_id']);
    $this->assertSame('source', $edges[0]['matched_in']);

    $this->assertSame([], $this->scan(['include_source_field' => FALSE]));
  }

  /**
   * Group captions are capped to the longest, most specific phrases.
   */
  public function testMaxPerSourceKeepsLongestPhrases(): void {
    $this->makeNode('biography', 'Walter Max Ulyate Sisulu');
    $this->makeNode('biography', 'Ahmed Kathrada');
    $this->makeNode('biography', 'Denis Goldberg');
    $this->makeNode('image', 'Walter Max Ulyate Sisulu with Ahmed Kathrada and Denis Goldberg');

    $edges = $this->scan(['max_per_source' => 2]);
    $this->assertCount(2, $edges);
    $lengths = array_map(static fn(array $e): int => mb_strlen($e['evidence']), $edges);
    $this->assertNotEmpty($lengths);
    $targets = array_column($edges, 'target_bundle');
    $this->assertSame(['biography', 'biography'], $targets);
    // The longest phrase (the Sisulu full name) must survive the cap.
    $kept = Node::load($edges[0]['target_id']);
    $this->assertSame('Walter Max Ulyate Sisulu', $kept->getTitle());
  }

  /**
   * Unpublished sources are never scanned.
   */
  public function testUnpublishedSourceExcluded(): void {
    $this->makeNode('biography', 'Lilian Ngoyi');
    $this->makeNode('image', 'Lilian Ngoyi at the Women\'s March', ['status' => 0]);

    $this->assertSame([], $this->scan());
  }

}
