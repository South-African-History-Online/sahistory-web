<?php

declare(strict_types=1);

namespace Drupal\saho_relations\Service;

/**
 * Turns raw candidate edges into tiered edges with a confidence score.
 *
 * The score is deterministic and explainable, derived from how many times the
 * name occurs, how specific the name is, and whether the name is a homonym
 * shared by several biographies. The tier routes each edge:
 * - high: strong, unambiguous matches, pre-ticked for approval.
 * - review: plausible but ambiguous; the LLM adjudication stage refines these.
 * - low: weak signals, held back.
 *
 * The LLM adjudication stage consumes the 'review' tier and may promote or
 * reject edges, writing the same edge shape back.
 */
final class CandidateScorer {

  /**
   * Score a set of candidate edges.
   *
   * @param array $candidates
   *   Candidate edges from CandidateGenerator.
   * @param array $dictionary
   *   Dictionary entries (keyed by nid or a flat list) used to detect homonyms.
   *
   * @return array
   *   Edges with added 'confidence' (float) and 'tier' (string) keys, sorted
   *   by confidence descending.
   */
  public function score(array $candidates, array $dictionary): array {
    // Count how many dictionary entries share each match phrase (homonyms).
    $homonyms = [];
    foreach ($dictionary as $entry) {
      $match = $entry['match'] ?? NULL;
      if ($match !== NULL) {
        $homonyms[$match] = ($homonyms[$match] ?? 0) + 1;
      }
    }
    // Index dictionary by nid for target lookups.
    $by_nid = [];
    foreach ($dictionary as $entry) {
      $by_nid[(int) $entry['nid']] = $entry;
    }

    $scored = [];
    foreach ($candidates as $edge) {
      if (($edge['signal'] ?? '') !== 'name_match') {
        // MLT and other signals are scored elsewhere; pass through untouched.
        $scored[] = $edge + ['confidence' => (float) ($edge['confidence'] ?? 0.4), 'tier' => 'review'];
        continue;
      }
      $target = $by_nid[(int) $edge['target_id']] ?? NULL;
      $mentions = (int) ($edge['mentions'] ?? 1);
      $tokens = $target ? count($target['tokens']) : 2;
      $match = $target['match'] ?? '';
      $shared = $homonyms[$match] ?? 1;

      // Base confidence from frequency of the verbatim mention.
      $confidence = match (TRUE) {
        $mentions >= 3 => 0.82,
        $mentions === 2 => 0.70,
        default => 0.55,
      };
      // More name tokens means a more specific, less collision-prone match.
      if ($tokens >= 3) {
        $confidence += 0.12;
      }
      // Homonyms: the name maps to several people, so we cannot be sure which.
      if ($shared > 1) {
        $confidence = min($confidence, 0.45);
      }
      $confidence = max(0.0, min(1.0, $confidence));

      $tier = match (TRUE) {
        $confidence >= 0.80 => 'high',
        $confidence >= 0.50 => 'review',
        default => 'low',
      };

      $scored[] = $edge + [
        'confidence' => round($confidence, 2),
        'tier' => $tier,
        'homonyms' => $shared,
      ];
    }

    usort($scored, static fn($a, $b) => $b['confidence'] <=> $a['confidence']);
    return $scored;
  }

}
