<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_relations\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\saho_relations\Service\CandidateScorer;

/**
 * Tests the title-match scoring matrix.
 *
 * @group saho_relations
 */
final class CandidateScorerTitleMatchTest extends UnitTestCase {

  /**
   * Builds a dictionary entry.
   */
  protected function entry(int $nid, string $match): array {
    return [
      'nid' => $nid,
      'bundle' => 'biography',
      'match' => $match,
      'tokens' => explode(' ', $match),
    ];
  }

  /**
   * Builds a title-match edge.
   */
  protected function edge(int $target, string $kind, string $in = 'title'): array {
    return [
      'source_nid' => 900000 + $target,
      'field' => 'field_people_related_tab',
      'target_id' => $target,
      'signal' => 'title_match',
      'match_kind' => $kind,
      'matched_in' => $in,
    ];
  }

  /**
   * The tier matrix: exact/contains crossed with token specificity.
   */
  public function testTierMatrix(): void {
    $dictionary = [
      $this->entry(1, 'hendrik frensch verwoerd'),
      $this->entry(2, 'sol plaatje'),
    ];
    $scorer = new CandidateScorer();

    $scored = $scorer->score([
      $this->edge(1, 'exact'),
      $this->edge(2, 'exact'),
      $this->edge(1, 'contains'),
      $this->edge(2, 'contains'),
    ], $dictionary);

    $by = [];
    foreach ($scored as $edge) {
      $by[$edge['target_id'] . ':' . $edge['match_kind']] = $edge;
    }
    $this->assertSame(0.90, $by['1:exact']['confidence']);
    $this->assertSame('high', $by['1:exact']['tier']);
    $this->assertSame(0.72, $by['2:exact']['confidence']);
    $this->assertSame('review', $by['2:exact']['tier']);
    $this->assertSame(0.62, $by['1:contains']['confidence']);
    $this->assertSame('review', $by['1:contains']['tier']);
    $this->assertSame(0.50, $by['2:contains']['confidence']);
    $this->assertSame('review', $by['2:contains']['tier']);
  }

  /**
   * A credit-line-only match scores lower than a title match.
   */
  public function testSourceFieldPenalty(): void {
    $dictionary = [$this->entry(1, 'hendrik frensch verwoerd')];
    $scored = (new CandidateScorer())->score([
      $this->edge(1, 'exact', 'source'),
    ], $dictionary);
    $this->assertSame(0.80, $scored[0]['confidence']);
  }

  /**
   * Homonym targets are capped into the low tier.
   */
  public function testHomonymCap(): void {
    $dictionary = [
      $this->entry(1, 'john smith'),
      $this->entry(2, 'john smith'),
    ];
    $scored = (new CandidateScorer())->score([$this->edge(1, 'exact')], $dictionary);
    $this->assertSame(0.45, $scored[0]['confidence']);
    $this->assertSame('low', $scored[0]['tier']);
    $this->assertSame(2, $scored[0]['homonyms']);
  }

  /**
   * Hot targets demote containment edges but exact edges survive.
   */
  public function testDocumentFrequencyCeiling(): void {
    $dictionary = [$this->entry(1, 'cape town harbour board')];
    $edges = [];
    for ($i = 0; $i < CandidateScorer::TITLE_MATCH_DF_CEILING + 1; $i++) {
      $edge = $this->edge(1, 'contains');
      $edge['source_nid'] = 100000 + $i;
      $edges[] = $edge;
    }
    $edges[] = $this->edge(1, 'exact');

    $scored = (new CandidateScorer())->score($edges, $dictionary);
    $contains = array_values(array_filter($scored, static fn(array $e): bool => $e['match_kind'] === 'contains'));
    $exact = array_values(array_filter($scored, static fn(array $e): bool => $e['match_kind'] === 'exact'));
    $this->assertSame(0.45, $contains[0]['confidence']);
    $this->assertSame('low', $contains[0]['tier']);
    $this->assertSame(0.90, $exact[0]['confidence']);
    $this->assertSame('high', $exact[0]['tier']);
  }

  /**
   * Name-match scoring is untouched by the new branch.
   */
  public function testNameMatchPathUnchanged(): void {
    $dictionary = [$this->entry(1, 'hendrik frensch verwoerd')];
    $scored = (new CandidateScorer())->score([
      [
        'source_nid' => 5,
        'target_id' => 1,
        'signal' => 'name_match',
        'mentions' => 3,
      ],
    ], $dictionary);
    $this->assertSame(0.94, $scored[0]['confidence']);
    $this->assertSame('high', $scored[0]['tier']);
  }

}
