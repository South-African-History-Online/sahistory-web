**Button** — SAHO's institutional action button. Restrained, small radius (never pill-shaped), Archivo semibold. Use `primary` (oxblood) for the main action on a surface; `outline`/`quiet` for secondary; `secondary` (slate) inside Place contexts.

```jsx
<Button variant="primary" size="md">Browse the archive</Button>
<Button variant="outline" href="/timeline">Open timeline</Button>
<Button variant="quiet" size="sm" iconAfter={<span>→</span>}>Read more</Button>
```

Variants: `primary` · `secondary` · `outline` · `quiet` · `ghost`. Sizes: `sm` · `md` · `lg`. Pass `href` to render an `<a>`; `fullWidth` to stretch.
