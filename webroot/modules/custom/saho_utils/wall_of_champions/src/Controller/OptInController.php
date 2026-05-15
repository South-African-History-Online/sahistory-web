<?php

namespace Drupal\wall_of_champions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * One-click "opt me in to the Wall of Champions" handler.
 *
 * Used from the order-completion page after a Champion signup, where we
 * want to capture the opt-in in the moment - the visitor would otherwise
 * have to discover the user profile form to flip
 * field_champion_wall_opt_in themselves. This controller flips it for
 * them and lands them on /champions so they see the wall they're on.
 *
 * The route requires _csrf_token + _user_is_logged_in, so an attacker
 * cannot opt a victim in via a forged link.
 */
class OptInController extends ControllerBase {

  /**
   * Sets field_champion_wall_opt_in = 1 on the current user.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to /champions, with a status flash on success.
   */
  public function optIn(): RedirectResponse {
    $uid = (int) $this->currentUser()->id();
    /** @var \Drupal\user\UserInterface|null $user */
    $user = $this->entityTypeManager()->getStorage('user')->load($uid);

    if ($user && $user->hasField('field_champion_wall_opt_in')) {
      $current = (int) $user->get('field_champion_wall_opt_in')->value;
      if ($current !== 1) {
        $user->set('field_champion_wall_opt_in', 1);
        $user->save();
        $this->messenger()->addStatus(
          $this->t('Thank you - your name will appear on the Wall of Champions.')
        );
      }
    }

    return $this->redirect('wall_of_champions.page');
  }

}
