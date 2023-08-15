<?php

namespace Drupal\glazed_builder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GlazedBuilderUserRegisterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a GlazedBuilderUserReistrationBlock object
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $formBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, 
      $plugin_id, 
      $plugin_definition, 
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#prefix' => '<div id="glazed_builder_user_registration_form_block">',
      '#suffix' => '</div>',
      'form' => $this->formBuilder->getForm('Drupal\user\RegisterForm'),
    ];
  }
}
