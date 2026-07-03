<?php

declare(strict_types=1);

namespace Drupal\saho_classroom\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A language switcher that appears only where a translation actually exists.
 *
 * Unlike the core language switcher (which lists every enabled language on
 * every page), this renders nothing unless the current route's content entity
 * has more than one translation, and then links only the languages that entity
 * is actually translated into.
 */
#[Block(
  id: 'saho_classroom_translation_switcher',
  admin_label: new TranslatableMarkup('Translation switcher (only where translations exist)'),
)]
final class TranslationSwitcherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the block.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('current_route_match'));
  }

  /**
   * Returns the translatable content entity on the current route, if any.
   */
  private function currentEntity(): ?ContentEntityInterface {
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if ($parameter instanceof ContentEntityInterface && $parameter->isTranslatable()) {
        return $parameter;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $entity = $this->currentEntity();
    if (!$entity) {
      return [];
    }
    $languages = $entity->getTranslationLanguages();
    // Nothing to switch to unless there is at least one translation.
    if (count($languages) < 2) {
      return [];
    }

    $current = $entity->language()->getId();
    $links = [];
    foreach ($languages as $langcode => $language) {
      try {
        $url = $entity->getTranslation($langcode)->toUrl('canonical', ['language' => $language]);
      }
      catch (\Throwable $e) {
        continue;
      }
      $links[$langcode] = [
        'title' => $language->getName(),
        'url' => $url,
        'attributes' => $langcode === $current
          ? ['class' => ['is-active'], 'aria-current' => 'true']
          : [],
      ];
    }
    if (count($links) < 2) {
      return [];
    }

    return [
      '#theme' => 'links',
      '#links' => $links,
      '#attributes' => ['class' => ['saho-translation-switcher', 'nav']],
      '#cache' => [
        'contexts' => ['route', 'languages:language_interface'],
        'tags' => $entity->getCacheTags(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route', 'languages:language_interface']);
  }

}
