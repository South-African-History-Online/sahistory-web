<?php

namespace Drupal\simple_gse_search\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\simple_gse_search\Form\SearchForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Google CSE Search' block.
 *
 * @Block(
 *   id = "simple_gse_search_block",
 *   admin_label = @Translation("Simple GSE Search Block"),
 *   category = @Translation("Search"),
 * )
 */
class GSESearchFormBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;
  /**
   * Constructs a new GSESearchFormBlock object.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   Form builder service.
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
    return $this->formBuilder->getForm(SearchForm::class);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url'];
  }
}
