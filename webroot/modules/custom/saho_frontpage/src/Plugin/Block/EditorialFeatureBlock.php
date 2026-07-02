<?php

declare(strict_types=1);

namespace Drupal\saho_frontpage\Plugin\Block;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Drupal\saho_refs\DisplayRefService;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders the current editorial feature: the lead of the front-page row.
 *
 * The feature node comes from the curated "Front page content" view (driven
 * by the field_home_page_feature* flags), falling back to the newest featured
 * biography so the lead never renders blank.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\saho_refs\DisplayRefService $displayRef
   *   The display reference service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('entity_type.manager'),
      $container->get('saho_refs.display_ref'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->featuredNode();
    if (!$node instanceof NodeInterface) {
      return [];
    }
    return [
      '#type' => 'component',
      '#component' => 'saho:saho-home-feature',
      '#props' => [
        'kicker' => 'Current feature',
        'title' => (string) $node->label(),
        'standfirst' => $this->standfirst($node),
        'href' => $node->toUrl()->toString(),
        'cta_label' => 'Open the feature',
        'secondary_href' => '/timelines',
        'secondary_label' => 'View chronology',
        'meta' => 'Editorial register · ' . $this->displayRef->getRef($node),
        'heading_level' => 'h1',
      ],
      '#cache' => [
        'tags' => ['node_list', 'node:' . $node->id()],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Loads the curated feature node, or the newest featured biography.
   */
  private function featuredNode(): ?NodeInterface {
    $view = Views::getView('front_page_content');
    if ($view !== NULL) {
      $view->setDisplay('default');
      $view->setItemsPerPage(1);
      $view->execute();
      $entity = $view->result[0]->_entity ?? NULL;
      if ($entity instanceof NodeInterface) {
        return $entity;
      }
    }
    $storage = $this->entityTypeManager->getStorage('node');
    $nids = $storage->getQuery()
      ->condition('type', 'biography')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();
    $node = $nids !== [] ? $storage->load(reset($nids)) : NULL;
    return $node instanceof NodeInterface ? $node : NULL;
  }

  /**
   * Builds a plain-text standfirst from the node summary or body.
   */
  private function standfirst(NodeInterface $node): ?string {
    if (!$node->hasField('body') || $node->get('body')->isEmpty()) {
      return NULL;
    }
    $item = $node->get('body')->first();
    $summary = trim((string) $item->get('summary')->getValue());
    $value = trim((string) $item->get('value')->getValue());
    $text = $summary !== '' ? $summary : $value;
    $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5));
    if ($text === '') {
      return NULL;
    }
    return Unicode::truncate($text, 280, TRUE, TRUE);
  }

}
