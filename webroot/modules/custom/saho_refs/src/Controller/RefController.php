<?php

declare(strict_types=1);

namespace Drupal\saho_refs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\saho_refs\DisplayRefService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves a display reference (/ref/{ref}) to its node.
 */
final class RefController extends ControllerBase {

  /**
   * Constructs the controller.
   *
   * @param \Drupal\saho_refs\DisplayRefService $displayRef
   *   The display reference service.
   */
  public function __construct(
    private readonly DisplayRefService $displayRef,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self($container->get('saho_refs.display_ref'));
  }

  /**
   * 301-redirects a display reference to the referenced node.
   *
   * @param string $ref
   *   The display reference (e.g. "B-0085550").
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A permanent redirect to the node's canonical URL.
   */
  public function resolve(string $ref): RedirectResponse {
    $nid = $this->displayRef->nidFromRef($ref);
    $node = $nid ? $this->entityTypeManager()->getStorage('node')->load($nid) : NULL;
    if (!$node instanceof NodeInterface || !$node->access('view')) {
      throw new NotFoundHttpException();
    }
    return new RedirectResponse($node->toUrl('canonical', ['absolute' => TRUE])->toString(), 301);
  }

}
