<?php

namespace Drupal\Tests\feeds_tamper\Functional;

/**
 * Tests adding/editing/removing tamper plugins using the UI.
 *
 * @group feeds_tamper
 */
class UiCrudTest extends FeedsTamperBrowserTestBase {

  /**
   * A feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * The url to the tamper listing page.
   *
   * @var string
   */
  protected $url;

  /**
   * The manager for FeedTypeTamperMeta instances.
   *
   * @var \Drupal\feeds_tamper\FeedTypeTamperManager
   */
  protected $feedTypeTamperManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Add body field.
    node_add_body_field($this->nodeType);

    // Add a feed type with mapping to body.
    $this->feedType = $this->createFeedType([
      'mappings' => array_merge($this->getDefaultMappings(), [
        [
          'target' => 'body',
          'map' => [
            'summary' => 'description',
            'value' => 'content',
          ],
        ],
      ]),
    ]);

    $this->url = $this->feedType->toUrl('tamper');

    $this->feedTypeTamperManager = \Drupal::service('feeds_tamper.feed_type_tamper_manager');
  }

  /**
   * Tests adding a Tamper plugin using the UI.
   */
  public function testAddTamperInstance() {
    // Go to the tamper listing.
    $this->drupalGet($this->url);

    // Click link for adding a tamper plugin to the source 'description'.
    $this->getSession()
      ->getPage()
      ->find('css', '#edit-description-add-link')
      ->click();

    // Select plugin.
    $edit = [
      'tamper_id' => 'trim',
    ];
    $this->drupalPostForm(NULL, $edit, 'Submit');

    // Configure plugin.
    $edit = [
      'plugin_configuration[label]' => 'Trim test',
      'plugin_configuration[side]' => 'ltrim',
    ];
    $this->drupalPostForm(NULL, $edit, 'Submit');

    // And assert that the tamper plugin was added.
    $this->feedType = $this->reloadEntity($this->feedType);
    $plugin_collection = $this->feedTypeTamperManager
      ->getTamperMeta($this->feedType, TRUE)
      ->getTampers();
    $this->assertCount(1, $plugin_collection);

    $tamper = $plugin_collection->getIterator()->current();
    $this->assertEquals('trim', $tamper->getPluginId());
    $this->assertEquals('Trim test', $tamper->getSetting('label'));
    $this->assertEquals('ltrim', $tamper->getSetting('side'));
    $this->assertEquals('description', $tamper->getSetting('source'));
  }

  /**
   * Tests editing a Tamper plugin using the UI.
   */
  public function testEditTamperInstance() {
    // Programmatically add a tamper plugin instance.
    $uuid = $this->feedTypeTamperManager
      ->getTamperMeta($this->feedType)
      ->addTamper([
        'plugin' => 'convert_case',
        'operation' => 'strtoupper',
        'label' => 'Str to Upper',
        'source' => 'title',
        'description' => 'Convert the case to uppercase.',
      ]);
    $this->feedType->save();

    // Go to the tamper listing.
    $this->drupalGet($this->url);

    // Click link for editing this tamper plugin.
    $this->getSession()
      ->getPage()
      ->find('css', '#edit-title ul.dropbutton li:nth-child(1) a')
      ->click();

    // Change a setting.
    $edit = [
      'plugin_configuration[operation]' => 'ucfirst',
    ];
    $this->drupalPostForm(NULL, $edit, 'Submit');

    // Assert that the tamper instance configuration was updated.
    $this->feedType = $this->reloadEntity($this->feedType);
    $plugin_collection = $this->feedTypeTamperManager
      ->getTamperMeta($this->feedType, TRUE)
      ->getTampers();
    $this->assertCount(1, $plugin_collection);

    $tamper = $plugin_collection->getIterator()->current();
    $this->assertEquals('convert_case', $tamper->getPluginId());
    $this->assertEquals($uuid, $tamper->getSetting('uuid'));
    $this->assertEquals('ucfirst', $tamper->getSetting('operation'));
    $this->assertEquals('title', $tamper->getSetting('source'));
  }

  /**
   * Tests removing a Tamper plugin using the UI.
   */
  public function testRemoveTamperInstance() {
    // Programmatically add a tamper plugin instance.
    $this->feedTypeTamperManager
      ->getTamperMeta($this->feedType, TRUE)
      ->addTamper([
        'plugin' => 'convert_case',
        'operation' => 'strtoupper',
        'label' => 'Str to Upper',
        'source' => 'title',
        'description' => 'Convert the case to uppercase.',
      ]);
    $this->feedType->save();

    // Go to the tamper listing.
    $this->drupalGet($this->url);

    // Click link for removing this tamper plugin.
    $this->getSession()
      ->getPage()
      ->find('css', '#edit-title ul.dropbutton li:nth-child(2) a')
      ->click();

    // Confirm.
    $this->drupalPostForm(NULL, [], 'Confirm');

    // Assert that the tamper instance was removed.
    $this->feedType = $this->reloadEntity($this->feedType);
    $plugin_collection = $this->feedTypeTamperManager
      ->getTamperMeta($this->feedType, TRUE)
      ->getTampers();
    $this->assertCount(0, $plugin_collection);
  }

  /**
   * Tests changing weights of Tamper plugins.
   */
  public function testChangeTamperOrder() {
    // Programmatically add a few tamper plugin instances.
    $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($this->feedType, TRUE);
    $uuid_content_1 = $tamper_meta->addTamper([
      'plugin' => 'explode',
      'label' => 'Explode',
      'separator' => '|',
      'source' => 'content',
    ]);
    $uuid_content_2 = $tamper_meta->addTamper([
      'plugin' => 'implode',
      'label' => 'Implode',
      'glue' => '-',
      'source' => 'content',
    ]);
    $uuid_content_3 = $tamper_meta->addTamper([
      'plugin' => 'trim',
      'label' => 'Trim Content',
      'side' => 'trim',
      'source' => 'content',
    ]);
    $uuid_title_1 = $tamper_meta->addTamper([
      'plugin' => 'trim',
      'label' => 'Trim Title',
      'side' => 'trim',
      'source' => 'title',
    ]);
    $uuid_title_2 = $tamper_meta->addTamper([
      'plugin' => 'required',
      'label' => 'Required',
      'invert' => FALSE,
      'source' => 'title',
    ]);
    $this->feedType->save();

    // Go to the tamper listing.
    $this->drupalGet($this->url);

    // Change weights.
    $edit = [
      "title[$uuid_title_1][weight]" => -9,
      "title[$uuid_title_2][weight]" => -10,
      "content[$uuid_content_1][weight]" => -10,
      "content[$uuid_content_2][weight]" => -8,
      "content[$uuid_content_3][weight]" => -9,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Assert that the weights of all tamper plugins were updated.
    $this->feedType = $this->reloadEntity($this->feedType);
    $tamper_meta = $this->feedTypeTamperManager->getTamperMeta($this->feedType, TRUE);
    $this->assertEquals(-9, $tamper_meta->getTamper($uuid_title_1)->getSetting('weight'));
    $this->assertEquals(-10, $tamper_meta->getTamper($uuid_title_2)->getSetting('weight'));
    $this->assertEquals(-10, $tamper_meta->getTamper($uuid_content_1)->getSetting('weight'));
    $this->assertEquals(-8, $tamper_meta->getTamper($uuid_content_2)->getSetting('weight'));
    $this->assertEquals(-9, $tamper_meta->getTamper($uuid_content_3)->getSetting('weight'));
  }

}
