<?php

namespace Drupal\tvi\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Views;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Default implementation of TaxonomyViewsIntegratorManagerInterface.
 *
 * The manager will inspect the configuration of the passed TermInterface object
 * and determine which view will be injected as the page output.
 *
 * At a later point, it would be great to support adherence to the Views
 * permission settings, there is an outstanding patch and issue for that.
 */
class TaxonomyViewsIntegratorManager implements TaxonomyViewsIntegratorManagerInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Core config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $config;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The current request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $requestStack;

  /**
   * TaxonomyViewsIntegratorManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Core config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The current request stack.
   */
  public function __construct(ConfigFactory $config, EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack) {
    $this->config = $config;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $requestStack;
  }

  /**
   * Get the taxonomy view integrator settings for this term entity.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   Active taxonomy term.
   *
   * @return \Drupal\Core\Config\Config
   *   TVI config for the term.
   */
  public function getTermConfigSettings(TermInterface $taxonomy_term) {
    return $this->config->get('tvi.taxonomy_term.' . $taxonomy_term->id());
  }

  /**
   * Get the taxonomy view integrator settings for this terms vocabulary entity.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   Active taxonomy term.
   *
   * @return \Drupal\Core\Config\Config
   *   TVI config for the vocabulary.
   */
  public function getVocabularyConfigSettings(TermInterface $taxonomy_term) {
    return $this->config->get('tvi.taxonomy_vocabulary.' . $taxonomy_term->bundle());
  }

  /**
   * Get the default taxonomy view integrator settings.
   *
   * @return \Drupal\Core\Config\Config
   *   The TVI configuration.
   */
  public function getDefaultConfigSettings() {
    return $this->config->get('tvi.global_settings');
  }

  /**
   * Wrapper method for obtaining parents of a given taxonomy term.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   Starting term.
   *
   * @return array
   *   Parent terms of given term.
   */
  public function getTermParents(TermInterface $taxonomy_term) {
    return $this->entityTypeManager->getStorage('taxonomy_term')->loadAllParents($taxonomy_term->id());
  }

  /**
   * Return an array of arguments from the URI.
   *
   * It is assumed tha URI will be taxonomy/term/{taxonomy_term}, so anything
   * after that will be returned.
   *
   * @return array
   *   Views arguments.
   */
  public function getRequestUriArguments() {
    return array_slice(explode('/', $this->requestStack->getCurrentRequest()->getRequestUri()), 3);
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxonomyTermView(TermInterface $taxonomy_term) {
    $view_info = $this->getTaxonomyTermViewAndDisplayId($taxonomy_term);

    $config = $this->getTermConfigSettings($taxonomy_term);
    $view_arguments = [$taxonomy_term->id()];

    // If the option to pass all args to views is enabled, pass them on.
    if ($config->get('pass_arguments')) {
      $view_arguments += $this->getRequestUriArguments();
    }

    $build = NULL;
    if ($view_info['display_id']) {
      $view = Views::getView($view_info['view_id']);
      // Ensure view exists and is enabled.
      if ($view && $view->storage->status()) {
        $view->override_url = Url::fromUri($this->requestStack->getCurrentRequest()->getUri());
        $build = $view->executeDisplay($view_info['display_id'], $view_arguments);
      }
    }

    if (!$build) {
      $build = $this->entityTypeManager
        ->getViewBuilder('taxonomy_term')
        ->view($taxonomy_term, 'full');
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaxonomyTermViewAndDisplayId(TermInterface $taxonomy_term) {
    // If we have no matches, we are going to call the default core term view.
    $view_name = 'taxonomy_term';
    $view_id = 'page_1';

    $global_config = $this->getDefaultConfigSettings();
    if ($global_config->get('disable_default_view')) {
      $view_name = NULL;
      $view_id = NULL;
    }
    elseif ($global_config->get('enable_override')) {
      $view_name = $global_config->get('view');
      $view_id = $global_config->get('view_display');
    }

    // Check for global overrides.
    $term_config = $this->getTermConfigSettings($taxonomy_term);
    if ($term_config->get('enable_override')) {
      $view_name = $term_config->get('view');
      $view_id = $term_config->get('view_display');
    }
    else {
      // Check parent terms and vocab for settings.
      $vocab_config = $this->getVocabularyConfigSettings($taxonomy_term);
      if ($vocab_config->get('enable_override') && $vocab_config->get('inherit_settings')) {
        $view_name = $vocab_config->get('view');
        $view_id = $vocab_config->get('view_display');
      }

      $parents = $this->getTermParents($taxonomy_term);
      unset($parents[$taxonomy_term->id()]);
      foreach ($parents as $parent) {
        $parent_config = $this->getTermConfigSettings($parent);
        if ($parent_config->get('enable_override') && $parent_config->get('inherit_settings')) {
          $view_name = $parent_config->get('view');
          $view_id = $parent_config->get('view_display');
          break;
        }
      }
    }

    return [
      'view_id' => $view_name,
      'display_id' => $view_id,
    ];
  }

}
