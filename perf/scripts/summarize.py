#!/usr/bin/env python3
"""Parse Lighthouse JSON reports and emit a helicopter-view summary.

Usage: perf/scripts/summarize.py perf/baselines/<date>/prod-mobile/*.report.json
"""
from __future__ import annotations
import json, sys, os, glob, statistics
from collections import defaultdict

# Metrics worth showing. Lighthouse units are ms unless noted.
CORE_METRICS = [
    ("first-contentful-paint", "FCP", "ms"),
    ("largest-contentful-paint", "LCP", "ms"),
    ("cumulative-layout-shift", "CLS", "score"),
    ("total-blocking-time", "TBT", "ms"),
    ("speed-index", "SI", "ms"),
    ("interactive", "TTI", "ms"),
    ("server-response-time", "TTFB", "ms"),
]

# Audits that report estimated savings ("opportunities" + a few diagnostics)
OPP_AUDITS = [
    "render-blocking-resources",
    "uses-rel-preconnect",
    "uses-rel-preload",
    "unminified-css",
    "unminified-javascript",
    "unused-css-rules",
    "unused-javascript",
    "modern-image-formats",
    "uses-optimized-images",
    "uses-text-compression",
    "uses-responsive-images",
    "efficient-animated-content",
    "duplicated-javascript",
    "legacy-javascript",
    "uses-webp-images",
    "offscreen-images",
    "total-byte-weight",
    "dom-size",
    "mainthread-work-breakdown",
    "bootup-time",
    "uses-long-cache-ttl",
    "third-party-summary",
    "third-party-facades",
    "network-server-latency",
    "non-composited-animations",
    "layout-shift-elements",
    "largest-contentful-paint-element",
    "lcp-lazy-loaded",
    "preload-lcp-image",
    "prioritize-lcp-image",
]


def fmt(metric_id: str, audit: dict) -> str:
    """Return a short human string for an audit numeric value."""
    nv = audit.get("numericValue")
    if nv is None:
        return audit.get("displayValue") or "-"
    unit = audit.get("numericUnit") or ""
    if unit == "millisecond":
        return f"{nv:,.0f} ms"
    if unit == "byte":
        return f"{nv/1024:,.0f} KB"
    if unit == "element":
        return f"{int(nv)} elements"
    if unit == "unitless":
        return f"{nv:.3f}"
    return f"{nv} ({unit})"


def grade(metric_id: str, value: float | None) -> str:
    """CWV-style colour code."""
    if value is None:
        return "?"
    thresholds = {
        "largest-contentful-paint": (2500, 4000),
        "cumulative-layout-shift": (0.1, 0.25),
        "total-blocking-time": (200, 600),
        "first-contentful-paint": (1800, 3000),
        "speed-index": (3400, 5800),
        "server-response-time": (800, 1800),
        "interactive": (3800, 7300),
    }
    if metric_id not in thresholds:
        return "-"
    good, fail = thresholds[metric_id]
    if value <= good:
        return "GOOD"
    if value <= fail:
        return "NEEDS"
    return "POOR"


def per_page(report: dict) -> dict:
    audits = report["audits"]
    perf = report.get("categories", {}).get("performance", {})
    return {
        "url": report.get("finalUrl") or report.get("requestedUrl"),
        "score": int((perf.get("score") or 0) * 100),
        "metrics": {
            mid: {
                "label": label,
                "value": audits.get(mid, {}).get("numericValue"),
                "display": audits.get(mid, {}).get("displayValue"),
                "grade": grade(mid, audits.get(mid, {}).get("numericValue")),
            }
            for mid, label, _unit in CORE_METRICS
        },
        "opps": [
            {
                "id": aid,
                "title": audits[aid].get("title"),
                "score": audits[aid].get("score"),
                "numeric_value": audits[aid].get("numericValue"),
                "numeric_unit": audits[aid].get("numericUnit"),
                "display_value": audits[aid].get("displayValue"),
                "savings_ms": (audits[aid].get("details") or {}).get("overallSavingsMs"),
                "savings_bytes": (audits[aid].get("details") or {}).get("overallSavingsBytes"),
            }
            for aid in OPP_AUDITS
            if aid in audits
        ],
    }


