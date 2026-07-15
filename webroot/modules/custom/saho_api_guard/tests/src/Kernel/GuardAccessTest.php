<?php

declare(strict_types=1);

namespace Drupal\Tests\saho_api_guard\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests the API guard access hooks and the forced-unpublish presave.
 *
 * @group saho_api_guard
 */
final class GuardAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'saho_api_guard',
  ];

  /**
   * The restricted service account (harvester-shaped permissions).
   */
  protected User $restricted;

  /**
   * An unrestricted editor with administer nodes.
   */
  protected User $editor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['saho_api_guard']);

    NodeType::create(['type' => 'archive', 'name' => 'Archive'])->save();

    // User 1 is a superuser; burn it so test accounts get real access checks.
    User::create(['name' => 'root'])->save();

    $this->restricted = $this->createUserWithPermissions([
      'access content',
      'create archive content',
      'edit own archive content',
      'edit any archive content',
      'restricted to unpublished content (api guard)',
    ], 'svc_harvester');

    $this->editor = $this->createUserWithPermissions([
      'access content',
      'create archive content',
      'edit any archive content',
      'administer nodes',
    ], 'editor');
  }

  /**
   * A node saved by a restricted account is always stored unpublished.
   */
  public function testPresaveForcesUnpublished(): void {
    $this->setCurrentUser($this->restricted);
    $node = Node::create([
      'type' => 'archive',
      'title' => 'Harvested item',
      'uid' => $this->restricted->id(),
      'status' => 1,
    ]);
    $node->save();
    $this->assertFalse($node->isPublished(), 'Restricted save is forced unpublished even when status=1 was set.');
  }

  /**
   * Accounts with administer nodes are not affected by the presave guard.
   */
  public function testPresaveLeavesEditorsAlone(): void {
    $this->setCurrentUser($this->editor);
    $node = Node::create([
      'type' => 'archive',
      'title' => 'Editorial item',
      'uid' => $this->editor->id(),
      'status' => 1,
    ]);
    $node->save();
    $this->assertTrue($node->isPublished(), 'Editor saves keep their published status.');
  }

  /**
   * Restricted accounts cannot update or delete published nodes.
   */
  public function testPublishedContentLockout(): void {
    $published = Node::create([
      'type' => 'archive',
      'title' => 'Live record',
      'uid' => $this->editor->id(),
      'status' => 1,
    ]);
    $published->save();

    $draft = Node::create([
      'type' => 'archive',
      'title' => 'Draft record',
      'uid' => $this->editor->id(),
      'status' => 0,
    ]);
    $draft->save();

    $this->assertFalse($published->access('update', $this->restricted), 'Restricted account cannot update a published node despite edit any.');
    $this->assertFalse($published->access('delete', $this->restricted), 'Restricted account cannot delete a published node.');
    $this->assertTrue($draft->access('update', $this->restricted), 'Restricted account can update an unpublished node.');
    $this->assertTrue($published->access('update', $this->editor), 'Editor retains update access to published nodes.');
  }

  /**
   * The view marker grants read access to others' unpublished nodes.
   */
  public function testViewAnyUnpublished(): void {
    $draft = Node::create([
      'type' => 'archive',
      'title' => 'Someone else’s draft',
      'uid' => $this->editor->id(),
      'status' => 0,
    ]);
    $draft->save();

    $this->assertFalse($draft->access('view', $this->restricted), 'Without the marker, unpublished nodes of other accounts are not visible.');

    $viewer = $this->createUserWithPermissions([
      'access content',
      'view any unpublished content (api guard)',
    ], 'svc_aiditor');
    $this->assertTrue($draft->access('view', $viewer), 'The view marker grants access to unpublished nodes owned by others.');
  }

  /**
   * Locks in the core mechanism the roles depend on: status field access.
   */
  public function testStatusFieldEditAccess(): void {
    $node = Node::create([
      'type' => 'archive',
      'title' => 'Field access probe',
      'uid' => $this->restricted->id(),
      'status' => 0,
    ]);
    $node->save();

    $this->assertFalse(
      $node->get('status')->access('edit', $this->restricted),
      'Without administer node published status, the status field is not editable - JSON:API 403s payloads that include it.'
    );
    $this->assertTrue(
      $node->get('status')->access('edit', $this->editor),
      'administer nodes unlocks status field edits.'
    );
  }

  /**
   * Creates a user with a dedicated role holding the given permissions.
   *
   * @param string[] $permissions
   *   Permissions for the role.
   * @param string $name
   *   Account name (doubles as role id).
   *
   * @return \Drupal\user\Entity\User
   *   The saved user.
   */
  protected function createUserWithPermissions(array $permissions, string $name): User {
    $role = Role::create(['id' => $name . '_role', 'label' => $name]);
    foreach ($permissions as $permission) {
      $role->grantPermission($permission);
    }
    $role->save();
    $user = User::create(['name' => $name, 'roles' => [$role->id()]]);
    $user->save();
    return $user;
  }

  /**
   * Sets the acting user for presave hooks.
   */
  protected function setCurrentUser(User $user): void {
    $this->container->get('current_user')->setAccount($user);
  }

}
