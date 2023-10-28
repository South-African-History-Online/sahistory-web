<?php

namespace Drupal\uc_store;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;

/**
 * Deprecated. Handles encryption of credit-card information.
 *
 * @deprecated in Ubercart 8.x-4.x. This class is provided only for
 *   backwards compatibility with Drupal 6 and Drupal 7 Ubercart sites.
 *
 * Trimmed down version of GPL class by Tony Marston.  Details available at
 * http://www.tonymarston.co.uk/php-mysql/encryption.html
 *
 * Usage:
 * 1) Obtain the encryption object.
 *    ex: $crypt = \Drupal::service('uc_store.encryption');
 * 2) To encrypt string data, use the encrypt method with the key.
 *    ex: $encrypted = $crypt->encrypt($key, $string);
 * 3) To decrypt string data, use the decrypt method with the original key.
 *    ex: $decrypted = $crypt->decrypt($key, $string);
 * 4) To check for errors, use the errors method to return an array of errors.
 *    ex: $errors = $crypt->getErrors();
 */
class MarstonEncryption implements EncryptionInterface {

  protected static $scramble1 = '! #$%&()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[]^_`"abcdefghijklmnopqrstuvwxyz{|}~';
  protected static $scramble2 = 'f^jAE]okIOzU[2&q1{3`h5w_794p@6s8?BgP>dFV=m" D<TcS%Ze|r:lGK/uCy.Jx)HiQ!#$~(;Lt-R}Ma,NvW+Ynb*0X';

  protected $errors = array();
  protected $adj = 1.75;
  protected $mod = 3;


  /**
   * {@inheritdoc}
   */
  public function encrypt($key, $plaintext, $sourcelen = 0) {
    $this->errors = array();

    // Convert key into sequence of numbers.
    $fudgefactor = $this->convertKey($key);
    if ($this->errors) {
      return;
    }

    if (empty($plaintext)) {
      // Commented out to prevent errors getting logged for use cases that may
      // have variable encryption/decryption requirements. -RS
      // $this->errors[] = t('No value has been supplied for encryption');
      return;
    }

    while (strlen($plaintext) < $sourcelen) {
      $plaintext .= ' ';
    }

    $target = NULL;
    $factor2 = 0;

    for ($i = 0; $i < Unicode::strlen($plaintext); $i++) {
      $char1 = Unicode::substr($plaintext, $i, 1);

      $num1 = strpos(self::$scramble1, $char1);
      if ($num1 === FALSE) {
        $this->errors[] = t('Source string contains an invalid character (@char)', ['@char' => $char1]);
        return;
      }

      $adj = $this->applyFudgeFactor($fudgefactor);
      $factor1 = $factor2 + $adj;
      $num2 = round($factor1) + $num1;
      $num2 = $this->checkRange($num2);
      $factor2 = $factor1 + $num2;
      $char2 = substr(self::$scramble2, $num2, 1);
      $target .= $char2;
    }

    return $target;
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($key, $cyphertext) {
    $this->errors = array();

    // Convert key into sequence of numbers.
    $fudgefactor = $this->convertKey($key);
    if ($this->errors) {
      return;
    }

    if (empty($cyphertext)) {
      // Commented out to prevent errors getting logged for use cases that may
      // have variable encryption/decryption requirements. -RS
      // $this->errors[] = t('No value has been supplied for decryption');
      return;
    }

    $target = NULL;
    $factor2 = 0;

    for ($i = 0; $i < strlen($cyphertext); $i++) {
      $char2 = substr($cyphertext, $i, 1);

      $num2 = strpos(self::$scramble2, $char2);
      if ($num2 === FALSE) {
        $this->errors[] = t('Source string contains an invalid character (@char)', ['@char' => $char2]);
        return;
      }

      $adj = $this->applyFudgeFactor($fudgefactor);
      $factor1 = $factor2 + $adj;
      $num1 = $num2 - round($factor1);
      $num1 = $this->checkRange($num1);
      $factor2 = $factor1 + $num2;

      $char1 = substr(self::$scramble1, $num1, 1);
      $target .= $char1;
    }

    return rtrim($target);
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * {@inheritdoc}
   */
  public function setErrors(array $errors) {
    $this->errors = $errors;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCypher($cypher) {
    // This function is a no-op for MarstonEncryption.
    return $this;
  }

  /**
   * Accessor for adj property.
   */
  public function getAdjustment() {
    return $this->adj;
  }

  /**
   * Mutator for adj property.
   */
  public function setAdjustment($adj) {
    $this->adj = (float) $adj;
    return $this;
  }

  /**
   * Accessor for mod property.
   */
  public function getModulus() {
    return $this->mod;
  }

  /**
   * Mutator for mod property.
   */
  public function setModulus($mod) {
    $this->mod = (int) abs($mod);
    return $this;
  }

  /**
   * Returns an adjustment value based on the contents of $fudgefactor.
   */
  protected function applyFudgeFactor(&$fudgefactor) {
    static $alerted = FALSE;

    if (!is_array($fudgefactor)) {
      $fudge = 0;
      if (!$alerted) {
        // Throw an error that makes sense so this stops getting reported.
        $this->errors[] = t('No encryption key was found.');
        drupal_set_message(t('Ubercart cannot find a necessary encryption key. Refer to the store admin <a href=":url">dashboard</a> to isolate which one.', [':url' => Url::fromRoute('uc_store.admin')->toString()]), 'error');

        $alerted = TRUE;
      }
    }
    else {
      $fudge = array_shift($fudgefactor);
    }

    $fudge = $fudge + $this->adj;
    $fudgefactor[] = $fudge;

    if (!empty($this->mod)) {
      if ($fudge % $this->mod == 0) {
        $fudge = $fudge * -1;
      }
    }

    return $fudge;
  }

  /**
   * Checks that $num points to an entry in self::$scramble1.
   */
  protected function checkRange($num) {
    $num = round($num);
    $limit = strlen(self::$scramble1);

    while ($num >= $limit) {
      $num = $num - $limit;
    }
    while ($num < 0) {
      $num = $num + $limit;
    }

    return $num;
  }

  /**
   * Converts encryption key into an array of numbers.
   *
   * @param string $key
   *   Encryption key.
   *
   * @return array
   *   Array of integers.
   */
  protected function convertKey($key) {
    if (empty($key)) {
      // Commented out to prevent errors getting logged for use cases that may
      // have variable encryption/decryption requirements. -RS
      // $this->errors[] = 'No value has been supplied for the encryption key';
      return;
    }

    $array[] = strlen($key);

    $tot = 0;
    for ($i = 0; $i < strlen($key); $i++) {
      $char = substr($key, $i, 1);

      $num = strpos(self::$scramble1, $char);
      if ($num === FALSE) {
        $this->errors[] = t('Key contains an invalid character (@char)', ['@char' => $char]);
        return;
      }

      $array[] = $num;
      $tot = $tot + $num;
    }

    $array[] = $tot;

    return $array;
  }

}