def main(paths: list[str]) -> int:
    pages = {}
    for p in paths:
        slug = os.path.basename(p).replace(".report.json", "")
        with open(p) as f:
            pages[slug] = per_page(json.load(f))

    # 1) Per-page metric table
    print("\n=== Per-page metrics (mobile, prod) ===")
    header = f"{'page':<22} {'score':<6} " + " ".join(f"{m[1]:<8}" for m in CORE_METRICS)
    print(header)
    for slug, data in pages.items():
        row = f"{slug:<22} {data['score']:<6} "
        for mid, label, _u in CORE_METRICS:
            m = data["metrics"][mid]
            disp = m["display"] or (fmt(mid, {"numericValue": m["value"], "numericUnit": "millisecond" if mid != "cumulative-layout-shift" else "unitless"}))
            disp = (disp or "-").replace(" ms", "ms").replace(" ", " ")
            row += f"{disp:<8} "
        print(row)

    # 2) Grades table (CWV colour codes)
    print("\n=== CWV grades ===")
    print(f"{'page':<22} " + " ".join(f"{m[1]:<8}" for m in CORE_METRICS))
    for slug, data in pages.items():
        row = f"{slug:<22} "
        for mid, label, _u in CORE_METRICS:
            row += f"{data['metrics'][mid]['grade']:<8} "
        print(row)

    # 3) Cross-page opportunity ranking
    print("\n=== Top opportunities by estimated wall-clock savings (sum across pages) ===")
    agg_ms = defaultdict(float)
    agg_bytes = defaultdict(float)
    titles = {}
    for slug, data in pages.items():
        for o in data["opps"]:
            if o["score"] is not None and o["score"] >= 0.9:
                continue  # already passing
            if o["savings_ms"]:
                agg_ms[o["id"]] += o["savings_ms"]
            if o["savings_bytes"]:
                agg_bytes[o["id"]] += o["savings_bytes"]
            titles[o["id"]] = o["title"]

    print(f"{'audit':<35} {'~ms saved':<12} {'~KB saved':<12} title")
    seen = set()
    for aid, ms in sorted(agg_ms.items(), key=lambda x: -x[1]):
        seen.add(aid)
        kb = agg_bytes.get(aid, 0) / 1024
        print(f"{aid:<35} {ms:<12,.0f} {kb:<12,.0f} {titles[aid]}")
    for aid, b in sorted(agg_bytes.items(), key=lambda x: -x[1]):
        if aid in seen:
            continue
        print(f"{aid:<35} {'-':<12} {b/1024:<12,.0f} {titles[aid]}")

    # 4) Per-page LCP element + layout-shift elements
    print("\n=== LCP element / layout-shift offenders ===")
    for slug, data in pages.items():
        report_path = paths_by_slug[slug]
        with open(report_path) as f:
            r = json.load(f)
        lcp_el = r["audits"].get("largest-contentful-paint-element", {})
        ls_el = r["audits"].get("layout-shift-elements", {})
        print(f"\n  -- {slug} --")
        items = (lcp_el.get("details") or {}).get("items") or []
        if items:
            inner = (items[0].get("items") or [{}])[0]
            node = inner.get("node") or {}
            print(f"    LCP element: {(node.get('snippet') or '-')[:140]}")
            print(f"    LCP selector: {node.get('selector') or '-'}")
        ls_items = (ls_el.get("details") or {}).get("items") or []
        if ls_items:
            print(f"    Layout-shift contributors ({len(ls_items)} elements):")
            for li in ls_items[:5]:
                node = li.get("node") or {}
                print(f"      - shift={li.get('score', 0):.4f} {((node.get('snippet') or '')[:120])}")
    return 0


if __name__ == "__main__":
    paths = sys.argv[1:] or sorted(glob.glob("perf/baselines/*/prod-mobile/*.report.json"))
    paths_by_slug = {os.path.basename(p).replace(".report.json", ""): p for p in paths}
    sys.exit(main(paths))
