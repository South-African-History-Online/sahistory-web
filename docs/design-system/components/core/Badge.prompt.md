**Badge** — names the content type (Article, Biography, Place, Archive, Event, Topic) with the meaning-bearing colour. Use top-left/right of cards and beside titles. `onImage` for placement over photography.

```jsx
<Badge type="biography" />
<Badge type="place">Place</Badge>
<Badge type="archive" onImage />
```

**Tag** — bordered chip for filters, eras, themes and "related" cross-references. Set `active` for selected filters; `count` shows a mono result count.

```jsx
<Tag href="#" active count={428}>1948–1994</Tag>
<Tag href="#">Defiance Campaign</Tag>
```
