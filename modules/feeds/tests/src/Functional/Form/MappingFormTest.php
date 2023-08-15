<?php

namespace Drupal\Tests\feeds\Functional\Form;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\feeds\Form\MappingForm
 * @group feeds
 */
class MappingFormTest extends FeedsBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'feeds',
    'node',
    'user',
    'feeds_test_plugin',
  ];

  /**
   * Tests that custom source names are unique.
   */
  public function testCustomSourceUnique() {
    // Create a feed type with two custom sources called 'guid' and 'title'.
    $feed_type = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
    ]);

    // Add a new target.
    $edit = [
      'add_target' => 'status',
    ];
    $this->drupalPostForm('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping', $edit, 'Save');

    // Now try to map to a new source called 'title'. This shouldn't be allowed
    // because that source name already exists on the feed type.
    $edit = [
      'mappings[2][map][value][select]' => 'custom__csv',
      'mappings[2][map][value][custom__csv][value]' => 'title',
      'mappings[2][map][value][custom__csv][machine_name]' => 'title',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Assert that the double source name is detected.
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
  }

  /**
   * Tests adding a CSV source for the CSV parser.
   */
  public function testAddCsvSource() {
    // Create a feed type for CSV with zero mappings.
    $feed_type = $this->createFeedTypeForCsv([], [
      'mappings' => [],
    ]);

    // Add a new target.
    $edit = [
      'add_target' => 'title',
    ];
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping');
    $this->submitForm($edit, 'Save');

    // Create a new CSV source and map that.
    $edit = [
      'mappings[0][map][value][select]' => 'custom__csv',
      'mappings[0][map][value][custom__csv][value]' => 'title',
      'mappings[0][map][value][custom__csv][machine_name]' => 'title',
    ];
    $this->submitForm($edit, 'Save');

    // Reload feed type and assert that a custom source of type "csv" was added.
    $feed_type = $this->reloadEntity($feed_type);
    $expected = [
      'title' => [
        'value' => 'title',
        'label' => 'title',
        'type' => 'csv',
        'machine_name' => 'title',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests adding a blank source for the RSS parser.
   */
  public function testAddBlankSource() {
    // Create a feed type for RSS with zero mappings.
    $feed_type = $this->createFeedType([
      'mappings' => [],
    ]);

    // Add a new target.
    $edit = [
      'add_target' => 'title',
    ];
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping');
    $this->submitForm($edit, 'Save');

    // Create a new blank source and map that.
    $edit = [
      'mappings[0][map][value][select]' => 'custom__blank',
      'mappings[0][map][value][custom__blank][value]' => 'title',
      'mappings[0][map][value][custom__blank][machine_name]' => 'title',
    ];
    $this->submitForm($edit, 'Save');

    // Reload feed type and assert that a custom source of type "blank" was
    // added.
    $feed_type = $this->reloadEntity($feed_type);
    $expected = [
      'title' => [
        'value' => 'title',
        'label' => 'title',
        'type' => 'blank',
        'machine_name' => 'title',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests adding a blank source for a parser that does not extend ParserBase.
   */
  public function testAddBlankSourceForBasicParser() {
    // Create a feed type with the parser "Parser with mapping form".
    // That parser does not extend \Drupal\feeds\Feeds\Parser\ParserBase.
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_mapping_form',
      'mappings' => [],
    ]);

    // Add a new target and set field specific for this parser.
    $edit = [
      'dummy' => 'dummyValue',
      'add_target' => 'title',
    ];
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping');
    $this->submitForm($edit, 'Save');

    // Create a new blank source and map that.
    $edit = [
      'mappings[0][map][value][select]' => 'custom__blank',
      'mappings[0][map][value][custom__blank][value]' => 'title',
      'mappings[0][map][value][custom__blank][machine_name]' => 'title',
    ];
    $this->submitForm($edit, 'Save');

    // Reload feed type and assert that a custom source of type "blank" was
    // added.
    $feed_type = $this->reloadEntity($feed_type);
    $expected = [
      'title' => [
        'value' => 'title',
        'label' => 'title',
        'type' => 'blank',
        'machine_name' => 'title',
      ],
    ];
    $this->assertEquals($expected, $feed_type->getCustomSources());
  }

  /**
   * Tests that validation handlers on custom sources are ran.
   */
  public function testAddCustomSourceValidation() {
    // Create a feed type with the parser "Parser with Foo Source", because for
    // that parser the custom source type "Foo" is available.
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_foo_source',
      'mappings' => [],
    ]);

    // Add a new target.
    $edit = [
      'add_target' => 'title',
    ];
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping');
    $this->submitForm($edit, 'Save');

    // Try to map a source of type "Foo" to the title. Give it a value that
    // triggers a validation error.
    $edit = [
      'mappings[0][map][value][select]' => 'custom__foo',
      'mappings[0][map][value][custom__foo][value]' => 'title',
      'mappings[0][map][value][custom__foo][machine_name]' => 'title',
      'mappings[0][map][value][custom__foo][proptext]' => 'Illegal value',
    ];
    $this->submitForm($edit, 'Save');

    // Assert that an error message is displayed.
    $this->assertSession()->pageTextContains('The textfield contains "Illegal value".');

    // Reload feed type and assert that no custom sources were added.
    $feed_type = $this->reloadEntity($feed_type);
    $this->assertEquals([], $feed_type->getCustomSources());
  }

  /**
   * Tests that plugins can alter the mapping form.
   */
  public function testPluginWithMappingForm() {
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_mapping_form',
    ]);

    $edit = [
      'dummy' => 'dummyValue',
    ];

    $this->drupalPostForm('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping', $edit, 'Save');

    // Assert that the dummy value was saved for the parser.
    $feed_type = $this->reloadEntity($feed_type);
    $config = $feed_type->getParser()->getConfiguration();
    $this->assertEquals('dummyValue', $config['dummy']);
  }

  /**
   * Tests that plugins validate the mapping form.
   */
  public function testPluginWithMappingFormValidate() {
    $feed_type = $this->createFeedType([
      'parser' => 'parser_with_mapping_form',
    ]);

    // ParserWithMappingForm::mappingFormValidate() doesn't accept the value
    // 'invalid'.
    $edit = [
      'dummy' => 'invalid',
    ];

    $this->drupalPostForm('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping', $edit, 'Save');
    $this->assertSession()->responseContains('Invalid value.');

    // Assert that the dummy value was *not* saved for the parser.
    $feed_type = $this->reloadEntity($feed_type);
    $config = $feed_type->getParser()->getConfiguration();
    $this->assertEquals('', $config['dummy']);
  }

  /**
   * Tests mapping to entity ID target without setting it as unique.
   *
   * When adding a target to entity ID and set it is not as unique, a warning
   * should get displayed, recommending to set the target as unique.
   */
  public function testMappingToEntityIdWarning() {
    $feed_type = $this->createFeedType();

    // Add mapping to node ID.
    $edit = [
      'add_target' => 'nid',
    ];
    $this->drupalPostForm('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping', $edit, 'Save');

    // Now untick "unique".
    $edit = [
      'mappings[2][unique][value]' => 0,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Assert that a message is being displayed.
    $this->assertSession()->pageTextContains('When mapping to the entity ID (ID), it is recommended to set it as unique.');

    // But ensure "unique" can get unticked for entity ID targets anyway.
    // Because this could perhaps be useful in advanced use cases.
    $feed_type = $this->reloadEntity($feed_type);
    $mapping = $feed_type->getMappings()[2];
    $this->assertTrue(empty($mapping['unique']['value']));
  }

  /**
   * Tests that the mapping page is displayed with a missing target plugin.
   */
  public function testMissingTargetWarning() {
    // Create a feed type and map to a non-existent target.
    $feed_type = $this->createFeedType();
    $feed_type->addMapping([
      'target' => 'non_existent',
      'map' => ['value' => 'title'],
    ]);
    $feed_type->save();

    // Go to the mapping page and assert that a warning is being displayed.
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping');
    $this->assertSession()->pageTextContains('The Feeds target "non_existent" does not exist.');
    $this->assertSession()->pageTextContains('Error: target is missing (non_existent)');

    // Try to resolve the issue by removing the mapping.
    $edit = [
      'remove_mappings[2]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Reload the page to clear any warnings.
    $this->drupalGet('/admin/structure/feeds/manage/' . $feed_type->id() . '/mapping');

    // Assert that the warning is no longer displayed.
    $this->assertSession()->pageTextNotContains('The Feeds target "non_existent" does not exist.');
    $this->assertSession()->pageTextNotContains('Error: target is missing (non_existent)');

    // Assert that the particular mapping no longer exists on the feed type.
    $feed_type = $this->reloadEntity($feed_type);
    $this->assertEquals($this->getDefaultMappings(), $feed_type->getMappings());
  }

}
