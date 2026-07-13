/* @ds-bundle: {"format":3,"namespace":"SAHODesignSystem_50e062","components":[{"name":"ArchiveCard","sourcePath":"components/content/ArchiveCard.jsx"},{"name":"MetadataBlock","sourcePath":"components/content/MetadataBlock.jsx"},{"name":"Badge","sourcePath":"components/core/Badge.jsx"},{"name":"Button","sourcePath":"components/core/Button.jsx"},{"name":"Tag","sourcePath":"components/core/Tag.jsx"},{"name":"RelatedList","sourcePath":"components/navigation/RelatedList.jsx"},{"name":"SearchField","sourcePath":"components/navigation/SearchField.jsx"},{"name":"Timeline","sourcePath":"components/navigation/Timeline.jsx"},{"name":"Citation","sourcePath":"components/provenance/Citation.jsx"},{"name":"ContentWarning","sourcePath":"components/provenance/ContentWarning.jsx"},{"name":"ImageCredit","sourcePath":"components/provenance/ImageCredit.jsx"},{"name":"ProvenanceBlock","sourcePath":"components/provenance/ProvenanceBlock.jsx"},{"name":"IndexTable","sourcePath":"components/record/IndexTable.jsx"},{"name":"RecordHeader","sourcePath":"components/record/RecordHeader.jsx"}],"sourceHashes":{"components/content/ArchiveCard.jsx":"e5fe0eca8544","components/content/MetadataBlock.jsx":"b47d09ec4b4e","components/core/Badge.jsx":"56064e54801b","components/core/Button.jsx":"536b563440af","components/core/Tag.jsx":"1849de23e2bd","components/navigation/RelatedList.jsx":"cd1706682b07","components/navigation/SearchField.jsx":"b8fbd19df64e","components/navigation/Timeline.jsx":"38b868b1e02e","components/provenance/Citation.jsx":"8804dee2384c","components/provenance/ContentWarning.jsx":"f281051385d8","components/provenance/ImageCredit.jsx":"ea4d4cd8bdd9","components/provenance/ProvenanceBlock.jsx":"a89d0cbd7554","components/record/IndexTable.jsx":"f46964335de5","components/record/RecordHeader.jsx":"e586471f5b3e","ui_kits/sahistory/BiographyScreen.jsx":"028728cbad12","ui_kits/sahistory/Chrome.jsx":"8a6541638896","ui_kits/sahistory/HomeScreen.jsx":"88eb9f350a6e","ui_kits/sahistory/SearchScreen.jsx":"e9b6c164129b","ui_kits/sahistory/TimelineScreen.jsx":"beaf2587ad30"},"inlinedExternals":[],"unexposedExports":[]} */

(() => {

const __ds_ns = (window.SAHODesignSystem_50e062 = window.SAHODesignSystem_50e062 || {});

const __ds_scope = {};

(__ds_ns.__errors = __ds_ns.__errors || []);

// components/content/MetadataBlock.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * SAHO MetadataBlock · a consistent, tabular key/value block usable on people,
 * events, places and objects. Mono labels, designed like a finding aid · not an
 * afterthought. Optional content-type accent rule down the left edge.
 */
function MetadataBlock({
  items = [],
  accent,
  title,
  style,
  ...rest
}) {
  const accentColor = accent ? `var(--type-${accent})` : 'var(--border-strong)';
  return /*#__PURE__*/React.createElement("section", _extends({
    style: {
      border: 'var(--bw-hair) solid var(--border-default)',
      borderTop: `var(--bw-rule) solid ${accentColor}`,
      background: 'var(--surface-card)',
      ...style
    }
  }, rest), title && /*#__PURE__*/React.createElement("h4", {
    style: {
      margin: 0,
      fontFamily: 'var(--font-mono)',
      fontSize: '10px',
      fontWeight: 600,
      letterSpacing: '0.07em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      padding: '9px 14px',
      background: 'var(--surface-sunk)',
      borderBottom: 'var(--bw-hair) solid var(--border-default)'
    }
  }, title), /*#__PURE__*/React.createElement("dl", {
    style: {
      margin: 0,
      display: 'grid',
      gridTemplateColumns: 'minmax(92px, max-content) 1fr',
      gap: 0
    }
  }, items.map((it, i) => /*#__PURE__*/React.createElement(React.Fragment, {
    key: i
  }, /*#__PURE__*/React.createElement("dt", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.05em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      padding: '9px 14px',
      background: 'var(--surface-sunk)',
      borderBottom: i < items.length - 1 ? 'var(--bw-hair) solid var(--border-faint)' : 'none',
      borderRight: 'var(--bw-hair) solid var(--border-default)'
    }
  }, it.label), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: 0,
      fontFamily: 'var(--font-sans)',
      fontSize: '14px',
      lineHeight: 1.45,
      color: 'var(--text-primary)',
      padding: '9px 14px',
      borderBottom: i < items.length - 1 ? 'var(--bw-hair) solid var(--border-faint)' : 'none'
    }
  }, it.href ? /*#__PURE__*/React.createElement("a", {
    href: it.href,
    style: {
      color: 'var(--link-rest)'
    }
  }, it.value) : it.value)))));
}
Object.assign(__ds_scope, { MetadataBlock });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/content/MetadataBlock.jsx", error: String((e && e.message) || e) }); }

// components/core/Badge.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const TYPE_COLOR = {
  article: 'var(--type-article)',
  biography: 'var(--type-biography)',
  place: 'var(--type-place)',
  archive: 'var(--type-archive)',
  event: 'var(--type-event)',
  topic: 'var(--type-topic)'
};

/**
 * SAHO content-type badge. Small, uppercase Plex Sans label that names what a
 * piece of content is. Solid (on paper) or onImage (90% opacity over photos).
 */
function Badge({
  type = 'article',
  children,
  onImage = false,
  style,
  ...rest
}) {
  const color = TYPE_COLOR[type] || 'var(--type-article)';
  const label = children || type.charAt(0).toUpperCase() + type.slice(1);
  return /*#__PURE__*/React.createElement("span", _extends({
    style: {
      display: 'inline-block',
      fontFamily: 'var(--font-sans)',
      fontSize: '11px',
      fontWeight: 600,
      letterSpacing: '0.05em',
      textTransform: 'uppercase',
      color: '#fff',
      background: color,
      padding: '4px 9px',
      borderRadius: 'var(--radius-xs)',
      opacity: onImage ? 0.92 : 1,
      ...style
    }
  }, rest), label);
}
Object.assign(__ds_scope, { Badge });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Badge.jsx", error: String((e && e.message) || e) }); }

// components/content/ArchiveCard.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * SAHO ArchiveCard · the workhorse catalogue card for biographies, articles,
 * events and places. Square, content-type accent edge, optional duotone
 * thumbnail, accession ref + mono meta. Hover selects the record (no lift).
 */
function ArchiveCard({
  type = 'article',
  title,
  href = '#',
  excerpt,
  image,
  meta,
  dates,
  reference,
  duotone = true,
  style,
  ...rest
}) {
  const [hover, setHover] = React.useState(false);
  return /*#__PURE__*/React.createElement("a", _extends({
    href: href,
    onMouseEnter: () => setHover(true),
    onMouseLeave: () => setHover(false),
    style: {
      display: 'block',
      textDecoration: 'none',
      background: hover ? 'var(--surface-sunk)' : 'var(--surface-card)',
      border: 'var(--bw-hair) solid var(--border-default)',
      borderLeft: `var(--bw-accent) solid var(--type-${type})`,
      boxShadow: 'none',
      transition: 'background var(--t-fast), border-color var(--t-fast)',
      ...(hover ? {
        borderColor: 'var(--saho-ink)'
      } : null),
      ...style
    }
  }, rest), image && /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative',
      aspectRatio: '16 / 10',
      overflow: 'hidden',
      background: 'var(--surface-sunk)',
      borderBottom: 'var(--bw-hair) solid var(--border-default)'
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: image,
    alt: "",
    style: {
      width: '100%',
      height: '100%',
      objectFit: 'cover',
      filter: duotone ? 'var(--img-duotone)' : 'none'
    }
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      top: 0,
      left: 0
    }
  }, /*#__PURE__*/React.createElement(__ds_scope.Badge, {
    type: type
  }))), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 'var(--space-4)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: '10px',
      marginBottom: '10px'
    }
  }, !image ? /*#__PURE__*/React.createElement(__ds_scope.Badge, {
    type: type
  }) : /*#__PURE__*/React.createElement("span", null), reference && /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.04em',
      color: 'var(--text-muted)'
    }
  }, reference)), dates && /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '0 0 6px',
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.05em',
      color: 'var(--text-muted)'
    }
  }, dates), /*#__PURE__*/React.createElement("h3", {
    style: {
      margin: '0 0 8px',
      fontFamily: 'var(--font-display)',
      fontSize: 'var(--fs-lg)',
      fontWeight: 700,
      lineHeight: 1.18,
      color: hover ? 'var(--link-hover)' : 'var(--text-primary)',
      transition: 'color var(--t-fast)'
    }
  }, title), excerpt && /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      fontFamily: 'var(--font-serif)',
      fontSize: '14px',
      lineHeight: 1.55,
      color: 'var(--text-secondary)',
      maxWidth: 'none'
    }
  }, excerpt), meta && /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '12px 0 0',
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.04em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      borderTop: 'var(--bw-hair) solid var(--border-faint)',
      paddingTop: '10px'
    }
  }, meta)));
}
Object.assign(__ds_scope, { ArchiveCard });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/content/ArchiveCard.jsx", error: String((e && e.message) || e) }); }

