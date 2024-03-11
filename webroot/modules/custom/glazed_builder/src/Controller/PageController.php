<?php

namespace Drupal\glazed_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PageController extends ControllerBase implements PageControllerInterface {

  /**
   * The form builder service
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Construct a PageController object
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service
   */
  public function __construct(FormBuilderInterface $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function configPage() {
    return [
      '#prefix' => '<div id="glazed_builder_configuration_page">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('Drupal\glazed_builder\Form\ConfigForm'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function uninstallPage() {
    return [
      '#prefix' => '<div id="glazed_builder_uninstallPage_page">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('Drupal\glazed_builder\Form\UninstallForm'),
    ];
  }
}
