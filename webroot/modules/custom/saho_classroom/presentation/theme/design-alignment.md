# Presentation deck - Open Record design alignment

This proposal aligns the Classroom 2.0 presentation deck with the site's 2027
"Open Record" design system (theme branch `feature/open-record-wireframes`),
**without editing the co-owned engine or component CSS**.

All alignment lives in one new, additive file:

- `presentation/theme/deck-tokens.css` - a token layer that re-declares the
  deck's CSS custom properties on its two host selectors (`:root` and
  `.saho-deck`).

Loaded **after** the engine CSS, it re-points the deck onto the Open Record
palette and typography. It is self-contained: no `@import`, no `url()`, no
external fonts. Font family names match the fonts the theme already loads
(`base/_fonts.scss`); standalone `file://` decks fall back to system fonts and
still make zero network requests.

The change lands in two tiers:

- **Tier A - free (no rule edits).** The engine already consumes `--saho-red`,
  `--saho-red-dark`, `--saho-slate`, `--saho-gold`, `--saho-green`, `--ink`,
  `--ink-soft`, `--paper`, `--paper-tint`, `--line`, `--focus` and `--font`.
  Re-declaring those in `deck-tokens.css` repaints the whole deck as soon as the
  file loads. Nothing in `deck.css` / `presentation_deck.css` changes.
- **Tier B - small rule edits (owner applies).** Two things a single-variable
  re-map cannot reach: the three-font typography split (Caslon titles / Archivo
  UI / Plex Mono apparatus), and the hardcoded warm-brown dark-chrome literals.
  These are listed rule-by-rule below.

Source tokens referenced (theme):
`base/_variables.scss` (`--saho-oxblood`, `--saho-ink*`, `--saho-paper*`,
`--saho-rule`, `--font-serif|sans|mono`, `--focus-ring`, `--bw-*`),
`base/_open-record-scale.scss` (`--fs-*`, `--space-*`, `--lh-*`, `--shadow-*`),
`base/_open-record.scss` (focus ring + register conventions).

---

## Step 1 - Load `deck-tokens.css` after the engine CSS

### 1a. Standalone / self-contained decks (`presentation/render.php`)

`saho_classroom_render_deck()` inlines the engine CSS into one `<style>` (line
~129: `"<style>\n" . $css . "\n</style>\n"`), where `$css` defaults to
`saho_classroom_engine_asset('deck.css')` (line ~81).

Append the token layer to that inline CSS so it wins the cascade. No signature
change is required - the caller can pass the concatenation via the existing
`$css` override argument:

```php
$engine = saho_classroom_engine_asset('deck.css');
$tokens = file_get_contents(__DIR__ . '/theme/deck-tokens.css');
$html   = saho_classroom_render_deck($deck, $engine . "\n" . $tokens);
```

Or, if you prefer defaults to carry it, concatenate inside the helper where
`$css` is resolved (line ~81) - still one `<style>`, still zero external
requests.

### 1b. Embedded SDC (`components/presentation_deck/`)

The component auto-attaches `presentation_deck.css` as its library. Add
`deck-tokens.css` to that library **after** it so the re-declarations win.
Because the token file lives under `presentation/theme/`, the cleanest options
are:

- copy/symlink it beside the component and extend the library via
  `libraryOverrides.css.theme` in `presentation_deck.component.yml` (owner
  edit, ordered after `presentation_deck.css`); or
- attach it as a small module library
  (`saho_classroom.libraries.yml: deck_tokens`) that depends on the component
  library, and attach it wherever the deck field/formatter renders.

Either way the requirement is only ordering: `deck-tokens.css` loads last.

After Step 1, **Tier A is complete** - palette, ink, paper and focus are
aligned across both render paths with no further edits.

---

## Step 2 - Typography split (Tier B, optional but recommended)

The engine sets one family (`--font`) for everything. Open Record uses three
registers. `deck-tokens.css` already exposes `--deck-font-serif`,
`--deck-font-sans`, `--deck-font-mono` and a matching `--deck-fs-*` scale. Apply
these families to the relevant rules. Line numbers are for
`engine/deck.css`; the same edits apply to the `.saho-deck`-scoped twins in
`components/presentation_deck/presentation_deck.css`.