// components/core/Button.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * SAHO Button · restrained, institutional. Small radius (no pills), Archivo,
 * oxblood primary. Calm hover (darken + subtle lift), visible focus ring.
 */
function Button({
  children,
  variant = 'primary',
  size = 'md',
  as = 'button',
  href,
  iconBefore,
  iconAfter,
  fullWidth = false,
  disabled = false,
  style,
  ...rest
}) {
  const sizes = {
    sm: {
      padding: '6px 12px',
      fontSize: '13px',
      gap: '6px'
    },
    md: {
      padding: '9px 18px',
      fontSize: '14px',
      gap: '8px'
    },
    lg: {
      padding: '13px 24px',
      fontSize: '15px',
      gap: '10px'
    }
  };
  const variants = {
    primary: {
      background: 'var(--accent)',
      color: 'var(--text-on-accent)',
      border: '1px solid var(--accent)'
    },
    secondary: {
      background: 'var(--saho-slate)',
      color: '#f3ecdd',
      border: '1px solid var(--saho-slate)'
    },
    outline: {
      background: 'transparent',
      color: 'var(--accent)',
      border: '1px solid var(--accent)'
    },
    quiet: {
      background: 'transparent',
      color: 'var(--text-primary)',
      border: '1px solid var(--border-default)'
    },
    ghost: {
      background: 'transparent',
      color: 'var(--text-secondary)',
      border: '1px solid transparent'
    }
  };
  const base = {
    display: fullWidth ? 'flex' : 'inline-flex',
    width: fullWidth ? '100%' : 'auto',
    alignItems: 'center',
    justifyContent: 'center',
    gap: sizes[size].gap,
    fontFamily: 'var(--font-sans)',
    fontWeight: 600,
    lineHeight: 1.1,
    letterSpacing: '0.01em',
    padding: sizes[size].padding,
    fontSize: sizes[size].fontSize,
    borderRadius: 'var(--radius-sm)',
    cursor: disabled ? 'not-allowed' : 'pointer',
    opacity: disabled ? 0.5 : 1,
    textDecoration: 'none',
    transition: 'background var(--t-fast), color var(--t-fast), box-shadow var(--t-fast)',
    ...variants[variant],
    ...style
  };
  const Tag = href ? 'a' : as;
  return /*#__PURE__*/React.createElement(Tag, _extends({
    href: href,
    style: base,
    "aria-disabled": disabled || undefined
  }, rest), iconBefore, /*#__PURE__*/React.createElement("span", null, children), iconAfter);
}
Object.assign(__ds_scope, { Button });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Button.jsx", error: String((e && e.message) || e) }); }

// components/core/Tag.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * SAHO Tag · a small bordered chip for filters, eras, themes and cross-references.
 * Quieter than a Badge: used for navigation, not content-type identity.
 */
function Tag({
  children,
  href,
  active = false,
  count,
  style,
  ...rest
}) {
  const Tag = href ? 'a' : 'span';
  return /*#__PURE__*/React.createElement(Tag, _extends({
    href: href,
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '7px',
      fontFamily: 'var(--font-sans)',
      fontSize: '13px',
      fontWeight: 500,
      color: active ? 'var(--text-on-accent)' : 'var(--text-primary)',
      background: active ? 'var(--accent)' : 'var(--surface-card)',
      border: `1px solid ${active ? 'var(--accent)' : 'var(--border-default)'}`,
      padding: '5px 11px',
      borderRadius: 'var(--radius-xs)',
      textDecoration: 'none',
      cursor: href ? 'pointer' : 'default',
      transition: 'background var(--t-fast), border-color var(--t-fast)',
      ...style
    }
  }, rest), children, count != null && /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      color: active ? 'rgba(255,255,255,0.8)' : 'var(--text-muted)'
    }
  }, count));
}
Object.assign(__ds_scope, { Tag });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/core/Tag.jsx", error: String((e && e.message) || e) }); }

// components/navigation/RelatedList.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const TYPE_COLOR = {
  article: 'var(--type-article)',
  biography: 'var(--type-biography)',
  place: 'var(--type-place)',
  archive: 'var(--type-archive)',
  event: 'var(--type-event)',
  topic: 'var(--type-topic)'
};

/**
 * SAHO RelatedList · the connective tissue. Knits people, events, places and
 * dates together as cross-references. A dot marks each item's content type so
 * colour is never the sole carrier of meaning (the type is also labelled).
 */
function RelatedList({
  title = 'Related',
  items = [],
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("section", _extends({
    style: {
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("h4", {
    style: {
      margin: '0 0 4px',
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      fontWeight: 600,
      letterSpacing: '0.06em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      borderBottom: '1px solid var(--border-default)',
      paddingBottom: '8px'
    }
  }, title), /*#__PURE__*/React.createElement("ul", {
    style: {
      listStyle: 'none',
      margin: 0,
      padding: 0
    }
  }, items.map((it, i) => /*#__PURE__*/React.createElement("li", {
    key: i,
    style: {
      borderBottom: '1px solid var(--border-faint)'
    }
  }, /*#__PURE__*/React.createElement("a", {
    href: it.href || '#',
    style: {
      display: 'flex',
      alignItems: 'baseline',
      gap: '10px',
      padding: '11px 0',
      textDecoration: 'none'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: '9px',
      height: '9px',
      flex: 'none',
      background: TYPE_COLOR[it.type] || 'var(--type-article)',
      transform: 'translateY(1px)'
    }
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      flex: 1
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'block',
      fontFamily: 'var(--font-sans)',
      fontSize: '14px',
      fontWeight: 600,
      color: 'var(--text-primary)'
    }
  }, it.label), it.note && /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'block',
      fontFamily: 'var(--font-sans)',
      fontSize: '12.5px',
      color: 'var(--text-muted)'
    }
  }, it.note)), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '10px',
      letterSpacing: '0.05em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)'
    }
  }, it.type))))));
}
Object.assign(__ds_scope, { RelatedList });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/RelatedList.jsx", error: String((e && e.message) || e) }); }

// components/navigation/SearchField.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * SAHO SearchField · the front door. Fast, forgiving, prominent. A large
 * paper-sunk field with a serif placeholder voice and an oxblood submit.
 * Optional scope chips constrain the search across content types.
 */
function SearchField({
  placeholder = 'Search people, events, places, dates…',
  size = 'lg',
  scopes = [],
  defaultScope,
  buttonLabel = 'Search',
  style,
  ...rest
}) {
  const [scope, setScope] = React.useState(defaultScope || scopes[0] || null);
  const pad = size === 'lg' ? '16px 18px' : '11px 14px';
  const fs = size === 'lg' ? '17px' : '15px';
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("form", {
    style: {
      display: 'flex',
      alignItems: 'stretch',
      background: 'var(--surface-card)',
      border: '2px solid var(--border-strong)',
      borderRadius: 'var(--radius-md)',
      overflow: 'hidden'
    },
    onSubmit: e => e.preventDefault()
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'flex',
      alignItems: 'center',
      paddingLeft: '16px',
      color: 'var(--text-muted)',
      fontSize: '18px'
    },
    "aria-hidden": true
  }, "\u2315"), /*#__PURE__*/React.createElement("input", {
    type: "search",
    placeholder: placeholder,
    style: {
      flex: 1,
      border: 'none',
      outline: 'none',
      background: 'transparent',
      padding: pad,
      fontFamily: 'var(--font-sans)',
      fontSize: fs,
      color: 'var(--text-primary)'
    }
  }), /*#__PURE__*/React.createElement("button", {
    type: "submit",
    style: {
      border: 'none',
      cursor: 'pointer',
      background: 'var(--accent)',
      color: 'var(--text-on-accent)',
      fontFamily: 'var(--font-sans)',
      fontSize: fs,
      fontWeight: 600,
      padding: '0 22px'
    }
  }, buttonLabel)), scopes.length > 0 && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: '6px',
      flexWrap: 'wrap',
      marginTop: '12px'
    }
  }, scopes.map(s => /*#__PURE__*/React.createElement("button", {
    key: s,
    onClick: () => setScope(s),
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '12.5px',
      fontWeight: 500,
      padding: '5px 11px',
      borderRadius: 'var(--radius-xs)',
      cursor: 'pointer',
      border: `1px solid ${scope === s ? 'var(--accent)' : 'var(--border-default)'}`,
      background: scope === s ? 'var(--accent)' : 'transparent',
      color: scope === s ? 'var(--text-on-accent)' : 'var(--text-secondary)'
    }
  }, s))));
}
Object.assign(__ds_scope, { SearchField });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/SearchField.jsx", error: String((e && e.message) || e) }); }

// components/navigation/Timeline.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * SAHO Timeline · a first-class navigational surface. The recurring structural
 * motif: a chronology spine with dot markers, era/theme filter tags, and linked
 * events. Filterable by the `themes` toggles. Calm, still, finding-aid styling.
 */
