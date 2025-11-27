# Fieldset Pane Component

A semantic fieldset component that reuses content-pane styling and functionality while rendering as
proper `<fieldset>` elements.

## Properties

- **title**: Fieldset legend text
- **description**: Fieldset description text
- **icon**: Icon name (e.g., credit-card, truck, check-circle)
- **variant**: Component variant (default, minimal, title-outside)
- **collapsible**: Whether the fieldset is collapsible
- **collapsed**: Whether the collapsible fieldset is initially collapsed
- **disabled**: Whether the fieldset and all its children are disabled

## Component Slots

- **content**: Main fieldset content

## CSS Classes

- `.fieldset-pane` - Base fieldset pane wrapper
- `.fieldset-pane--{variant}` - Variant modifier classes
- `.fieldset-pane--collapsible` - Collapsible fieldset
- `.fieldset-pane--disabled` - Disabled fieldset
- `.fieldset-pane__legend` - Fieldset legend element
- `.fieldset-pane__title-row` - Title row container (icon + title)
- `.fieldset-pane__icon` - Fieldset icon
- `.fieldset-pane__title` - Fieldset title
- `.fieldset-pane__description` - Fieldset description
- `.fieldset-pane__content` - Fieldset content wrapper
- `.fieldset-pane__content-inner` - Fieldset content inner wrapper

## CSS Variables

- `--fieldset-pane-margin-bottom` - Bottom margin
- `--fieldset-pane-border-radius` - Border radius
- `--fieldset-pane-legend-padding` - Legend padding
- `--fieldset-pane-content-padding` - Content padding
- `--fieldset-pane-bg` - Background color
- `--fieldset-pane-border` - Border color
- `--fieldset-pane-shadow` - Box shadow
- `--fieldset-pane-legend-bg` - Legend background
- `--fieldset-pane-legend-border` - Legend border color
- `--fieldset-pane-title-font-size` - Title font size
- `--fieldset-pane-title-font-weight` - Title font weight
- `--fieldset-pane-title-color` - Title color
- `--fieldset-pane-description-font-size` - Description font size
- `--fieldset-pane-description-color` - Description color
- `--fieldset-pane-icon-size` - Icon size
- `--fieldset-pane-icon-color` - Icon color
- `--fieldset-pane-transition` - Transition timing

## Usage

This component is automatically used by the fieldset preprocess function when fieldsets are
rendered. It provides semantic HTML with content-pane styling and functionality.

## Variants

- **default**: Standard fieldset with borders and padding
- **minimal**: No borders, no padding, clean minimal look
- **title-outside**: Title appears outside the fieldset border

## Features

- Semantic `<fieldset>` and `<legend>` elements
- Automatic icon detection based on fieldset context
- Collapsible functionality with Bootstrap collapse
- Disabled state support
- Responsive design
- Theme settings integration
