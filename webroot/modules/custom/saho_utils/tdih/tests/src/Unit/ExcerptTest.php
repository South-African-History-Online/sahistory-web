<?php

declare(strict_types=1);

namespace Drupal\Tests\tdih\Unit;

use Drupal\tdih\Excerpt;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the sentence-aware excerpt helper.
 *
 * @coversDefaultClass \Drupal\tdih\Excerpt
 * @group tdih
 */
class ExcerptTest extends UnitTestCase {

  /**
   * @covers ::plain
   */
  public function testPlainStripsTagsDecodesEntitiesAndCollapsesSpace(): void {
    $html = "<p>Walter&nbsp;Sisulu &amp; others\n\n are  <em>arrested</em>.</p>";
    $this->assertSame('Walter Sisulu & others are arrested.', Excerpt::plain($html));
  }

  /**
   * @covers ::sentences
   */
  public function testShortTextIsReturnedWhole(): void {
    $this->assertSame('Selassie becomes emperor.', Excerpt::sentences('Selassie becomes emperor.', 220));
    $this->assertSame('', Excerpt::sentences('<p></p>', 220));
  }

  /**
   * @covers ::sentences
   */
  public function testCutsAtLastSentenceThatFits(): void {
    $text = 'Haile Selassie is crowned emperor of Ethiopia. The coronation draws delegations from across the world. South African newspapers report it a week later.';
    $this->assertSame(
      'Haile Selassie is crowned emperor of Ethiopia. The coronation draws delegations from across the world.',
      Excerpt::sentences($text, 110),
    );
  }

  /**
   * @covers ::sentences
   */
  public function testCutsAtWordWhenFirstSentenceIsTooLong(): void {
    $text = 'Forces loyal to Zambian president Frederick Chiluba quash a coup by a handful of disgruntled military officers, arresting nine men.';
    $excerpt = Excerpt::sentences($text, 60);
    $this->assertSame('Forces loyal to Zambian president Frederick Chiluba quash a...', $excerpt);
    $this->assertLessThanOrEqual(63, mb_strlen($excerpt));
  }

  /**
   * @covers ::sentences
   */
  public function testStubSentenceFallsThroughToWordCut(): void {
    // "Dr." within the first third of the budget is not a usable cut.
    $text = 'Dr. Beyers Naudé is served with his second banning order under the Suppression of Communism Act after speaking in Johannesburg';
    $excerpt = Excerpt::sentences($text, 50);
    $this->assertStringStartsWith('Dr. Beyers Naudé is served with his second', $excerpt);
    $this->assertStringEndsWith('...', $excerpt);
  }

  /**
   * @covers ::sentences
   */
  public function testMultibyteSafe(): void {
    $text = str_repeat('Naudé ', 60);
    $excerpt = Excerpt::sentences($text, 40);
    $this->assertStringEndsWith('...', $excerpt);
    $this->assertLessThanOrEqual(43, mb_strlen($excerpt));
    $this->assertTrue(mb_check_encoding($excerpt, 'UTF-8'));
  }

}