function Timeline({
  events = [],
  themes = [],
  title = 'Chronology',
  style,
  ...rest
}) {
  const [active, setActive] = React.useState('All');
  const filters = ['All', ...themes];
  const shown = active === 'All' ? events : events.filter(e => e.theme === active);
  return /*#__PURE__*/React.createElement("section", _extends({
    style: {
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("header", {
    style: {
      display: 'flex',
      alignItems: 'baseline',
      justifyContent: 'space-between',
      gap: '16px',
      flexWrap: 'wrap',
      marginBottom: '16px',
      borderBottom: '2px solid var(--border-strong)',
      paddingBottom: '10px'
    }
  }, /*#__PURE__*/React.createElement("h3", {
    style: {
      margin: 0,
      fontFamily: 'var(--font-display)',
      fontSize: 'var(--fs-h3)',
      fontWeight: 700,
      color: 'var(--text-primary)'
    }
  }, title), filters.length > 1 && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: '6px',
      flexWrap: 'wrap'
    }
  }, filters.map(f => /*#__PURE__*/React.createElement("button", {
    key: f,
    onClick: () => setActive(f),
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '12px',
      fontWeight: 600,
      padding: '5px 11px',
      borderRadius: 'var(--radius-xs)',
      cursor: 'pointer',
      border: `1px solid ${active === f ? 'var(--accent)' : 'var(--border-default)'}`,
      background: active === f ? 'var(--accent)' : 'transparent',
      color: active === f ? 'var(--text-on-accent)' : 'var(--text-secondary)'
    }
  }, f)))), /*#__PURE__*/React.createElement("ol", {
    style: {
      listStyle: 'none',
      margin: 0,
      padding: '0 0 0 24px',
      borderLeft: '2px solid var(--accent)'
    }
  }, shown.map((e, i) => /*#__PURE__*/React.createElement("li", {
    key: i,
    style: {
      position: 'relative',
      paddingBottom: i < shown.length - 1 ? 'var(--space-5)' : 0
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      position: 'absolute',
      left: '-31px',
      top: '4px',
      width: '11px',
      height: '11px',
      background: 'var(--surface-page)',
      border: 'var(--bw-accent) solid var(--accent)'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '13px',
      fontWeight: 600,
      color: 'var(--accent)',
      letterSpacing: '0.04em'
    }
  }, e.year), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: '2px'
    }
  }, e.href ? /*#__PURE__*/React.createElement("a", {
    href: e.href,
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 'var(--fs-h5)',
      fontWeight: 700,
      color: 'var(--text-primary)',
      textDecoration: 'none'
    }
  }, e.title) : /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: 'var(--fs-h5)',
      fontWeight: 700,
      color: 'var(--text-primary)'
    }
  }, e.title)), e.detail && /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '4px 0 0',
      fontFamily: 'var(--font-serif)',
      fontSize: '14px',
      lineHeight: 1.5,
      color: 'var(--text-secondary)',
      maxWidth: '60ch'
    }
  }, e.detail), e.theme && /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'inline-block',
      marginTop: '7px',
      fontFamily: 'var(--font-mono)',
      fontSize: '10px',
      letterSpacing: '0.06em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)'
    }
  }, e.theme)))));
}
Object.assign(__ds_scope, { Timeline });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/navigation/Timeline.jsx", error: String((e && e.message) || e) }); }

// components/provenance/Citation.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const FORMATS = ['Chicago', 'APA', 'MLA'];

/**
 * SAHO Citation · "Cite this entry." Selectable citation formats rendered in
 * mono. Makes attribution a first-class, copyable action on every entry.
 */
function Citation({
  formats,
  defaultFormat = 'Chicago',
  style,
  ...rest
}) {
  const data = formats || {
    Chicago: 'South African History Online. "Nelson Rolihlahla Mandela." Last modified February 11, 2026. https://sahistory.org.za.',
    APA: 'South African History Online. (2026). Nelson Rolihlahla Mandela. Retrieved from https://sahistory.org.za',
    MLA: '"Nelson Rolihlahla Mandela." South African History Online, 11 Feb. 2026, sahistory.org.za.'
  };
  const [active, setActive] = React.useState(defaultFormat);
  return /*#__PURE__*/React.createElement("section", _extends({
    style: {
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)',
      background: 'var(--surface-card)',
      overflow: 'hidden',
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("header", {
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: '12px',
      padding: '12px 16px',
      borderBottom: '1px solid var(--border-faint)',
      background: 'var(--surface-sunk)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '13px',
      fontWeight: 700,
      letterSpacing: '0.04em',
      textTransform: 'uppercase',
      color: 'var(--text-primary)'
    }
  }, "Cite this entry"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: '4px'
    }
  }, FORMATS.map(f => /*#__PURE__*/React.createElement("button", {
    key: f,
    onClick: () => setActive(f),
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      fontWeight: 600,
      letterSpacing: '0.04em',
      padding: '4px 9px',
      borderRadius: 'var(--radius-xs)',
      cursor: 'pointer',
      border: `1px solid ${active === f ? 'var(--accent)' : 'var(--border-default)'}`,
      background: active === f ? 'var(--accent)' : 'transparent',
      color: active === f ? 'var(--text-on-accent)' : 'var(--text-secondary)'
    }
  }, f)))), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: '14px',
      alignItems: 'flex-start',
      padding: '16px'
    }
  }, /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      flex: 1,
      fontFamily: 'var(--font-mono)',
      fontSize: '12.5px',
      lineHeight: 1.65,
      color: 'var(--text-primary)'
    }
  }, data[active]), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '12px',
      fontWeight: 600,
      color: 'var(--accent)',
      cursor: 'pointer',
      whiteSpace: 'nowrap',
      paddingTop: '2px'
    }
  }, "Copy")));
}
Object.assign(__ds_scope, { Citation });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/provenance/Citation.jsx", error: String((e && e.message) || e) }); }

// components/provenance/ContentWarning.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * SAHO ContentWarning · a sensitivity affordance for difficult imagery and
 * topics. Obscures content behind a calm, dignified notice the reader chooses
 * to reveal. No sensationalism; dignity is the governing word.
 */
function ContentWarning({
  reason = 'This page contains imagery and accounts of apartheid-era violence.',
  children,
  revealLabel = 'Show content',
  defaultRevealed = false,
  style,
  ...rest
}) {
  const [revealed, setRevealed] = React.useState(defaultRevealed);
  if (revealed) return /*#__PURE__*/React.createElement(React.Fragment, null, children);
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      border: '1px solid var(--border-strong)',
      borderRadius: 'var(--radius-md)',
      background: 'var(--surface-sunk)',
      padding: 'var(--space-6)',
      textAlign: 'center',
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '0 0 6px',
      fontFamily: 'var(--font-sans)',
      fontSize: '11px',
      fontWeight: 700,
      letterSpacing: '0.08em',
      textTransform: 'uppercase',
      color: 'var(--saho-warning)'
    }
  }, "Content sensitivity"), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '0 auto 16px',
      maxWidth: '46ch',
      fontFamily: 'var(--font-serif)',
      fontSize: '15px',
      lineHeight: 1.55,
      color: 'var(--text-secondary)'
    }
  }, reason), /*#__PURE__*/React.createElement("button", {
    onClick: () => setRevealed(true),
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '14px',
      fontWeight: 600,
      color: 'var(--text-on-accent)',
      background: 'var(--accent)',
      border: '1px solid var(--accent)',
      borderRadius: 'var(--radius-sm)',
      padding: '9px 18px',
      cursor: 'pointer'
    }
  }, revealLabel));
}
Object.assign(__ds_scope, { ContentWarning });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/provenance/ContentWarning.jsx", error: String((e && e.message) || e) }); }

// components/provenance/ImageCredit.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * SAHO ImageCredit · a figure that treats credit and provenance as designed,
 * always-visible elements. Optional duotone unifies disparate archival sources.
 */
function ImageCredit({
  src,
  alt = '',
  credit,
  source,
  caption,
  duotone = false,
  ratio = '4 / 3',
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("figure", _extends({
    style: {
      margin: 0,
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("div", {
    style: {
      position: 'relative',
      aspectRatio: ratio,
      overflow: 'hidden',
      background: 'var(--surface-sunk)',
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-sm)'
    }
  }, src && /*#__PURE__*/React.createElement("img", {
    src: src,
    alt: alt,
    style: {
      width: '100%',
      height: '100%',
      objectFit: 'cover',
      display: 'block',
      filter: duotone ? 'var(--img-duotone)' : 'none'
    }
  })), /*#__PURE__*/React.createElement("figcaption", {
    style: {
      marginTop: '8px'
    }
  }, caption && /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '0 0 4px',
      fontFamily: 'var(--font-serif)',
      fontSize: '14px',
      lineHeight: 1.5,
      color: 'var(--text-secondary)',
      maxWidth: 'none'
    }
  }, caption), /*#__PURE__*/React.createElement("p", {
    style: {
      margin: 0,
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      lineHeight: 1.55,
      color: 'var(--text-muted)',
      maxWidth: 'none'
    }
  }, credit && /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--text-secondary)',
      fontWeight: 600
    }
  }, "Credit \xB7 "), credit), source && /*#__PURE__*/React.createElement("span", null, credit ? ' · ' : '', source))));
}
Object.assign(__ds_scope, { ImageCredit });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/provenance/ImageCredit.jsx", error: String((e && e.message) || e) }); }

