<?php

namespace Drupal\uc_store;


/**
 * Provides common interface for encryption methods.
 */
interface EncryptionInterface {

  /**
   * Encrypts plaintext.
   *
   * @param string $key
   *   Key used for encryption.
   * @param string $plaintext
   *   Text string to be encrypted.
   * @param int $sourcelen
   *   Minimum $plaintext length. Plaintext which is shorter than
   *   $sourcelen will be padded by appending spaces.
   *
   * @return string
   *   Cyphertext. String containing encrypted text.
   */
  public function encrypt($key, $plaintext, $sourcelen);

  /**
   * Decrypts cyphertext.
   *
   * @param string $key
   *   Key used for encryption.
   * @param string $cyphertext
   *   String containing text to be encrypted.
   *
   * @return string
   *   Plaintext. Decrypted text.
   */
  public function decrypt($key, $cyphertext);

  /**
   * Accessor for errors property.
   *
   * @return array
   *   Array of text strings containing error messages.
   */
  public function getErrors();

  /**
   * Mutator for errors property.
   *
   * @param array $errors
   *   Array of text strings containing error messages.
   *
   * @return $this
   */
  public function setErrors(array $errors);

  /**
   * Sets the cypher used.
   *
   * @param string $cypher
   *   The cypher to use.
   *
   * @return $this
   */
  public function setCypher($cypher);

}
