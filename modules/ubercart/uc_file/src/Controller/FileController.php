<?php

namespace Drupal\uc_file\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles administrative view of files that may be purchased and downloaded.
 */
class FileController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a FileController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, FormBuilderInterface $form_builder) {
    $this->database = $database;
    $this->formBuilder = $form_builder;
  }

  /**
   * Displays list of all files available to attach to products.
   *
   * @return array
   *   A render array.
   */
  public function show() {
    $build['#tree'] = TRUE;
    $build['#attached']['library'][] = 'uc_file/uc_file.scripts';

    // Form that provides operations.
    $build['file_action_form'] = $this->formBuilder->getForm('Drupal\uc_file\Form\FileActionForm');

    // Table of files to operate on.
    $header = array(
      // Fake out tableselect JavaScript into operating on our table.
      array('data' => '', 'class' => array('select-all')),
      'filename' => array('data' => $this->t('File'), 'field' => 'f.filename', 'sort' => 'asc'),
      'title' => array('data' => $this->t('Product'), 'field' => 'n.title'),
      'model' => array('data' => $this->t('SKU'), 'field' => 'fp.model', 'class' => array(RESPONSIVE_PRIORITY_LOW)),
    );

    // Create pager.
    $query = $this->database->select('uc_files', 'f')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->limit(UC_FILE_PAGER_SIZE);
    $query->leftJoin('uc_file_products', 'fp', 'f.fid = fp.fid');
    $query->leftJoin('uc_product_features', 'pf', 'fp.pfid = pf.pfid');
    $query->leftJoin('node_field_data', 'n', 'pf.nid = n.nid');
    $query->addField('n', 'nid');
    $query->addField('f', 'filename');
    $query->addField('n', 'title');
    $query->addField('fp', 'model');
    $query->addField('f', 'fid');
    $query->addField('pf', 'pfid');

    $count_query = $this->database->select('uc_files');
    $count_query->addExpression('COUNT(*)');

    $query->setCountQuery($count_query);
    $result = $query->execute();

    $options = array();
    foreach ($result as $file) {
      // All files are shown here, including files which are not attached to products.
      if (isset($file->nid)) {
        // These are attached to products.
        $options[$file->fid] = array(
          'checked' => array('data' => array('#type' => 'checkbox', '#default_value' => 0)),
          'filename' => array(
            'data' => array('#plain_text' => $file->filename),
            'class' => is_dir(uc_file_qualify_file($file->filename)) ? array('uc-file-directory-view') : array(),
          ),
          'title' => array(
            'data' => array(
              '#type' => 'link',
              '#title' => $file->title,
              '#url' => Url::fromRoute('entity.node.canonical', ['node' => $file->nid]),
            ),
          ),
          'model' => array(
            'data' => array('#plain_text' => $file->model),
          ),
        );
      }
      else {
        // These are not attached to products.
        $options[$file->fid] = array(
          'checked' => array('data' => array('#type' => 'checkbox', '#default_value' => 0)),
          'filename' => array(
            'data' => array('#plain_text' => $file->filename),
            'class' => is_dir(uc_file_qualify_file($file->filename)) ? array('uc-file-directory-view') : array(),
          ),
          'title' => '',
          'model' => '',
        );
      }
    }

    // Create checkboxes for each file.
    $build['file_select'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $options,
      '#empty' => $this->t('No file downloads available.'),
    );
    $build['file_select_pager'] = array('#type' => 'pager');

    return $build;
  }

}
