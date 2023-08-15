<?php

namespace Drupal\Tests\file_entity\FunctionalJavascript;

use Drupal\Core\Config\Config;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests file entity settings.
 *
 * @group file_entity
 */
class FileEntitySettingsTest extends WebDriverTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['file_entity', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * File entity config.
   *
   * @var Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config = $this->config('file_entity.settings');
  }

  /**
   * Tests file image formatter settings.
   */
  public function testFileImageFormatterSettings() {
    $assert_session = $this->assertSession();
    $account = $this->drupalCreateUser([
      'administer file display'
    ]);

    $this->drupalLogin($account);
    $this->drupalGet('admin/structure/file-types/manage/image/edit/display');
    $assert_session->pageTextContains('Field used for the image title attribute: field_image_title_text');
    $assert_session->pageTextContains('Field used for the image title attribute: field_image_title_text');

    $this->drupalPostForm(NULL, [], 'uri_settings_edit');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->responseContains('fields[uri][settings_edit_form][settings][title]');
    $assert_session->responseContains('fields[uri][settings_edit_form][settings][alt]');

    $edit = [
      'fields[uri][settings_edit_form][settings][title]' => '_none',
      'fields[uri][settings_edit_form][settings][alt]' => '_none',
    ];
    $this->drupalPostForm(NULL, $edit, 'Update');
    $assert_session->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], 'Save');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Title attribute is hidden.');
    $assert_session->pageTextContains('Alt attribute is hidden.');

    $this->drupalLogin($this->drupalCreateUser(['create files']));
    $test_file = $this->getTestFiles('image');
    $this->drupalGet('file/add');
    $page = $this->getSession()->getPage();
    $page->attachFileToField('files[upload]', $this->container->get('file_system')->realpath($test_file[0]->uri));
    $assert_session->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], 'Next');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('Destination');
    $this->drupalPostForm(NULL, [], 'Next');
    $edit = [
      'field_image_alt_text[0][value]' => 'Alt text',
      'field_image_title_text[0][value]' => 'Title text',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $assert_session->responseNotContains('alt="Alt text"', 'Alt attribute is hidden.');
    $assert_session->responseNotContains('title="Title text"', 'Title attribute is hidden.');
  }
}
