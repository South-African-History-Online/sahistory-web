#!/usr/bin/env node
/**
 * Capture full-page Playwright screenshots for the SAHO performance baseline.
 *
 * Run after a Lighthouse pass; produces the "before" visual reference
 * we'll diff against once we ship the perf fixes.
 *
 * Usage: node perf/scripts/screenshots.mjs <out-dir>
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'fs';

const outDir = process.argv[2] || `perf/baselines/${new Date().toISOString().slice(0, 10)}/screenshots`;
mkdirSync(outDir, { recursive: true });

const VIEWPORTS = {
  mobile: { width: 375, height: 812, dpr: 2 },
  desktop: { width: 1280, height: 800, dpr: 1 },
};

const PAGES = {
  home: 'https://sahistory.org.za/',
  'article-poqo': 'https://sahistory.org.za/article/poqo',
  'bio-dorothy-adams': 'https://sahistory.org.za/people/dorothy-adams',
  featured: 'https://sahistory.org.za/featured',
};

const browser = await chromium.launch({
  executablePath:
    process.env.CHROMIUM_PATH ||
    '/home/mno/.cache/ms-playwright/chromium-1217/chrome-linux64/chrome',
});
try {
  for (const [vname, v] of Object.entries(VIEWPORTS)) {
    for (const [slug, url] of Object.entries(PAGES)) {
      const ctx = await browser.newContext({
        viewport: { width: v.width, height: v.height },
        deviceScaleFactor: v.dpr,
        userAgent:
          vname === 'mobile'
            ? 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
            : undefined,
      });
      const page = await ctx.newPage();
      try {
        await page.goto(url, { waitUntil: 'networkidle', timeout: 45000 });
      } catch (e) {
        // fall back to domcontentloaded if networkidle never settles (long polling, etc.)
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 45000 });
      }
      const file = `${outDir}/${vname}-${slug}.png`;
      try {
        await page.screenshot({ path: file, fullPage: true });
        console.log(`  ✓ ${file}`);
      } catch (err) {
        // Some pages are so tall the protocol bails. Fall back to viewport-only.
        try {
          await page.screenshot({ path: file, fullPage: false });
          console.log(`  ✓ ${file} (viewport-only fallback)`);
        } catch (err2) {
          console.log(`  ✗ ${file} — ${err2.message}`);
        }
      }
      await ctx.close();
    }
  }
} finally {
  await browser.close();
}
