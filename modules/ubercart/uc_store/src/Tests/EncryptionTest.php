<?php

namespace Drupal\uc_store\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the encryption and decryption of strings.
 *
 * @group Ubercart
 */
class EncryptionTest extends WebTestBase {

  // Need access to uc_store.encryption service.
  public static $modules = array('uc_store');

  /**
   * Encryption object
   *
   * @var \Drupal\uc_store\EncryptionInterface
   */
  protected $crypt;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->crypt = \Drupal::service('uc_store.encryption');
  }

  /**
   * Tests operation of uc_store.encryption service.
   */
  public function testEncryptionService() {
    // 16-byte random key.
    $key = $this->randomMachineName(16);

    $this->crypt->setCypher('ubbi-dubbi');
    $errors = $this->crypt->getErrors();
    if (!empty($errors)) {
      $this->pass('Tried to use invalid cypher.');
      $this->assertEqual($errors[0], 'ubbi-dubbi is not a valid cypher', 'Invalid cypher error message found.');
    }

    $this->crypt->setCypher('aes-128-cbc');
    $errors = $this->crypt->getErrors();
    if (empty($errors)) {
      $this->pass('AES-128-CBC cypher found.');
    }

    $plaintext = 'The quick brown fox jumps over the lazy dog.';
    $cyphertext = $this->crypt->encrypt($key, $plaintext);
    $errors = $this->crypt->getErrors();
    $this->assertTrue(empty($errors), 'Encryption successful.');

    $decrypted = $this->crypt->decrypt($key, $cyphertext);
    $errors = $this->crypt->getErrors();
    $this->assertTrue(empty($errors), 'Decryption successful.');
    $this->assertEqual($decrypted, $plaintext, 'Decrypted text is the same as initial plaintext.');
  }

}