// components/provenance/ProvenanceBlock.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
/**
 * SAHO ProvenanceBlock · "How we know this." The scholarly apparatus made
 * beautiful and obvious. A titled panel holding an explanatory note and a
 * numbered, linked source list. Provenance is part of the visual language,
 * never buried in a footer.
 */
function ProvenanceBlock({
  title = 'How we know this',
  note,
  sources = [],
  lastUpdated,
  style,
  ...rest
}) {
  return /*#__PURE__*/React.createElement("section", _extends({
    style: {
      background: 'var(--surface-card)',
      border: '1px solid var(--border-default)',
      borderLeft: '3px solid var(--accent)',
      borderRadius: 'var(--radius-md)',
      padding: 'var(--space-5)',
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("header", {
    style: {
      display: 'flex',
      alignItems: 'baseline',
      gap: '10px',
      marginBottom: '12px'
    }
  }, /*#__PURE__*/React.createElement("h3", {
    style: {
      margin: 0,
      fontFamily: 'var(--font-display)',
      fontSize: 'var(--fs-h4)',
      fontWeight: 700,
      color: 'var(--text-primary)'
    }
  }, title)), note && /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '0 0 16px',
      fontFamily: 'var(--font-serif)',
      fontSize: '15px',
      lineHeight: 1.6,
      color: 'var(--text-secondary)',
      maxWidth: '62ch'
    }
  }, note), sources.length > 0 && /*#__PURE__*/React.createElement("ol", {
    style: {
      listStyle: 'none',
      counterReset: 'src',
      margin: 0,
      padding: 0,
      borderTop: '1px solid var(--border-faint)'
    }
  }, sources.map((s, i) => /*#__PURE__*/React.createElement("li", {
    key: i,
    style: {
      counterIncrement: 'src',
      display: 'grid',
      gridTemplateColumns: '28px 1fr',
      gap: '12px',
      padding: '11px 0',
      borderBottom: '1px solid var(--border-faint)',
      alignItems: 'start'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '12px',
      fontWeight: 600,
      color: 'var(--accent)',
      paddingTop: '2px'
    }
  }, String(i + 1).padStart(2, '0')), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '13.5px',
      lineHeight: 1.5,
      color: 'var(--text-primary)'
    }
  }, s.author && /*#__PURE__*/React.createElement("span", {
    style: {
      fontWeight: 600
    }
  }, s.author, ". "), s.href ? /*#__PURE__*/React.createElement("a", {
    href: s.href,
    style: {
      color: 'var(--link-rest)'
    }
  }, s.title) : /*#__PURE__*/React.createElement("span", {
    style: {
      fontStyle: 'italic'
    }
  }, s.title), s.detail && /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--text-muted)'
    }
  }, ". ", s.detail))))), lastUpdated && /*#__PURE__*/React.createElement("p", {
    style: {
      margin: '12px 0 0',
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.05em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)'
    }
  }, "Last updated \xB7 ", lastUpdated));
}
Object.assign(__ds_scope, { ProvenanceBlock });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/provenance/ProvenanceBlock.jsx", error: String((e && e.message) || e) }); }

// components/record/IndexTable.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const TYPE_COLOR = {
  article: 'var(--type-article)',
  biography: 'var(--type-biography)',
  place: 'var(--type-place)',
  archive: 'var(--type-archive)',
  event: 'var(--type-event)',
  topic: 'var(--type-topic)'
};

/**
 * SAHO IndexTable. The archive itself, pulled in as a ruled catalogue: rows are
 * records, columns are fields. The primary way to browse and query the data.
 * Sortable headers, a content-type swatch per row, mono accession refs.
 */
function IndexTable({
  columns = [],
  rows = [],
  sortKey,
  style,
  ...rest
}) {
  const [sort, setSort] = React.useState(sortKey || null);
  return /*#__PURE__*/React.createElement("div", _extends({
    style: {
      border: 'var(--bw-hair) solid var(--border-default)',
      background: 'var(--surface-card)',
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("table", {
    style: {
      width: '100%',
      borderCollapse: 'collapse',
      fontFamily: 'var(--font-sans)'
    }
  }, /*#__PURE__*/React.createElement("thead", null, /*#__PURE__*/React.createElement("tr", null, columns.map((c, i) => /*#__PURE__*/React.createElement("th", {
    key: i,
    onClick: () => c.sortable && setSort(c.key),
    style: {
      textAlign: c.align || 'left',
      fontFamily: 'var(--font-mono)',
      fontSize: '10px',
      fontWeight: 600,
      letterSpacing: '0.07em',
      textTransform: 'uppercase',
      color: sort === c.key ? 'var(--text-primary)' : 'var(--text-muted)',
      padding: '10px 14px',
      whiteSpace: 'nowrap',
      background: 'var(--surface-sunk)',
      borderBottom: 'var(--bw-rule) solid var(--border-strong)',
      cursor: c.sortable ? 'pointer' : 'default',
      width: c.width
    }
  }, c.label, c.sortable && sort === c.key ? ' \u2193' : '')))), /*#__PURE__*/React.createElement("tbody", null, rows.map((r, ri) => /*#__PURE__*/React.createElement("tr", {
    key: ri,
    style: {
      borderBottom: 'var(--bw-hair) solid var(--border-faint)',
      cursor: 'pointer'
    },
    onMouseEnter: e => {
      e.currentTarget.style.background = 'var(--surface-sunk)';
    },
    onMouseLeave: e => {
      e.currentTarget.style.background = 'transparent';
    }
  }, columns.map((c, ci) => /*#__PURE__*/React.createElement("td", {
    key: ci,
    style: {
      textAlign: c.align || 'left',
      padding: '11px 14px',
      verticalAlign: 'baseline',
      fontFamily: c.mono ? 'var(--font-mono)' : 'var(--font-sans)',
      fontSize: c.mono ? '12px' : '14px',
      color: c.muted ? 'var(--text-muted)' : 'var(--text-primary)',
      fontWeight: c.key === 'title' ? 600 : 400
    }
  }, c.key === 'type' ? /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '8px'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: '9px',
      height: '9px',
      background: TYPE_COLOR[r.type] || 'var(--type-article)'
    }
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.04em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)'
    }
  }, r.type)) : c.render ? c.render(r) : r[c.key])))))));
}
Object.assign(__ds_scope, { IndexTable });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/record/IndexTable.jsx", error: String((e && e.message) || e) }); }

// components/record/RecordHeader.jsx
try { (() => {
function _extends() { return _extends = Object.assign ? Object.assign.bind() : function (n) { for (var e = 1; e < arguments.length; e++) { var t = arguments[e]; for (var r in t) ({}).hasOwnProperty.call(t, r) && (n[r] = t[r]); } return n; }, _extends.apply(null, arguments); }
const TYPE_COLOR = {
  article: 'var(--type-article)',
  biography: 'var(--type-biography)',
  place: 'var(--type-place)',
  archive: 'var(--type-archive)',
  event: 'var(--type-event)',
  topic: 'var(--type-topic)'
};

/**
 * SAHO RecordHeader. Frames any archive entity as a catalogue record: a typed
 * folder tab, an accession reference, the record title, and a ruled strip of
 * key fields. This is the data structure made visible: every page is a record.
 */
function RecordHeader({
  type = 'article',
  reference,
  kicker,
  title,
  facts = [],
  status,
  actions,
  style,
  ...rest
}) {
  const color = TYPE_COLOR[type] || 'var(--type-article)';
  return /*#__PURE__*/React.createElement("header", _extends({
    style: {
      borderTop: `var(--bw-rule) solid ${color}`,
      ...style
    }
  }, rest), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'stretch',
      borderBottom: 'var(--bw-hair) solid var(--border-default)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      background: color,
      color: '#fff',
      fontFamily: 'var(--font-sans)',
      fontSize: '11px',
      fontWeight: 700,
      letterSpacing: '0.08em',
      textTransform: 'uppercase',
      padding: '7px 12px'
    }
  }, type), reference && /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '8px',
      fontFamily: 'var(--font-mono)',
      fontSize: '12px',
      color: 'var(--text-muted)',
      letterSpacing: '0.04em',
      padding: '7px 12px',
      borderLeft: 'var(--bw-hair) solid var(--border-default)'
    }
  }, "REF ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--text-primary)',
      fontWeight: 600
    }
  }, reference)), /*#__PURE__*/React.createElement("span", {
    style: {
      flex: 1
    }
  }), status && /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '7px',
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.05em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      padding: '7px 12px',
      borderLeft: 'var(--bw-hair) solid var(--border-default)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: '7px',
      height: '7px',
      borderRadius: 'var(--radius-full)',
      background: 'var(--saho-success)'
    }
  }), status)), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: 'var(--space-5) 0 var(--space-4)'
    }
  }, kicker && /*#__PURE__*/React.createElement("p", {
    className: "saho-eyebrow",
    style: {
      margin: '0 0 10px'
    }
  }, kicker), /*#__PURE__*/React.createElement("h1", {
    style: {
      margin: 0
    }
  }, title)), facts.length > 0 && /*#__PURE__*/React.createElement("dl", {
    style: {
      margin: 0,
      display: 'flex',
      flexWrap: 'wrap',
      gap: 0,
      borderTop: 'var(--bw-hair) solid var(--border-default)',
      borderBottom: 'var(--bw-hair) solid var(--border-default)'
    }
  }, facts.map((f, i) => /*#__PURE__*/React.createElement("div", {
    key: i,
    style: {
      padding: '10px 18px 10px 0',
      marginRight: '18px',
      borderRight: i < facts.length - 1 ? 'var(--bw-hair) solid var(--border-faint)' : 'none'
    }
  }, /*#__PURE__*/React.createElement("dt", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '10px',
      letterSpacing: '0.06em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)'
    }
  }, f.label), /*#__PURE__*/React.createElement("dd", {
    style: {
      margin: '3px 0 0',
      fontFamily: 'var(--font-sans)',
      fontSize: '14px',
      fontWeight: 600,
      color: 'var(--text-primary)'
    }
  }, f.value)))), actions && /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: '10px',
      marginTop: 'var(--space-4)'
    }
  }, actions));
}
Object.assign(__ds_scope, { RecordHeader });
})(); } catch (e) { __ds_ns.__errors.push({ path: "components/record/RecordHeader.jsx", error: String((e && e.message) || e) }); }

