# Content Pane Component

A Single Directory Component (SDC) for creating consistent content panes with icon, title, and
content.

> **Note**: This component automatically loads its CSS when included. No library attachment is
> required.

## Usage

### In Twig Templates

```twig
{%
  set component_data = {
    'title': 'Payment Information',
    'content': payment_form,
    'icon': 'bi-credit-card',
  }
%}

{{ include('saho_shop:content-pane', component_data) }}
```

### Component Properties

| Property      | Type      | Description                                                                                 | Default |
| ------------- | --------- | ------------------------------------------------------------------------------------------- | ------- |
| `title`       | string    | Pane title                                                                                  | -       |
| `description` | string    | Pane description text                                                                       | -       |
| `icon`        | string    | Icon name (e.g., credit-card, truck, check-circle)                                          | -       |
| `variant`     | string    | Component variant (default, minimal, title-outside)                                         | default |
| `collapsible` | boolean   | Whether the content pane is collapsible                                                     | false   |
| `collapsed`   | boolean   | Whether the collapsible pane is initially collapsed (only applies when collapsible is true) | true    |
| `attributes`  | Attribute | HTML attributes                                                                             | -       |

### Component Slots

| Slot      | Type         | Description       |
| --------- | ------------ | ----------------- |
| `content` | string/array | Main pane content |

### Variants

#### Default Variant

Standard content pane with borders, padding, and title inside.

```twig
{%
  set component_data = {
    'title': 'Payment Information',
    'description': 'Enter your payment details to complete your order.',
    'content': payment_form,
    'icon': 'bi-credit-card',
    'variant': 'default',
  }
%}
{{ include('saho_shop:content-pane', component_data) }}
```

#### Minimal Variant

No borders, no padding - clean minimal look.

```twig
{%
  set component_data = {
    'title': 'Shipping Information',
    'description': 'Provide your shipping address and preferred delivery method.',
    'content': shipping_form,
    'icon': 'bi-truck',
    'variant': 'minimal',
  }
%}
{{ include('saho_shop:content-pane', component_data) }}
```

#### Title Outside Variant

Title is rendered outside the pane border.

```twig
{%
  set component_data = {
    'title': 'Review Your Order',
    'description': 'Please review your order details before proceeding to payment.',
    'content': order_summary,
    'icon': 'bi-check-circle',
    'variant': 'title-outside',
  }
%}
{{ include('saho_shop:content-pane', component_data) }}
```

### Collapsible Option

Any variant can be made collapsible by setting the `collapsible` option to `true`. This makes the
title clickable and adds accordion behavior to the content pane.

```twig
{%
  set component_data = {
    'title': 'Advanced Options',
    'description': 'Configure additional settings for your order.',
    'content': advanced_form,
    'icon': 'bi-gear',
    'variant': 'default',
    'collapsible': true,
    'collapsed': true,
  }
%}
{{ include('saho_shop:content-pane', component_data) }}
```

**Collapsible Properties:**

- `collapsible`: Set to `true` to enable collapsible behavior (default: `false`)
- `collapsed`: Set to `true` to have the pane initially collapsed (default: `true`)

## CSS Classes

The component generates the following CSS classes:

- `.content-pane` - Base component class
- `.content-pane--default` - Default variant (default)
- `.content-pane--minimal` - Minimal variant (no borders/padding)
- `.content-pane--title-outside` - Title outside variant
- `.content-pane--collapsible` - Collapsible pane (when collapsible option is true)
- `.content-pane__header` - Header container
- `.content-pane__title-row` - Title row container (for icon + title)
- `.content-pane__title--collapsible` - Collapsible title (when collapsible option is true)
- `.content-pane__icon` - Icon element
- `.content-pane__title` - Title element
- `.content-pane__description` - Description element
- `.content-pane__content` - Content container

## CSS Variables

All styling can be customized using CSS custom properties:

```css
:root {
  /* Spacing */
  --content-pane-margin-bottom: var(--form-item-spacing);
  --content-pane-border-radius: 0.5rem;
  --content-pane-header-padding: 1rem 1.5rem;
  --content-pane-content-padding: 1.5rem;

  /* Colors */
  --content-pane-bg: var(--beo-white);
  --content-pane-border: var(--beo-border-color);
  --content-pane-shadow: var(--beo-box-shadow-sm);
  --content-pane-header-bg: transparent;
  --content-pane-header-border: var(--beo-border-color);

  /* Typography */
  --content-pane-title-font-size: 1.25rem;
  --content-pane-title-font-weight: 600;
  --content-pane-title-color: var(--beo-body-color);
  --content-pane-description-font-size: 0.875rem;
  --content-pane-description-color: var(--beo-secondary-color);

  /* Icon */
  --content-pane-icon-size: 1.5rem;
  --content-pane-icon-color: var(--beo-primary);

  /* Transitions */
  --content-pane-transition: all 0.2s ease-in-out;
}
```
