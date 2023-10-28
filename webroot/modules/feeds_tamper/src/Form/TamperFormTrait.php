<?php

namespace Drupal\feeds_tamper\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds_tamper\FeedTypeTamperManagerInterface;
use Drupal\tamper\TamperManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Trait TamperFormTrait.
 *
 * Provides helper methods for the Tamper forms.
 *
 * @package Drupal\feeds_tamper\Form
 */
trait TamperFormTrait {

  /**
   * The feed item we are adding a tamper plugin to.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedsFeedType;

  /**
   * The tamper plugin instance.
   *
   * @var \Drupal\tamper\TamperInterface
   */
  protected $plugin;

  /**
   * The tamper plugin manager.
   *
   * @var \Drupal\tamper\TamperManagerInterface
   */
  protected $tamperManager;

  /**
   * The feeds tamper metadata manager.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperManagerInterface
   */
  protected $feedTypeTamperManager;

  /**
   * Sets the tamper manager.
   *
   * @param \Drupal\tamper\TamperManagerInterface $tamper_manager
   *   Tamper plugin manager.
   */
  public function setTamperManager(TamperManagerInterface $tamper_manager) {
    $this->tamperManager = $tamper_manager;
  }

  /**
   * Sets the feed type tamper manager.
   *
   * @param \Drupal\feeds_tamper\FeedTypeTamperManagerInterface $feed_type_tamper_manager
   *   Feed type tamper manager.
   */
  public function setTamperMetaManager(FeedTypeTamperManagerInterface $feed_type_tamper_manager) {
    $this->feedTypeTamperManager = $feed_type_tamper_manager;
  }

  /**
   * Makes sure that the tamper exists.
   *
   * @param \Drupal\feeds\FeedTypeInterface $feeds_feed_type
   *   The feed.
   * @param string $tamper_uuid
   *   The tamper uuid.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   In case a Tamper plugin with the given uuid could not be found.
   */
  protected function assertTamper(FeedTypeInterface $feeds_feed_type, $tamper_uuid) {
    $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($feeds_feed_type);

    try {
      $tamper_meta->getTamper($tamper_uuid);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Checks access for the form page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    if ($account->hasPermission('administer feeds_tamper')) {
      return AccessResult::allowed();
    }

    /** @var \Drupal\feeds\Entity\FeedType $feed_type */
    $feed_type = $route_match->getParameter('feeds_feed_type');
    return AccessResult::allowedIf($account->hasPermission('tamper ' . $feed_type->id()))
      ->addCacheableDependency($feed_type);
  }

}