| Selector | File / rule | Change |
| --- | --- | --- |
| `.slide h1` | deck.css L107-112 | add `font-family: var(--deck-font-serif);` |
| `.slide h2` | deck.css L113-118 | add `font-family: var(--deck-font-serif);` |
| `.slide h3`, `.card h3` | deck.css L119, L141 | add `font-family: var(--deck-font-serif);` |
| `blockquote` | deck.css L143-152 | add `font-family: var(--deck-font-serif);` (Caslon reads better than the current italic sans) |
| `.slide__eyebrow` | deck.css L95-105 | add `font-family: var(--deck-font-mono); letter-spacing: var(--deck-ls-label);` (apparatus label) |
| `blockquote cite` | deck.css L153-159 | add `font-family: var(--deck-font-mono);` |
| `.figure figcaption` | deck.css L163-167 | add `font-family: var(--deck-font-mono);` |
| `.counter` | deck.css L245-250 | add `font-family: var(--deck-font-mono);` (pairs with the existing `tabular-nums`) |
| `.notes h4` | deck.css L314-320 | add `font-family: var(--deck-font-mono); letter-spacing: var(--deck-ls-label);` |
| `.slide p`, `.slide li`, `.lead` | deck.css L120-123 | leave as-is (inherit Archivo via `--font`) - optionally swap the `clamp()` literals for `--deck-fs-body` / `--deck-fs-lead` |

Rationale: titles and pull-quotes move to the reference register (Caslon);
metadata, counters, captions, cites and labels move to the scholarly-apparatus
register (Plex Mono); body and UI stay Archivo. This mirrors
`base/_open-record.scss` lines 124-140.

---

## Step 3 - Cool dark chrome (Tier B, optional)

The engine's toolbar, stage backdrop, edge nav and buttons use warm-brown
literals (`#201a17`, `#17120f`, `#241d18`, `#4a3f36`, `#322820`, `#f4ece2`,
`#c9bdae`, `#d8ccbd`, `#fbf7f0`, `rgba(23,18,15,.92)`). Open Record's ink is a
**cool** near-black (`#1b1c17`). Re-tune the frame to match the paper it
surrounds by swapping literals for the `--deck-chrome-*` / `--deck-btn-*` tokens
already defined in `deck-tokens.css`:

| Literal (deck.css / presentation_deck.css) | Replace with |
| --- | --- |
| `html, body { background: #201a17 }` (deck.css L37) and `.saho-deck { background: #201a17 }` (presentation_deck.css L31) | `var(--deck-chrome-bg)` |
| `.toolbar { background: #17120f }` (L204/L217) | `var(--deck-chrome-bar)` |
| `.toolbar { color: #f4ece2 }` | `var(--deck-chrome-text)` |
| `.brand small { color: #c9bdae }` | `var(--deck-chrome-text-muted)` |
| `.counter { color: #d8ccbd }` | `var(--deck-chrome-text-muted)` |
| `.btn { background: #241d18; border-color: #4a3f36; color: #f4ece2 }` | `var(--deck-btn-bg)` / `var(--deck-btn-border)` / `var(--deck-chrome-text)` |
| `.btn:hover { background: #322820 }` | `var(--deck-btn-bg-hover)` |
| `.notes { background: #fbf7f0 }` | `var(--deck-notes-bg)` |
| `.hint { background: rgba(23,18,15,.92) }` | `var(--deck-hint-bg)` |
| `.toolbar` / `.notes` top borders using `--saho-gold` | already aligned (Tier A); optionally `var(--deck-chrome-accent)` |

The gold top rules and progress bar/dots already re-point to ochre via Tier A
(they consume `--saho-gold`). The focus outline already re-points to oxblood via
`--focus` (Tier A); to add the Open Record oxblood focus ring, extend the
existing `:focus-visible` rules (deck.css L241, L267, L290) with
`box-shadow: var(--deck-focus-ring);`.

---

## Step 4 - Effects (Tier B, optional)

Open Record is "square by discipline" with borders doing the structural work.
The deck's slide sheet and cards currently use `border-radius: 14px` / `8px` and
a heavy `0 18px 50px rgba(0,0,0,.45)` drop shadow.

- `.slide` (deck.css L76-85): `border-radius: var(--deck-radius-slide);`
  (collapses to 0) and `box-shadow: var(--deck-shadow-slide);` (the lighter,
  cooler Open Record `--shadow-xl`).
- `.card`, `.qa`, `blockquote`, `.figure img/svg` radii: `var(--deck-radius)`.

This step is presentation-critical for a strict Open Record match but is the
most visually opinionated; ship Steps 1-3 first if you want a conservative roll.

---

## Verification

1. Standalone: open a rendered deck from `file://` with no network - confirm it
   still loads (system-font fallback) and shows oxblood accents, warm paper
   slides and cool chrome.
2. Embedded: render the `presentation_deck` SDC on a themed page and confirm the
   deck picks up the real Caslon/Archivo/Plex Mono webfonts and the theme's
   `--saho-*` values (DevTools: computed `--paper` = `#f1efe7`, `--focus` =
   `#990000`).
3. Diff-check that `deck.css` and `presentation_deck.css` are unchanged when
   only Tier A is applied.

## Rollback

Remove the `deck-tokens.css` include (Step 1). The engine CSS is untouched, so
the deck reverts to its original look with no other change.
