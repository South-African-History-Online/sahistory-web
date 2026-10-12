<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_linkfix\Kernel;

use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Dead archive PDF URLs redirect to the genuine copy on disk.
 *
 * @group saho_linkfix
 */
final class ArchivePdfFallbackSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'file', 'saho_linkfix'];

  /**
   * The subscriber under test.
   *
   * @var \Drupal\saho_linkfix\EventSubscriber\ArchivePdfFallbackSubscriber
   */
  protected $subscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->subscriber = $this->container->get('saho_linkfix.archive_pdf_fallback_subscriber');

    // A genuine PDF only in archive-files4.
    $this->writeFile('archive-files4/LaSep80.pdf', "%PDF-1.4\n%test\n");
    // An HTML page saved as .pdf in 2019-05, genuine copy in archive-files.
    $this->writeFile('2019-05/abjun73.pdf', "<!DOCTYPE html><html></html>");
    $this->writeFile('archive-files/AbJun73.pdf', "%PDF-1.6\n%test\n");
    // Only an HTML copy exists: nothing to redirect to.
    $this->writeFile('2019-05/onlyhtml.pdf', "<!DOCTYPE html><html></html>");
    // An empty file is not a genuine copy either.
    $this->writeFile('archive-files/empty.pdf', '');
    // Mixed-case original known to file_managed.
    $this->writeFile('archive-files/CrMay87.1684.pdf', "%PDF-1.3\n%test\n");
    File::create([
      'uri' => 'public://archive-files/CrMay87.1684.pdf',
      'filename' => 'CrMay87.1684.pdf',
      'status' => 1,
    ])->save();
  }

  /**
   * Write a file under public://.
   */
  protected function writeFile(string $relative, string $contents): void {
    $dir = dirname('public://' . $relative);
    \Drupal::service('file_system')->prepareDirectory($dir, 1);
    file_put_contents('public://' . $relative, $contents);
  }

  /**
   * Dispatch an exception for a path and return the redirect target, or NULL.
   */
  protected function redirectFor(string $path, ?\Throwable $exception = NULL): ?string {
    $event = new ExceptionEvent(
      $this->container->get('http_kernel'),
      Request::create($path),
      HttpKernelInterface::MAIN_REQUEST,
      $exception ?? new NotFoundHttpException(),
    );
    $this->subscriber->onException($event);
    $response = $event->getResponse();
    if ($response) {
      $this->assertSame(301, $response->getStatusCode());
    }
    return $response ? $response->headers->get('Location') : NULL;
  }

  /**
   * The bare /archive/NAME.pdf path finds the copy in any archive directory.
   */
  public function testBareArchivePathRedirects(): void {
    $this->assertStringEndsWith('/archive-files4/LaSep80.pdf', (string) $this->redirectFor('/archive/LaSep80.pdf'));
  }

  /**
   * HTML-as-.pdf copies are skipped in favour of the genuine one.
   */
  public function testHtmlCopyIsSkipped(): void {
    $this->assertStringEndsWith('/archive-files/AbJun73.pdf', (string) $this->redirectFor('/archive/AbJun73.pdf'));
    $this->assertNull($this->redirectFor('/archive/onlyhtml.pdf'));
    $this->assertNull($this->redirectFor('/archive/empty.pdf'));
  }

  /**
   * A differently-cased request resolves through file_managed.filename.
   */
  public function testCaseInsensitiveMatch(): void {
    $this->assertStringEndsWith('/archive-files/CrMay87.1684.pdf', (string) $this->redirectFor('/archive/crmay87.1684.pdf'));
  }

  /**
   * Dead directories under the public path redirect too.
   */
  public function testDeadPublicDirectoriesRedirect(): void {
    $this->assertStringEndsWith('/archive-files4/LaSep80.pdf', (string) $this->redirectFor('/sites/default/files/archivefiles/LaSep80.pdf'));
    $this->assertStringEndsWith('/archive-files4/LaSep80.pdf', (string) $this->redirectFor('/sites/default/files/DC/LaSep80/LaSep80.pdf'));
  }

  /**
   * Unknown names, other paths, traversal and non-404s are left alone.
   */
  public function testLeavesOtherRequestsAlone(): void {
    $this->assertNull($this->redirectFor('/archive/no-such-file.pdf'));
    $this->assertNull($this->redirectFor('/archive/night-watchmen'));
    $this->assertNull($this->redirectFor('/archive/sub/LaSep80.pdf'));
    $this->assertNull($this->redirectFor('/archive/..%2FLaSep80.pdf'));
    $this->assertNull($this->redirectFor('/archive/LaSep80.pdf', new AccessDeniedHttpException()));
  }

}
