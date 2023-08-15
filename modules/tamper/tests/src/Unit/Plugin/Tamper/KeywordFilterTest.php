<?php

namespace Drupal\Tests\tamper\Unit\Plugin\Tamper;

use Drupal\tamper\Plugin\Tamper\KeywordFilter;

/**
 * Tests the keyword filter plugin.
 *
 * @coversDefaultClass \Drupal\tamper\Plugin\Tamper\KeywordFilter
 * @group tamper
 */
class KeywordFilterTest extends TamperPluginTestBase {

  /**
   * {@inheritdoc}
   */
  protected function instantiatePlugin() {
    return new KeywordFilter([], 'keyword_filter', [], $this->getMockSourceDefinition());
  }

  /**
   * @covers ::tamper
   * @dataProvider providerKeywordFilter
   */
  public function testKeywordFilter($expected, $config) {
    $this->plugin = new KeywordFilter($config, 'keyword_filter', [], $this->getMockSourceDefinition());
    $this->assertEquals($expected, $this->plugin->tamper('This is atitle'));
  }

  /**
   * Data provider for testKeywordFilter().
   */
  public function providerKeywordFilter() {
    return [
      'StriPosFilter' => [
        '', [
          KeywordFilter::WORDS => 'booya',
          KeywordFilter::WORD_BOUNDARIES => FALSE,
          KeywordFilter::EXACT => FALSE,
          KeywordFilter::CASE_SENSITIVE => FALSE,
          KeywordFilter::INVERT => FALSE,
          KeywordFilter::WORD_LIST => ['booya'],
          KeywordFilter::REGEX => FALSE,
          KeywordFilter::FUNCTION => 'stripos',
        ],
      ],
      'StriPosPass' => [
        'This is atitle', [
          KeywordFilter::WORDS => 'this',
          KeywordFilter::WORD_BOUNDARIES => FALSE,
          KeywordFilter::EXACT => FALSE,
          KeywordFilter::CASE_SENSITIVE => FALSE,
          KeywordFilter::INVERT => FALSE,
          KeywordFilter::WORD_LIST => ['this'],
          KeywordFilter::REGEX => FALSE,
          KeywordFilter::FUNCTION => 'stripos',
        ],
      ],
      'StrPosFilter' => [
        '', [
          KeywordFilter::WORDS => 'this',
          KeywordFilter::WORD_BOUNDARIES => FALSE,
          KeywordFilter::EXACT => FALSE,
          KeywordFilter::CASE_SENSITIVE => TRUE,
          KeywordFilter::INVERT => FALSE,
          KeywordFilter::WORD_LIST => ['this'],
          KeywordFilter::REGEX => FALSE,
          KeywordFilter::FUNCTION => 'strpos',
        ],
      ],
      'ExactFilter' => [
        '', [
          KeywordFilter::WORDS => '/^a title$/ui',
          KeywordFilter::WORD_BOUNDARIES => TRUE,
          KeywordFilter::EXACT => TRUE,
          KeywordFilter::CASE_SENSITIVE => FALSE,
          KeywordFilter::INVERT => FALSE,
          KeywordFilter::WORD_LIST => ['/^a title$/ui'],
          KeywordFilter::REGEX => TRUE,
          KeywordFilter::FUNCTION => 'matchRegex',
        ],
      ],
      'ExactFilterDataNotExact' => [
        '', [
          KeywordFilter::WORDS => '/^This is a Title$/ui',
          KeywordFilter::WORD_BOUNDARIES => FALSE,
          KeywordFilter::EXACT => TRUE,
          KeywordFilter::CASE_SENSITIVE => FALSE,
          KeywordFilter::INVERT => FALSE,
          KeywordFilter::WORD_LIST => ['/^This is a Title$/ui'],
          KeywordFilter::REGEX => TRUE,
          KeywordFilter::FUNCTION => 'matchRegex',
        ],
      ],
      'WordBoundariesFilter' => [
        '', [
          KeywordFilter::WORDS => '/\btitle\b/ui',
          KeywordFilter::WORD_BOUNDARIES => TRUE,
          KeywordFilter::EXACT => FALSE,
          KeywordFilter::CASE_SENSITIVE => FALSE,
          KeywordFilter::INVERT => FALSE,
          KeywordFilter::WORD_LIST => ['/\btitle\b/ui'],
          KeywordFilter::REGEX => TRUE,
          KeywordFilter::FUNCTION => 'matchRegex',
        ],
      ],
      'InvertEnablingResult' => [
        'This is atitle', [
          KeywordFilter::WORDS => 'booya',
          KeywordFilter::WORD_BOUNDARIES => FALSE,
          KeywordFilter::EXACT => FALSE,
          KeywordFilter::CASE_SENSITIVE => FALSE,
          KeywordFilter::INVERT => TRUE,
          KeywordFilter::WORD_LIST => ['booya'],
          KeywordFilter::REGEX => FALSE,
          KeywordFilter::FUNCTION => 'stripos',
        ],
      ],
      'InvertFilteringResult' => [
        '', [
          KeywordFilter::WORDS => 'this',
          KeywordFilter::WORD_BOUNDARIES => FALSE,
          KeywordFilter::EXACT => FALSE,
          KeywordFilter::CASE_SENSITIVE => FALSE,
          KeywordFilter::INVERT => TRUE,
          KeywordFilter::WORD_LIST => ['this'],
          KeywordFilter::REGEX => FALSE,
          KeywordFilter::FUNCTION => 'stripos',
        ],
      ],
      'InvertEnablingFailedCaseResult' => [
        'This is atitle', [
          KeywordFilter::WORDS => 'this',
          KeywordFilter::WORD_BOUNDARIES => FALSE,
          KeywordFilter::EXACT => FALSE,
          KeywordFilter::CASE_SENSITIVE => TRUE,
          KeywordFilter::INVERT => TRUE,
          KeywordFilter::WORD_LIST => ['this'],
          KeywordFilter::REGEX => FALSE,
          KeywordFilter::FUNCTION => 'strpos',
        ],
      ],
      'InvertEnablingFailedExactResult' => [
        'This is atitle', [
          KeywordFilter::WORDS => '/^a title$/ui',
          KeywordFilter::WORD_BOUNDARIES => TRUE,
          KeywordFilter::EXACT => TRUE,
          KeywordFilter::CASE_SENSITIVE => FALSE,
          KeywordFilter::INVERT => TRUE,
          KeywordFilter::WORD_LIST => ['/^a title$/ui'],
          KeywordFilter::REGEX => TRUE,
          KeywordFilter::FUNCTION => 'matchRegex',
        ],
      ],
      'InvertFilteringPassedExactResult' => [
        'This is atitle', [
          KeywordFilter::WORDS => '/^This is a title$/ui',
          KeywordFilter::WORD_BOUNDARIES => TRUE,
          KeywordFilter::EXACT => TRUE,
          KeywordFilter::CASE_SENSITIVE => FALSE,
          KeywordFilter::INVERT => TRUE,
          KeywordFilter::WORD_LIST => ['/^This is a title$/ui'],
          KeywordFilter::REGEX => TRUE,
          KeywordFilter::FUNCTION => 'matchRegex',
        ],
      ],
      'InvertWordBoundariesFilter' => [
        'This is atitle', [
          KeywordFilter::WORDS => '/\btitle\b/ui',
          KeywordFilter::WORD_BOUNDARIES => TRUE,
          KeywordFilter::EXACT => FALSE,
          KeywordFilter::CASE_SENSITIVE => FALSE,
          KeywordFilter::INVERT => TRUE,
          KeywordFilter::WORD_LIST => ['/\btitle\b/ui'],
          KeywordFilter::REGEX => TRUE,
          KeywordFilter::FUNCTION => 'matchRegex',
        ],
      ],
    ];
  }

}
