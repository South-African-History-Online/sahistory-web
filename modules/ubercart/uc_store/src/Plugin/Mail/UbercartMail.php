<?php

namespace Drupal\uc_store\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;

/**
 * Modifies the Drupal mail system to send HTML emails.
 *
 * @Mail(
 *   id = "ubercart_mail",
 *   label = @Translation("Ubercart mailer"),
 *   description = @Translation("Sends the message as HTML, using PHP's native mail() function.")
 * )
 */
class UbercartMail extends PhpMail {

  /**
   * Concatenates the e-mail body for HTML mails.
   *
   * @param $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return
   *   The formatted $message.
   */
  public function format(array $message) {
    $message['body'] = implode("\n\n", $message['body']);
    return $message;
  }

}
