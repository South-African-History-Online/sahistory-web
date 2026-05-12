# SAHO performance measurement

Small harness for measuring mobile CWV (LCP focus) and producing the
"is the fix working?" reports.

## Tools

- **Lighthouse CLI** — fired via `npx --yes lighthouse`. Mobile form factor + simulated 3G is the default for CWV-targeted runs.
- **Playwright** — visual-regression screenshots at mobile (375×812 @2x) and desktop (1280×800).
- **`scripts/summarize.py`** — parses Lighthouse JSON, prints a per-page metric table + ranks the biggest opportunities + lists LCP/CLS element details.
- **`scripts/screenshots.mjs`** — captures full-page screenshots for both viewports across 4 SAHO page types.

## Run a baseline against prod

```bash
DAY=$(date +%Y-%m-%d)
mkdir -p perf/baselines/$DAY/prod-mobile
for slug:url in home:https://sahistory.org.za/ \
                article-poqo:https://sahistory.org.za/article/poqo \
                bio-dorothy-adams:https://sahistory.org.za/people/dorothy-adams \
                featured:https://sahistory.org.za/featured; do
  CHROME_PATH=/usr/bin/google-chrome npx --yes lighthouse "${slug:url#*:}" \
    --quiet \
    --chrome-flags="--headless=new --no-sandbox --disable-dev-shm-usage" \
    --form-factor=mobile --only-categories=performance \
    --output=html --output=json \
    --output-path="perf/baselines/$DAY/prod-mobile/${slug:url%:*}" \
    --max-wait-for-load=60000
done

python3 perf/scripts/summarize.py perf/baselines/$DAY/prod-mobile/*.report.json
```

(In bash use the explicit form — the inline shell example above is just illustrative.)

## Run a baseline against local DDEV

Same commands but swap the URLs to `https://sahistory-web.ddev.site/...` and add `--ignore-certificate-errors` to `--chrome-flags`. Note: local DDEV variance is high — a single run swings TTFB ±700ms. Run 3 trials and take the median, or rely on the diagnostic insights (render-blocking-insight, image-delivery-insight) rather than raw LCP numbers when iterating on a fix.

## Visual baselines

```bash
# perf/node_modules is a symlink to a working playwright install
ln -sfn /tmp/saho-pw/node_modules perf/node_modules
node perf/scripts/screenshots.mjs perf/baselines/$(date +%Y-%m-%d)/screenshots
```

## Why prod-only and mobile-only

Per GSC Core Web Vitals (May 2026): 28,187 mobile URLs are failing LCP, 49 are poor. Desktop is essentially solved (0 poor). All CWV optimisation work for SAHO should target **mobile LCP**.
