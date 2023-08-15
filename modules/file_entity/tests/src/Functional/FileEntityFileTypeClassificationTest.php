<?php

namespace Drupal\Tests\file_entity\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\views\Views;

/**
 * Test existing file entity classification functionality.
 *
 * @group file_entity
 */
class FileEntityFileTypeClassificationTest extends BrowserTestBase {

  use CronRunTrait;
  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Get the file type of a given file.
   *
   * @param $file
   *   A file object.
   *
   * @return
   *   The file's file type as a string.
   */
  function getFileType($file) {
    $type = \Drupal::database()->select('file_managed', 'fm')
      ->fields('fm', array('type'))
      ->condition('fid', $file->id(), '=')
      ->execute()
      ->fetchAssoc();

    return $type;
  }

  /**
   * Test that existing files are properly classified by file type.
   */
  function testFileTypeClassification() {
    // Get test text and image files.
    $file = current($this->getTestFiles('text'));
    $text_file = File::create((array) $file);
    $text_file->save();
    $file = current($this->getTestFiles('image'));
    $image_file = File::create((array) $file);
    $image_file->save();

    // Enable file entity which adds adds a file type property to files and
    // queues up existing files for classification.
    \Drupal::service('module_installer')->install(array('file_entity'));
    $change_summary = \Drupal::entityDefinitionUpdateManager()->getChangeSummary();
    $this->assertTrue(empty($change_summary), 'No entity definition changes pending');

    // Existing files have yet to be classified and should have an undefined
    // file type.
    $file_type = $this->getFileType($text_file);
    $this->assertEqual($file_type['type'], 'undefined', t('The text file has an undefined file type.'));
    $file_type = $this->getFileType($image_file);
    $this->assertEqual($file_type['type'], 'undefined', t('The image file has an undefined file type.'));

    // When editing files before cron has run the bundle should have been
    // updated.
    $account = $this->drupalCreateUser(['bypass file access']);
    $this->drupalLogin($account);
    $this->assertNotEqual($image_file->bundle(), 'image', 'The image file does not have correct bundle before loading it.');
    $this->drupalGet('file/' . $image_file->id() . '/edit');
    $this->drupalPostForm(NULL, [], t('Save'));
    $image_file = File::load($image_file->id());
    $this->assertEqual($image_file->bundle(), 'image', 'The image file has correct bundle after load.');

    // The classification queue is processed during cron runs. Run cron to
    // trigger the classification process.
    $this->cronRun();

    // The classification process should assign a file type to any file whose
    // MIME type is assigned to a file type. Check to see if each file was
    // assigned a proper file type.
    $file_type = $this->getFileType($text_file);
    $this->assertEqual($file_type['type'], 'document', t('The text file was properly assigned the Document file type.'));
    $file_type = $this->getFileType($image_file);
    $this->assertEqual($file_type['type'], 'image', t('The image file was properly assigned the Image file type.'));

    // Uninstall the file_entity module and ensure that cron can run and files
    // can still be loaded.
    \Drupal::service('module_installer')->uninstall(['file_entity']);
    $this->assertEqual([], \Drupal::entityDefinitionUpdateManager()->getChangeList());

    $image_file = File::load($image_file->id());
    $this->assertEqual(get_class($image_file), File::class);
    $this->cronRun();
    Views::viewsData()->getAll();
  }

}
