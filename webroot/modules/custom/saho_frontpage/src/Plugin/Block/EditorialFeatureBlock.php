<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_frontpage\CurrentFeatureService;
use Drupal\saho_refs\DisplayRefService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the current editorial feature: the lead of the front-page row.
 *
 * The feature node comes from the shared current-feature engine
 * (saho_frontpage.current_feature), which /featured also leads with.
 *
 * @Block(
 *   id = "saho_editorial_feature",
 *   admin_label = @Translation("SAHO Editorial feature"),
 *   category = @Translation("SAHO Front page"),
 * )
 */
final class EditorialFeatureBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the block.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\saho_frontpage\CurrentFeatureService $currentFeature
   *   The shared current-feature engine.
   * @param \Drupal\saho_refs\DisplayRefService $displayRef
   *   The display reference service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly CurrentFeatureService $currentFeature,
    private readonly DisplayRefService $displayRef,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('saho_frontpage.current_feature'),
      $container->get('saho_refs.display_ref'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->currentFeature->node();
    if (!$node instanceof NodeInterface) {
      // Publishing eligible content should restore the hero, so carry the
      // cache metadata even when nothing resolves yet.
      return [
        '#cache' => [
          'tags' => ['node_list'],
          'max-age' => 3600,
        ],
      ];
    }
    return [
      '#type' => 'component',
      '#component' => 'saho:saho-home-feature',
      '#props' => [
        'kicker' => $this->t('Current feature'),
        'title' => (string) $node->label(),
        'standfirst' => $this->currentFeature->standfirst($node),
        'href' => $node->toUrl()->toString(),
        'cta_label' => $this->t('Open the feature'),
        'secondary_href' => '/timelines',
        'secondary_label' => $this->t('View chronology'),
        'meta' => $this->t('Editorial register · @ref', ['@ref' => $this->displayRef->getRef($node)]),
        'heading_level' => 'h1',
      ],
      '#cache' => [
        'tags' => ['node_list', 'node:' . $node->id()],
        'max-age' => 3600,
      ],
    ];
  }

}
