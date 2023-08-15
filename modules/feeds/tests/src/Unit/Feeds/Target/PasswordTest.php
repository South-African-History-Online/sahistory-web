<?php

namespace Drupal\Tests\feeds\Unit\Feeds\Target;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Password\PhpassHashedPassword;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\FeedTypeInterface;
use Drupal\feeds\Feeds\Target\Password;

/**
 * @coversDefaultClass \Drupal\feeds\Feeds\Target\Password
 * @group feeds
 */
class PasswordTest extends FieldTargetTestBase {

  /**
   * The password hash service.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Core\Password\PasswordInterface
   */
  protected $passwordHasher;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->passwordHasher = $this->prophesize(PasswordInterface::class);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Instantiates the FeedsTarget plugin "password".
   *
   * @param array $configuration
   *   (optional) The configuration to pass to the plugin.
   *
   * @return \Drupal\feeds\Feeds\Target\Password
   *   A password target instance.
   */
  protected function instantiatePlugin(array $configuration = []) {
    $method = $this->getMethod(Password::class, 'prepareTarget')->getClosure();

    $configuration += [
      'feed_type' => $this->createMock(FeedTypeInterface::class),
      'target_definition' => $method($this->getMockFieldDefinition()),
    ];

    return new Password($configuration, 'password', [], $this->passwordHasher->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetClass() {
    return Password::class;
  }

  /**
   * Tests preparing a plain text password.
   *
   * @covers ::prepareValue
   */
  public function testPrepareValueUsingPlainPassword() {
    $target = $this->instantiatePlugin();

    // Test password as a plain text.
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = ['value' => 'password'];
    $method(0, $values);
    $this->assertSame('password', $values['value']);
  }

  /**
   * Tests preparing a md5 hashed password.
   *
   * @covers ::prepareValue
   */
  public function testPrepareValueUsingMd5Password() {
    $md5 = md5('password');
    $this->passwordHasher->hash($md5)
      ->willReturn('$S$5psAlzq7nesZ7uXLLMRPHI45GL3PaadvAP9.kmYHIh6QMDq0EFhc');

    $target = $this->instantiatePlugin([
      'pass_encryption' => Password::PASS_MD5,
    ]);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = [
      'value' => $md5,
    ];

    $method(0, $values);
    $this->assertSame('U$S$5psAlzq7nesZ7uXLLMRPHI45GL3PaadvAP9.kmYHIh6QMDq0EFhc', $values['value']);
    $this->assertSame(TRUE, $values['pre_hashed']);
  }

  /**
   * Tests preparing a md5 hashed password that fails.
   *
   * @covers ::prepareValue
   */
  public function testPrepareValueUsingMd5PasswordThatFails() {
    $md5 = md5('password');
    $this->passwordHasher->hash($md5)
      ->willReturn(FALSE);

    $target = $this->instantiatePlugin([
      'pass_encryption' => Password::PASS_MD5,
    ]);

    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = [
      'value' => $md5,
    ];

    $this->expectException(TargetValidationException::class);
    $method(0, $values);
  }

  /**
   * Tests preparing a sha512 hashed password.
   *
   * @covers ::prepareValue
   */
  public function testPrepareValueUsingHashedPassword() {
    $target = $this->instantiatePlugin([
      'pass_encryption' => Password::PASS_SHA512,
    ]);

    $hasher = new PhpassHashedPassword(1);
    $hash = $hasher->hash(md5('password'));
    $method = $this->getProtectedClosure($target, 'prepareValue');

    $values = [
      'value' => $hash,
    ];

    $method(0, $values);
    $this->assertSame($hash, $values['value']);
    $this->assertSame(TRUE, $values['pre_hashed']);
  }

}