// ui_kits/sahistory/BiographyScreen.jsx
try { (() => {
/* SAHO UI kit · Biography. A catalogue record view: record header with accession
   ref, ruled field tables, the sourced article, provenance, and typed relations. */

function BioRecordHeader() {
  return /*#__PURE__*/React.createElement(RecordHeader, {
    type: "biography",
    reference: "B-0427",
    kicker: "Biography record",
    title: "Nelson Rolihlahla Mandela",
    status: "Verified",
    facts: [{
      label: 'Born',
      value: '18 Jul 1918'
    }, {
      label: 'Died',
      value: '5 Dec 2013'
    }, {
      label: 'Lived',
      value: '95 years'
    }, {
      label: 'Sources',
      value: '14'
    }, {
      label: 'Updated',
      value: '2026-02-11'
    }],
    actions: [/*#__PURE__*/React.createElement(Button, {
      key: "1",
      variant: "primary",
      size: "sm"
    }, "Cite this record"), /*#__PURE__*/React.createElement(Button, {
      key: "2",
      variant: "quiet",
      size: "sm"
    }, "Download sources")]
  });
}
function BioArticle() {
  return /*#__PURE__*/React.createElement("article", {
    style: {
      maxWidth: 'var(--measure)'
    }
  }, /*#__PURE__*/React.createElement("p", {
    style: {
      fontFamily: 'var(--font-serif)',
      fontSize: 'var(--fs-base)',
      lineHeight: 1.62,
      color: 'var(--text-primary)'
    }
  }, "Nelson Mandela was born in ", /*#__PURE__*/React.createElement("a", {
    href: "#"
  }, "Mvezo"), ", in the ", /*#__PURE__*/React.createElement("a", {
    href: "#"
  }, "Eastern Cape"), ", on 18 July 1918. Trained as a lawyer, he joined the ", /*#__PURE__*/React.createElement("a", {
    href: "#"
  }, "African National Congress"), " in 1943 and rose to prominence in the ", /*#__PURE__*/React.createElement("a", {
    href: "#"
  }, "Defiance Campaign"), " of 1952, organising mass resistance to the apartheid state's pass laws and racial legislation."), /*#__PURE__*/React.createElement("p", {
    style: {
      fontFamily: 'var(--font-serif)',
      fontSize: 'var(--fs-base)',
      lineHeight: 1.62,
      color: 'var(--text-primary)'
    }
  }, "Following the ", /*#__PURE__*/React.createElement("a", {
    href: "#"
  }, "Sharpeville massacre"), " in 1960 and the banning of the ANC, Mandela went underground and helped found Umkhonto we Sizwe. He was arrested in 1962 and, at the ", /*#__PURE__*/React.createElement("a", {
    href: "#"
  }, "Rivonia Trial"), ", sentenced to life imprisonment on ", /*#__PURE__*/React.createElement("a", {
    href: "#"
  }, "Robben Island"), "."), /*#__PURE__*/React.createElement("blockquote", null, "\"I have cherished the ideal of a democratic and free society. It is an ideal which I hope to live for and to achieve. But if needs be, it is an ideal for which I am prepared to die.\""), /*#__PURE__*/React.createElement("p", {
    style: {
      fontFamily: 'var(--font-serif)',
      fontSize: 'var(--fs-base)',
      lineHeight: 1.62,
      color: 'var(--text-primary)'
    }
  }, "Released in 1990, Mandela led negotiations to end apartheid. In 1994 he became president in the country's first fully representative election, presiding over the ", /*#__PURE__*/React.createElement("a", {
    href: "#"
  }, "Truth and Reconciliation Commission"), " and the transition to constitutional democracy."), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 'var(--space-7)'
    }
  }, /*#__PURE__*/React.createElement(ProvenanceBlock, {
    note: "This record is compiled from the Nelson Mandela Foundation archive, contemporary press records, and court transcripts, and is cross-checked against the Truth and Reconciliation Commission report (1998).",
    sources: [{
      author: 'Truth and Reconciliation Commission',
      title: 'Final Report, vol. 2',
      detail: 'Cape Town, 1998',
      href: '#'
    }, {
      author: 'Mandela, N.',
      title: 'Long Walk to Freedom',
      detail: 'Little, Brown, 1994'
    }, {
      author: 'Sampson, A.',
      title: 'Mandela: The Authorised Biography',
      detail: 'HarperCollins, 1999'
    }, {
      title: 'SAHO Biography Project, ref. B-0427',
      href: '#'
    }],
    lastUpdated: "2026-02-11"
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      marginTop: 'var(--space-5)'
    }
  }, /*#__PURE__*/React.createElement(Citation, null)));
}
function BioRail() {
  return /*#__PURE__*/React.createElement("aside", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-6)'
    }
  }, /*#__PURE__*/React.createElement(ImageCredit, {
    src: "../../assets/default-portrait.svg",
    duotone: true,
    ratio: "3 / 4",
    credit: "Photographer unknown, c.1961",
    source: "SAHO Collection, ref. P-1182"
  }), /*#__PURE__*/React.createElement(MetadataBlock, {
    accent: "biography",
    title: "Record fields",
    items: [{
      label: 'Full name',
      value: 'Nelson Rolihlahla Mandela'
    }, {
      label: 'Born',
      value: '18 July 1918, Mvezo'
    }, {
      label: 'Died',
      value: '5 Dec 2013, Johannesburg'
    }, {
      label: 'Roles',
      value: 'Activist, Statesman'
    }, {
      label: 'Office',
      value: 'President, 1994–1999'
    }, {
      label: 'Affiliation',
      value: 'ANC',
      href: '#'
    }]
  }), /*#__PURE__*/React.createElement(Timeline, {
    title: "Life chronology",
    events: [{
      year: '1944',
      title: 'Joins the ANC Youth League'
    }, {
      year: '1952',
      title: 'Defiance Campaign',
      href: '#'
    }, {
      year: '1964',
      title: 'Rivonia Trial, life sentence'
    }, {
      year: '1990',
      title: 'Released from prison',
      href: '#'
    }, {
      year: '1994',
      title: 'Elected president'
    }]
  }), /*#__PURE__*/React.createElement(RelatedList, {
    title: "Linked records",
    items: [{
      label: 'Walter Sisulu',
      type: 'biography',
      note: '1912–2003'
    }, {
      label: 'Robben Island',
      type: 'place',
      note: 'Western Cape'
    }, {
      label: 'Rivonia Trial',
      type: 'event',
      note: '1963–1964'
    }, {
      label: 'Freedom Charter',
      type: 'archive',
      note: '1955'
    }]
  }));
}
function BiographyScreen() {
  return /*#__PURE__*/React.createElement("main", {
    style: {
      maxWidth: 'var(--container-standard)',
      margin: '0 auto',
      padding: 'var(--space-5) var(--gutter-page) 0'
    }
  }, /*#__PURE__*/React.createElement("nav", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.05em',
      color: 'var(--text-muted)',
      marginBottom: 'var(--space-4)'
    }
  }, /*#__PURE__*/React.createElement("a", {
    href: "#home",
    "data-nav": "home",
    style: {
      color: 'var(--text-muted)'
    }
  }, "ARCHIVE"), " / ", /*#__PURE__*/React.createElement("a", {
    href: "#",
    style: {
      color: 'var(--text-muted)'
    }
  }, "BIOGRAPHIES"), " / ", /*#__PURE__*/React.createElement("span", {
    style: {
      color: 'var(--saho-oxblood)'
    }
  }, "B-0427")), /*#__PURE__*/React.createElement(BioRecordHeader, null), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr var(--rail-width)',
      gap: 'var(--rail-gap)',
      alignItems: 'start',
      marginTop: 'var(--space-6)'
    }
  }, /*#__PURE__*/React.createElement(BioArticle, null), /*#__PURE__*/React.createElement(BioRail, null)));
}
Object.assign(window, {
  BiographyScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/sahistory/BiographyScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/sahistory/Chrome.jsx
try { (() => {
/* SAHO UI kit · shared site chrome (header, footer, accent rule).
   Uses global React (loaded via UMD) and the compiled DS bundle. */

const {
  useState
} = React;
function AccentBar() {
  return /*#__PURE__*/React.createElement("div", {
    style: {
      height: '3px',
      background: 'linear-gradient(90deg, var(--saho-oxblood) 0%, var(--saho-oxblood) 62%, var(--saho-ochre) 62%, var(--saho-ochre) 100%)'
    }
  });
}
function Wordmark({
  small
}) {
  return /*#__PURE__*/React.createElement("a", {
    href: "#home",
    "data-nav": "home",
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: '12px',
      textDecoration: 'none'
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: "../../assets/saho-logo.svg",
    alt: "SAHO",
    style: {
      width: small ? '36px' : '46px',
      height: small ? '36px' : '46px'
    }
  }), /*#__PURE__*/React.createElement("span", {
    style: {
      lineHeight: 1.04
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'block',
      fontFamily: 'var(--font-display)',
      fontWeight: 800,
      fontSize: small ? '17px' : '20px',
      color: 'var(--text-primary)',
      letterSpacing: '-0.015em'
    }
  }, "South African History Online"), /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'block',
      fontFamily: 'var(--font-mono)',
      fontSize: '10.5px',
      letterSpacing: '0.08em',
      textTransform: 'uppercase',
      color: 'var(--saho-oxblood)',
      marginTop: '2px'
    }
  }, "The open record \xB7 est. 2000")));
}
function SiteHeader({
  active,
  onNav
}) {
  const items = [['Biographies', 'biography'], ['Timeline', 'timeline'], ['Topics', 'home'], ['Places', 'home'], ['Archive', 'search'], ['Classroom', 'home']];
  return /*#__PURE__*/React.createElement("header", {
    style: {
      position: 'sticky',
      top: 0,
      zIndex: 1030,
      background: 'var(--saho-paper-raised)',
      borderBottom: '1px solid var(--border-default)'
    }
  }, /*#__PURE__*/React.createElement(AccentBar, null), /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 'var(--container-wide)',
      margin: '0 auto',
      padding: '14px var(--gutter-page)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: '24px'
    }
  }, /*#__PURE__*/React.createElement(Wordmark, null), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: '18px'
    }
  }, /*#__PURE__*/React.createElement("nav", {
    style: {
      display: 'flex',
      gap: '4px'
    }
  }, items.map(([label, key], i) => /*#__PURE__*/React.createElement("a", {
    key: i,
    href: '#' + key,
    "data-nav": key,
    onClick: () => onNav && onNav(key),
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '14px',
      fontWeight: 600,
      color: active === key ? 'var(--saho-oxblood)' : 'var(--text-primary)',
      textDecoration: 'none',
      padding: '8px 11px',
      borderRadius: 'var(--radius-xs)',
      borderBottom: active === key ? '2px solid var(--saho-oxblood)' : '2px solid transparent'
    }
  }, label))), /*#__PURE__*/React.createElement("a", {
    href: "#search",
    "data-nav": "search",
    onClick: () => onNav && onNav('search'),
    "aria-label": "Search",
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      width: '40px',
      height: '40px',
      background: 'var(--accent)',
      color: 'var(--text-on-accent)',
      borderRadius: 'var(--radius-sm)',
      textDecoration: 'none',
      fontSize: '19px'
    }
  }, "\u2315"))));
}
function SiteFooter() {
  const cols = [['Browse', ['Biographies', 'Topics', 'Places', 'Timeline', 'Archive']], ['Programmes', ['Classroom', 'Commemorations', 'Features', 'Educator resources']], ['About SAHO', ['Our mission', 'How we source', 'Contribute', 'Contact']]];
  return /*#__PURE__*/React.createElement("footer", {
    style: {
      background: 'var(--saho-ink)',
      color: 'var(--text-on-dark)',
      marginTop: 'var(--space-9)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      height: '3px',
      background: 'linear-gradient(90deg, var(--saho-oxblood) 0%, var(--saho-oxblood) 62%, var(--saho-ochre) 62%, var(--saho-ochre) 100%)'
    }
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 'var(--container-wide)',
      margin: '0 auto',
      padding: 'var(--space-8) var(--gutter-page) var(--space-6)',
      display: 'grid',
      gridTemplateColumns: '1.4fr 1fr 1fr 1fr',
      gap: '40px'
    }
  }, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-display)',
      fontWeight: 800,
      fontSize: '20px',
      letterSpacing: '-0.015em'
    }
  }, "South African History Online"), /*#__PURE__*/React.createElement("p", {
    style: {
      fontFamily: 'var(--font-serif)',
      fontSize: '14px',
      lineHeight: 1.6,
      color: '#c8bba4',
      maxWidth: '34ch',
      marginTop: '10px'
    }
  }, "The largest independent, non-partisan history archive in South Africa. Anti-paywall by principle \xB7 knowledge here is free, sourced and accessible.")), cols.map(([title, links], i) => /*#__PURE__*/React.createElement("div", {
    key: i
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.06em',
      textTransform: 'uppercase',
      color: 'var(--saho-ochre)',
      marginBottom: '12px'
    }
  }, title), links.map((l, j) => /*#__PURE__*/React.createElement("a", {
    key: j,
    href: "#",
    style: {
      display: 'block',
      fontFamily: 'var(--font-sans)',
      fontSize: '13.5px',
      color: '#d8cdb9',
      textDecoration: 'none',
      padding: '5px 0'
    }
  }, l))))), /*#__PURE__*/React.createElement("div", {
    style: {
      maxWidth: 'var(--container-wide)',
      margin: '0 auto',
      padding: '16px var(--gutter-page)',
      borderTop: '1px solid rgba(255,255,255,0.12)',
      display: 'flex',
      justifyContent: 'space-between',
      gap: '16px',
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      color: '#a89a82'
    }
  }, "\xA9 2000\u20132026 SAHO \xB7 Content licensed CC BY-NC-SA 4.0"), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      color: '#a89a82'
    }
  }, "Self-hosted \xB7 WCAG 2.2 AA")));
}
Object.assign(window, {
  AccentBar,
  Wordmark,
  SiteHeader,
  SiteFooter
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/sahistory/Chrome.jsx", error: String((e && e.message) || e) }); }

// ui_kits/sahistory/HomeScreen.jsx
try { (() => {
/* SAHO UI kit · Home. Not a splash: the catalogue front page. A status line of
   record counts, the current editorial feature, a browse index, and the most
   recent additions pulled in as a catalogue table. */

function ArchiveStatusBar() {
  const stats = [['Records', '21,684'], ['Biographies', '4,210'], ['Events', '2,140'], ['Places', '930'], ['Documents', '12,400'], ['Sources cited', '58,902']];
  return /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexWrap: 'wrap',
      borderTop: 'var(--bw-rule) solid var(--saho-ink)',
      borderBottom: 'var(--bw-hair) solid var(--border-default)'
    }
  }, stats.map(([k, v], i) => /*#__PURE__*/React.createElement("div", {
    key: i,
    style: {
      padding: '10px 18px 10px 0',
      marginRight: '18px',
      borderRight: i < stats.length - 1 ? 'var(--bw-hair) solid var(--border-faint)' : 'none'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '10px',
      letterSpacing: '0.06em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)'
    }
  }, k), /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '17px',
      fontWeight: 600,
      color: 'var(--text-primary)',
      marginTop: '2px'
    }
  }, v))));
}
function HomeFeature() {
  return /*#__PURE__*/React.createElement("section", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1.55fr 1fr',
      gap: 'var(--space-7)',
      alignItems: 'stretch',
      margin: 'var(--space-7) 0',
      borderBottom: 'var(--bw-hair) solid var(--border-default)',
      paddingBottom: 'var(--space-7)'
    }
  }, /*#__PURE__*/React.createElement("article", {
    style: {
      background: 'var(--saho-ink)',
      color: 'var(--text-on-dark)',
      padding: 'var(--space-6)',
      borderTop: 'var(--bw-accent) solid var(--saho-oxblood)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.07em',
      textTransform: 'uppercase',
      color: 'var(--saho-ochre)'
    }
  }, "Current feature \xB7 Commemoration"), /*#__PURE__*/React.createElement("h1", {
    className: "saho-editorial-title",
    style: {
      fontSize: 'clamp(2.4rem, 1.5rem + 3.4vw, 4.2rem)',
      margin: '14px 0 16px',
      color: 'var(--text-on-dark)'
    }
  }, "The children of the Soweto Uprising"), /*#__PURE__*/React.createElement("p", {
    style: {
      fontFamily: 'var(--font-serif)',
      fontSize: 'var(--fs-md)',
      lineHeight: 1.6,
      color: '#c8c2b2',
      maxWidth: '52ch'
    }
  }, "On 16 June 1976, thousands of Soweto students protested the imposition of Afrikaans as a language of instruction. The state's response reshaped the struggle. Forty-nine years on, we revisit the record: the names, the photographs, and how we know what happened."), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      gap: '12px',
      marginTop: '22px',
      flexWrap: 'wrap'
    }
  }, /*#__PURE__*/React.createElement(Button, {
    variant: "primary",
    size: "lg"
  }, "Open the feature"), /*#__PURE__*/React.createElement(Button, {
    variant: "ghost",
    size: "lg",
    style: {
      color: 'var(--saho-ochre)',
      borderColor: 'rgba(255,255,255,0.25)'
    }
  }, "View chronology")), /*#__PURE__*/React.createElement("p", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.05em',
      color: '#9a937f',
      marginTop: '20px'
    }
  }, "EDITORIAL REGISTER \xB7 14 LINKED RECORDS")), /*#__PURE__*/React.createElement("aside", {
    style: {
      border: 'var(--bw-hair) solid var(--border-default)',
      display: 'flex',
      flexDirection: 'column'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.06em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      padding: '11px 16px',
      background: 'var(--surface-sunk)',
      borderBottom: 'var(--bw-hair) solid var(--border-default)'
    }
  }, "This day \xB7 16 June"), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '6px 16px 16px',
      flex: 1
    }
  }, [['1976', 'Soweto Uprising begins', 'event'], ['1980', 'Cape Town schools boycott', 'event'], ['1913', 'Natives Land Act protests', 'topic']].map(([y, t, type], i) => /*#__PURE__*/React.createElement("a", {
    key: i,
    href: "#",
    style: {
      display: 'grid',
      gridTemplateColumns: '9px 1fr',
      gap: '12px',
      padding: '12px 0',
      borderBottom: i < 2 ? 'var(--bw-hair) solid var(--border-faint)' : 'none',
      textDecoration: 'none',
      alignItems: 'baseline'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      width: '9px',
      height: '9px',
      background: `var(--type-${type})`,
      transform: 'translateY(3px)'
    }
  }), /*#__PURE__*/React.createElement("span", null, /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'block',
      fontFamily: 'var(--font-mono)',
      fontSize: '12px',
      color: 'var(--accent)',
      fontWeight: 600
    }
  }, y), /*#__PURE__*/React.createElement("span", {
    style: {
      display: 'block',
      fontFamily: 'var(--font-display)',
      fontSize: '17px',
      fontWeight: 700,
      color: 'var(--text-primary)',
      lineHeight: 1.2
    }
  }, t)))), /*#__PURE__*/React.createElement("a", {
    href: "#timeline",
    "data-nav": "timeline",
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '13px',
      fontWeight: 600,
      display: 'inline-block',
      marginTop: '8px'
    }
  }, "Open the full timeline \u2192"))));
}
function BrowseIndex() {
  const items = [['Biographies', 'biography', '4,210'], ['Topics', 'topic', '1,860'], ['Places', 'place', '930'], ['Events', 'event', '2,140'], ['Archive', 'archive', '12,400'], ['Classroom', 'article', '320']];
  return /*#__PURE__*/React.createElement("section", {
    style: {
      marginBottom: 'var(--space-7)'
    }
  }, /*#__PURE__*/React.createElement("h2", {
    style: {
      fontSize: 'var(--fs-h3)',
      marginBottom: '16px'
    }
  }, "Browse the archive"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'repeat(auto-fill, minmax(210px, 1fr))',
      gap: 0,
      borderTop: 'var(--bw-hair) solid var(--border-default)',
      borderLeft: 'var(--bw-hair) solid var(--border-default)'
    }
  }, items.map(([label, type, count], i) => /*#__PURE__*/React.createElement("a", {
    key: i,
    href: "#",
    style: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: '12px',
      background: 'var(--surface-card)',
      borderLeft: `var(--bw-accent) solid var(--type-${type})`,
      borderRight: 'var(--bw-hair) solid var(--border-default)',
      borderBottom: 'var(--bw-hair) solid var(--border-default)',
      padding: '15px 16px',
      textDecoration: 'none'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-display)',
      fontSize: '19px',
      fontWeight: 700,
      color: 'var(--text-primary)'
    }
  }, label), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '12px',
      color: 'var(--text-muted)'
    }
  }, count)))));
}
function RecentRecords() {
  const rows = [{
    ref: 'B-0427',
    type: 'biography',
    title: 'Nelson Rolihlahla Mandela',
    dates: '1918–2013',
    status: 'Revised'
  }, {
    ref: 'E-1190',
    type: 'event',
    title: 'The Sharpeville massacre',
    dates: '21 Mar 1960',
    status: 'Verified'
  }, {
    ref: 'P-0042',
    type: 'place',
    title: 'Robben Island',
    dates: 'Western Cape',
    status: 'Verified'
  }, {
    ref: 'A-2255',
    type: 'archive',
    title: 'The Freedom Charter',
    dates: '26 Jun 1955',
    status: 'New'
  }, {
    ref: 'T-0318',
    type: 'topic',
    title: 'The Defiance Campaign',
    dates: '1952',
    status: 'Revised'
  }];
  const columns = [{
    key: 'ref',
    label: 'Ref',
    mono: true,
    width: '92px'
  }, {
    key: 'type',
    label: 'Type',
    sortable: true,
    width: '150px'
  }, {
    key: 'title',
    label: 'Record',
    sortable: true,
    render: r => /*#__PURE__*/React.createElement("a", {
      href: "#biography",
      "data-nav": "biography",
      style: {
        color: 'var(--text-primary)',
        textDecoration: 'none',
        fontWeight: 600
      }
    }, r.title)
  }, {
    key: 'dates',
    label: 'Dates',
    mono: true,
    muted: true,
    width: '150px'
  }, {
    key: 'status',
    label: 'Status',
    mono: true,
    muted: true,
    width: '110px'
  }];
  return /*#__PURE__*/React.createElement("section", {
    style: {
      paddingBottom: 'var(--space-7)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      alignItems: 'baseline',
      justifyContent: 'space-between',
      marginBottom: '16px'
    }
  }, /*#__PURE__*/React.createElement("h2", {
    style: {
      fontSize: 'var(--fs-h3)',
      margin: 0
    }
  }, "Recently added to the archive"), /*#__PURE__*/React.createElement("a", {
    href: "#search",
    "data-nav": "search",
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '14px',
      fontWeight: 600
    }
  }, "All updates \u2192")), /*#__PURE__*/React.createElement(IndexTable, {
    columns: columns,
    rows: rows,
    sortKey: "title"
  }));
}
function HomeScreen() {
  return /*#__PURE__*/React.createElement("main", {
    style: {
      maxWidth: 'var(--container-standard)',
      margin: '0 auto',
      padding: 'var(--space-5) var(--gutter-page) 0'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      marginBottom: 'var(--space-5)'
    }
  }, /*#__PURE__*/React.createElement(SearchField, {
    scopes: ['All', 'People', 'Events', 'Places', 'Archive']
  })), /*#__PURE__*/React.createElement(ArchiveStatusBar, null), /*#__PURE__*/React.createElement(HomeFeature, null), /*#__PURE__*/React.createElement(BrowseIndex, null), /*#__PURE__*/React.createElement(RecentRecords, null));
}
Object.assign(window, {
  HomeScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/sahistory/HomeScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/sahistory/SearchScreen.jsx
try { (() => {
/* SAHO UI kit · Search. The front door = a query over the archive. Results pull
   in as a ruled catalogue index (IndexTable): rows are records, columns fields. */

function SearchScreen() {
  const rows = [{
    ref: 'B-0427',
    type: 'biography',
    title: 'Nelson Rolihlahla Mandela',
    dates: '1918–2013',
    sources: 14
  }, {
    ref: 'E-1190',
    type: 'event',
    title: 'The Sharpeville massacre',
    dates: '21 Mar 1960',
    sources: 11
  }, {
    ref: 'P-0042',
    type: 'place',
    title: 'Robben Island',
    dates: 'Western Cape',
    sources: 7
  }, {
    ref: 'A-2255',
    type: 'archive',
    title: 'The Freedom Charter',
    dates: '26 Jun 1955',
    sources: 9
  }, {
    ref: 'T-0318',
    type: 'topic',
    title: 'The Defiance Campaign',
    dates: '1952',
    sources: 9
  }, {
    ref: 'B-0512',
    type: 'biography',
    title: 'Walter Sisulu',
    dates: '1912–2003',
    sources: 6
  }, {
    ref: 'E-0904',
    type: 'event',
    title: 'Soweto Uprising',
    dates: '16 Jun 1976',
    sources: 13
  }];
  const columns = [{
    key: 'ref',
    label: 'Ref',
    mono: true,
    width: '92px'
  }, {
    key: 'type',
    label: 'Type',
    sortable: true,
    width: '150px'
  }, {
    key: 'title',
    label: 'Record',
    sortable: true,
    render: r => /*#__PURE__*/React.createElement("a", {
      href: "#",
      style: {
        color: 'var(--text-primary)',
        textDecoration: 'none',
        fontWeight: 600
      }
    }, r.title)
  }, {
    key: 'dates',
    label: 'Dates',
    mono: true,
    muted: true,
    sortable: true,
    width: '140px'
  }, {
    key: 'sources',
    label: 'Src',
    mono: true,
    align: 'right',
    sortable: true,
    width: '64px'
  }];
  return /*#__PURE__*/React.createElement("main", {
    style: {
      maxWidth: 'var(--container-standard)',
      margin: '0 auto',
      padding: 'var(--space-7) var(--gutter-page) 0'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      marginBottom: 'var(--space-6)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    className: "saho-eyebrow"
  }, "Query the archive"), /*#__PURE__*/React.createElement("h1", {
    style: {
      fontSize: 'clamp(2rem, 1.5rem + 2vw, 3rem)',
      margin: '10px 0 18px'
    }
  }, "Search the record"), /*#__PURE__*/React.createElement(SearchField, {
    scopes: ['All', 'People', 'Events', 'Places', 'Archive', 'Topics']
  })), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: 'var(--rail-width) 1fr',
      gap: 'var(--rail-gap)',
      alignItems: 'start'
    }
  }, /*#__PURE__*/React.createElement("aside", null, /*#__PURE__*/React.createElement("div", {
    style: {
      border: 'var(--bw-hair) solid var(--border-default)',
      background: 'var(--surface-card)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '10px',
      letterSpacing: '0.07em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      padding: '9px 14px',
      background: 'var(--surface-sunk)',
      borderBottom: 'var(--bw-hair) solid var(--border-default)'
    }
  }, "Refine \xB7 1,284 records"), /*#__PURE__*/React.createElement("div", {
    style: {
      padding: '14px'
    }
  }, [['Content type', ['Biography', 'Event', 'Place', 'Archive', 'Topic']], ['Era', ['Pre-1948', '1948–1990', 'Post-1994']]].map(([group, opts], i) => /*#__PURE__*/React.createElement("div", {
    key: i,
    style: {
      marginBottom: i === 0 ? '18px' : 0
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '13px',
      fontWeight: 700,
      color: 'var(--text-primary)',
      marginBottom: '9px'
    }
  }, group), opts.map((o, j) => /*#__PURE__*/React.createElement("label", {
    key: j,
    style: {
      display: 'flex',
      alignItems: 'center',
      gap: '9px',
      padding: '4px 0',
      fontFamily: 'var(--font-sans)',
      fontSize: '13.5px',
      color: 'var(--text-secondary)',
      cursor: 'pointer'
    }
  }, /*#__PURE__*/React.createElement("input", {
    type: "checkbox",
    defaultChecked: i === 0 && j === 0,
    style: {
      accentColor: 'var(--saho-oxblood)',
      width: '15px',
      height: '15px',
      borderRadius: 0
    }
  }), " ", o))))))), /*#__PURE__*/React.createElement("section", null, /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'baseline',
      marginBottom: 'var(--space-3)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '12px',
      color: 'var(--text-muted)',
      letterSpacing: '0.04em'
    }
  }, "RESULTS 1\u20137 OF 1,284"), /*#__PURE__*/React.createElement("span", {
    style: {
      fontFamily: 'var(--font-sans)',
      fontSize: '13px',
      color: 'var(--text-secondary)'
    }
  }, "Sort: ", /*#__PURE__*/React.createElement("b", {
    style: {
      color: 'var(--text-primary)'
    }
  }, "Relevance"))), /*#__PURE__*/React.createElement(IndexTable, {
    columns: columns,
    rows: rows,
    sortKey: "title"
  }))));
}
Object.assign(window, {
  SearchScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/sahistory/SearchScreen.jsx", error: String((e && e.message) || e) }); }

// ui_kits/sahistory/TimelineScreen.jsx
try { (() => {
/* SAHO UI kit · Timeline. A first-class navigational surface, filterable. */

function TimelineScreen() {
  return /*#__PURE__*/React.createElement("main", {
    style: {
      maxWidth: 'var(--container-standard)',
      margin: '0 auto',
      padding: 'var(--space-7) var(--gutter-page) 0'
    }
  }, /*#__PURE__*/React.createElement("header", {
    style: {
      marginBottom: 'var(--space-6)'
    }
  }, /*#__PURE__*/React.createElement("span", {
    className: "saho-eyebrow"
  }, "Navigational surface"), /*#__PURE__*/React.createElement("h1", {
    style: {
      fontSize: 'clamp(2.4rem, 1.7rem + 3vw, 4rem)',
      margin: '10px 0 14px'
    }
  }, "A timeline of South African history"), /*#__PURE__*/React.createElement("p", {
    style: {
      fontFamily: 'var(--font-serif)',
      fontSize: 'var(--fs-md)',
      lineHeight: 1.55,
      color: 'var(--text-secondary)',
      maxWidth: '60ch'
    }
  }, "Chronology is how this material wants to be navigated. Filter the record by theme to trace a single thread through four centuries.")), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'grid',
      gridTemplateColumns: '1fr var(--rail-width)',
      gap: 'var(--rail-gap)',
      alignItems: 'start'
    }
  }, /*#__PURE__*/React.createElement(Timeline, {
    title: "The record",
    themes: ['Legislation', 'Protest', 'Trials', 'Transition'],
    events: [{
      year: '1652',
      title: 'Dutch settlement at the Cape',
      detail: 'The Dutch East India Company establishes a refreshment station, beginning colonial settlement.',
      theme: 'Legislation'
    }, {
      year: '1910',
      title: 'Union of South Africa',
      detail: 'Four colonies unite; political rights are restricted along racial lines.',
      theme: 'Legislation',
      href: '#'
    }, {
      year: '1948',
      title: 'Apartheid legislated',
      detail: 'The National Party comes to power and codifies racial segregation into law.',
      theme: 'Legislation',
      href: '#'
    }, {
      year: '1955',
      title: 'The Freedom Charter',
      detail: 'Adopted at the Congress of the People in Kliptown.',
      theme: 'Protest'
    }, {
      year: '1960',
      title: 'Sharpeville massacre',
      detail: '69 killed during a pass-law protest; the ANC and PAC are banned.',
      theme: 'Protest',
      href: '#'
    }, {
      year: '1964',
      title: 'Rivonia Trial',
      detail: 'Mandela and others sentenced to life imprisonment.',
      theme: 'Trials'
    }, {
      year: '1976',
      title: 'Soweto Uprising',
      detail: 'Students protest Afrikaans-medium instruction; the state responds with force.',
      theme: 'Protest',
      href: '#'
    }, {
      year: '1990',
      title: 'Mandela released; bans lifted',
      detail: 'Negotiations to end apartheid begin.',
      theme: 'Transition'
    }, {
      year: '1994',
      title: 'First democratic election',
      detail: 'Universal franchise; Mandela becomes president.',
      theme: 'Transition',
      href: '#'
    }]
  }), /*#__PURE__*/React.createElement("aside", {
    style: {
      display: 'flex',
      flexDirection: 'column',
      gap: 'var(--space-6)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      background: 'var(--surface-card)',
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)',
      padding: 'var(--space-5)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.06em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      marginBottom: '12px'
    }
  }, "Filter by era"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexWrap: 'wrap',
      gap: '7px'
    }
  }, ['Pre-1910', 'Union era', 'Apartheid 1948–1990', 'Transition', 'Democratic'].map((e, i) => /*#__PURE__*/React.createElement(Tag, {
    key: i,
    href: "#",
    active: i === 2
  }, e)))), /*#__PURE__*/React.createElement("div", {
    style: {
      background: 'var(--surface-card)',
      border: '1px solid var(--border-default)',
      borderRadius: 'var(--radius-md)',
      padding: 'var(--space-5)'
    }
  }, /*#__PURE__*/React.createElement("div", {
    style: {
      fontFamily: 'var(--font-mono)',
      fontSize: '11px',
      letterSpacing: '0.06em',
      textTransform: 'uppercase',
      color: 'var(--text-muted)',
      marginBottom: '12px'
    }
  }, "Filter by place"), /*#__PURE__*/React.createElement("div", {
    style: {
      display: 'flex',
      flexWrap: 'wrap',
      gap: '7px'
    }
  }, ['Cape', 'Gauteng', 'KwaZulu-Natal', 'Eastern Cape'].map((e, i) => /*#__PURE__*/React.createElement(Tag, {
    key: i,
    href: "#",
    count: [210, 184, 96, 72][i]
  }, e)))))));
}
Object.assign(window, {
  TimelineScreen
});
})(); } catch (e) { __ds_ns.__errors.push({ path: "ui_kits/sahistory/TimelineScreen.jsx", error: String((e && e.message) || e) }); }

__ds_ns.ArchiveCard = __ds_scope.ArchiveCard;

__ds_ns.MetadataBlock = __ds_scope.MetadataBlock;

__ds_ns.Badge = __ds_scope.Badge;

__ds_ns.Button = __ds_scope.Button;

__ds_ns.Tag = __ds_scope.Tag;

__ds_ns.RelatedList = __ds_scope.RelatedList;

__ds_ns.SearchField = __ds_scope.SearchField;

__ds_ns.Timeline = __ds_scope.Timeline;

__ds_ns.Citation = __ds_scope.Citation;

__ds_ns.ContentWarning = __ds_scope.ContentWarning;

__ds_ns.ImageCredit = __ds_scope.ImageCredit;

__ds_ns.ProvenanceBlock = __ds_scope.ProvenanceBlock;

__ds_ns.IndexTable = __ds_scope.IndexTable;

__ds_ns.RecordHeader = __ds_scope.RecordHeader;

})();
