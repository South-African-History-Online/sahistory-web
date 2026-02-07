# SAHO Utils Services

This directory contains shared services used across SAHO blocks and modules to reduce code duplication and ensure consistency.

## SortingService

Centralizes all sorting logic for entity queries and loaded entities.

### Usage in a Block

```php
use Drupal\saho_utils\Service\SortingService;

class MyCustomBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The sorting service.
   *
   * @var \Drupal\saho_utils\Service\SortingService
   */
  protected $sortingService;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    SortingService $sorting_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->sortingService = $sorting_service;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('saho_utils.sorting')
    );
  }
}
```

### Method 1: applySorting()

Apply sorting to an entity query before execution.

```php
// Build your query
$query = $this->entityTypeManager->getStorage('node')->getQuery()
  ->condition('type', 'article')
  ->condition('status', 1)
  ->accessCheck(TRUE);

// Apply sorting
$sort_by = $this->configuration['sort_by'] ?? 'latest';
$query = $this->sortingService->applySorting($query, $sort_by);

// Execute and load
$nids = $query->execute();
$nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
```

**Supported sort options:**
- `latest` or `created` - Newest first (created DESC)
- `oldest` - Oldest first (created ASC)
- `recently_updated` or `changed` - Most recently modified (changed DESC)
- `title_asc` or `title` - Alphabetical A-Z
- `random` - No query sorting (shuffle after loading)
- `none` - No sorting

### Method 2: applyRandomWithImages()

For featured content blocks that need random selection with image requirements.

```php
$query = $this->entityTypeManager->getStorage('node')->getQuery()
  ->condition('type', 'article')
  ->condition('status', 1)
  ->condition('field_home_page_feature', 1)
  ->accessCheck(TRUE);

// Apply random selection with image requirement
$query = $this->sortingService->applyRandomWithImages(
  $query,
  'field_article_image',
  50  // Fetch 50 items to provide good randomization
);

$nids = $query->execute();

if (!empty($nids)) {
  // Shuffle for random selection
  $nids_array = array_values($nids);
  shuffle($nids_array);
  $nid = reset($nids_array);

  $node = $this->entityTypeManager->getStorage('node')->load($nid);
}
```

### Method 3: sortLoadedEntities()

Sort entities after they've been loaded.

```php
// Load entities
$nids = [123, 456, 789];
$nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

// Sort them
$sort_by = $this->configuration['sort_by'] ?? 'title_asc';
$sorted_nodes = $this->sortingService->sortLoadedEntities($nodes, $sort_by);

// Use sorted entities
foreach ($sorted_nodes as $node) {
  // Process node...
}
```

### Method 4: getSortingOptions()

Get standard sorting options for block configuration forms.

```php
public function blockForm($form, FormStateInterface $form_state) {
  $form['sort_by'] = [
    '#type' => 'select',
    '#title' => $this->t('Sort By'),
    '#description' => $this->t('Choose how to sort the content.'),
    '#options' => $this->sortingService->getSortingOptions(
      TRUE,  // Include 'random' option
      TRUE   // Include 'none' option
    ),
    '#default_value' => $this->configuration['sort_by'] ?? 'latest',
  ];

  return $form;
}
```

## Benefits

- **Eliminates 80-120 lines of duplicate code** per block
- **Consistent sorting behavior** across all SAHO blocks
- **Easy to maintain** - update sorting logic in one place
- **Type-safe** - Uses proper type hints for better IDE support
- **Well-documented** - Comprehensive DocBlocks for all methods
- **Error-resilient** - Handles edge cases and invalid options gracefully
- **Drupal 11 compliant** - Follows all coding standards

## Examples of Blocks Using This Service

- FeaturedArticleBlock
- FeaturedBiographyBlock
- EntityOverviewBlock
- TdihBlock
- WallOfChampionsBlock
