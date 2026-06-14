#!/usr/bin/env python3
"""Analyze a Google Search Console Performance export bundle for SAHO.

Usage: python3 seo/scripts/analyze_gsc.py seo/inbox/gsc-performance
Reads Pages.csv / Queries.csv / Devices.csv and surfaces:
  - overall + per-device totals and CTR
  - "striking distance" queries (pos 4-20, high impressions) = organic upside
  - content-type performance (parsed from URL path)
  - high-impression / low-CTR pages (snippet + rich-result opportunity)
"""
import csv, sys, re
from collections import defaultdict
from pathlib import Path

DIR = Path(sys.argv[1] if len(sys.argv) > 1 else "seo/inbox/gsc-performance")

def num(s):
    s = (s or "").replace(",", "").replace("%", "").strip()
    try:
        return float(s)
    except ValueError:
        return 0.0

def rows(name):
    p = DIR / name
    if not p.exists():
        return []
    with open(p, newline="", encoding="utf-8-sig") as f:
        return list(csv.DictReader(f))

def get(r, *keys):
    for k in r:
        for want in keys:
            if k.strip().lower() == want.lower():
                return r[k]
    return ""

# ---- Content type from URL path ----
def ctype(url):
    m = re.search(r"sahistory\.org\.za/([^/]+)/", url)
    seg = m.group(1) if m else "(root/other)"
    mapping = {
        "people": "biography", "biography": "biography",
        "article": "article", "archive": "archive",
        "place": "place", "places": "place",
        "dated-event": "event", "topic": "topic-listing",
        "politics-society": "feature", "south-africa": "feature",
    }
    return mapping.get(seg, seg)

print("=" * 70)
print("GSC PERFORMANCE ANALYSIS — SAHO (16 months)")
print("=" * 70)

# ---- Queries: striking distance ----
q = rows("Queries.csv")
striking = []
for r in q:
    query = get(r, "Top queries", "Query")
    clk, imp = num(get(r, "Clicks")), num(get(r, "Impressions"))
    pos, ctr = num(get(r, "Position")), num(get(r, "CTR"))
    if 4.0 <= pos <= 20.0 and imp >= 5000:
        # upside = impressions * realistic CTR gap if moved to top 3 (~8% blended)
        upside = imp * max(0.0, 0.08 - ctr / 100.0)
        striking.append((upside, query, clk, imp, ctr, pos))
striking.sort(reverse=True)
print(f"\n--- TOP 25 STRIKING-DISTANCE QUERIES (pos 4-20, imp>=5k) ---")
print(f"{'est.upside':>10} {'pos':>5} {'CTR%':>5} {'impr':>9}  query")
for up, query, clk, imp, ctr, pos in striking[:25]:
    print(f"{up:10.0f} {pos:5.1f} {ctr:5.1f} {imp:9.0f}  {query[:48]}")
print(f"\nTotal striking-distance queries: {len(striking)}  |  "
      f"sum est. monthly-ish upside (clicks): {sum(s[0] for s in striking):,.0f}")

# ---- Pages: content type rollup ----
p = rows("Pages.csv")
agg = defaultdict(lambda: [0.0, 0.0])  # clicks, impressions
for r in p:
    url = get(r, "Top pages", "Page")
    agg[ctype(url)][0] += num(get(r, "Clicks"))
    agg[ctype(url)][1] += num(get(r, "Impressions"))
print(f"\n--- CONTENT-TYPE PERFORMANCE (from top {len(p)} pages) ---")
print(f"{'type':<16} {'clicks':>10} {'impressions':>13} {'CTR%':>6}")
for t, (c, i) in sorted(agg.items(), key=lambda x: -x[1][1]):
    ctr = (c / i * 100) if i else 0
    print(f"{t:<16} {c:10.0f} {i:13.0f} {ctr:6.2f}")

# ---- High-impression low-CTR pages ----
lowctr = []
for r in p:
    url = get(r, "Top pages", "Page")
    clk, imp = num(get(r, "Clicks")), num(get(r, "Impressions"))
    ctr, pos = num(get(r, "CTR")), num(get(r, "Position"))
    if imp >= 50000 and ctr < 1.0:
        lowctr.append((imp, url, clk, ctr, pos))
lowctr.sort(reverse=True)
print(f"\n--- HIGH-IMPRESSION / LOW-CTR PAGES (imp>=50k, CTR<1%) — snippet/rich-result upside ---")
print(f"{'impr':>9} {'CTR%':>5} {'pos':>5}  url")
for imp, url, clk, ctr, pos in lowctr[:20]:
    print(f"{imp:9.0f} {ctr:5.2f} {pos:5.1f}  {url[:60]}")
print(f"\nHigh-impression low-CTR pages in top {len(p)}: {len(lowctr)}")
