<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_tools\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\saho_tools\Controller\LlmTxtController;

/**
 * The llms.txt endpoint serves spec-shaped Markdown, not bare plain text.
 *
 * @group saho_tools
 */
final class LlmTxtControllerTest extends KernelTestBase {

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
   * Response is Markdown with one H1, a blockquote summary and real links.
   */
  public function testMarkdownShape(): void {
    $response = LlmTxtController::create($this->container)->generate();
    $body = (string) $response->getContent();

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('text/markdown; charset=UTF-8', $response->headers->get('Content-Type'));
    $this->assertSame('noindex', $response->headers->get('X-Robots-Tag'));

    // Exactly one top-level H1, and it is the first line.
    $this->assertStringStartsWith('# ', $body);
    $this->assertCount(1, preg_grep('/^# [^#]/', explode("\n", $body)));

    // Blockquote summary follows the title per the llms.txt convention.
    $this->assertMatchesRegularExpression('/^> /m', $body);

    // Markdown links on the canonical host - the audit's "contains links".
    $this->assertMatchesRegularExpression('/\]\(https:\/\/schema\.test\//', $body);

    // Host-canonical: never leaks the local or request hostname.
    $this->assertStringNotContainsString('ddev.site', $body);
  }

}
