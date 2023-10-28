<?php

namespace Drupal\uc_store;

use Drupal\Component\Utility\Crypt;

/**
 * Provides encryption and decryption using OpenSSL.
 */
class OpenSSLEncryption implements EncryptionInterface {

  // Default to using AES cypher in CBC mode.
  protected $cypher = 'AES-128-CBC';

  protected $errors = array();

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    $errors = $this->errors;
    // Reset error array.
    $this->errors = array();
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function setErrors(array $errors) {
    $this->errors = $errors;
    return $this;
  }

  /**
   * Sets the cypher used.
   *
   * Available cyphers can be seen by using the command-line command
   * 'openssl list-cipher-algorithms'.
   *
   * @param string $cypher
   *   The cypher to use.
   *
   * @return $this
   */
  public function setCypher($cypher) {
    $methods = openssl_get_cipher_methods();
    if (in_array($cypher, $methods)) {
      $this->cypher = $cypher;
    }
    else {
      // Set an error, don't change cypher.
      $this->errors[] = t('@cypher is not a valid cypher', ['@cypher' => $cypher]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($key, $plaintext, $sourcelen = 0) {
    $iv = Crypt::randomBytes(16);
    $encrypted = openssl_encrypt($plaintext, $this->cypher, $key, OPENSSL_RAW_DATA, $iv);
    if (FALSE === $encrypted) {
      $this->errors[] = t('Unknown error encrypting plaintext.');
    }
    return bin2hex($iv) . base64_encode($encrypted);
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($key, $cyphertext) {
    $iv = hex2bin(substr($cyphertext, 0, 32));
    $decrypted = openssl_decrypt(base64_decode(substr($cyphertext, 32)), $this->cypher, $key, OPENSSL_RAW_DATA, $iv);
    if (FALSE === $decrypted) {
      $this->errors[] = t('Unknown error decrypting plaintext.');
    }
    return $decrypted;
  }

}
